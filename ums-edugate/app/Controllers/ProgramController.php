<?php
/**
 * UMS EduGate — Program controller (Phase 7: Programs and program detail).
 * Routes: /programs (list w/ combined filters),
 *         /programs/create, /programs/store,
 *         /programs/{id}, /programs/{id}/edit, /programs/{id}/update,
 *         /programs/{id}/archive, /programs/{id}/restore
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/Core/Auth.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
require_once BASE_PATH . '/app/Core/Session.php';
require_once BASE_PATH . '/app/Core/Validator.php';
require_once BASE_PATH . '/app/Repositories/ProgramRepository.php';

final class ProgramController
{
    private ProgramRepository $repo;

    public function __construct()
    {
        Session::start();
        Auth::resumeFromCookie();
        $this->repo = new ProgramRepository(Auth::pdo());
    }

    /** GET /programs */
    public function index(): void
    {
        Rbac::require('view');
        $filters = [
            'q'             => trim((string) ($_GET['q'] ?? '')),
            'status'        => (string) ($_GET['status'] ?? 'active'),
            'country_id'    => (int) ($_GET['country_id'] ?? 0) ?: null,
            'university_id' => (int) ($_GET['university_id'] ?? 0) ?: null,
            'campus_id'     => (int) ($_GET['campus_id'] ?? 0) ?: null,
            'level'         => (string) ($_GET['level'] ?? ''),
            'major'         => trim((string) ($_GET['major'] ?? '')),
            'mode'          => (string) ($_GET['mode'] ?? ''),
            'language'      => trim((string) ($_GET['language'] ?? '')),
        ];
        if (!in_array($filters['status'], ['active', 'archived', 'all'], true)) {
            $filters['status'] = 'active';
        }
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $list = $this->repo->programsList($filters, $page);

        render_view('programs/index', [
            'title'       => 'Programs — UMS EduGate',
            'path'        => '/programs',
            'rows'        => $list['rows'],
            'total'       => $list['total'],
            'page'        => $list['page'],
            'per'         => $list['per'],
            'filters'     => $filters,
            'countries'   => $this->repo->activeCountries(),
            'universities'=> $this->repo->activeUniversities(),
            'campuses'    => $this->repo->activeCampuses($filters['university_id']),
            'canEdit'     => Rbac::can(Auth::role(), 'create'),
        ]);
    }

    /** GET /programs/create */
    public function create(): void
    {
        Rbac::require('create');
        $this->showForm(null, [], null, '/programs/store', 'Add program', 200);
    }

    /** POST /programs/store */
    public function store(): void
    {
        Rbac::require('create');
        Csrf::check();
        [$data, $errors] = $this->validate($_POST);

        if ($errors !== []) {
            $this->showForm(null, $errors, $_POST, '/programs/store', 'Add program', 422);
            return;
        }
        try {
            $id = $this->repo->createProgram($data);
            Session::flash('success', 'Program "' . $data['name'] . '" created.');
            header('Location: /programs/' . $id, true, 302);
        } catch (RuntimeException $e) {
            $this->showForm(null, ['university_id' => $e->getMessage()], $_POST, '/programs/store', 'Add program', 422);
        }
    }

    /** GET /programs/{id} — detail page with related data tabs/placeholders */
    public function show(): void
    {
        Rbac::require('view');
        $id = $this->routeId();
        $program = $this->repo->findProgram($id);
        if ($program === null) {
            $this->notFound();
            return;
        }
        render_view('programs/show', [
            'title'       => $program['name'] . ' — UMS EduGate',
            'path'        => '/programs',
            'program'     => $program,
            'counts'      => $this->repo->relatedCounts($id),
            'breadcrumbs' => [
                ['label' => 'Programs', 'url' => '/programs'],
                ['label' => (string) $program['name']],
            ],
            'canEdit'     => Rbac::can(Auth::role(), 'create'),
        ]);
    }

    /** GET /programs/{id}/edit */
    public function edit(): void
    {
        Rbac::require('edit');
        $id = $this->routeId();
        $program = $this->repo->findProgram($id);
        if ($program === null) {
            $this->notFound();
            return;
        }
        $this->showForm($program, [], null, '/programs/' . $id . '/update', 'Edit program', 200);
    }

    /** POST /programs/{id}/update */
    public function update(): void
    {
        Rbac::require('edit');
        Csrf::check();
        $id = $this->routeId();
        $program = $this->repo->findProgram($id);
        if ($program === null) {
            $this->notFound();
            return;
        }
        [$data, $errors] = $this->validate($_POST);
        if ($errors !== []) {
            $this->showForm($program, $errors, $_POST, '/programs/' . $id . '/update', 'Edit program', 422);
            return;
        }
        try {
            $this->repo->updateProgram($id, $data);
            Session::flash('success', 'Program "' . $data['name'] . '" updated.');
            header('Location: /programs/' . $id, true, 302);
        } catch (RuntimeException $e) {
            $this->showForm($program, ['university_id' => $e->getMessage()], $_POST, '/programs/' . $id . '/update', 'Edit program', 422);
        }
    }

    /** POST /programs/{id}/archive */
    public function archive(): void
    {
        Rbac::require('delete');
        Csrf::check();
        $result = $this->repo->archiveProgram($this->routeId());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        header('Location: /programs', true, 302);
    }

    /** POST /programs/{id}/restore */
    public function restore(): void
    {
        Rbac::require('delete');
        Csrf::check();
        $result = $this->repo->restoreProgram($this->routeId());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        header('Location: /programs?status=archived', true, 302);
    }

    /* ------------------------------------------------------------------ */

    private function showForm(?array $program, array $errors, ?array $old, string $action, string $heading, int $status): void
    {
        // Campus options depend on the selected university (old input or stored row).
        $universityId = null;
        if ($old !== null && !empty($old['university_id'])) {
            $universityId = (int) $old['university_id'];
        } elseif ($program !== null) {
            $universityId = (int) $program['university_id'];
        }
        render_view('programs/form', [
            'title'        => $heading . ' — UMS EduGate',
            'path'         => '/programs',
            'program'      => $program,
            'errors'       => $errors,
            'old'          => $old,
            'action'       => $action,
            'heading'      => $heading,
            'universities' => $this->repo->activeUniversities(),
            'campuses'     => $universityId !== null ? $this->repo->activeCampuses($universityId) : [],
        ], $status);
    }

    private function routeId(): int
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (preg_match('#^/programs/(\d+)#', $path, $m)) {
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
        $v->required('name', 'Program name', 190)
          ->id('university_id', 'University')
          ->id('campus_id', 'Campus', false)
          ->in('level', ProgramRepository::LEVELS, 'Degree level')
          ->optional('field', 'Field / major', 120)
          ->intInRange('duration_months', 'Duration (months)', 1, 120)
          ->intInRange('credits', 'Credits', 1, 999)
          ->optional('language_of_instruction', 'Language of instruction', 60)
          ->in('study_mode', array_keys(ProgramRepository::MODES), 'Study mode')
          ->textarea('description', 'Description', 5000)
          ->in('confidence_level', ProgramRepository::CONFIDENCE_LEVELS, 'Confidence level');

        if ($v->fails()) {
            return [[], $v->errors()];
        }
        $data = $v->data();
        $data['confidence_level'] ??= 'UNVERIFIED';
        $data['study_mode'] ??= 'ONCAMPUS';

        // Parent-child integrity (PRD acceptance): a program cannot reference
        // a nonexistent university/campus.
        $activeUniIds = array_map('intval', array_column($this->repo->activeUniversities(), 'id'));
        if (!in_array((int) $data['university_id'], $activeUniIds, true)) {
            $v->addError('university_id', 'Select an existing, active university.');
            return [[], $v->errors()];
        }
        if ($data['campus_id'] !== null) {
            $campusIds = array_map('intval', array_column($this->repo->activeCampuses((int) $data['university_id']), 'id'));
            if (!in_array((int) $data['campus_id'], $campusIds, true)) {
                $v->addError('campus_id', 'The campus must exist and belong to the selected university.');
                return [[], $v->errors()];
            }
        }
        return [$data, $v->errors()];
    }
}
