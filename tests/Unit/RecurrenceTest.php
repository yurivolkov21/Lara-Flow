<?php

namespace Tests\Unit;

use App\Data\Recurrence;
use App\Enums\RecurrenceFrequency;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RecurrenceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $rule
     */
    #[DataProvider('occurrences')]
    public function test_next_occurrence(array $rule, string $from, string $expected)
    {
        $recurrence = Recurrence::fromArray($rule);

        $this->assertSame(
            $expected,
            $recurrence->nextOccurrence(CarbonImmutable::parse($from))->toDateString(),
        );
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: string}>
     */
    public static function occurrences(): array
    {
        return [
            'daily' => [['frequency' => 'daily'], '2026-10-15', '2026-10-16'],
            'every 3 days' => [['frequency' => 'daily', 'interval' => 3], '2026-10-15', '2026-10-18'],
            'weekly without weekdays' => [['frequency' => 'weekly'], '2026-10-15', '2026-10-22'],
            'weekly, later this week' => [['frequency' => 'weekly', 'weekdays' => [1, 3, 5]], '2026-10-14', '2026-10-16'],
            'weekly, wraps to next week' => [['frequency' => 'weekly', 'weekdays' => [1, 3, 5]], '2026-10-16', '2026-10-19'],
            'every 2 weeks, wraps' => [['frequency' => 'weekly', 'interval' => 2, 'weekdays' => [1, 3]], '2026-10-14', '2026-10-26'],
            'weekly, unsorted weekdays' => [['frequency' => 'weekly', 'weekdays' => [5, 1]], '2026-10-13', '2026-10-16'],
            'monthly, later this month' => [['frequency' => 'monthly', 'month_day' => 20], '2026-10-15', '2026-10-20'],
            'monthly, next month' => [['frequency' => 'monthly', 'month_day' => 5], '2026-10-05', '2026-11-05'],
            'monthly on the 31st clamps' => [['frequency' => 'monthly', 'month_day' => 31], '2027-01-31', '2027-02-28'],
            'monthly on the 31st, after a short month' => [['frequency' => 'monthly', 'month_day' => 31], '2027-02-28', '2027-03-31'],
            'monthly on the 30th in a leap year' => [['frequency' => 'monthly', 'month_day' => 30], '2028-01-30', '2028-02-29'],
            'every 3 months' => [['frequency' => 'monthly', 'interval' => 3, 'month_day' => 1], '2026-10-01', '2027-01-01'],
        ];
    }

    public function test_next_due_date_skips_occurrences_that_already_passed()
    {
        $recurrence = new Recurrence(RecurrenceFrequency::Daily);

        $next = $recurrence->nextDueDate(
            from: CarbonImmutable::parse('2026-10-01'),
            today: CarbonImmutable::parse('2026-10-10'),
        );

        $this->assertSame('2026-10-11', $next->toDateString());
    }

    public function test_next_due_date_keeps_the_schedule_when_completed_early()
    {
        $recurrence = Recurrence::fromArray(['frequency' => 'monthly', 'month_day' => 5]);

        $next = $recurrence->nextDueDate(
            from: CarbonImmutable::parse('2026-11-05'),
            today: CarbonImmutable::parse('2026-10-20'),
        );

        $this->assertSame('2026-12-05', $next->toDateString());
    }

    public function test_from_array_ignores_fields_that_do_not_apply()
    {
        $recurrence = Recurrence::fromArray([
            'frequency' => 'daily',
            'interval' => 0,
            'weekdays' => [1, 2],
            'month_day' => 10,
        ]);

        $this->assertSame([
            'frequency' => 'daily',
            'interval' => 1,
            'weekdays' => [],
            'month_day' => null,
        ], $recurrence->toArray());
    }

    public function test_from_array_drops_invalid_weekdays()
    {
        $recurrence = Recurrence::fromArray([
            'frequency' => 'weekly',
            'weekdays' => [3, 9, '1', 3, 0],
        ]);

        $this->assertSame([1, 3], $recurrence->weekdays);
    }
}
