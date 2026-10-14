<?php
/**
 * UMS EduGate — Intake controller (Phase 6: Campuses, Intakes & Application Rounds).
 * Routes: /intakes (list w/ search + university/year filters),
 *         /intakes/create, /intakes/store,
 *         /intakes/{id}, /intakes/{id}/edit, /intakes/{id}/update,
 *         /intakes/{id}/archive, /intakes/{id}/restore
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/Core/Auth.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
require_once BASE_PATH . '/app/Core/Session.php';
require_once BASE_PATH . '/app/Core/Validator.php';
require_once BASE_PATH . '/app/Repositories/CalendarRepository.php';

final class IntakeController
{
    private CalendarRepository $repo;

    public function __construct()
    {
        Session::start();
        Auth::resumeFromCookie();
        $this->repo = new CalendarRepository(Auth::pdo());
    }

    /** GET /intakes */
    public function index(): void
    {
        Rbac::require('view');
        $search   = trim((string) ($_GET['q'] ?? ''));
        $status   = (string) ($_GET['status'] ?? 'active');
        $univId   = (int) ($_GET['university_id'] ?? 0) ?: null;
        $year     = (int) ($_GET['year'] ?? 0) ?: null;
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        if (!in_array($status, ['active', 'archived', 'all'], true)) {
            $status = 'active';
        }
        $list = $this->repo->intakesList($search, $status, $univId, $year, $page);

        render_view('intakes/index', [
            'title'        => 'Intakes — UMS EduGate',
            'path'         => '/intakes',
            'rows'         => $list['rows'],
            'total'        => $list['total'],
            'page'         => $list['page'],
            'per'          => $list['per'],
            'search'       => $search,
            'status'       => $status,
            'universityId' => $univId,
            'year'         => $year,
            'universities' => $this->repo->activeUniversities(),
            'canEdit'      => Rbac::can(Auth::role(), 'create'),
        ]);
    }

    /** GET /intakes/create */
    public function create(): void
    {
        Rbac::require('create');
        $this->showForm(null, [], null, '/intakes/store', 'Add intake', 200);
    }

    /** POST /intakes/store */
    public function store(): void
    {
        Rbac::require('create');
        Csrf::check();
        [$data, $errors] = $this->validate($_POST);

        if ($errors !== []) {
            $this->showForm(null, $errors, $_POST, '/intakes/store', 'Add intake', 422);
            return;
        }
        try {
            $id = $this->repo->createIntake($data);
            Session::flash('success', 'Intake "' . $data['name'] . ' ' . $data['intake_year'] . '" created.');
            header('Location: /intakes/' . $id, true, 302);
        } catch (RuntimeException $e) {
            $this->showForm(null, ['university_id' => $e->getMessage()], $_POST, '/intakes/store', 'Add intake', 422);
        }
    }

    /** GET /intakes/{id} */
    public function show(): void
    {
        Rbac::require('view');
        $id = $this->routeId();
        $intake = $this->repo->findIntake($id);
        if ($intake === null) {
            $this->notFound();
            return;
        }
        render_view('intakes/show', [
            'title'       => $intake['name'] . ' ' . $intake['intake_year'] . ' — UMS EduGate',
            'path'        => '/intakes',
            'intake'      => $intake,
            'breadcrumbs' => [
                ['label' => 'Intakes', 'url' => '/intakes'],
                ['label' => trim((string) $intake['name'] . ' ' . (string) $intake['intake_year'])],
            ],
            'canEdit'     => Rbac::can(Auth::role(), 'create'),
        ]);
    }

    /** GET /intakes/{id}/edit */
    public function edit(): void
    {
        Rbac::require('edit');
        $id = $this->routeId();
        $intake = $this->repo->findIntake($id);
        if ($intake === null) {
            $this->notFound();
            return;
        }
        $this->showForm($intake, [], null, '/intakes/' . $id . '/update', 'Edit intake', 200);
    }

    /** POST /intakes/{id}/update */
    public function update(): void
    {
        Rbac::require('edit');
        Csrf::check();
        $id = $this->routeId();
        $intake = $this->repo->findIntake($id);
        if ($intake === null) {
            $this->notFound();
            return;
        }
        [$data, $errors] = $this->validate($_POST);
        if ($errors !== []) {
            $this->showForm($intake, $errors, $_POST, '/intakes/' . $id . '/update', 'Edit intake', 422);
            return;
        }
        try {
            $this->repo->updateIntake($id, $data);
            Session::flash('success', 'Intake "' . $data['name'] . ' ' . $data['intake_year'] . '" updated.');
            header('Location: /intakes/' . $id, true, 302);
        } catch (RuntimeException $e) {
            $this->showForm($intake, ['university_id' => $e->getMessage()], $_POST, '/intakes/' . $id . '/update', 'Edit intake', 422);
        }
    }

    /** POST /intakes/{id}/archive */
    public function archive(): void
    {
        Rbac::require('delete');
        Csrf::check();
        $result = $this->repo->archiveIntake($this->routeId());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        header('Location: /intakes', true, 302);
    }

    /** POST /intakes/{id}/restore */
    public function restore(): void
    {
        Rbac::require('delete');
        Csrf::check();
        $result = $this->repo->restoreIntake($this->routeId());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        header('Location: /intakes?status=archived', true, 302);
    }

    /* ------------------------------------------------------------------ */

    private function showForm(?array $intake, array $errors, ?array $old, string $action, string $heading, int $status): void
    {
        render_view('intakes/form', [
            'title'        => $heading . ' — UMS EduGate',
            'path'         => '/intakes',
            'intake'       => $intake,
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
        if (preg_match('#^/intakes/(\d+)#', $path, $m)) {
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
        $v->required('name', 'Intake name', 120)
          ->id('university_id', 'University')
          ->intInRange('intake_year', 'Intake year', 2000, 2100, true)
          ->date('starts_on', 'Starts on')
          ->date('ends_on', 'Ends on')
          ->in('confidence_level', CalendarRepository::CONFIDENCE_LEVELS, 'Confidence level');
        if ($v->fails()) {
            return [[], $v->errors()];
        }
        $data = $v->data();
        $data['confidence_level'] ??= 'UNVERIFIED';
        // Parent-child integrity: university must exist and be active.
        $activeUniIds = array_map('intval', array_column($this->repo->activeUniversities(), 'id'));
        if (!in_array((int) $data['university_id'], $activeUniIds, true)) {
            $v->addError('university_id', 'Select an existing, active university.');
            return [[], $v->errors()];
        }
        // Date sanity: ends_on cannot precede starts_on.
        if ($data['starts_on'] !== null && $data['ends_on'] !== null && $data['ends_on'] < $data['starts_on']) {
            $v->addError('ends_on', 'End date cannot be before the start date.');
            return [[], $v->errors()];
        }
        return [$data, $v->errors()];
    }
}
