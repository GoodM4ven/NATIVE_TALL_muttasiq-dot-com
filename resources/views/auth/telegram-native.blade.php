<!DOCTYPE html>
<html
    lang="ar"
    dir="rtl"
>

<head>
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    />
    <title>{{ arabic_text('تسجيل الدخول عبر تيليجرام') }}</title>
    <style>
        :root {
            color-scheme: light;
            --telegram-shell-width: min(100%, 34rem);
            --telegram-page-bg:
                radial-gradient(circle at top, rgba(74, 112, 148, 0.24), transparent 34%),
                radial-gradient(circle at 16% 20%, rgba(15, 118, 110, 0.18), transparent 26%),
                linear-gradient(180deg, #f5f7fb 0%, #edf3f7 85%, #e7eef2 100%);
            --telegram-shell-bg: rgba(255, 255, 255, 0.82);
            --telegram-shell-border: rgba(255, 255, 255, 0.72);
            --telegram-text-strong: #123247;
            --telegram-text-muted: #486273;
            --telegram-accent: #2a82c9;
            --telegram-shadow: 0 2rem 5rem rgba(26, 58, 78, 0.18);
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100dvh;
            margin: 0;
            display: grid;
            place-items: center;
            overflow: hidden;
            padding: 1.25rem;
            background: var(--telegram-page-bg);
            color: var(--telegram-text-strong);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            opacity: 0;
            transition: opacity 0.35s ease;
        }

        body.tg-ready {
            opacity: 1;
        }

        body.tg-leaving {
            opacity: 0;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(0.25rem);
        }

        body::before {
            inset-inline-start: -8rem;
            inset-block-end: -7rem;
            width: 15rem;
            height: 15rem;
            background: linear-gradient(135deg, rgba(42, 130, 201, 0.22), rgba(13, 148, 136, 0.12));
        }

        body::after {
            inset-inline-end: -6rem;
            inset-block-start: -5rem;
            width: 13rem;
            height: 13rem;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.18), rgba(255, 255, 255, 0.08));
        }

        .telegram-auth-screen {
            width: var(--telegram-shell-width);
            position: relative;
            padding: 1.25rem;
        }

        .telegram-auth-shell {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--telegram-shell-border);
            border-radius: 1.75rem;
            padding: 1.35rem;
            background: var(--telegram-shell-bg);
            box-shadow: var(--telegram-shadow);
            backdrop-filter: blur(18px);
        }

        .telegram-auth-brand {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .telegram-auth-brand-mark {
            width: clamp(3.5rem, 11vw, 4.5rem);
            height: auto;
            display: block;
        }

        .telegram-auth-shell::before {
            opacity: 0.5;
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(135deg, rgba(255, 255, 255, 0.68), transparent 44%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.28), transparent 38%);
            pointer-events: none;
        }

        .telegram-auth-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.48rem;
            border-radius: 999px;
            padding: 0.46rem 0.78rem;
            background: rgba(42, 130, 201, 0.12);
            color: var(--telegram-accent);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .telegram-auth-badge-dot {
            width: 0.54rem;
            height: 0.54rem;
            border-radius: 999px;
            background: currentColor;
            box-shadow: 0 0 0 0.42rem rgba(42, 130, 201, 0.12);
        }

        .telegram-auth-copy {
            margin-top: 1rem;
            display: grid;
            gap: 0.8rem;
            text-align: center;
        }

        .telegram-auth-title {
            margin: 0;
            font-size: clamp(1.6rem, 4vw, 2.35rem);
            line-height: 1.08;
            letter-spacing: -0.04em;
            font-weight: 800;
        }

        .telegram-auth-status,
        .telegram-auth-fallback {
            margin: 0;
            font-size: clamp(0.95rem, 2.35vw, 1.04rem);
            font-weight: 500;
            line-height: 1.75;
            color: var(--telegram-text-muted);
        }

        .telegram-auth-widget-wrap {
            margin-top: 1.5rem;
            display: grid;
            justify-items: center;
            gap: 0.9rem;
        }

        .telegram-auth-widget {
            width: 100%;
            min-height: 3.65rem;
            display: grid;
            place-items: center;
            border-radius: 1.4rem;
            padding: 1rem;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.72), rgba(233, 240, 246, 0.92)),
                rgba(255, 255, 255, 0.65);
            border: 1px solid rgba(130, 161, 182, 0.22);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
        }

        .telegram-auth-widget iframe {
            max-width: 100%;
        }

        .telegram-auth-note {
            margin-top: 1.35rem;
            display: grid;
            gap: 0.72rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(111, 141, 160, 0.16);
            text-align: center;
        }

        @media (min-width: 40rem) {
            body {
                padding: 1.8rem;
            }

            .telegram-auth-screen {
                padding: 1.5rem;
            }

            .telegram-auth-shell {
                padding: 1.8rem;
            }
        }
    </style>
