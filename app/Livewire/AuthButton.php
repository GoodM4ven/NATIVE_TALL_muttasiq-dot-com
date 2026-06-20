<?php

declare(strict_types=1);

namespace App\Livewire;

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
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Livewire\Component;

class AuthButton extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    private const MODAL_WIDTH = Width::Medium;

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
                    ->rule(fn(Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        $user = User::query()->where('username', (string) $get('username'))->first();

                        if ($user === null || ! Hash::check((string) $value, $user->password)) {
                            $fail(arabic_text('بيانات الدخول غير صحيحة'));
                        }
                    }),
                Text::make(new HtmlString('<hr class="border-gray-200 dark:border-gray-700">'))
                    ->visible(fn(): bool => $this->awaitingTwoFactor),
                TextInput::make('code')
                    ->label(arabic_text('رمز المصادقة الثنائية'))
                    ->numeric()
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->visible(fn(): bool => $this->awaitingTwoFactor)
                    ->required(fn(): bool => $this->awaitingTwoFactor)
                    ->rule(fn(Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        if (! $this->awaitingTwoFactor) {
                            return;
                        }

                        $user = User::query()->where('username', (string) $get('username'))->first();

                        if ($user === null || $user->two_factor_secret === null) {
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
                }

                Auth::login($user, remember: true);

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
                . ' x-data=\'{ password: ' . json_encode($this->freshCredentials['password']) . ' }\'>'
                . '<span class="text-center">' . e(arabic_text('تم إنشاء حسابك ولله الحمد...')) . '</span><br><br>'
                . '<strong>' . e(arabic_text('كلمة المرور')) . ':</strong><br>'
                . '<span class="font-bold text-left flex justify-start hover:cursor-pointer" dir="ltr" x-on:click="$clipboard(password); $tippy(`تم النسخ`, `top`)" x-text="password"></span>'
                . '</div>'
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
                            Text::make(fn(): HtmlString => $this->freshCredentialsHtml())
                                ->visible(fn(): bool => $this->freshCredentials !== null)
                                ->extraAttributes(['class' => 'flex justify-center']),
                            SchemaActions::make([
                                $this->changePasswordAction(),
                                $this->twoFactorAction(),
                            ])->alignCenter(),
                        ]),
                    Tab::make('data')
                        ->label(arabic_text('البيانات'))
                        ->icon('heroicon-o-circle-stack')
                        ->schema([
                            Text::make(arabic_text('قريبا بإذن الله...'))->color('gray'),
                        ]),
                ]),
            ]);
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
                $this->currentUser()->forceFill([
                    'password' => Hash::make((string) $data['password']),
                ])->save();

                notify('mdi.content-save-check', arabic_text('تم تحديث كلمة المرور'));
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
            ->modalContent(fn(): View => view('livewire.auth.two-factor', [
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
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();

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
            ->modalDescription(arabic_text('سيتم حذف حسابك وبياناته نهائيا. لا يمكن التراجع عن هذا.'))
            ->modalSubmitActionLabel(arabic_text('نعم، احذف حسابي'))
            ->action(function (Action $action): void {
                $user = $this->currentUser();

                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();

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

    public function render(): View
    {
        return view('livewire.auth-button');
    }
}
