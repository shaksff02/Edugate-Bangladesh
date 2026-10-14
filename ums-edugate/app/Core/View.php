<?php
/**
 * UMS EduGate — View helpers (Phase 3: shared UI shell).
 *
 * Small, dependency-free component functions used by every module:
 * alerts/badges/empty states, breadcrumbs, pagination, form-field
 * rendering with old-input + validation-error support, and the
 * sidebar navigation registry (single source of truth for nav state:
 * ready / coming-soon / hidden).
 */

declare(strict_types=1);

final class View
{
    /**
     * Sidebar navigation registry.
     *   state 'ready'        -> live link
     *   state 'coming_soon'  -> disabled link labelled "Coming in a later phase"
     *   state 'hidden'       -> not rendered at all
     * PRD acceptance: navigation must never present dead links as completed features.
     *
     * @return array<string, array<int, array{label:string,url:string,state:string,icon:string}>>
     */
    public static function navigation(): array
    {
        return [
            'Overview' => [
                ['label' => 'Dashboard',   'url' => '/dashboard', 'state' => 'ready',       'icon' => 'speedometer2'],
                ['label' => 'System status','url' => '/',          'state' => 'ready',       'icon' => 'activity'],
            ],
            'Reference data' => [
                ['label' => 'Countries',      'url' => '/countries',   'state' => 'ready', 'icon' => 'globe-americas'],
                ['label' => 'Universities',   'url' => '/universities','state' => 'ready', 'icon' => 'building'],
                ['label' => 'Campuses',       'url' => '/campuses',    'state' => 'ready', 'icon' => 'geo-alt'],
                ['label' => 'Intakes',        'url' => '/intakes',     'state' => 'ready', 'icon' => 'calendar3'],
                ['label' => 'Rounds',         'url' => '/rounds',      'state' => 'ready', 'icon' => 'hourglass-split'],
                ['label' => 'Programmes',     'url' => '/programmes',  'state' => 'coming_soon', 'icon' => 'mortarboard'],
            ],
            'Operations' => [
                ['label' => 'Students',       'url' => '/students',    'state' => 'coming_soon', 'icon' => 'people'],
                ['label' => 'Applications',   'url' => '/applications','state' => 'coming_soon', 'icon' => 'card-listing'],
                ['label' => 'Agents',         'url' => '/agents',      'state' => 'coming_soon', 'icon' => 'person-badge'],
            ],
            'Administration' => [
                ['label' => 'Users & roles',  'url' => '/users',       'state' => 'coming_soon', 'icon' => 'shield-lock'],
                ['label' => 'Audit log',      'url' => '/audit-log',   'state' => 'coming_soon', 'icon' => 'journal-text'],
                ['label' => 'Settings',       'url' => '/settings',    'state' => 'hidden',      'icon' => 'gear'],
            ],
        ];
    }

    /** True when $path matches the nav item url (exact or prefix for module roots). */
    public static function is_active(string $url, string $path): bool
    {
        if ($url === $path) {
            return true;
        }
        // treat "/students/create" etc. as inside "/students"
        return $url !== '/' && str_starts_with($path, rtrim($url, '/') . '/');
    }

    /* ------------------------------------------------------------------ */
    /* Components                                                          */
    /* ------------------------------------------------------------------ */

    /** Bootstrap alert. $type: success|danger|warning|info|secondary. Escaped content. */
    public static function alert(string $message, string $type = 'info', bool $dismissible = true): string
    {
        $type = in_array($type, ['success', 'danger', 'warning', 'info', 'secondary'], true) ? $type : 'info';
        $btn = $dismissible
            ? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            : '';
        return '<div class="alert alert-' . $type . ($dismissible ? ' alert-dismissible fade show' : '')
             . '" role="alert">' . e($message) . $btn . '</div>';
    }

    /** Pending flash messages rendered as alerts (does not clear them;
     *  render_view clears once after output so redirects still show them). */
    public static function flashes(): string
    {
        $out = '';
        foreach (Session::peekFlashes() as $key => $message) {
            $type = match ($key) {
                'error'   => 'danger',
                'success' => 'success',
                'warning' => 'warning',
                default   => 'info',
            };
            $out .= self::alert((string) $message, $type);
        }
        return $out;
    }

