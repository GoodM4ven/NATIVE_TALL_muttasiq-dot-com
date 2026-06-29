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

        .telegram-auth-copy {
            margin-top: 0.5rem;
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

        .telegram-auth-actions {
            margin-top: 1.6rem;
            display: grid;
            justify-items: center;
        }

        .telegram-auth-return {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            border: 0;
            border-radius: 1.4rem;
            padding: 1rem 1.5rem;
            background: linear-gradient(180deg, #2f8fd6 0%, var(--telegram-accent) 100%);
            color: #fff;
            font-size: clamp(1rem, 2.6vw, 1.1rem);
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 1rem 2rem rgba(42, 130, 201, 0.3);
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

            @if ($handoffUrl !== null)
                <div class="telegram-auth-copy">
                    <h1 class="telegram-auth-title">{{ arabic_text('تم تسجيل الدخول بنجاح') }}</h1>
                    <p class="telegram-auth-status">
                        {{ arabic_text('يمكنك الآن إغلاق هذه النافذة والعودة إلى التطبيق.') }}
                    </p>
                </div>

                <div class="telegram-auth-actions">
                    <a
                        class="telegram-auth-return"
                        href="{{ $handoffUrl }}"
                    >{{ arabic_text('العودة إلى التطبيق') }}</a>
                </div>
            @else
                <div class="telegram-auth-copy">
                    <h1 class="telegram-auth-title">{{ arabic_text('تعذّر تسجيل الدخول') }}</h1>
                    <p class="telegram-auth-fallback">
                        {{ arabic_text('تعذّر إتمام تسجيل الدخول عبر تيليجرام. أغلق هذه النافذة وحاول مرة أخرى من التطبيق.') }}
                    </p>
                </div>
            @endif
        </section>
    </main>
</body>

</html>
