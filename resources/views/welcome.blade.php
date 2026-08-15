<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ setting('shop_name', 'Unique Solution') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --accent: #0d9488; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: linear-gradient(160deg, #f1f5f9, #e2e8f0);
            color: #0f172a;
            padding: 1.5rem;
        }
        .box {
            max-width: 420px;
            text-align: center;
            background: #fff;
            border-radius: 1rem;
            padding: 2.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
        }
        .mark {
            width: 52px;
            height: 52px;
            margin: 0 auto 1rem;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--accent), #14b8a6);
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 800;
        }
        h1 { margin: 0 0 0.35rem; font-size: 1.4rem; font-weight: 800; }
        p { margin: 0 0 1.25rem; color: #64748b; font-size: 0.95rem; }
        a {
            display: inline-block;
            background: var(--accent);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            padding: 0.7rem 1.25rem;
            border-radius: 0.55rem;
        }
        a:hover { background: #0f766e; }
        .note { margin-top: 1rem; font-size: 0.8rem; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="box">
        <div class="mark">US</div>
        <h1>{{ setting('shop_name', 'Unique Solution') }}</h1>
        <p>{{ setting('shop_tagline', 'आपकी अपनी दुकान') }}</p>
        <a href="{{ url('/admin') }}">Go to Admin</a>
        <p class="note">Root storefront routes can replace this page later.</p>
    </div>
</body>
</html>
