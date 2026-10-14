<?php
/**
 * UMS EduGate — input validator (Phase 5: Countries & Universities CRUD).
 *
 * Server-side validation only; every module reuses this class.
 * Null-safe by design: all inputs are cast to string before any
 * string function runs (fixes the iso3 null-key crash from a previous
 * aborted attempt).
 */

declare(strict_types=1);

// mbstring is optional; provide byte-safe fallbacks so validation never crashes.
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $s, ?string $enc = null): int { return strlen($s); }
}
if (!function_exists('mb_substr')) {
    function mb_substr(string $s, int $start, ?int $len = null, ?string $enc = null): string {
        return $len === null ? substr($s, $start) : substr($s, $start, $len);
    }
}

final class Validator
{
    /** @var array<string,string> field => first error message */
    private array $errors = [];
    /** @var array<string,mixed> validated output */
    private array $data = [];

    public function __construct(private array $input)
    {
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function data(): array
    {
        return $this->data;
    }

    /** Attach an error from outside the class (e.g. referential checks in controllers). */
    public function addError(string $key, string $message): void
    {
        $this->fail($key, $message);
    }

    /** Raw trimmed string for a key ('' when absent/null). Never null. */
    private function raw(string $key): string
    {
        $v = $this->input[$key] ?? '';
        if (is_array($v)) {
            return '';
        }
        return trim((string) $v);
    }

    private function fail(string $key, string $message): void
    {
        $this->errors[$key] ??= $message;
    }

    /** Required text field with max length. */
    public function required(string $key, string $label, int $max = 190): self
    {
        $v = $this->raw($key);
        if ($v === '') {
            $this->fail($key, "{$label} is required.");
        } elseif (mb_strlen($v) > $max) {
            $this->fail($key, "{$label} must be at most {$max} characters.");
        } else {
            $this->data[$key] = $v;
        }
        return $this;
    }

    /** Optional text field (null when empty). */
    public function optional(string $key, string $label, int $max = 255): self
    {
        $v = $this->raw($key);
        if ($v === '') {
            $this->data[$key] = null;
        } elseif (mb_strlen($v) > $max) {
            $this->fail($key, "{$label} must be at most {$max} characters.");
        } else {
            $this->data[$key] = $v;
        }
        return $this;
    }

    /** ISO 3166-1 alpha-2 code: exactly 2 letters, uppercased. Empty -> null. */
    public function iso2(string $key = 'iso2'): self
    {
        $v = strtoupper($this->raw($key)); // raw() guarantees string, never null
        if ($v === '') {
            $this->data[$key] = null;
        } elseif (!preg_match('/^[A-Z]{2}$/', $v)) {
            $this->fail($key, 'ISO 2-letter code must be exactly two letters (e.g. BD).');
        } else {
            $this->data[$key] = $v;
        }
        return $this;
    }

    /** ISO 3166-1 alpha-3 code: exactly 3 letters, uppercased. Empty -> null. */
    public function iso3(string $key = 'iso3'): self
    {
        $v = strtoupper($this->raw($key)); // null-safe: no undefined-key crash
        if ($v === '') {
            $this->data[$key] = null;
        } elseif (!preg_match('/^[A-Z]{3}$/', $v)) {
            $this->fail($key, 'ISO 3-letter code must be exactly three letters (e.g. BGD).');
        } else {
            $this->data[$key] = $v;
        }
        return $this;
    }

    /** ISO 4217 currency code: 3 letters. Empty -> null. */
    public function currency(string $key = 'currency_code'): self
    {
        $v = strtoupper($this->raw($key));
        if ($v === '') {
            $this->data[$key] = null;
        } elseif (!preg_match('/^[A-Z]{3}$/', $v)) {
            $this->fail($key, 'Currency code must be three letters (e.g. BDT).');
        } else {
            $this->data[$key] = $v;
        }
        return $this;
    }

    /** http(s) URL or empty -> null. Normalised, scheme added if missing. */
    public function url(string $key, string $label = 'Website', int $max = 500): self
    {
        $v = $this->raw($key);
        if ($v === '') {
            $this->data[$key] = null;
            return $this;
        }
        if (mb_strlen($v) > $max) {
            $this->fail($key, "{$label} must be at most {$max} characters.");
            return $this;
        }
        if (!preg_match('#^https?://#i', $v)) {
            $v = 'https://' . $v;
        }
        if (!filter_var($v, FILTER_VALIDATE_URL) || !preg_match('#^https?://[^\s]+$#i', $v)) {
            $this->fail($key, "{$label} must be a valid http(s) URL.");
            return $this;
        }
        $this->data[$key] = $v;
        return $this;
    }

    /** Value must be one of an allow-list. Empty -> null unless required. */
    public function in(string $key, array $allowed, string $label, bool $requiredField = false): self
    {
        $v = $this->raw($key);
        if ($v === '') {
            if ($requiredField) {
                $this->fail($key, "{$label} is required.");
            } else {
                $this->data[$key] = null;
            }
            return $this;
        }
        $upper = strtoupper($v);
        if (!in_array($upper, $allowed, true)) {
            $this->fail($key, "{$label} must be one of: " . implode(', ', $allowed) . '.');
            return $this;
        }
        $this->data[$key] = $upper;
        return $this;
    }

    /** Positive integer id (from a select). */
    public function id(string $key, string $label, bool $requiredField = true): self
    {
        $v = $this->raw($key);
        if ($v === '') {
            if ($requiredField) {
                $this->fail($key, "{$label} is required.");
            } else {
                $this->data[$key] = null;
            }
            return $this;
        }
        if (!ctype_digit($v) || (int) $v < 1) {
            $this->fail($key, "{$label} must be a valid selection.");
            return $this;
        }
        $this->data[$key] = (int) $v;
        return $this;
    }

    /** Calendar date Y-m-d (HTML5 input format). Empty -> null unless required. */
    public function date(string $key, string $label, bool $requiredField = false): self
    {
        $v = $this->raw($key);
        if ($v === '') {
            if ($requiredField) {
                $this->fail($key, "{$label} is required.");
            } else {
                $this->data[$key] = null;
            }
            return $this;
        }
        if (!$this->isDate($v)) {
            $this->fail($key, "{$label} must be a valid calendar date (YYYY-MM-DD).");
            return $this;
        }
        $this->data[$key] = $v;
        return $this;
    }

    /** Date-time as "Y-m-d\TH:i" from datetime-local inputs; stored as "Y-m-d H:i:s". */
    public function dateTime(string $key, string $label, bool $requiredField = false): self
    {
        $v = $this->raw($key);
        if ($v === '') {
            if ($requiredField) {
                $this->fail($key, "{$label} is required.");
            } else {
                $this->data[$key] = null;
            }
            return $this;
        }
        $norm = str_replace('T', ' ', $v);
        if (preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})(:\d{2})?$/', $norm, $m)) {
            $norm = $m[1] . ' ' . $m[2] . ($m[3] ?? ':00');
        }
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $norm);
        if ($dt === false || $dt->format('Y-m-d H:i:s') !== $norm) {
            $this->fail($key, "{$label} must be a valid date and time.");
            return $this;
        }
        $this->data[$key] = $norm;
        return $this;
    }

    /** Small positive integer (e.g. year). Empty -> null unless required. */
    public function intInRange(string $key, string $label, int $min, int $max, bool $requiredField = false): self
    {
        $v = $this->raw($key);
        if ($v === '') {
            if ($requiredField) {
                $this->fail($key, "{$label} is required.");
            } else {
                $this->data[$key] = null;
            }
            return $this;
        }
        if (!preg_match('/^\d+$/', $v)) {
            $this->fail($key, "{$label} must be a whole number.");
            return $this;
        }
        $n = (int) $v;
        if ($n < $min || $n > $max) {
            $this->fail($key, "{$label} must be between {$min} and {$max}.");
            return $this;
        }
        $this->data[$key] = $n;
        return $this;
    }

    /** Checkbox-style boolean: presence of "1"/"on" -> 1, otherwise 0. */
    public function checkboxBool(string $key): self
    {
        $v = strtolower($this->raw($key));
        $this->data[$key] = in_array($v, ['1', 'on', 'true'], true) ? 1 : 0;
        return $this;
    }

    /**
     * Decimal number with at most 2 fraction digits, bounded (e.g. GPA / test score).
     * Empty -> null unless required. Stored as a normalized string ("3.75").
     */
    public function decimal(string $key, string $label, float $min, float $max, bool $requiredField = false): self
    {
        $v = $this->raw($key);
        if ($v === '') {
            if ($requiredField) {
                $this->fail($key, "{$label} is required.");
            } else {
                $this->data[$key] = null;
            }
            return $this;
        }
        if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $v)) {
            $this->fail($key, "{$label} must be a number with up to two decimals (e.g. 3.75).");
            return $this;
        }
        $n = (float) $v;
        if ($n < $min || $n > $max) {
            $this->fail($key, "{$label} must be between {$min} and {$max}.");
            return $this;
        }
        $this->data[$key] = number_format($n, 2, '.', '');
        return $this;
    }

    /** Integer percentage bounded 0–100 (e.g. minimum percentage requirement). */
    public function percent(string $key, string $label, bool $requiredField = false): self
    {
        $v = $this->raw($key);
        if ($v === '') {
            if ($requiredField) {
                $this->fail($key, "{$label} is required.");
            } else {
                $this->data[$key] = null;
            }
            return $this;
        }
        if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $v)) {
            $this->fail($key, "{$label} must be a number with up to two decimals.");
            return $this;
        }
        $n = (float) $v;
        if ($n < 0 || $n > 100) {
            $this->fail($key, "{$label} must be between 0 and 100.");
            return $this;
        }
        $this->data[$key] = number_format($n, 2, '.', '');
        return $this;
    }

    /** Multi-line text stored as a single column (textarea input). Optional. */
    public function textarea(string $key, string $label, int $max = 5000): self
    {
        $v = $this->raw($key);
        if ($v === '') {
            $this->data[$key] = null;
            return $this;
        }
        if (mb_strlen($v) > $max) {
            $this->fail($key, "{$label} must be at most {$max} characters.");
            return $this;
        }
        $this->data[$key] = $v;
        return $this;
    }

    /** Strict calendar-date check (no rollover like 2024-02-31). */
    private function isDate(string $v): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m)) {
            return false;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}
