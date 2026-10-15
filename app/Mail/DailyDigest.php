<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, Task>  $todayTasks
     * @param  Collection<int, Task>  $overdueTasks
     */
    public function __construct(
        public User $user,
        public CarbonImmutable $date,
        public Collection $todayTasks,
        public Collection $overdueTasks,
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your tasks for :date', ['date' => $this->date->settings(['locale' => $this->user->locale->value])->isoFormat('dddd, D MMMM')]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.daily-digest',
            with: [
                'showTeam' => $this->user->teams()->count() > 1,
            ],
        );
    }
}
