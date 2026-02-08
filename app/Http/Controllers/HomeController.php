<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return view('home', [
            'athkar' => \App\Models\Thikr::query()
                ->orderBy('id')
                ->get(['id', 'time', 'text', 'count'])
                ->map(
                    fn (\App\Models\Thikr $thikr): array => [
                        'id' => $thikr->id,
                        'time' => $thikr->time?->value ?? $thikr->time,
                        'text' => $thikr->text,
                        'count' => $thikr->count,
                    ],
                )
                ->all(),
            'athkarSettings' => \App\Models\Setting::query()
                ->whereIn('name', array_keys(\App\Models\Setting::defaults()))
                ->pluck('value', 'name')
                ->all(),
        ]);
    }
}
