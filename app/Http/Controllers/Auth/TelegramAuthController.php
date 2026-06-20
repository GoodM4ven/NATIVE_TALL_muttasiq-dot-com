<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class TelegramAuthController
{
    public function callback(): RedirectResponse
    {
        $telegramUser = Socialite::driver('telegram')->user();

        $existing = User::query()->where('telegram_id', $telegramUser->getId())->first();

        if ($existing === null) {
            $username = $this->generateUniqueUsername();
            $password = Str::password(12);

            $user = User::query()->create([
                'name' => trim((string) $telegramUser->getName()) ?: $username,
                'username' => $username,
                'telegram_username' => $telegramUser->getNickname(),
                'telegram_id' => $telegramUser->getId(),
                'password' => Hash::make($password),
            ]);

            $this->notifyCredentials((int) $telegramUser->getId(), $username, $password);

            session()->flash('auth.fresh_credentials', [
                'username' => $username,
                'password' => $password,
            ]);
        } else {
            $user = $existing;
            $user->forceFill(['telegram_username' => $telegramUser->getNickname()])->save();
        }

        Auth::login($user, remember: true);

        // Flashes through the redirect so it shows on the freshly loaded home,
        // matching the username/password login notification.
        notify('heroicon-o-check-circle', arabic_text('تم تسجيل الدخول بنجاح'));

        return redirect()->route('home');
    }

    private function generateUniqueUsername(): string
    {
        // ponytail: linear retry on collision; fine at this scale.
        do {
            $username = 'user_'.Str::lower(Str::random(16));
        } while (User::query()->where('username', $username)->exists());

        return $username;
    }

    private function notifyCredentials(int $telegramId, string $username, string $password): void
    {
        $token = config('services.telegram.client_secret');

        if (! is_string($token) || $token === '') {
            return;
        }

        // ponytail: fire-and-forget; user may not have started the bot.
        try {
            Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $telegramId,
                'text' => "تم إنشاء حساب لكم في منصة متسق.\n\nاسم المستخدم:\n{$username}\n\nكلمة المرور:\n{$password}",
            ]);
        } catch (\Throwable) {
            // TODO swallow, for now — the credentials are also shown in the modal.
        }
    }
}
