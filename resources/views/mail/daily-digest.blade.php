<x-mail::message>
# {{ __('Good morning, :name', ['name' => $user->name]) }}

@if ($todayTasks->isEmpty() && $overdueTasks->isEmpty())
{{ __('Nothing is due today. Enjoy the free time.') }}
@endif

@if ($todayTasks->isNotEmpty())
## {{ __('Due today') }} ({{ $todayTasks->count() }})

@foreach ($todayTasks as $task)
- {{ $task->due_time ? $task->due_time.' · ' : '' }}**{{ $task->title }}**@if ($task->project) · {{ $task->project->name }}@endif @if ($showTeam) · {{ $task->team->name }}@endif @if ($task->priority->value === 'high') · {{ __('High priority') }}@endif

@endforeach
@endif

@if ($overdueTasks->isNotEmpty())
## {{ __('Overdue') }} ({{ $overdueTasks->count() }})

@foreach ($overdueTasks as $task)
- **{{ $task->title }}** · {{ __('was due :date', ['date' => $task->due_on?->locale($user->locale->value)->isoFormat('D MMM')]) }}@if ($task->project) · {{ $task->project->name }}@endif @if ($showTeam) · {{ $task->team->name }}@endif

@endforeach
@endif

@if ($user->currentTeam)
<x-mail::button :url="route('tasks.today', ['current_team' => $user->currentTeam])">
{{ __('Open today\'s tasks') }}
</x-mail::button>
@endif

{{ __('You get this email because the morning digest is on. You can turn it off in Settings, Preferences.') }}
</x-mail::message>
