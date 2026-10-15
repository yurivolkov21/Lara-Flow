<?php

namespace App\Enums;

/**
 * The preset accent colors a user can pick. Each one swaps the shadcn primary color variables.
 */
enum AccentColor: string
{
    case Neutral = 'neutral';
    case Blue = 'blue';
    case Green = 'green';
    case Orange = 'orange';
    case Rose = 'rose';
    case Violet = 'violet';
}
