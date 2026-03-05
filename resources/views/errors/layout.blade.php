<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        <style>
            :root {
                --brand: #077cb6;
                --brand-soft: #e9faff;
                --text: #1b3849;
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
                    radial-gradient(90rem 38rem at 95% -5%, #d7f0ff 0%, transparent 70%),
                    radial-gradient(75rem 40rem at -10% 100%, #c6e9ff 0%, transparent 70%),
                    linear-gradient(160deg, #f8fcff 0%, #eaf5fe 100%);
            }

            .page {
                min-height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 1.5rem;
            }

            .card {
                width: min(32rem, 100%);
                border: 1px solid var(--line);
                border-radius: 1.5rem;
                padding: 2rem;
                text-align: center;
                background: linear-gradient(155deg, #ffffff 0%, #f4fbff 100%);
                box-shadow:
                    0 1rem 3rem rgba(7, 67, 99, 0.12),
                    0 0.35rem 1rem rgba(7, 67, 99, 0.08);
            }

            .brand {
                width: 5rem;
                height: 5rem;
                margin: 0 auto 1rem;
                border-radius: 1.25rem;
                border: 1px solid #cbe4f4;
                background: linear-gradient(160deg, #ffffff 0%, var(--brand-soft) 100%);
                display: grid;
                place-items: center;
                box-shadow: 0 0.5rem 1.5rem rgba(7, 124, 182, 0.15);
            }

            .brand svg {
                width: 3.2rem;
                height: auto;
            }

            .message {
                margin: 0;
                font-size: clamp(1rem, 4vw, 1.35rem);
                text-transform: uppercase;
                letter-spacing: 0.1em;
                color: #045781;
                font-weight: 700;
            }
        </style>
    </head>
    <body>
        <main class="page" role="main">
            <section class="card">
                <div class="brand" aria-hidden="true">
                    <x-application-logo fill="#077cb6" />
                </div>

                <p class="message">@yield('message')</p>
            </section>
        </main>
    </body>
</html>
