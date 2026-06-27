<?php

declare(strict_types=1);

use App\Jobs\SyncUserSettings;
use App\Livewire\AuthButton;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use PragmaRX\Google2FA\Google2FA;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Livewire\livewire;

function fakeTelegramUser(int $id = 123456, string $nickname = 'testperson'): void
{
    Http::fake();

    $socialiteUser = (new SocialiteUser)->map([
        'id' => $id,
        'name' => 'Test Person',
        'nickname' => $nickname,
    ]);

    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')->with('telegram')->andReturn($provider);
}

it('creates a username-driven account and logs in on first telegram contact', function () {
    fakeTelegramUser();

    get(route('auth.telegram.callback'))->assertRedirect(route('home'));

    $user = User::query()->first();

    expect($user)->not->toBeNull()
        ->and($user->telegram_id)->toBe(123456)
        ->and($user->telegram_username)->toBe('testperson')
        ->and($user->username)->toStartWith('user_')
        ->and($user->email)->toBeNull();

    assertAuthenticatedAs($user);
});

it('reuses the existing account on a returning telegram login', function () {
    $existing = User::factory()->create(['telegram_id' => 999, 'telegram_username' => 'old']);

    fakeTelegramUser(id: 999, nickname: 'updated');

    get(route('auth.telegram.callback'));

    expect(User::query()->count())->toBe(1)
        ->and($existing->fresh()->telegram_username)->toBe('updated');

    assertAuthenticatedAs($existing->fresh());
});

it('redirects home when telegram callback data is invalid', function () {
    get(route('auth.telegram.callback'))->assertRedirect(route('home'));

    assertGuest();
});

it('issues a one-time code and redirects native telegram login to the app deeplink', function () {
    fakeTelegramUser();

    // Native flow uses a dedicated route (never a query param — that would break
    // Telegram's hash check). The server 302s to the deeplink carrying a random,
    // single-use code the device exchanges over HTTPS.
    $response = get(route('auth.telegram.native.callback'))
        ->assertStatus(302);

    $location = (string) $response->headers->get('Location');

    expect($location)
        ->toStartWith(config('nativephp.deeplink_scheme').'://auth/telegram/handoff?code=');

    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
    $code = (string) ($query['code'] ?? '');

    expect($code)->not->toBe('');
    expect(Cache::get('native-auth-code:'.$code))->toBe(User::query()->first()?->getKey());
    assertGuest();
});

it('keeps the browser session guest during native telegram auth handoff', function () {
    fakeTelegramUser();

    get(route('auth.telegram.native.callback'))
        ->assertStatus(302);

    assertGuest();
});

it('exchanges a one-time code for the account payload', function () {
    $user = User::factory()->create([
        'telegram_id' => 555,
        'username' => 'user_exchange',
        'synced_data' => ['quran-reader-last-page-v1' => '42'],
    ]);
    Cache::put('native-auth-code:CODE123', $user->getKey(), now()->addMinutes(5));

    $response = postJson(route('api.native-auth.exchange'), ['code' => 'CODE123'])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('user.telegram_id', 555)
        ->assertJsonPath('user.username', 'user_exchange')
        ->assertJsonPath('user.synced_data.quran-reader-last-page-v1', '42');

    // A fresh Sanctum Bearer token is issued and persisted server-side.
    expect((string) $response->json('user.sync_token'))->toContain('|');
    expect($user->tokens()->count())->toBe(1);

    // Single-use: the code is consumed.
    postJson(route('api.native-auth.exchange'), ['code' => 'CODE123'])
        ->assertStatus(422);
});

it('pushes a native password change to the authoritative server', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    postJson(route('api.native-sync.password'), ['password' => 'brand-new-pass'])
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect(Hash::check('brand-new-pass', $user->fresh()->password))->toBeTrue();
});

it('pushes a native settings change to the authoritative server', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    postJson(route('api.native-sync.settings'), ['data' => ['athkar-progress-v1' => 'value']])
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect($user->fresh()->synced_data)->toBe(['athkar-progress-v1' => 'value']);
});

