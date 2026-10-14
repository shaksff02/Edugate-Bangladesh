<?php
/**
 * UMS EduGate — University controller (Phase 5: Countries & Universities CRUD).
 * Routes: /universities (list w/ search + country/type filters),
 *         /universities/create, /universities/store,
 *         /universities/{id}, /universities/{id}/edit, /universities/{id}/update,
 *         /universities/{id}/archive, /universities/{id}/restore
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/Core/Auth.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
require_once BASE_PATH . '/app/Core/Session.php';
require_once BASE_PATH . '/app/Core/Validator.php';
require_once BASE_PATH . '/app/Repositories/MasterDataRepository.php';

final class UniversityController
{
    private MasterDataRepository $repo;

    public function __construct()
    {
        Session::start();
        Auth::resumeFromCookie();
        $this->repo = new MasterDataRepository(Auth::pdo());
    }

    /** GET /universities */
    public function index(): void
    {
        Rbac::require('view');
        $search    = trim((string) ($_GET['q'] ?? ''));
        $status    = (string) ($_GET['status'] ?? 'active');
        $countryId = (int) ($_GET['country_id'] ?? 0) ?: null;
        $type      = (string) ($_GET['type'] ?? '');
        $page      = max(1, (int) ($_GET['page'] ?? 1));
        if (!in_array($status, ['active', 'archived', 'all'], true)) {
            $status = 'active';
        }
        $list = $this->repo->universitiesList($search, $status, $countryId, $type, $page);

        render_view('universities/index', [
            'title'      => 'Universities — UMS EduGate',
            'path'       => '/universities',
            'rows'       => $list['rows'],
            'total'      => $list['total'],
            'page'       => $list['page'],
            'per'        => $list['per'],
            'search'     => $search,
            'status'     => $status,
            'countryId'  => $countryId,
            'type'       => $type,
            'countries'  => $this->repo->activeCountries(),
            'canEdit'    => Rbac::can(Auth::role(), 'create'),
        ]);
    }

    /** GET /universities/create */
    public function create(): void
    {
        Rbac::require('create');
        render_view('universities/form', [
            'title'     => 'New university — UMS EduGate',
            'path'      => '/universities',
            'university' => null,
            'errors'    => [],
            'old'       => null,
            'action'    => '/universities/store',
            'heading'   => 'Add university',
            'countries' => $this->repo->activeCountries(),
        ]);
    }

    /** POST /universities/store */
    public function store(): void
    {
        Rbac::require('create');
        Csrf::check();
        [$data, $errors] = $this->validate($_POST);

        if ($errors !== []) {
            $this->showForm(null, $errors, $_POST, '/universities/store', 'Add university', 422);
            return;
        }
        try {
            $id = $this->repo->createUniversity($data);
            Session::flash('success', 'University "' . $data['name'] . '" created.');
            header('Location: /universities/' . $id, true, 302);
        } catch (RuntimeException $e) {
            $this->showForm(null, ['name' => $e->getMessage()], $_POST, '/universities/store', 'Add university', 422);
        }
    }

    /** GET /universities/{id} */
    public function show(): void
    {
        Rbac::require('view');
        $id = $this->routeId();
        $uni = $this->repo->findUniversity($id);
        if ($uni === null) {
            $this->notFound();
            return;
        }
        render_view('universities/show', [
            'title'      => $uni['name'] . ' — UMS EduGate',
            'path'       => '/universities',
            'university' => $uni,
            'breadcrumbs' => [
                ['label' => 'Universities', 'url' => '/universities'],
                ['label' => (string) $uni['name']],
            ],
            'canEdit'    => Rbac::can(Auth::role(), 'create'),
        ]);
    }

    /** GET /universities/{id}/edit */
    public function edit(): void
    {
        Rbac::require('edit');
        $id = $this->routeId();
        $uni = $this->repo->findUniversity($id);
        if ($uni === null) {
            $this->notFound();
            return;
        }
        $this->showForm($uni, [], null, '/universities/' . $id . '/update', 'Edit university', 200);
    }

    /** POST /universities/{id}/update */
    public function update(): void
    {
        Rbac::require('edit');
        Csrf::check();
        $id  = $this->routeId();
        $uni = $this->repo->findUniversity($id);
        if ($uni === null) {
            $this->notFound();
            return;
        }
        [$data, $errors] = $this->validate($_POST);
        if ($errors !== []) {
            $this->showForm($uni, $errors, $_POST, '/universities/' . $id . '/update', 'Edit university', 422);
            return;
        }
        try {
            $this->repo->updateUniversity($id, $data);
            Session::flash('success', 'University "' . $data['name'] . '" updated.');
            header('Location: /universities/' . $id, true, 302);
        } catch (RuntimeException $e) {
            $this->showForm($uni, ['name' => $e->getMessage()], $_POST, '/universities/' . $id . '/update', 'Edit university', 422);
        }
    }

    /** POST /universities/{id}/archive */
    public function archive(): void
    {
        Rbac::require('delete');
        Csrf::check();
        $result = $this->repo->archiveUniversity($this->routeId());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        header('Location: /universities', true, 302);
    }

    /** POST /universities/{id}/restore */
    public function restore(): void
    {
        Rbac::require('delete');
        Csrf::check();
        $result = $this->repo->restoreUniversity($this->routeId());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        header('Location: /universities?status=archived', true, 302);
    }

    /* ------------------------------------------------------------------ */

    private function showForm(?array $uni, array $errors, ?array $old, string $action, string $heading, int $status): void
    {
        render_view('universities/form', [
            'title'      => $heading . ' — UMS EduGate',
            'path'       => '/universities',
            'university' => $uni,
            'errors'     => $errors,
            'old'        => $old,
            'action'     => $action,
            'heading'    => $heading,
            'countries'  => $this->repo->activeCountries(),
        ], $status);
    }

    private function routeId(): int
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (preg_match('#^/universities/(\d+)#', $path, $m)) {
            return (int) $m[1];
        }
        $this->notFound();
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);
        render_view('errors/404', ['title' => 'Not found']);
    }

    /** @return array{0:array<string,mixed>,1:array<string,string>} */
    private function validate(array $post): array
    {
        $v = new Validator($post);
        $v->required('name', 'University name', 190)
          ->id('country_id', 'Country')
          ->url('website', 'Website')
          ->optional('city', 'City', 120)
          ->in('type', MasterDataRepository::UNI_TYPES, 'Type', true)
          ->optional('ranking_note', 'Ranking note', 255)
          ->optional('description', 'Description', 60000)
          ->in('confidence_level', MasterDataRepository::CONFIDENCE_LEVELS, 'Confidence level');
        if ($v->fails()) {
            $data = [];
        } else {
            $data = $v->data();
            $data['confidence_level'] ??= 'UNVERIFIED';
        }
        return [$data, $v->errors()];
    }
}