    /** Contextual badge (status pill). */
    public static function badge(string $text, string $variant = 'secondary'): string
    {
        $allowed = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
        $variant = in_array($variant, $allowed, true) ? $variant : 'secondary';
        return '<span class="badge text-bg-' . $variant . '">' . e($text) . '</span>';
    }

    /** Empty state block for lists with no rows yet. */
    public static function emptyState(string $title, string $hint = '', string $icon = 'inbox'): string
    {
        return '<div class="text-center py-5 text-secondary">'
             . '<i class="bi bi-' . e($icon) . ' fs-1" aria-hidden="true"></i>'
             . '<h2 class="h5 mt-2">' . e($title) . '</h2>'
             . ($hint !== '' ? '<p class="small mb-0">' . e($hint) . '</p>' : '')
             . '</div>';
    }

    /** Loading state (shown server-side while data is being fetched in later phases). */
    public static function loadingState(string $label = 'Loading…'): string
    {
        return '<div class="d-flex align-items-center gap-2 text-secondary py-4">'
             . '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
             . '<span>' . e($label) . '</span></div>';
    }

    /** Error state card (for failed data loads; message escaped). */
    public static function errorState(string $message, string $retryUrl = ''): string
    {
        $retry = $retryUrl !== ''
            ? '<a class="btn btn-sm btn-outline-secondary mt-3" href="' . e($retryUrl) . '">Retry</a>'
            : '';
        return '<div class="border border-danger-subtle rounded bg-body-tertiary p-4 text-center">'
             . '<i class="bi bi-exclamation-triangle text-danger fs-3" aria-hidden="true"></i>'
             . '<p class="mb-0 mt-2">' . e($message) . '</p>' . $retry . '</div>';
    }

    /**
     * Breadcrumb trail.
     * @param array<int, array{label:string,url?:string}> $items last item may omit url (= current page)
     */
    public static function breadcrumb(array $items): string
    {
        $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-2">';
        $last = count($items) - 1;
        foreach ($items as $i => $item) {
            $label = e($item['label'] ?? '');
            if ($i === $last || empty($item['url'])) {
                $html .= '<li class="breadcrumb-item active" aria-current="page">' . $label . '</li>';
            } else {
                $html .= '<li class="breadcrumb-item"><a href="' . e($item['url']) . '">' . $label . '</a></li>';
            }
        }
        return $html . '</ol></nav>';
    }

    /** Page heading row with optional primary action button. */
    public static function pageHeading(string $title, string $subtitle = '', string $actionUrl = '', string $actionLabel = ''): string
    {
        $action = ($actionUrl !== '' && $actionLabel !== '')
            ? '<a class="btn btn-primary btn-sm" href="' . e($actionUrl) . '"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>'
              . e($actionLabel) . '</a>'
            : '';
        $sub = $subtitle !== '' ? '<p class="text-secondary small mb-0">' . e($subtitle) . '</p>' : '';
        return '<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">'
             . '<div><h1 class="h4 mb-1">' . e($title) . '</h1>' . $sub . '</div>'
             . '<div>' . $action . '</div></div>';
    }

