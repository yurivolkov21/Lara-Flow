<?php

namespace App\Data;

use App\Enums\RecurrenceFrequency;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

readonly class Recurrence
{
    /**
     * @param  array<int, int>  $weekdays  ISO weekdays, 1 (Monday) to 7 (Sunday). Weekly only.
     * @param  int|null  $monthDay  Day of the month, 1 to 31. Monthly only; days past the end of a month use its last day.
     */
    public function __construct(
        public RecurrenceFrequency $frequency,
        public int $interval = 1,
        public array $weekdays = [],
        public ?int $monthDay = null,
    ) {
        if ($interval < 1) {
            throw new InvalidArgumentException('The recurrence interval must be at least 1.');
        }
    }

    /**
     * Create a recurrence from its stored array form.
     *
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $frequency = $data['frequency'] ?? null;

        if (! is_string($frequency)) {
            throw new InvalidArgumentException('The recurrence frequency is missing.');
        }

        $frequency = RecurrenceFrequency::from($frequency);
        $interval = $data['interval'] ?? 1;
        $weekdays = $data['weekdays'] ?? [];
        $monthDay = $data['month_day'] ?? null;

        return new self(
            frequency: $frequency,
            interval: is_numeric($interval) ? max(1, intval($interval)) : 1,
            weekdays: $frequency === RecurrenceFrequency::Weekly && is_array($weekdays)
                ? collect($weekdays)
                    ->filter(fn ($day) => is_numeric($day) && $day >= 1 && $day <= 7)
                    ->map(fn ($day) => intval($day))
                    ->unique()
                    ->sort()
                    ->values()
                    ->all()
                : [],
            monthDay: $frequency === RecurrenceFrequency::Monthly && is_numeric($monthDay)
                ? min(31, max(1, intval($monthDay)))
                : null,
        );
    }

    /**
     * Get the array form that is stored on the task.
     *
     * @return array{frequency: string, interval: int, weekdays: array<int, int>, month_day: int|null}
     */
    public function toArray(): array
    {
        return [
            'frequency' => $this->frequency->value,
            'interval' => $this->interval,
            'weekdays' => $this->weekdays,
            'month_day' => $this->monthDay,
        ];
    }

    /**
     * Get the next due date after the given one that is also after today.
     *
     * Occurrences that fell on or before today are skipped, so a task completed late
     * does not spawn a task that is already overdue.
     */
    public function nextDueDate(CarbonImmutable $from, CarbonImmutable $today): CarbonImmutable
    {
        $next = $this->nextOccurrence($from->startOfDay());

        while ($next->lessThanOrEqualTo($today->startOfDay())) {
            $next = $this->nextOccurrence($next);
        }

        return $next;
    }

    /**
     * Get the first occurrence strictly after the given date.
     */
    public function nextOccurrence(CarbonImmutable $date): CarbonImmutable
    {
        return match ($this->frequency) {
            RecurrenceFrequency::Daily => $date->addDays($this->interval),
            RecurrenceFrequency::Weekly => $this->nextWeekly($date),
            RecurrenceFrequency::Monthly => $this->nextMonthly($date),
        };
    }

    protected function nextWeekly(CarbonImmutable $date): CarbonImmutable
    {
        if ($this->weekdays === []) {
            return $date->addWeeks($this->interval);
        }

        $laterThisWeek = collect($this->weekdays)->first(fn (int $day) => $day > $date->isoWeekday());

        if ($laterThisWeek !== null) {
            return $date->addDays($laterThisWeek - $date->isoWeekday());
        }

        return $date->startOfWeek(CarbonImmutable::MONDAY)
            ->addWeeks($this->interval)
            ->addDays($this->weekdays[0] - 1);
    }

    protected function nextMonthly(CarbonImmutable $date): CarbonImmutable
    {
        $day = $this->monthDay ?? $date->day;

        $thisMonth = $date->setDay(min($day, $date->daysInMonth));

        if ($thisMonth->greaterThan($date)) {
            return $thisMonth;
        }

        $month = $date->startOfMonth()->addMonthsNoOverflow($this->interval);

        return $month->setDay(min($day, $month->daysInMonth));
    }
}
