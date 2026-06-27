<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{telegramId}', function (User $user, int $telegramId): bool {
    return (int) $user->telegram_id === $telegramId;
});
