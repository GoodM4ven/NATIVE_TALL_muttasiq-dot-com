<?php

declare(strict_types=1);

use App\Livewire\AuthButton;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

use function Pest\Laravel\get;
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

    $this->assertAuthenticatedAs($user);
});

it('reuses the existing account on a returning telegram login', function () {
    $existing = User::factory()->create(['telegram_id' => 999, 'telegram_username' => 'old']);

    fakeTelegramUser(id: 999, nickname: 'updated');

    get(route('auth.telegram.callback'));

    expect(User::query()->count())->toBe(1)
        ->and($existing->fresh()->telegram_username)->toBe('updated');

    $this->assertAuthenticatedAs($existing->fresh());
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

    $this->assertAuthenticatedAs($user);
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

    $this->assertGuest();
});

it('renders the auth modal loading overlay hooks', function () {
    $html = livewire(AuthButton::class)->html();

    expect($html)
        ->toContain('isAuthModalLoading')
        ->toContain('beginAuthModalLoading()')
        ->toContain('openAuthModal()')
        ->toContain('x-on:x-modal-opened.window');
});
