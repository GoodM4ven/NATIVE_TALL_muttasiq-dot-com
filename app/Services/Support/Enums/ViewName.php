<?php

declare(strict_types=1);

namespace App\Services\Support\Enums;

enum ViewName: string
{
    case MainMenu = 'main-menu';
    case AthkarAppGate = 'athkar-app-gate';
    case AthkarAppSabah = 'athkar-app-sabah';
    case AthkarAppMasaa = 'athkar-app-masaa';
    case QuranAppGate = 'quran-app-gate';
    case QuranAppTilawa = 'quran-app-tilawa';
    case QuranAppHifth = 'quran-app-hifth';
    case QuranAppTadabbur = 'quran-app-tadabbur';
}