it('ignores stale native settings pushes', function () {
    $user = User::factory()->create([
        'synced_data' => ['athkar-progress-v1' => 'newer'],
        'synced_data_updated_at' => now(),
    ]);
    $token = $user->createToken('native-device')->plainTextToken;

    postJson(route('api.native-sync.settings'), [
        'data' => ['athkar-progress-v1' => 'older'],
        'synced_at' => now()->subMinute()->toISOString(),
    ], ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonPath('stale', true);

    expect($user->fresh()->synced_data)->toBe(['athkar-progress-v1' => 'newer']);
});

it('rejects native sync without a valid bearer token', function () {
    postJson(route('api.native-sync.password'), ['password' => 'whatever-pass'])
        ->assertStatus(401);
});

it('queues the settings sync on native instead of pushing synchronously', function () {
    config(['nativephp-internal.running' => true]);
    Queue::fake();

    $user = User::factory()->create(['native_api_token' => 'dev-bearer']);
    Auth::login($user);

    livewire(AuthButton::class)->call('pushUserData', ['athkar-progress-v1' => 'x']);

    Queue::assertPushed(SyncUserSettings::class);
});

it('pushes the latest settings bundle to the server when the sync job runs', function () {
    Http::fake(['*/api/native-sync/settings' => Http::response(['ok' => true])]);

    $user = User::factory()->create([
        'native_api_token' => 'dev-bearer',
        'synced_data' => ['athkar-progress-v1' => 'latest'],
    ]);

    (new SyncUserSettings($user->getKey()))->handle();

    Http::assertSent(
        fn ($request): bool => str_contains($request->url(), '/api/native-sync/settings')
            && $request->hasHeader('Authorization', 'Bearer dev-bearer')
            && $request['data']['athkar-progress-v1'] === 'latest',
    );
});

it('deletes the authoritative account from a native device', function () {
    $user = User::factory()->create();
    $user->createToken('native-device');
    Sanctum::actingAs($user);

    postJson(route('api.native-sync.delete'))
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect(User::query()->whereKey($user->getKey())->exists())->toBeFalse();
});

it('revokes only the device token on native logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('native-device')->plainTextToken;

    expect($user->tokens()->count())->toBe(1);

    postJson(route('api.native-sync.logout'), [], ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect($user->fresh()->tokens()->count())->toBe(0);
});

it('lists and revokes native device tokens except the current one', function () {
    $user = User::factory()->create();
    $currentToken = $user->createToken('this-device')->plainTextToken;
    $otherToken = $user->createToken('other-device')->accessToken;

    getJson(route('api.native-sync.devices'), ['Authorization' => "Bearer {$currentToken}"])
        ->assertOk()
        ->assertJsonPath('devices.0.id', $otherToken->getKey())
        ->assertJsonPath('devices.0.name', 'other-device');

    postJson(route('api.native-sync.devices.revoke'), [
        'token_id' => $otherToken->getKey(),
    ], ['Authorization' => "Bearer {$currentToken}"])
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect($user->tokens()->whereKey($otherToken->getKey())->exists())->toBeFalse()
        ->and($user->tokens()->where('name', 'this-device')->exists())->toBeTrue();
});

it('verifies native two-factor codes against the authoritative server', function () {
    $provider = app(TwoFactorAuthenticationProvider::class);
    $secret = $provider->generateSecretKey();
    $code = (new Google2FA)->getCurrentOtp($secret);
    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);
    $token = $user->createToken('native-device')->plainTextToken;

    postJson(route('api.native-auth.two-factor'), ['code' => $code], ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonPath('ok', true);

    postJson(route('api.native-auth.two-factor'), ['code' => '000000'], ['Authorization' => "Bearer {$token}"])
        ->assertStatus(422);
});

it('uses server two-factor verification for native username logins', function () {
    config([
        'nativephp-internal.running' => true,
        'app.custom.native_end_points.settings' => 'https://sync.example.test/api/settings',
    ]);

    $user = User::factory()->create([
        'password' => Hash::make('secret-pass'),
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => now(),
        'native_api_token' => '1|native-token',
    ]);

    $component = livewire(AuthButton::class)
        ->mountAction('login')
        ->fillForm([
            'username' => $user->username,
            'password' => 'secret-pass',
            'code' => '',
        ])
        ->callMountedAction()
        ->assertSet('awaitingTwoFactor', true)
        ->assertNotDispatched('auth-blink-reload');

    assertGuest();

    Http::fake([
        'https://sync.example.test/api/native-auth/two-factor' => Http::response(['ok' => true]),
    ]);

    $component
        ->fillForm([
            'username' => $user->username,
            'password' => 'secret-pass',
            'code' => '123456',
        ])
        ->callMountedAction()
        ->assertDispatched('auth-blink-reload');

    assertAuthenticatedAs($user);
});

it('mirrors the account locally and flags a restart from the native deeplink handoff', function () {
    config(['nativephp-internal.running' => true]);

    Http::fake([
        '*/api/native-auth/exchange' => Http::response([
            'ok' => true,
            'user' => [
                'telegram_id' => 777,
                'telegram_username' => 'tg_handoff',
                'name' => 'Handoff User',
                'username' => 'user_handoff',
                'password' => bcrypt('secret-pass'),
                'sync_token' => 'sync-handoff',
                'synced_data' => ['quran-reader-last-page-v1' => '7'],
            ],
        ]),
    ]);

    // A RELATIVE redirect keeps the WebView on the local runtime; the home shell
    // then stores the remember token in SecureStorage, blinks, and restarts.
    get(route('auth.telegram.handoff', ['code' => 'ABC']))
        ->assertStatus(302)
        ->assertHeader('Location', '/')
        ->assertSessionHas('auth.native_restart', true)
        ->assertSessionHas('auth.native_restore_token');

    $user = User::query()->where('telegram_id', 777)->first();

    expect($user)->not->toBeNull()
        ->and($user->username)->toBe('user_handoff')
        // Server-authoritative state is mirrored locally on login.
        ->and($user->native_api_token)->toBe('sync-handoff')
        ->and($user->synced_data)->toBe(['quran-reader-last-page-v1' => '7']);

    assertAuthenticatedAs($user);

    // The mirrored hash is stored verbatim, so username/password still works.
    expect(Hash::check('secret-pass', $user->password))->toBeTrue();

    // The remember token is persisted for the cold-start restore.
    expect($user->fresh()->remember_token)->toBe(session('auth.native_restore_token'));
});

it('blocks the native handoff outside the native runtime', function () {
    get(route('auth.telegram.handoff', ['code' => 'ABC']))
        ->assertNotFound();

    assertGuest();
});

it('restores a native session from the persisted remember token', function () {
    config(['nativephp-internal.running' => true]);

    $user = User::factory()->create(['remember_token' => 'remember-token-xyz']);

    postJson(route('auth.telegram.native.restore'), ['token' => 'remember-token-xyz'])
        ->assertOk()
        ->assertJsonPath('restored', true);

    assertAuthenticatedAs($user);
});

it('wires the native auth restore endpoint into the home shell', function () {
    $html = get(route('home'))->getContent();

    expect($html)
        ->toContain('nativeAuthBootstrap')
        ->toContain('restoreUrl')
        ->toContain('realtimeSnapshotUrl');
});

it('hands the native auth restart payload to the home shell', function () {
    session([
        'auth.native_restart' => true,
        'auth.native_restore_token' => 'sample-restore-token',
    ]);

    $html = get(route('home'))->getContent();

    expect($html)
        ->toContain('nativeAuthRestart')
        ->toContain('sample-restore-token');
});

it('logs in with username and password', function () {
    $user = User::factory()->create(['password' => Hash::make('secret-pass')]);

    livewire(AuthButton::class)
        ->callAction('login', [
            'username' => $user->username,
            'password' => 'secret-pass',
            'code' => '',
        ])
        ->assertDispatched('auth-blink-reload');

    assertAuthenticatedAs($user);
});

it('rejects a wrong password', function () {
    $user = User::factory()->create(['password' => Hash::make('secret-pass')]);

    livewire(AuthButton::class)
        ->callAction('login', [
            'username' => $user->username,
            'password' => 'wrong',
            'code' => '',
        ])
        ->assertNotDispatched('auth-blink-reload');

    assertGuest();
});

it('dispatches the native auth cleanup hook on logout', function () {
    $user = User::factory()->create();
    Auth::login($user);

    livewire(AuthButton::class)
        ->callAction('logout')
        ->assertDispatched('native-auth-forget');

    assertGuest();
});

it('renders the auth modal loading overlay hooks', function () {
    $html = livewire(AuthButton::class)->html();

    expect($html)
        ->toContain('isAuthModalLoading')
        ->toContain('window.nativeNetwork?.status')
        ->toContain('closeAuthModalIfOpen()')
        ->toContain('beginAuthModalLoading()')
        ->toContain('openAuthModal()')
        ->toContain('x-on:x-modal-opened.window');
});

it('renders the offline pill with a native runtime guard', function () {
    $html = get(route('home'))->getContent();

    expect($html)
        ->toContain('isNativeRuntime: false')
        ->toContain('this.isNativeRuntime && window.nativeNetwork?.status');
});

it('renders a native-safe telegram auth launcher in mobile runtime', function () {
    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
        'app.custom.native_end_points.settings' => 'http://192.168.1.8:8787/api/settings',
        'app.custom.native_end_points.telegram_auth' => 'https://muttasiq.com/auth/telegram/native',
    ]);

    $html = view('livewire.auth.telegram-widget')->render();

    expect($html)
        ->toContain('browser?.auth')
        ->toContain('window.nativeNetwork?.status')
        ->toContain('http:\\/\\/192.168.1.8:8787\\/auth\\/telegram\\/native')
        ->toContain(arabic_text('يتطلب تسجيل الدخول عبر تيليجرام اتصالًا بالإنترنت.'));
});

it('renders the native telegram launcher page posting to the https callback', function () {
    config([
        'services.telegram.bot' => 'muttasiq_bot',
    ]);

    get(route('auth.telegram.native'))
        ->assertOk()
        ->assertSee('images/logo.svg', escape: false)
        ->assertSee('https://telegram.org/js/telegram-widget.js?22', escape: false)
        ->assertSee('auth\\/telegram\\/native\\/callback', escape: false)
        ->assertDontSee('muttasiq:', escape: false)
        ->assertSee('muttasiq_bot', escape: false);
});

it('renders the web telegram widget with a relative callback path', function () {
    $html = view('livewire.auth.telegram-widget')->render();

    expect($html)
        ->toContain('\\/auth\\/telegram\\/callback');

    expect(str_contains($html, 'https://muttasiq.dev.localhost/auth/telegram/callback'))->toBeFalse();
});