    /**
     * Pagination (server-side links; Bootstrap 5 pager).
     * @param int $page     current 1-based page
     * @param int $total    total items
     * @param int $perPage  items per page
     * @param string $baseUrl base URL; existing query string kept via $query
     * @param array<string,string> $query extra query params to preserve
     */
    public static function pagination(int $page, int $total, int $perPage, string $baseUrl, array $query = []): string
    {
        $pages = max(1, (int) ceil($total / max(1, $perPage)));
        if ($pages <= 1) {
            return '';
        }
        $link = static function (int $p) use ($baseUrl, $query): string {
            $query['page'] = $p;
            return e($baseUrl . '?' . http_build_query($query));
        };

        $html = '<nav aria-label="Pagination"><ul class="pagination pagination-sm justify-content-end mb-0">';
        // Prev
        $html .= $page <= 1
            ? '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>'
            : '<li class="page-item"><a class="page-link" href="' . $link($page - 1) . '" rel="prev">&laquo;</a></li>';
        // Windowed numbers: first, …, around current, …, last
        $show = [];
        for ($p = 1; $p <= $pages; $p++) {
            if ($p === 1 || $p === $pages || abs($p - $page) <= 2) {
                $show[] = $p;
            }
        }
        $prev = 0;
        foreach ($show as $p) {
            if ($prev > 0 && $p > $prev + 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
            $html .= $p === $page
                ? '<li class="page-item active" aria-current="page"><span class="page-link">' . $p . '</span></li>'
                : '<li class="page-item"><a class="page-link" href="' . $link($p) . '">' . $p . '</a></li>';
            $prev = $p;
        }
        $html .= $page >= $pages
            ? '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>'
            : '<li class="page-item"><a class="page-link" href="' . $link($page + 1) . '" rel="next">&raquo;</a></li>';
        return $html . '</ul></nav>';
    }

    /**
     * Text-like input field with label, old value, and validation error.
     * @param array{label:string,name:string,type?:string,value?:string,error?:string,required?:bool,placeholder?:string,maxlength?:int,help?:string} $f
     */
    public static function field(array $f): string
    {
        $type      = $f['type'] ?? 'text';
        $name      = (string) $f['name'];
        $id        = e($name);
        $value     = e((string) ($f['value'] ?? ''));
        $required  = !empty($f['required']) ? ' required' : '';
        $maxlen    = isset($f['maxlength']) ? ' maxlength="' . (int) $f['maxlength'] . '"' : '';
        $ph        = isset($f['placeholder']) ? ' placeholder="' . e($f['placeholder']) . '"' : '';
        $invalid   = !empty($f['error']);
        $errHtml   = $invalid ? '<div class="invalid-feedback d-block">' . e($f['error']) . '</div>' : '';
        $help      = !empty($f['help']) ? '<div class="form-text">' . e($f['help']) . '</div>' : '';
        $ac        = in_array($type, ['password'], true) ? ' autocomplete="current-password"' : ' autocomplete="off"';
        $isTextarea = $type === 'textarea';
        $control   = $isTextarea
            ? '<textarea class="form-control' . ($invalid ? ' is-invalid' : '') . '" id="' . $id . '" name="' . $id . '"'
              . $required . (isset($f['rows']) ? ' rows="' . (int) $f['rows'] . '"' : ' rows="4"') . $ph . '>' . $value . '</textarea>'
            : '<input type="' . e($type) . '" class="form-control' . ($invalid ? ' is-invalid' : '') . '" id="' . $id . '" name="' . $id . '"'
              . ' value="' . $value . '"' . $required . $maxlen . $ph . $ac . '>';

        return '<div class="mb-3">'
             . '<label class="form-label" for="' . $id . '">' . e($f['label']) . ($required ? ' <span class="text-danger">*</span>' : '') . '</label>'
             . $control
             . $errHtml . $help . '</div>';
    }

    /** Select field with options map value=>label. */
    public static function select(array $f, array $options): string
    {
        $name    = (string) $f['name'];
        $id      = e($name);
        $current = (string) ($f['value'] ?? '');
        $invalid = !empty($f['error']);
        $opts    = '';
        if (!isset($f['no_placeholder'])) {
            $opts = '<option value="">— Select —</option>';
        }
        foreach ($options as $val => $label) {
            $sel = ((string) $val === $current) ? ' selected' : '';
            $opts .= '<option value="' . e((string) $val) . '"' . $sel . '>' . e((string) $label) . '</option>';
        }
        $errHtml = $invalid ? '<div class="invalid-feedback d-block">' . e($f['error']) . '</div>' : '';
        return '<div class="mb-3">'
             . '<label class="form-label" for="' . $id . '">' . e($f['label']) . (isset($f['required']) && $f['required'] ? ' <span class="text-danger">*</span>' : '') . '</label>'
             . '<select class="form-select' . ($invalid ? ' is-invalid' : '') . '" id="' . $id . '" name="' . $id . '">' . $opts . '</select>'
             . $errHtml . '</div>';
    }

    /** Responsive striped table wrapper with caption-safe markup. */
    public static function table(string $headHtml, string $bodyHtml, string $captionId = ''): string
    {
        $cap = $captionId !== '' ? ' aria-describedby="' . e($captionId) . '"' : '';
        return '<div class="table-responsive">'
             . '<table class="table table-striped table-hover align-middle mb-0"' . $cap . '>'
             . '<thead class="table-light">' . $headHtml . '</thead>'
             . '<tbody>' . $bodyHtml . '</tbody></table></div>';
    }
}
