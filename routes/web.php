<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Projects\ProjectArchiveController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Tasks\TaskController;
use App\Http\Controllers\Tasks\TaskItemController;
use App\Http\Controllers\Tasks\TaskOrderController;
use App\Http\Controllers\Tasks\TaskStatusController;
use App\Http\Controllers\Tasks\TaskViewController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\TrashController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->scopeBindings()
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('today', [TaskViewController::class, 'today'])->name('tasks.today');
        Route::get('upcoming', [TaskViewController::class, 'upcoming'])->name('tasks.upcoming');
        Route::get('overdue', [TaskViewController::class, 'overdue'])->name('tasks.overdue');
        Route::get('inbox', [TaskViewController::class, 'inbox'])->name('tasks.inbox');

        Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::patch('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('projects/{project}/archive', [ProjectArchiveController::class, 'store'])->name('projects.archive');
        Route::delete('projects/{project}/archive', [ProjectArchiveController::class, 'destroy'])->name('projects.unarchive');

        Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::post('tasks/order', [TaskOrderController::class, 'update'])->name('tasks.order');
        Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::patch('tasks/{task}/status', [TaskStatusController::class, 'update'])->name('tasks.status');

        Route::post('tasks/{task}/items', [TaskItemController::class, 'store'])->name('tasks.items.store');
        Route::post('tasks/{task}/items/order', [TaskItemController::class, 'reorder'])->name('tasks.items.order');
        Route::patch('tasks/{task}/items/{item}', [TaskItemController::class, 'update'])->name('tasks.items.update');
        Route::delete('tasks/{task}/items/{item}', [TaskItemController::class, 'destroy'])->name('tasks.items.destroy');

        Route::get('trash', [TrashController::class, 'index'])->name('trash.index');
        Route::delete('trash', [TrashController::class, 'empty'])->name('trash.empty');
        Route::post('trash/projects/{project}/restore', [TrashController::class, 'restoreProject'])->withTrashed()->name('trash.projects.restore');
        Route::delete('trash/projects/{project}', [TrashController::class, 'destroyProject'])->withTrashed()->name('trash.projects.destroy');
        Route::post('trash/tasks/{task}/restore', [TrashController::class, 'restoreTask'])->withTrashed()->name('trash.tasks.restore');
        Route::delete('trash/tasks/{task}', [TrashController::class, 'destroyTask'])->withTrashed()->name('trash.tasks.destroy');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
