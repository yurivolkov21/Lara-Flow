<?php

namespace App\Console\Commands;

use App\Mail\DailyDigest;
use App\Models\User;
use App\Queries\DigestTasks;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

#[Signature('digest:send')]
#[Description('Email the morning digest to users whose digest time has come in their own timezone')]
class SendDailyDigests extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sent = 0;

        User::query()
            ->where('digest_enabled', true)
            ->whereNotNull('email_verified_at')
            ->chunkById(100, function (Collection $users) use (&$sent) {
                /** @var Collection<int, User> $users */
                foreach ($users as $user) {
                    $now = $user->localNow();

                    if ($now->format('H:i') < substr($user->digest_time, 0, 5)) {
                        continue;
                    }

                    if ($user->digest_last_sent_on?->toDateString() === $now->toDateString()) {
                        continue;
                    }

                    $tasks = DigestTasks::for($user, $now->startOfDay());

                    if ($tasks['today']->isNotEmpty() || $tasks['overdue']->isNotEmpty()) {
                        Mail::to($user)->queue(new DailyDigest($user, $now->startOfDay(), $tasks['today'], $tasks['overdue']));
                        $sent++;
                    }

                    $user->forceFill(['digest_last_sent_on' => $now->toDateString()])->save();
                }
            });

        $this->components->info("Sent {$sent} digests.");

        return self::SUCCESS;
    }
}
