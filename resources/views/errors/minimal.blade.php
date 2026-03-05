<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        <style>
            :root {
                --brand: #077cb6;
                --brand-dark: #045781;
                --brand-soft: #e9faff;
                --text: #0f2a3b;
                --muted: #476176;
                --panel: #ffffff;
                --line: #bfd9eb;
            }

            * {
                box-sizing: border-box;
            }

            html,
            body {
                height: 100%;
                margin: 0;
            }

            body {
                font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
                color: var(--text);
                background:
                    radial-gradient(85rem 38rem at 95% -5%, #d7f0ff 0%, transparent 70%),
                    radial-gradient(70rem 42rem at -10% 100%, #c6e9ff 0%, transparent 70%),
                    linear-gradient(160deg, #f8fcff 0%, #eaf5fe 100%);
            }

            .page {
                min-height: 100%;
                display: grid;
                place-items: center;
                padding: 1.5rem;
            }

            .card {
                width: min(48rem, 100%);
                padding: clamp(1.5rem, 2.5vw, 2.5rem);
                border: 1px solid var(--line);
                border-radius: 1.5rem;
                background: linear-gradient(155deg, var(--panel) 0%, #f4fbff 100%);
                box-shadow:
                    0 1rem 3rem rgba(7, 67, 99, 0.12),
                    0 0.35rem 1rem rgba(7, 67, 99, 0.08);
            }

            .brand {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 5.5rem;
                height: 5.5rem;
                border-radius: 1.25rem;
                border: 1px solid #cbe4f4;
                background: linear-gradient(160deg, #ffffff 0%, var(--brand-soft) 100%);
                box-shadow: 0 0.5rem 1.5rem rgba(7, 124, 182, 0.15);
            }

            .brand svg {
                width: 3.6rem;
                height: auto;
            }

            .status {
                margin: 1.2rem 0 0;
                font-size: clamp(3rem, 12vw, 5.5rem);
                line-height: 1;
                letter-spacing: 0.08em;
                color: var(--brand);
                font-weight: 800;
            }

            .message {
                margin: 0.8rem 0 0;
                font-size: clamp(1rem, 3.8vw, 1.4rem);
                text-transform: uppercase;
                letter-spacing: 0.13em;
                font-weight: 700;
                color: var(--brand-dark);
            }

            .note {
                margin: 0.8rem 0 0;
                max-width: 34ch;
                font-size: 0.95rem;
                line-height: 1.45;
                color: var(--muted);
            }

            .actions {
                margin-top: 1.4rem;
            }

            .home-link {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.65rem 1.1rem;
                border-radius: 999px;
                border: 1px solid transparent;
                font-size: 0.84rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #ffffff;
                background: linear-gradient(135deg, var(--brand) 0%, #04689b 100%);
                text-decoration: none;
                transition: transform 0.16s ease, box-shadow 0.16s ease;
                box-shadow: 0 0.45rem 1rem rgba(4, 104, 155, 0.3);
            }

            .home-link:hover,
            .home-link:focus-visible {
                transform: translateY(-1px);
                box-shadow: 0 0.55rem 1rem rgba(4, 104, 155, 0.35);
                outline: none;
            }

            @media (max-width: 32rem) {
                .card {
                    text-align: center;
                }

                .brand {
                    margin: 0 auto;
                }

                .note {
                    margin-left: auto;
                    margin-right: auto;
                }
            }
        </style>
    </head>
    <body>
        <main class="page" role="main">
            <section class="card" aria-labelledby="error-status">
                <div class="brand" aria-hidden="true">
                    <x-application-logo/>
                </div>

                <h1 id="error-status" class="status">@yield('code')</h1>

                <p class="message">@yield('message')</p>

                <p class="note">{{ __('If this problem keeps happening, please try again later or contact support.') }}</p>

                <div class="actions">
                    <a class="home-link" href="{{ url('/') }}">{{ __('Back Home') }}</a>
                </div>
            </section>
        </main>
    </body>
</html>
