<?php

namespace App\Enums;

/**
 * The preset colors a project can use. The frontend maps each name to its palette.
 */
enum ProjectColor: string
{
    case Slate = 'slate';
    case Red = 'red';
    case Orange = 'orange';
    case Amber = 'amber';
    case Green = 'green';
    case Teal = 'teal';
    case Blue = 'blue';
    case Violet = 'violet';
    case Pink = 'pink';
}
