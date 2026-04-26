<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}?v=2">
    <title>{{ $title ?? 'Something went wrong' }} | {{ config('app.name', 'Reloop') }}</title>
    <style>
        :root {
            --bg-1: #f2f6f3;
            --bg-2: #dfeee4;
            --panel: #ffffff;
            --text: #1a1c1c;
            --muted: #4b4f4d;
            --line: rgba(0, 106, 56, 0.22);
            --primary: #006a38;
            --primary-2: #0a8a4b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Manrope', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background:
                radial-gradient(700px 300px at 0% 0%, rgba(10, 138, 75, 0.18), transparent 60%),
                radial-gradient(500px 240px at 100% 100%, rgba(0, 106, 56, 0.16), transparent 60%),
                linear-gradient(135deg, var(--bg-1), var(--bg-2));
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .wrap {
            width: min(720px, 100%);
            background: var(--panel);
            border: 1px solid var(--line);
            box-shadow: 0 22px 56px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }

        .bar {
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--primary-2));
        }

        .content {
            padding: 34px 30px 30px;
        }

        .code {
            margin: 0;
            font-size: clamp(56px, 10vw, 92px);
            line-height: 0.95;
            font-weight: 800;
            letter-spacing: 0.03em;
            color: var(--primary);
            font-family: 'Space Grotesk', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .title {
            margin: 10px 0 10px;
            font-size: clamp(24px, 4vw, 34px);
            line-height: 1.15;
            font-weight: 700;
            font-family: 'Space Grotesk', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .desc {
            margin: 0;
            font-size: 15px;
            line-height: 1.6;
            color: var(--muted);
            max-width: 60ch;
        }

        .actions {
            margin-top: 26px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            appearance: none;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text);
            padding: 10px 16px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            transition: transform 0.12s ease, background-color 0.12s ease, color 0.12s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--primary-2));
            color: #fff;
        }

        .btn-secondary {
            border-color: var(--line);
            background: #f6fbf8;
        }

        .meta {
            margin-top: 14px;
            font-size: 12px;
            color: #69706d;
        }
    </style>
</head>
<body>
    <main class="wrap" role="main" aria-labelledby="error-title">
        <div class="bar"></div>

        <section class="content">
            <p class="code">{{ $code ?? 'Error' }}</p>
            <h1 id="error-title" class="title">{{ $heading ?? 'Unexpected Error' }}</h1>
            <p class="desc">{{ $message ?? 'Something unexpected happened while loading this page.' }}</p>

            <div class="actions">
                <button class="btn btn-secondary" type="button" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href='{{ url('/') }}'; }">
                    Go Back
                </button>
                <a class="btn btn-primary" href="{{ url('/') }}">Back To Home</a>
            </div>

            <p class="meta">{{ config('app.name', 'Reloop') }} • If the issue persists, try refreshing in a moment.</p>
        </section>
    </main>
</body>
</html>
