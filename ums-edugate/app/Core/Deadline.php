<?php
/**
 * UMS EduGate — deadline labelling (Phase 6: application rounds).
 *
 * PRD rule: missing deadlines remain UNKNOWN, never fabricated.
 * Labels: UPCOMING | CLOSING_SOON | PASSED | UNKNOWN
 */

declare(strict_types=1);

final class Deadline
{
    /** Days before closing_at that count as "closing soon". */
    public const CLOSING_SOON_DAYS = 14;

    /**
     * Classify a round's closing datetime against "now".
     *
     * @param string|null $closesAt 'Y-m-d H:i:s' or null (unknown)
     * @param DateTimeImmutable|null $now injectable for tests
     * @return array{label:string, css:string, days:?int}
     */
    public static function classify(?string $closesAt, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now');

        if ($closesAt === null || trim($closesAt) === '') {
            return self::unknown();
        }

        $closing = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $closesAt)
              ?: DateTimeImmutable::createFromFormat('Y-m-d H:i', $closesAt);
        if ($closing === false) {
            return self::unknown(); // malformed stored value -> unknown, never guess
        }

        $diffSeconds = $closing->getTimestamp() - $now->getTimestamp();
        $days = intdiv(abs($diffSeconds), 86400);
        $diffDays = $diffSeconds < 0 ? -$days : $days;

        if ($closing < $now) {
            return [
                'label' => 'Passed',
                'css'   => 'secondary',
                'days'  => $diffDays,
            ];
        }
        if ($diffDays <= self::CLOSING_SOON_DAYS) {
            return [
                'label' => 'Closing soon',
                'css'   => 'warning',
                'days'  => $diffDays,
            ];
        }
        return [
            'label' => 'Upcoming',
            'css'   => 'success',
            'days'  => $diffDays,
        ];
    }

    private static function unknown(): array
    {
        return ['label' => 'Unknown', 'css' => 'light text-body-secondary border', 'days' => null];
    }
}
