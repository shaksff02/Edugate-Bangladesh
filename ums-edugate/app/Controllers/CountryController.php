<?php
/**
 * UMS EduGate — Country controller (Phase 5: Countries & Universities CRUD).
 * Routes: /countries (list), /countries/create, /countries/store,
 *         /countries/{id}/edit, /countries/{id}/update,
 *         /countries/{id}/archive, /countries/{id}/restore
 * RBAC: view = all signed-in roles; create/edit/archive/restore = ADMIN+EDITOR.
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/Core/Auth.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
require_once BASE_PATH . '/app/Core/Session.php';
require_once BASE_PATH . '/app/Core/Validator.php';
require_once BASE_PATH . '/app/Repositories/MasterDataRepository.php';

final class CountryController
{
    private MasterDataRepository $repo;

    public function __construct()
    {
        Session::start();
        Auth::resumeFromCookie();
        $this->repo = new MasterDataRepository(Auth::pdo());
    }

    /** GET /countries — searchable, filterable, paginated list. */
    public function index(): void
    {
        Rbac::require('view');
        $search = trim((string) ($_GET['q'] ?? ''));
        $status = (string) ($_GET['status'] ?? 'active');
        if (!in_array($status, ['active', 'archived', 'all'], true)) {
            $status = 'active';
        }
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $list = $this->repo->countriesList($search, $status, $page);

        render_view('countries/index', [
            'title'    => 'Countries — UMS EduGate',
            'path'     => '/countries',
            'rows'     => $list['rows'],
            'total'    => $list['total'],
            'page'     => $list['page'],
            'per'      => $list['per'],
            'search'   => $search,
            'status'   => $status,
            'canEdit'  => Auth::can(Auth::role(), 'create'),
        ]);
    }

    /** GET /countries/create — blank form. */
    public function create(): void
    {
        Rbac::require('create');
        render_view('countries/form', [
            'title'   => 'New country — UMS EduGate',
            'path'    => '/countries',
            'country' => null,
            'errors'  => [],
            'old'     => null,
            'action'  => '/countries/store',
            'heading' => 'Add country',
        ]);
    }

    /** POST /countries/store. */
    public function store(): void
    {
        Rbac::require('create');
        Csrf::check();
        [$data, $errors] = $this->validate($_POST);

        if ($errors !== []) {
            render_view('countries/form', [
                'title' => 'New country — UMS EduGate', 'path' => '/countries',
                'country' => null, 'errors' => $errors, 'old' => $_POST,
                'action' => '/countries/store', 'heading' => 'Add country',
            ], 422);
            return;
        }
        try {
            $id = $this->repo->createCountry($data);
            Session::flash('success', 'Country "' . $data['name'] . '" created.');
            header('Location: /countries/' . $id, true, 302);
        } catch (RuntimeException $e) { // friendly duplicate messages
            render_view('countries/form', [
                'title' => 'New country — UMS EduGate', 'path' => '/countries',
                'country' => null, 'errors' => ['name' => $e->getMessage()], 'old' => $_POST,
                'action' => '/countries/store', 'heading' => 'Add country',
            ], 422);
        }
    }

    /** GET /countries/{id} — detail with its universities. */
    public function show(): void
    {
        Rbac::require('view');
        $id = $this->routeId();
        $country = $this->repo->findCountry($id);
        if ($country === null) {
            $this->notFound();
            return;
        }
        $unis = $this->repo->universitiesList('', 'all', $id, '', 1, 100)['rows'];
        render_view('countries/show', [
            'title'    => $country['name'] . ' — UMS EduGate',
            'path'     => '/countries',
            'country'  => $country,
            'unis'     => $unis,
            'breadcrumbs' => [
                ['label' => 'Countries', 'url' => '/countries'],
                ['label' => (string) $country['name']],
            ],
            'canEdit'  => Auth::can(Auth::role(), 'create'),
        ]);
    }

    /** GET /countries/{id}/edit. */
    public function edit(): void
    {
        Rbac::require('edit');
        $id = $this->routeId();
        $country = $this->repo->findCountry($id);
        if ($country === null) {
            $this->notFound();
            return;
        }
        render_view('countries/form', [
            'title'   => 'Edit ' . $country['name'] . ' — UMS EduGate',
            'path'    => '/countries',
            'country' => $country,
            'errors'  => [],
            'old'     => null,
            'action'  => '/countries/' . $id . '/update',
            'heading' => 'Edit country',
            'breadcrumbs' => [
                ['label' => 'Countries', 'url' => '/countries'],
                ['label' => (string) $country['name'], 'url' => '/countries/' . $id],
                ['label' => 'Edit'],
            ],
        ]);
    }

    /** POST /countries/{id}/update. */
    public function update(): void
    {
        Rbac::require('edit');
        Csrf::check();
        $id = $this->routeId();
        $country = $this->repo->findCountry($id);
        if ($country === null) {
            $this->notFound();
            return;
        }
        [$data, $errors] = $this->validate($_POST);
        if ($errors !== []) {
            render_view('countries/form', [
                'title' => 'Edit country — UMS EduGate', 'path' => '/countries',
                'country' => $country, 'errors' => $errors, 'old' => $_POST,
                'action' => '/countries/' . $id . '/update', 'heading' => 'Edit country',
            ], 422);
            return;
        }
        try {
            $this->repo->updateCountry($id, $data);
            Session::flash('success', 'Country "' . $data['name'] . '" updated.');
            header('Location: /countries/' . $id, true, 302);
        } catch (RuntimeException $e) {
            render_view('countries/form', [
                'title' => 'Edit country — UMS EduGate', 'path' => '/countries',
                'country' => $country, 'errors' => ['name' => $e->getMessage()], 'old' => $_POST,
                'action' => '/countries/' . $id . '/update', 'heading' => 'Edit country',
            ], 422);
        }
    }

    /** POST /countries/{id}/archive — soft delete with FK guard. */
    public function archive(): void
    {
        Rbac::require('delete');
        Csrf::check();
        $result = $this->repo->archiveCountry($this->routeId());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        header('Location: /countries', true, 302);
    }

    /** POST /countries/{id}/restore. */
    public function restore(): void
    {
        Rbac::require('delete');
        Csrf::check();
        $result = $this->repo->restoreCountry($this->routeId());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        header('Location: /countries?status=archived', true, 302);
    }

    /* ------------------------------------------------------------------ */

    private function routeId(): int
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (preg_match('#^/countries/(\d+)#', $path, $m)) {
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
        $v->required('name', 'Country name', 120)
          ->iso2()
          ->iso3()                       // null-safe: missing key never crashes
          ->optional('region', 'Region', 80)
          ->currency()
          ->optional('visa_notes', 'Visa notes', 60000)
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