</head>

<body>
    <main class="telegram-auth-screen">
        <section class="telegram-auth-shell">
            <div class="telegram-auth-brand">
                <img
                    class="telegram-auth-brand-mark"
                    src="{{ asset('images/logo.svg') }}"
                    alt="{{ config('app.name') }}"
                    loading="eager"
                    decoding="async"
                />
            </div>

            <span class="telegram-auth-badge">
                <span
                    class="telegram-auth-badge-dot"
                    aria-hidden="true"
                ></span>
                Telegram
            </span>

            <div class="telegram-auth-copy">
                <h1 class="telegram-auth-title">{{ arabic_text('تسجيل دخول تيليجرام') }}</h1>
            </div>

            @if ($telegramBotName !== '')
                <div class="telegram-auth-widget-wrap">
                    <div
                        class="telegram-auth-widget"
                        id="telegram-login-widget"
                    ></div>
                </div>

                <div class="telegram-auth-note">
                    <p
                        class="telegram-auth-status"
                        id="telegram-login-status"
                    >
                        {{ arabic_text('أذكر الله ريثما يظهر زرّ تيليجرام ثم انقر عليه للبدء...') }}
                    </p>
                </div>

                <script>
                    window.onTelegramNativeAuth = (user) => {
                        // Navigate to the dedicated NATIVE callback route (a JS jump to a custom
                        // scheme is blocked by Chrome Custom Tabs without a user gesture). Only the
                        // Telegram fields go in the query — no extra params, or the server-side hash
                        // check fails. The server finishes by 302-redirecting to the app's deeplink.
                        const params = new URLSearchParams();
                        const callbackUrl = @js($callbackUrl);

                        Object.entries(user ?? {}).forEach(([key, value]) => {
                            if (value !== undefined && value !== null) {
                                params.append(key, value);
                            }
                        });

                        const queryString = params.toString();
                        const destination = queryString === '' ? callbackUrl : `${callbackUrl}?${queryString}`;

                        // Fade out before navigating so it cross-fades into the callback view.
                        document.body.classList.add('tg-leaving');
                        window.setTimeout(() => window.location.replace(destination), 280);
                    };

                    (() => {
                        const slot = document.getElementById('telegram-login-widget');

                        if (!(slot instanceof Element)) {
                            return;
                        }

                        const script = document.createElement('script');
                        script.async = true;
                        script.src = 'https://telegram.org/js/telegram-widget.js?22';
                        script.setAttribute('data-telegram-login', @js($telegramBotName));
                        script.setAttribute('data-size', 'large');
                        script.setAttribute('data-userpic', 'false');
                        script.setAttribute('data-onauth', 'onTelegramNativeAuth(user)');
                        script.setAttribute('data-request-access', 'write');
                        slot.appendChild(script);
                    })();
                </script>
            @else
                <div class="telegram-auth-note">
                    <p class="telegram-auth-fallback">
                        {{ arabic_text('تسجيل الدخول عبر تيليجرام غير متاح حاليًا على هذا الرابط.') }}</p>
                </div>
            @endif
        </section>
    </main>

    <script>
        requestAnimationFrame(() => document.body.classList.add('tg-ready'));
    </script>
</body>

</html>
