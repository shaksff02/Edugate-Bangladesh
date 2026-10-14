<?php
/**
 * UMS EduGate — Campus controller (Phase 6: Campuses, Intakes & Application Rounds).
 * Routes: /campuses (list w/ search + university filter),
 *         /campuses/create, /campuses/store,
 *         /campuses/{id}, /campuses/{id}/edit, /campuses/{id}/update,
 *         /campuses/{id}/archive, /campuses/{id}/restore
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/Core/Auth.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
require_once BASE_PATH . '/app/Core/Session.php';
require_once BASE_PATH . '/app/Core/Validator.php';
require_once BASE_PATH . '/app/Repositories/CalendarRepository.php';

final class CampusController
{
    private CalendarRepository $repo;

    public function __construct()
    {
        Session::start();
        Auth::resumeFromCookie();
        $this->repo = new CalendarRepository(Auth::pdo());
    }

    /** GET /campuses */
    public function index(): void
    {
        Rbac::require('view');
        $search   = trim((string) ($_GET['q'] ?? ''));
        $status   = (string) ($_GET['status'] ?? 'active');
        $univId   = (int) ($_GET['university_id'] ?? 0) ?: null;
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        if (!in_array($status, ['active', 'archived', 'all'], true)) {
            $status = 'active';
        }
        $list = $this->repo->campusesList($search, $status, $univId, $page);

        render_view('campuses/index', [
            'title'       => 'Campuses — UMS EduGate',
            'path'        => '/campuses',
            'rows'        => $list['rows'],
            'total'       => $list['total'],
            'page'        => $list['page'],
            'per'         => $list['per'],
            'search'      => $search,
            'status'      => $status,
            'universityId'=> $univId,
            'universities'=> $this->repo->activeUniversities(),
            'canEdit'     => Rbac::can(Auth::role(), 'create'),
        ]);
    }

    /** GET /campuses/create */
    public function create(): void
    {
        Rbac::require('create');
        $this->showForm(null, [], null, '/campuses/store', 'Add campus', 200);
    }

    /** POST /campuses/store */
    public function store(): void
    {
        Rbac::require('create');
        Csrf::check();
        [$data, $errors] = $this->validate($_POST);

        if ($errors !== []) {
            $this->showForm(null, $errors, $_POST, '/campuses/store', 'Add campus', 422);
            return;
        }
        try {
            $id = $this->repo->createCampus($data);
            Session::flash('success', 'Campus "' . $data['name'] . '" created.');
            header('Location: /campuses/' . $id, true, 302);
        } catch (RuntimeException $e) {
            $this->showForm(null, ['university_id' => $e->getMessage()], $_POST, '/campuses/store', 'Add campus', 422);
        }
    }

    /** GET /campuses/{id} */
    public function show(): void
    {
        Rbac::require('view');
        $id = $this->routeId();
        $campus = $this->repo->findCampus($id);
        if ($campus === null) {
            $this->notFound();
            return;
        }
        render_view('campuses/show', [
            'title'       => $campus['name'] . ' — UMS EduGate',
            'path'        => '/campuses',
            'campus'      => $campus,
            'breadcrumbs' => [
                ['label' => 'Campuses', 'url' => '/campuses'],
                ['label' => (string) $campus['name']],
            ],
            'canEdit'     => Rbac::can(Auth::role(), 'create'),
        ]);
    }

    /** GET /campuses/{id}/edit */
    public function edit(): void
    {
        Rbac::require('edit');
        $id = $this->routeId();
        $campus = $this->repo->findCampus($id);
        if ($campus === null) {
            $this->notFound();
            return;
        }
        $this->showForm($campus, [], null, '/campuses/' . $id . '/update', 'Edit campus', 200);
    }

    /** POST /campuses/{id}/update */
    public function update(): void
    {
        Rbac::require('edit');
        Csrf::check();
        $id = $this->routeId();
        $campus = $this->repo->findCampus($id);
        if ($campus === null) {
            $this->notFound();
            return;
        }
        [$data, $errors] = $this->validate($_POST);
        if ($errors !== []) {
            $this->showForm($campus, $errors, $_POST, '/campuses/' . $id . '/update', 'Edit campus', 422);
            return;
        }
        try {
            $this->repo->updateCampus($id, $data);
            Session::flash('success', 'Campus "' . $data['name'] . '" updated.');
            header('Location: /campuses/' . $id, true, 302);
        } catch (RuntimeException $e) {
            $this->showForm($campus, ['university_id' => $e->getMessage()], $_POST, '/campuses/' . $id . '/update', 'Edit campus', 422);
        }
    }

    /** POST /campuses/{id}/archive */
    public function archive(): void
    {
        Rbac::require('delete');
        Csrf::check();
        $result = $this->repo->archiveCampus($this->routeId());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        header('Location: /campuses', true, 302);
    }

    /** POST /campuses/{id}/restore */
    public function restore(): void
    {
        Rbac::require('delete');
        Csrf::check();
        $result = $this->repo->restoreCampus($this->routeId());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        header('Location: /campuses?status=archived', true, 302);
    }

    /* ------------------------------------------------------------------ */

    private function showForm(?array $campus, array $errors, ?array $old, string $action, string $heading, int $status): void
    {
        render_view('campuses/form', [
            'title'        => $heading . ' — UMS EduGate',
            'path'         => '/campuses',
            'campus'       => $campus,
            'errors'       => $errors,
            'old'          => $old,
            'action'       => $action,
            'heading'      => $heading,
            'universities' => $this->repo->activeUniversities(),
        ], $status);
    }

    private function routeId(): int
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (preg_match('#^/campuses/(\d+)#', $path, $m)) {
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
        $v->required('name', 'Campus name', 190)
          ->id('university_id', 'University')
          ->optional('city', 'City', 120)
          ->optional('address', 'Address', 255)
          ->checkboxBool('is_main')
          ->in('confidence_level', CalendarRepository::CONFIDENCE_LEVELS, 'Confidence level');
        if ($v->fails()) {
            $data = [];
        } else {
            $data = $v->data();
            $data['confidence_level'] ??= 'UNVERIFIED';
            // Parent-child integrity: university must exist and be active.
            $activeUniIds = array_map('intval', array_column($this->repo->activeUniversities(), 'id'));
            if (!in_array((int) $data['university_id'], $activeUniIds, true)) {
                $v->addError('university_id', 'Select an existing, active university.');
                return [[], $v->errors()];
            }
        }
        return [$data, $v->errors()];
    }
}
