<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class TelegramAuthController
{
    public function native(): View
    {
        // The launcher page is served from the public host and opened inside the
        // OAuth browser. It posts the Telegram result to the dedicated NATIVE
        // callback route — the native signal MUST be the route, never a query
        // param: Telegram's hash is computed over every received field, so an
        // extra param would break signature validation.
        return view('auth.telegram-native', [
            'telegramBotName' => trim((string) config('services.telegram.bot', '')),
            'callbackUrl' => route('auth.telegram.native.callback'),
        ]);
    }

    public function callback(): RedirectResponse
    {
        $user = $this->authenticateTelegramUser();

        if ($user === null) {
            return redirect()->route('home');
        }

        // Flashes through the redirect so it shows on the freshly loaded home,
        // matching the username/password login notification.
        notify('heroicon-o-check-circle', arabic_text('تم تسجيل الدخول بنجاح'));

        return redirect()->route('home');
    }

    public function nativeCallback(): RedirectResponse
    {
        $user = $this->authenticateTelegramUser();

        if ($user === null) {
            return redirect()->route('home');
        }

        // Runs on the public host inside the OAuth browser. Issue a short-lived,
        // single-use code the device can exchange over HTTPS for the account
        // payload (the device has its own local DB + APP_KEY, so it can't share
        // an encrypted token or the user row). A SERVER-side 302 to the custom
        // scheme hands control back to the installed app; the code is random
        // alphanumeric, so it survives the deeplink round-trip intact.
        $code = Str::random(64);
        Cache::put('native-auth-code:'.$code, $user->getKey(), now()->addMinutes(5));

        return redirect()->away($this->nativeScheme().'://auth/telegram/handoff?code='.$code);
    }

    private function authenticateTelegramUser(): ?User
    {
        try {
            $telegramUser = Socialite::driver('telegram')->user();
        } catch (\Throwable) {
            notify(
                'heroicon-o-exclamation-circle',
                arabic_text('تعذر إتمام تسجيل الدخول عبر تيليجرام. حاول مرة أخرى.'),
            );

            return null;
        }

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

        return $user;
    }

    public function handoff(Request $request): RedirectResponse|Response
    {
        // Reached only via the deeplink, which routes into the LOCAL native
        // runtime's WebView. Exchange the one-time code over HTTPS for the account
        // payload, mirror the user into the device's own DB, log in, then hand off
        // to home for SecureStorage persistence + the blink/restart (Quran UX).
        abort_unless(is_platform('native'), 404);

        $user = $this->mirrorAccountFromExchange((string) $request->query('code', ''));

        if ($user === null) {
            // Relative so the WebView stays on the local runtime (127.0.0.1).
            return response('', 302, ['Location' => '/']);
        }

        // remember: true makes Auth set/persist the remember token; we then hand
        // that exact token to the device. It survives the Android cold-start
        // cookie wipe via SecureStorage, and restoreNative() re-logs-in by it.
        Auth::login($user, remember: true);

        session()->flash('auth.native_restore_token', (string) $user->getRememberToken());
        session()->flash('auth.native_restart', true);

        return response('', 302, ['Location' => '/']);
    }

    public function restoreNative(Request $request): JsonResponse
    {
        abort_unless(is_platform('native'), 404);

        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $nativeUser = User::query()->where('remember_token', $validated['token'])->first();

        if (! $nativeUser instanceof User) {
            return response()->json([
                'restored' => false,
            ], 422);
        }

        Auth::login($nativeUser, remember: true);

        return response()->json([
            'restored' => true,
        ]);
    }

    private function mirrorAccountFromExchange(string $code): ?User
    {
        $serverBase = native_server_base();

        if ($code === '' || $serverBase === null) {
            return null;
        }

        try {
            $response = Http::asJson()->acceptJson()
                ->post($serverBase.'/api/native-auth/exchange', ['code' => $code]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful() || $response->json('ok') !== true) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $data = (array) $response->json('user');
        $telegramId = $data['telegram_id'] ?? null;

        if ($telegramId === null) {
            return null;
        }

        $passwordHash = (string) ($data['password'] ?? '');

        $user = User::query()->updateOrCreate(
            ['telegram_id' => $telegramId],
            [
                'name' => (string) ($data['name'] ?? ''),
                'username' => (string) ($data['username'] ?? ''),
                'telegram_username' => $data['telegram_username'] ?? null,
                'password' => $passwordHash,
                // Pull the server's settings + API token so the device mirror is
                // consistent (server stays authoritative).
                'synced_data' => is_array($data['synced_data'] ?? null) ? $data['synced_data'] : null,
                'native_api_token' => $data['sync_token'] ?? null,
            ],
        );

        // Re-set the password via the query builder so the already-hashed value
        // from the server is stored verbatim instead of being hashed again.
        User::query()->whereKey($user->getKey())->update(['password' => $passwordHash]);

        return $user->refresh();
    }

    private function nativeScheme(): string
    {
        $scheme = trim((string) config('nativephp.deeplink_scheme', 'nativephp'));

        return $scheme !== '' ? $scheme : 'nativephp';
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
