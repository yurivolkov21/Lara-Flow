<?php

namespace App\Http\Requests\Tasks;

use App\Enums\RecurrenceFrequency;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTaskRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        /** @var Team $team */
        $team = $this->route('current_team');

        return [
            'title' => [$required, 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'project_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('projects', 'id')
                    ->where('team_id', $team->id)
                    ->whereNull('deleted_at')
                    ->whereNull('archived_at'),
            ],
            'due_on' => ['nullable', 'date_format:Y-m-d', 'required_with:recurrence'],
            'due_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'recurrence' => ['sometimes', 'nullable', 'array'],
            'recurrence.frequency' => ['required_with:recurrence', Rule::enum(RecurrenceFrequency::class)],
            'recurrence.interval' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'recurrence.weekdays' => ['sometimes', 'array'],
            'recurrence.weekdays.*' => ['integer', 'between:1,7'],
            'recurrence.month_day' => ['sometimes', 'nullable', 'integer', 'between:1,31'],
        ];
    }

    /**
     * Get the validated task attributes, filling in recurrence defaults from the due date.
     *
     * @return array<string, mixed>
     */
    public function taskAttributes(): array
    {
        $attributes = $this->validated();

        if (isset($attributes['recurrence']) && is_array($attributes['recurrence'])) {
            $recurrence = $attributes['recurrence'];
            $dueOn = isset($attributes['due_on']) && is_string($attributes['due_on'])
                ? CarbonImmutable::parse($attributes['due_on'])
                : null;

            if ($recurrence['frequency'] === RecurrenceFrequency::Weekly->value && empty($recurrence['weekdays']) && $dueOn) {
                $recurrence['weekdays'] = [$dueOn->isoWeekday()];
            }

            if ($recurrence['frequency'] === RecurrenceFrequency::Monthly->value && empty($recurrence['month_day']) && $dueOn) {
                $recurrence['month_day'] = $dueOn->day;
            }

            $attributes['recurrence'] = $recurrence;
        }

        return $attributes;
    }
}
