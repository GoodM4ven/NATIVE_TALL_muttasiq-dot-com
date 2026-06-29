<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Events\UserRealtimeEvent;
use App\Jobs\SyncUserSettings;
use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Livewire\Attributes\On;
use Livewire\Component;

class AuthButton extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    private const MODAL_WIDTH = Width::Medium;

    /**
     * Max serialized size of a user's synced data bundle (5 MB). Generous
     * enough for full athkar content overrides (text + origin per thikr) while
     * still bounding abusive writes.
     */
    private const SYNCED_DATA_MAX_BYTES = 5_242_880;

    public string $twoFactorConfirmCode = '';

    /**
     * Reveals the 2FA code field in the login modal once a 2FA-enabled user has
     * submitted a valid username + password.
     */
    public bool $awaitingTwoFactor = false;

    /**
     * Plaintext credentials generated on first Telegram registration, surfaced
     * once in the account modal. Captured from the flash on mount so it survives
     * the later Livewire action roundtrip (flash data would otherwise be gone).
     *
     * @var array{username: string, password: string}|null
     */
    public ?array $freshCredentials = null;

    public function mount(): void
    {
        $credentials = session('auth.fresh_credentials');

        if (is_array($credentials)) {
            $this->freshCredentials = [
                'username' => (string) $credentials['username'],
                'password' => (string) $credentials['password'],
            ];
        }
    }

    // * =========
    // * Login
    // * =======

    public function loginAction(): Action
    {
        return Action::make('login')
            ->modal()
            ->modalWidth(self::MODAL_WIDTH)
            ->label(arabic_text('تسجيل الدخول'))
            ->modalHeading(arabic_text('تسجيل الدخول'))
            ->modalDescription(arabic_text('ادخل عبر تيليجرام، أو باسم المستخدم وكلمة المرور إن كانا لديك.'))
            ->modalSubmitActionLabel(arabic_text('دخول'))
            ->extraModalWindowAttributes(['class' => 'muttasiq-modal-window'])
            ->extraModalOverlayAttributes(['class' => 'muttasiq-modal-overlay'])
            ->mountUsing(function (): void {
                $this->awaitingTwoFactor = false;
            })
            ->schema([
                SchemaView::make('livewire.auth.telegram-widget')->extraAttributes(['class' => '[.fi-sc-component]&flex']),
                TextInput::make('username')
                    ->label(arabic_text('اسم المستخدم'))
                    ->required()
                    ->extraAttributes(['class' => 'ltr-enforced']),
                TextInput::make('password')
                    ->label(arabic_text('كلمة المرور'))
                    ->password()
                    ->required()
                    ->extraAttributes(['class' => 'ltr-enforced'])
                    ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        $user = User::query()->where('username', (string) $get('username'))->first();

                        if ($user === null || ! Hash::check((string) $value, $user->password)) {
                            $fail(arabic_text('بيانات الدخول غير صحيحة'));
                        }
                    }),
                Text::make(new HtmlString('<hr class="border-gray-200 dark:border-gray-700">'))
                    ->visible(fn (): bool => $this->awaitingTwoFactor),
                TextInput::make('code')
                    ->label(arabic_text('رمز المصادقة الثنائية'))
                    ->numeric()
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->visible(fn (): bool => $this->awaitingTwoFactor)
                    ->required(fn (): bool => $this->awaitingTwoFactor)
                    ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        if (! $this->awaitingTwoFactor) {
                            return;
                        }

                        $user = User::query()->where('username', (string) $get('username'))->first();

                        if ($user === null) {
                            return;
                        }

                        if ($user->two_factor_secret === null) {
                            if (! $this->verifyNativeTwoFactorCode($user, (string) $value)) {
                                $fail(arabic_text('رمز المصادقة الثنائية غير صحيح'));
                            }

                            return;
                        }

                        if (! app(TwoFactorAuthenticationProvider::class)->verify(decrypt($user->two_factor_secret), (string) $value)) {
                            $fail(arabic_text('رمز المصادقة الثنائية غير صحيح'));
                        }
                    }),
            ])
            ->action(function (array $data, Action $action): void {
                $user = User::query()->where('username', (string) $data['username'])->first();

                if ($user === null) {
                    return;
                }

                // 2FA-enabled users: reveal the code field on the first valid pass,
                // keep the modal open, and let the code rule validate the resubmit.
                if ($user->two_factor_confirmed_at !== null && ! $this->awaitingTwoFactor) {
                    $this->awaitingTwoFactor = true;

                    $action->halt();

                    return;
                }

                // remember: false — web sessions must re-authenticate each time;
                // a long-lived remember cookie is what let a browser silently sign
                // in as the native account on the shared-DB dev host.
                Auth::login($user, remember: false);

                notify('heroicon-o-check-circle', arabic_text('تم تسجيل الدخول بنجاح'));

                $this->dispatch('auth-blink-reload');

                // Keep the modal in place under the blinker until the reload,
                // instead of letting it snap-close before the fade completes.
                $action->halt();
            });
    }

    private function freshCredentialsHtml(): HtmlString
    {
        if ($this->freshCredentials === null) {
            return new HtmlString('');
        }

        return new HtmlString(
            '<div class="rounded-lg bg-primary-50 dark:bg-primary-950 p-3 text-sm"'
                .' x-data=\'{ password: '.json_encode($this->freshCredentials['password']).' }\'>'
                .'<span class="text-center">'.e(arabic_text('تم إنشاء حسابك ولله الحمد...')).'</span><br><br>'
                .'<strong>'.e(arabic_text('كلمة المرور')).':</strong><br>'
                .'<span class="font-bold text-left flex justify-start hover:cursor-pointer" dir="ltr" x-on:click="$clipboard(password); $tippy(`تم النسخ`, `top`)" x-text="password"></span>'
                .'</div>'
        );
    }

    // * =========
    // * Account
    // * =======

    public function accountAction(): Action
    {
        return Action::make('account')
            ->modal()
            ->modalWidth(self::MODAL_WIDTH)
            ->label(arabic_text('الحساب'))
            ->modalHeading(arabic_text('حسابي'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(arabic_text('إغلاق'))
            ->extraModalWindowAttributes(['class' => 'muttasiq-modal-window'])
            ->extraModalOverlayAttributes(['class' => 'muttasiq-modal-overlay'])
            ->extraModalFooterActions([
                $this->logoutAction(),
                $this->deleteAccountAction(),
            ])
            ->schema([
                Tabs::make('Tabs')->tabs([
                    Tab::make('account')
                        ->key('account')
                        ->label(arabic_text('الحساب'))
                        ->icon('tabler.id-badge-2')
                        ->schema([
                            TextInput::make('username')
                                ->label(arabic_text('اسم المستخدم'))
                                ->disabled()
                                ->dehydrated(false)
                                ->extraAttributes(['class' => 'ltr-enforced'])
                                ->default(function (): string {
                                    $user = Auth::user();

                                    return $user instanceof User ? $user->username : '';
                                })
                                ->suffixAction(
                                    Action::make('copyUsername')
                                        ->icon('heroicon-m-clipboard')
                                        ->tooltip('نسخ اسم المستخدم')
                                        ->alpineClickHandler(
                                            "navigator.clipboard.writeText(\$el.closest('.fi-input-wrp').querySelector('input').value).then(() => \$wire.call('notifyCopied'))"
                                        )
                                ),
                            Text::make(fn (): HtmlString => $this->freshCredentialsHtml())
                                ->visible(fn (): bool => $this->freshCredentials !== null)
                                ->extraAttributes(['class' => 'flex justify-center']),
                            SchemaActions::make([
                                $this->changePasswordAction(),
                                $this->twoFactorAction(),
                            ])->alignCenter(),
                        ]),
                    Tab::make('data')
                        ->key('data')
                        ->label(arabic_text('البيانات'))
                        ->icon('tabler.packages')
                        ->schema([
                            Text::make(arabic_text('نقل البيانات بين الجهاز والحساب'))
                                ->color('gray')
                                ->extraAttributes(['class' => 'flex justify-center w-full']),
                            SchemaActions::make([
                                $this->overrideGuestWithAccountAction(),
                                $this->overrideAccountWithGuestAction(),
                            ])->alignCenter(),
                        ]),
                    Tab::make('devices')
                        ->key('devices')
                        ->label(arabic_text('أجهزتي'))
                        ->icon('heroicon-o-device-phone-mobile')
                        ->schema([
                            Text::make(fn (): HtmlString => $this->devicesHtml())
                                ->extraAttributes(['class' => 'w-full']),
                        ]),
                ]),
            ]);
    }

    public function overrideGuestWithAccountAction(): Action
    {
        return Action::make('overrideGuestWithAccount')
            ->label(arabic_text('اجعل بيانات الجهاز كحسابي'))
            ->icon('heroicon-o-arrow-down-on-square')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading(arabic_text('استبدال بيانات الجهاز'))
            ->modalDescription(arabic_text('سيتم استبدال بيانات الزائر المحلية في هذا الجهاز (أو المتصفح) ببيانات حسابك على المخدم. لا يمكن التراجع عن هذا...'))
            ->modalSubmitActionLabel(arabic_text('نعم، استبدلها'))
            ->action(function (Action $action): void {
                // Stashed (not sent) so it surfaces on the post-reload page load
                // instead of being consumed live during the blinker fade.
                session()->put('data-branch-override-notice', arabic_text('تم استبدال بيانات الجهاز ببيانات حسابك'));

                $this->dispatch('override-data-branch', fromBranch: 'user', toBranch: 'guest');
                $this->dispatch('auth-blink-reload');

                $action->halt();
            });
    }

    public function overrideAccountWithGuestAction(): Action
    {
        return Action::make('overrideAccountWithGuest')
            ->label(arabic_text('اجعل بيانات حسابي كالجهاز'))
            ->icon('heroicon-o-arrow-up-on-square')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(arabic_text('استبدال بيانات الحساب'))
            ->modalDescription(arabic_text('سيتم استبدال بيانات حسابك على المخدم ببيانات الزائر المحلية في هذا الجهاز (أو المتصفح). لا يمكن التراجع...'))
            ->modalSubmitActionLabel(arabic_text('نعم، استبدلها'))
            ->action(function (Action $action): void {
                // Stashed (not sent) so it surfaces on the post-reload page load
                // instead of being consumed live during the blinker fade.
                session()->put('data-branch-override-notice', arabic_text('تم استبدال بيانات حسابك ببيانات الجهاز'));

                // No auth-blink-reload here: the override copies guest→user in JS,
                // then pushUserData() pushes to the server and dispatches the reload
                // itself, so the native sync isn't cut off by an early reload.
                $this->dispatch('override-data-branch', fromBranch: 'guest', toBranch: 'user');

                $action->halt();
            });
    }

    /**
     * Persist the logged-in user's local data bundle to the server so it
     * follows the account across devices. Driven (debounced) from the JS
     * data-branch chokepoint whenever user-branch storage changes.
     *
     * @param  array<string, mixed>  $data
     */
    #[On('push-user-data')]
    public function pushUserData(array $data, bool $reloadAfter = false, ?string $socketId = null): void
    {
        $user = Auth::user();
        $normalizedSocketId = normalize_socket_id($socketId ?? request()->headers->get('X-Socket-ID'));

        if (! $user instanceof User) {
            return;
        }

        $bundle = array_filter($data, static fn ($value): bool => is_string($value));

        if (strlen((string) json_encode($bundle)) <= self::SYNCED_DATA_MAX_BYTES) {
            $syncedAt = now();

            $user->forceFill([
                'synced_data' => $bundle,
                'synced_data_updated_at' => $syncedAt,
            ])->save();

            $realtimeType = $reloadAfter ? 'dataOverridden' : 'dataSynced';

            // On native the local DB is just a mirror. Queue the server push so it
            // survives offline stretches and coalesces rapid changes (the job is
            // unique-until-processing and reads the latest bundle at run time).
            if (is_platform('native')) {
                SyncUserSettings::dispatch($user->getKey(), $normalizedSocketId, $realtimeType);
            } elseif ($user->telegram_id !== null) {
                $this->broadcastRealtimeEvent(new UserRealtimeEvent(
                    (int) $user->telegram_id,
                    $realtimeType,
                    socketId: $normalizedSocketId,
                ));
            }
        }

        // The "make account = device" override defers its blinker reload to here,
        // so the server push above completes before the page reloads (otherwise
        // the reload would race and cut off the native sync).
        if ($reloadAfter) {
            $this->dispatch('auth-blink-reload');
        }
    }

    /**
     * Push a local account change to the authoritative server (native only).
     * The server account is keyed by the device's mirrored sync token.
     *
     * @param  array<string, mixed>  $payload
     */
    private function pushNativeSync(string $routeName, array $payload): bool
    {
        if (! is_platform('native')) {
            return false;
        }

        $user = Auth::user();
        $serverBase = native_server_base();
        $socketId = normalize_socket_id(request()->headers->get('X-Socket-ID'));

        if (! $user instanceof User || blank($user->native_api_token) || $serverBase === null) {
            return false;
        }

        try {
            $response = Http::asJson()->acceptJson()
                ->connectTimeout(3)->timeout(4)
                ->withToken((string) $user->native_api_token)
                ->withHeaders(array_filter([
                    'X-Socket-ID' => $socketId,
                ]))
                ->post($serverBase.'/api/'.str_replace('.', '/', $routeName), $payload);

            return $response->successful() && $response->json('ok') === true;
        } catch (\Throwable $exception) {
            Log::warning('Native account sync failed.', [
                'route' => $routeName,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function notifyCopied(): void
    {
        Notification::make()
            ->icon('heroicon-m-clipboard')
            ->title('تم النسخ')
            ->send();
    }

    public function changePasswordAction(): Action
    {
        return Action::make('changePassword')
            ->label(arabic_text('تغيير كلمة المرور'))
            ->icon('heroicon-o-key')
            ->modalWidth(self::MODAL_WIDTH)
            ->modalSubmitActionLabel(arabic_text('حفظ'))
            ->schema([
                TextInput::make('password')
                    ->label(arabic_text('كلمة المرور الجديدة'))
                    ->password()
                    ->required()
                    ->rule(Password::default())
                    ->rule('confirmed')
                    ->extraAttributes(['class' => 'ltr-enforced']),
                TextInput::make('password_confirmation')
                    ->label(arabic_text('تأكيد كلمة المرور'))
                    ->password()
                    ->required()
                    ->extraAttributes(['class' => 'ltr-enforced']),
            ])
            ->action(function (array $data): void {
                $user = $this->currentUser();

                $user->forceFill([
                    'password' => Hash::make((string) $data['password']),
                ])->save();

                // Native: mirror the change to the authoritative server so web +
                // other devices use the new password.
                $synced = $this->pushNativeSync('native-sync.password', [
                    'password' => (string) $data['password'],
                ]);

                if (! is_platform('native') && $user->telegram_id !== null) {
                    $user->tokens()->delete();
                    $this->broadcastRealtimeEvent(new UserRealtimeEvent(
                        (int) $user->telegram_id,
                        'passwordChanged',
                        socketId: normalize_socket_id(request()->headers->get('X-Socket-ID')),
                    ));
                }

                notify(
                    'mdi.content-save-check',
                    (is_platform('native') && ! $synced)
                        ? arabic_text('تم تحديث كلمة المرور على هذا الجهاز فقط، تعذرت المزامنة مع الخادم')
                        : arabic_text('تم تحديث كلمة المرور'),
                );
            });
    }

    public function twoFactorAction(): Action
    {
        return Action::make('twoFactor')
            ->label(arabic_text('المصادقة الثنائية'))
            ->icon('heroicon-o-shield-check')
            ->modalWidth(self::MODAL_WIDTH)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(arabic_text('إغلاق'))
            ->modalContent(fn (): View => view('livewire.auth.two-factor', [
                'user' => $this->currentUser(),
            ]));
    }

    public function enableTwoFactor(): void
    {
        app(EnableTwoFactorAuthentication::class)($this->currentUser());
    }

    public function confirmTwoFactor(): void
    {
        app(ConfirmTwoFactorAuthentication::class)($this->currentUser(), trim($this->twoFactorConfirmCode));

        $this->twoFactorConfirmCode = '';

        notify('heroicon-o-shield-check', arabic_text('تم تفعيل المصادقة الثنائية'));
    }

    public function disableTwoFactor(): void
    {
        app(DisableTwoFactorAuthentication::class)($this->currentUser());

        notify('heroicon-o-shield-exclamation', arabic_text('تم تعطيل المصادقة الثنائية'));
    }

    public function regenerateRecoveryCodes(): void
    {
        app(GenerateNewRecoveryCodes::class)($this->currentUser());
    }

    public function revokeDevice(int $tokenId): void
    {
        $user = $this->currentUser();

        if (is_platform('native')) {
            $didRevoke = $this->pushNativeSync('native-sync.devices.revoke', [
                'token_id' => $tokenId,
            ]);
        } else {
            $didRevoke = $user->tokens()->whereKey($tokenId)->delete() > 0;

            if ($didRevoke && $user->telegram_id !== null) {
                $this->broadcastRealtimeEvent(new UserRealtimeEvent(
                    (int) $user->telegram_id,
                    'deviceLoggedOut',
                    $tokenId,
                    normalize_socket_id(request()->headers->get('X-Socket-ID')),
                ));
            }
        }

        notify(
            $didRevoke ? 'heroicon-o-device-phone-mobile' : 'heroicon-o-exclamation-circle',
            $didRevoke
                ? arabic_text('تم تسجيل خروج الجهاز')
            : arabic_text('تعذر تسجيل خروج الجهاز، حاول مرة أخرى'),
        );
    }

    #[On('native-devices-refresh')]
    public function refreshDevices(): void
    {
        // Empty on purpose. Livewire re-renders the component after the event,
        // which refreshes the device list in the tab.
    }

    #[On('realtime-other-device-notice')]
    public function realtimeOtherDeviceNotice(): void
    {
        notify('heroicon-o-arrow-path', arabic_text('تمّت مزامنة تغييرات من جهازٍ آخر'));
    }

    public function logoutAction(): Action
    {
        return Action::make('logout')
            ->label(arabic_text('تسجيل الخروج'))
            ->icon('heroicon-o-arrow-left-start-on-rectangle')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(arabic_text('تسجيل الخروج'))
            ->modalDescription(arabic_text('هل تريد تسجيل الخروج من حسابك؟'))
            ->modalSubmitActionLabel(arabic_text('نعم، سجّل خروجي'))
            ->action(function (Action $action): void {
                // Native: best-effort revoke this device's server token (don't block
                // logout on server reachability).
                $this->pushNativeSync('native-sync.logout', []);

                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();

                $this->dispatch('native-auth-forget');
                notify('heroicon-o-arrow-left-start-on-rectangle', arabic_text('تم تسجيل الخروج'));

                $this->dispatch('auth-blink-reload');

                $action->halt();
            });
    }

    public function deleteAccountAction(): Action
    {
        return Action::make('deleteAccount')
            ->label(arabic_text('حذف الحساب'))
            ->icon('bootstrap.x-circle-fill')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(arabic_text('حذف الحساب نهائيا'))
            ->modalDescription(arabic_text('سيتم حذف حسابك وبياناته نهائيا. لا يمكن التراجع عن هذا...'))
            ->modalSubmitActionLabel(arabic_text('نعم، احذف حسابي'))
            ->action(function (Action $action): void {
                $user = $this->currentUser();

                // Native: the authoritative account lives on the server. Delete it
                // there first (while we still hold the token); abort if we can't,
                // so we never leave a local-deleted / server-alive divergence.
                if (is_platform('native') && ! $this->pushNativeSync('native-sync.delete', [])) {
                    notify('heroicon-o-exclamation-circle', arabic_text('تعذر حذف الحساب من الخادم، حاول مرة أخرى'));

                    return;
                }

                if (! is_platform('native') && $user->telegram_id !== null) {
                    $this->broadcastRealtimeEvent(new UserRealtimeEvent((int) $user->telegram_id, 'accountDeleted'));
                    $user->tokens()->delete();
                }

                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();

                $this->dispatch('native-auth-forget');
                $user->delete();

                notify('heroicon-o-trash', arabic_text('تم حذف حسابك'));

                $this->dispatch('auth-blink-reload');

                $action->halt();
            });
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    private function broadcastRealtimeEvent(UserRealtimeEvent $event): void
    {
        try {
            broadcast($event);
        } catch (\Throwable) {
            // ponytail: realtime is best-effort; auth/data changes must not fail when Reverb is down.
        }
    }

    private function verifyNativeTwoFactorCode(User $user, string $code): bool
    {
        if (! is_platform('native') || blank($user->native_api_token)) {
            return false;
        }

        $serverBase = native_server_base();

        if ($serverBase === null) {
            return false;
        }

        try {
            $response = Http::asJson()->acceptJson()
                ->connectTimeout(3)->timeout(4)
                ->withToken((string) $user->native_api_token)
                ->post($serverBase.'/api/native-auth/two-factor', ['code' => $code]);

            return $response->successful() && $response->json('ok') === true;
        } catch (\Throwable $exception) {
            Log::warning('Native two-factor verification failed.', [
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function currentNativeTokenId(): ?int
    {
        $token = (string) $this->currentUser()->native_api_token;

        if (preg_match('/^(?<id>\d+)\|/', $token, $matches) !== 1) {
            return null;
        }

        return (int) $matches['id'];
    }

    private function devicesHtml(): HtmlString
    {
        $devices = $this->nativeDevices();

        if ($devices === null) {
            return new HtmlString(
                '<div class="rounded-xl border border-amber-300/70 bg-amber-50/90 px-3 py-2 text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-950/70 dark:text-amber-100">'
                    .e(arabic_text('تعذر تحميل الأجهزة الآن. تحقق من الاتصال ثم حاول مرة أخرى.'))
                    .'</div>',
            );
        }

        if ($devices === []) {
            return new HtmlString(
                '<div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-center text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200">'
                    .e(arabic_text('لا توجد أجهزة أخرى مسجلة.'))
                    .'</div>',
            );
        }

        $items = collect($devices)
            ->map(function (array $device): string {
                $id = (int) ($device['id'] ?? 0);
                $name = e((string) ($device['name'] ?? arabic_text('جهاز')));
                $caption = e($this->deviceCaption($device));
                $revokeLabel = e(arabic_text('تسجيل خروجه'));

                return <<<HTML
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white/80 px-3 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-950/70">
                        <div class="min-w-0">
                            <div class="truncate font-semibold text-gray-950 dark:text-gray-50">{$name}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{$caption}</div>
                        </div>
                        <button type="button" wire:click="revokeDevice({$id})" wire:loading.attr="disabled" class="shrink-0 rounded-full bg-danger-600 px-3 py-1 text-xs font-bold text-white transition hover:bg-danger-700 disabled:opacity-50">
                            {$revokeLabel}
                        </button>
                    </div>
                    HTML;
            })
            ->implode('');

        return new HtmlString('<div class="space-y-2">'.$items.'</div>');
    }

    /**
     * @return array<int, array{id:int|string|null,name:string|null,last_used_at:string|null,created_at:string|null}>|null
     */
    private function nativeDevices(): ?array
    {
        $user = $this->currentUser();

        if (is_platform('native')) {
            $serverBase = native_server_base();

            if (blank($user->native_api_token) || $serverBase === null) {
                return null;
            }

            try {
                $response = Http::acceptJson()
                    ->connectTimeout(3)->timeout(4)
                    ->withToken((string) $user->native_api_token)
                    ->get($serverBase.'/api/native-sync/devices');

                return $response->successful() && $response->json('ok') === true
                    ? (array) $response->json('devices', [])
                    : null;
            } catch (\Throwable $exception) {
                Log::warning('Native devices list fetch failed.', [
                    'message' => $exception->getMessage(),
                ]);

                return null;
            }
        }

        $currentTokenId = $this->currentNativeTokenId();

        return $user->tokens()
            ->latest('id')
            ->get()
            ->reject(fn ($token): bool => $currentTokenId !== null && (int) $token->getKey() === $currentTokenId)
            ->values()
            ->map(fn ($token): array => [
                'id' => $token->getKey(),
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->toISOString(),
                'created_at' => $token->created_at?->toISOString(),
            ])
            ->all();
    }

    /**
     * @param  array{id?:int|string|null,name?:string|null,last_used_at?:string|null,created_at?:string|null}  $device
     */
    private function deviceCaption(array $device): string
    {
        $lastUsedAt = (string) ($device['last_used_at'] ?? '');

        if ($lastUsedAt !== '') {
            return arabic_text('آخر استخدام: ').Str::of($lastUsedAt)->replace('T', ' ')->before('.');
        }

        $createdAt = (string) ($device['created_at'] ?? '');

        if ($createdAt !== '') {
            return arabic_text('أضيف: ').Str::of($createdAt)->replace('T', ' ')->before('.');
        }

        return arabic_text('جهاز مسجل');
    }

    public function render(): View
    {
        return view('livewire.auth-button');
    }
}
