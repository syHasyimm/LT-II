<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') · {{ config('app.name') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <style>
            * { box-sizing: border-box; }
            body {
                margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px;
                font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
                background: linear-gradient(160deg, #8a1820 0%, #5a0d12 50%, #2e0508 100%); color: #fff;
            }
            .card { max-width: 440px; text-align: center; }
            .code { font-family: 'Playfair Display', Georgia, serif; font-size: 96px; font-weight: 700; line-height: 1; color: #e8cf8a; }
            h1 { font-family: 'Playfair Display', Georgia, serif; font-size: 30px; margin: 16px 0 8px; }
            p { color: #f3d9d6; line-height: 1.6; margin: 0 0 28px; }
            a { display: inline-block; padding: 14px 28px; border-radius: 14px; font-weight: 700; text-decoration: none; color: #3e070b;
                background: linear-gradient(135deg, #f3e2ae 0%, #c9a24a 50%, #9c7a2b 100%); }
        </style>
    </head>
    <body>
        <main class="card">
            <div class="code">@yield('code')</div>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>
            <a href="{{ url('/') }}">Kembali ke beranda</a>
        </main>
    </body>
</html>
