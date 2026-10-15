<?php

namespace App\Console\Commands;

use App\Actions\Trash\PruneTrash as PruneTrashAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('trash:prune')]
#[Description('Permanently delete trashed projects and tasks past their team\'s retention period, and teams deleted more than 30 days ago')]
class PruneTrash extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PruneTrashAction $pruneTrash): int
    {
        $deleted = $pruneTrash->handle();

        $this->components->info(sprintf(
            'Deleted %d projects, %d tasks and %d teams.',
            $deleted['projects'],
            $deleted['tasks'],
            $deleted['teams'],
        ));

        return self::SUCCESS;
    }
}
