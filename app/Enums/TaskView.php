<?php

namespace App\Enums;

/**
 * The built-in task screens. Project screens are handled separately.
 */
enum TaskView: string
{
    case Today = 'today';
    case Upcoming = 'upcoming';
    case Overdue = 'overdue';
    case Inbox = 'inbox';
    case Project = 'project';
}
