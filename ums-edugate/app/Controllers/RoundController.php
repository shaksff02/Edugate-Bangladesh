<?php
/**
 * UMS EduGate — Application-round controller (Phase 6: Campuses, Intakes & Application Rounds).
 * Routes: /rounds (list w/ search + university/intake filters),
 *         /rounds/create, /rounds/store,
 *         /rounds/{id}, /rounds/{id}/edit, /rounds/{id}/update,
 *         /rounds/{id}/archive, /rounds/{id}/restore,
 *         /rounds/deadlines (deadline report)
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/Core/Auth.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
require_once BASE_PATH . '/app/Core/Session.php';
require_once BASE_PATH . '/app/Core/Validator.php';
require_once BASE_PATH . '/app/Repositories/CalendarRepository.php';

final class RoundController
{
    private CalendarRepository $repo;

    public function __construct()
    {
        Session::start();
        Auth::resumeFromCookie();
        $this->repo = new CalendarRepository(Auth::pdo());
    }

    /** GET /rounds */
    public function index(): void
    {
        Rbac::require('view');
        $search   = trim((string) ($_GET['q'] ?? ''));
        $status   = (string) ($_GET['status'] ?? 'active');
        $univId   = (int) ($_GET['university_id'] ?? 0) ?: null;
        $intakeId = (int) ($_GET['intake_id'] ?? 0) ?: null;
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        if (!in_array($status, ['active', 'archived', 'all'], true)) {
            $status = 'active';
        }
        $list = $this->repo->roundsList($search, $status, $univId, $intakeId, $page);

        render_view('rounds/index', [
            'title'        => 'Application rounds — UMS EduGate',
            'path'         => '/rounds',
            'rows'         => $list['rows'],
            'total'        => $list['total'],
            'page'         => $list['page'],
            'per'          => $list['per'],
            'search'       => $search,
            'status'       => $status,
            'universityId' => $univId,
            'intakeId'     => $intakeId,
            'universities' => $this->repo->activeUniversities(),
            'intakes'      => $this->repo->activeIntakes($univId),
            'canEdit'      => Auth::can(Auth::role(), 'create'),
        ]);
    }

    /** GET /rounds/deadlines — PRD acceptance: upcoming / closing-soon / passed / unknown */
    public function deadlines(): void
    {
        Rbac::require('view');
        $report = $this->repo->deadlineReport();

        render_view('rounds/deadlines', [
            'title'       => 'Deadline report — UMS EduGate',
            'path'        => '/rounds',
            'report'      => $report,
            'breadcrumbs' => [
                ['label' => 'Rounds', 'url' => '/rounds'],
                ['label' => 'Deadlines'],
            ],
        ]);
    }

    /** GET /rounds/create */
    public function create(): void
    {
        Rbac::require('create');
        $this->showForm(null, [], null, '/rounds/store', 'Add application round', 200);
    }

    /** POST /rounds/store */
    public function store(): void
    {
        Rbac::require('create');
        Csrf::check();
        [$data, $errors] = $this->validate($_POST);

        if ($errors !== []) {
            $this->showForm(null, $errors, $_POST, '/rounds/store', 'Add application round', 422);
            return;
        }
        try {
            $id = $this->repo->createRound($data);
            Session::flash('success', 'Application round "' . $data['name'] . '" created.');
            header('Location: /rounds/' . $id, true, 302);
        } catch (RuntimeException $e) {
            $this->showForm(null, ['intake_id' => $e->getMessage()], $_POST, '/rounds/store', 'Add application round', 422);
        }
    }

    /** GET /rounds/{id} */
    public function show(): void
    {
        Rbac::require('view');
        $id = $this->routeId();
        $round = $this->repo->findRound($id);
        if ($round === null) {
            $this->notFound();
            return;
        }
        render_view('rounds/show', [
            'title'       => $round['name'] . ' — UMS EduGate',
            'path'        => '/rounds',
            'round'       => $round,
            'breadcrumbs' => [
                ['label' => 'Rounds', 'url' => '/rounds'],
                ['label' => (string) $round['name']],
            ],
            'canEdit'     => Auth::can(Auth::role(), 'create'),
        ]);
    }

    /** GET /rounds/{id}/edit */
    public function edit(): void
    {
        Rbac::require('edit');
        $id = $this->routeId();
        $round = $this->repo->findRound($id);
        if ($round === null) {
            $this->notFound();
            return;
        }
        $this->showForm($round, [], null, '/rounds/' . $id . '/update', 'Edit application round', 200);
    }

    /** POST /rounds/{id}/update */
    public function update(): void
    {
        Rbac::require('edit');
        Csrf::check();
        $id = $this->routeId();
        $round = $this->repo->findRound($id);
        if ($round === null) {
            $this->notFound();
            return;
        }
        [$data, $errors] = $this->validate($_POST);
        if ($errors !== []) {
            $this->showForm($round, $errors, $_POST, '/rounds/' . $id . '/update', 'Edit application round', 422);
            return;
        }
        try {
            $this->repo->updateRound($id, $data);
            Session::flash('success', 'Application round "' . $data['name'] . '" updated.');
            header('Location: /rounds/' . $id, true, 302);
        } catch (RuntimeException $e) {
            $this->showForm($round, ['intake_id' => $e->getMessage()], $_POST, '/rounds/' . $id . '/update', 'Edit application round', 422);
        }
    }

    /** POST /rounds/{id}/archive */
    public function archive(): void
    {
        Rbac::require('delete');
        Csrf::check();
        $result = $this->repo->archiveRound($this->routeId());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        header('Location: /rounds', true, 302);
    }

    /** POST /rounds/{id}/restore */
    public function restore(): void
    {
        Rbac::require('delete');
        Csrf::check();
        $result = $this->repo->restoreRound($this->routeId());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        header('Location: /rounds?status=archived', true, 302);
    }

    /* ------------------------------------------------------------------ */

    private function showForm(?array $round, array $errors, ?array $old, string $action, string $heading, int $status): void
    {
        render_view('rounds/form', [
            'title'        => $heading . ' — UMS EduGate',
            'path'         => '/rounds',
            'round'        => $round,
            'errors'       => $errors,
            'old'          => $old,
            'action'       => $action,
            'heading'      => $heading,
            'universities' => $this->repo->activeUniversities(),
            'intakes'      => $this->repo->activeIntakes(),
        ], $status);
    }

    private function routeId(): int
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (preg_match('#^/rounds/(\d+)#', $path, $m)) {
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
        $v->required('name', 'Round name', 120)
          ->id('intake_id', 'Intake')
          ->dateTime('opens_at', 'Opens at')
          ->dateTime('closes_at', 'Closes at')
          ->date('result_date', 'Result date')
          ->in('confidence_level', CalendarRepository::CONFIDENCE_LEVELS, 'Confidence level');
        if ($v->fails()) {
            return [[], $v->errors()];
        }
        $data = $v->data();
        $data['confidence_level'] ??= 'UNVERIFIED';

        // Parent-child integrity: intake must exist and be active.
        $activeIntakeIds = array_map('intval', array_column($this->repo->activeIntakes(), 'id'));
        if (!in_array((int) $data['intake_id'], $activeIntakeIds, true)) {
            $v->addError('intake_id', 'Select an existing, active intake.');
            return [[], $v->errors()];
        }

        // Date sanity: opens <= closes; result date after opening.
        if ($data['opens_at'] !== null && $data['closes_at'] !== null && $data['closes_at'] < $data['opens_at']) {
            $v->addError('closes_at', 'Closing datetime cannot be before the opening datetime.');
            return [[], $v->errors()];
        }
        if ($data['result_date'] !== null && $data['opens_at'] !== null && $data['result_date'] < substr($data['opens_at'], 0, 10)) {
            $v->addError('result_date', 'Result date cannot be before the round opens.');
            return [[], $v->errors()];
        }
        return [$data, $v->errors()];
    }
}
