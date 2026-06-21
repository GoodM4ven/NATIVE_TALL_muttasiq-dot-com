@php
    $isPending = $user->two_factor_secret && !$user->two_factor_confirmed_at;
@endphp

<div class="space-y-4 text-sm text-gray-800 dark:text-gray-100">
    @if (!$user->two_factor_secret)
        <p>{{ arabic_text('المعامل الثاني لحماية حسابك باستخدام تطبيق مصادقة على هاتفك غالبا.') }}</p>

        <button
            class="bg-primary-600 rounded-lg px-4 py-2 text-white"
            type="button"
            wire:click="enableTwoFactor"
        >
            {{ arabic_text('تفعيل') }}
        </button>
    @elseif ($isPending)
        <p>{{ arabic_text('امسح رمز الـ QR بتطبيق المصادقة، ثم أدخل الرمز المعروض للتأكيد.') }}</p>

        <div class="flex justify-center">{!! $user->twoFactorQrCodeSvg() !!}</div>

        <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-800">
            <p class="mb-1 flex w-full justify-center font-semibold">{{ arabic_text('رموز الاسترداد الاحتياطية') }}</p>
            <div class="mt-2 grid grid-cols-2 gap-1 font-mono text-xs">
                @foreach ($user->recoveryCodes() as $code)
                    <span>{{ $code }}</span>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input
                class="w-32 rounded-lg border px-3 py-2 dark:bg-gray-900"
                type="text"
                inputmode="numeric"
                wire:model="twoFactorConfirmCode"
                placeholder="{{ arabic_text('الرمز') }}"
            />
            <button
                class="bg-primary-600 rounded-lg px-4 py-2 text-white"
                type="button"
                wire:click="confirmTwoFactor"
            >
                {{ arabic_text('تأكيد') }}
            </button>
        </div>
    @else
        <p class="font-semibold text-green-600 dark:text-green-400">
            {{ arabic_text('المصادقة الثنائية مفعّلة.') }}
        </p>

        <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-800">
            <p class="mb-1 font-semibold">{{ arabic_text('رموز الاسترداد:') }}</p>
            <div class="grid grid-cols-2 gap-1 font-mono text-xs">
                @foreach ($user->recoveryCodes() as $code)
                    <span>{{ $code }}</span>
                @endforeach
            </div>
        </div>

        <div class="flex gap-2">
            <button
                class="rounded-lg bg-gray-600 px-4 py-2 text-white"
                type="button"
                wire:click="regenerateRecoveryCodes"
            >
                {{ arabic_text('تجديد رموز الاسترداد') }}
            </button>
            <button
                class="rounded-lg bg-red-600 px-4 py-2 text-white"
                type="button"
                wire:click="disableTwoFactor"
            >
                {{ arabic_text('تعطيل') }}
            </button>
        </div>
    @endif
</div>
