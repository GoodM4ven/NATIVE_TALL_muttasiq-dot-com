<?php

declare(strict_types=1);

namespace App\Services\Enums;

enum ViewName: string
{
    case MainMenu = 'main-menu';
    case AthkarAppGate = 'athkar-app-gate';
    case AthkarAppSabah = 'athkar-app-sabah';
    case AthkarAppMasaa = 'athkar-app-masaa';
}
