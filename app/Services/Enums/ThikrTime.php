<?php

declare(strict_types=1);

namespace App\Services\Enums;

enum ThikrTime: string
{
    case Shared = 'shared';
    case Sabah = 'sabah';
    case Masaa = 'masaa';
}
