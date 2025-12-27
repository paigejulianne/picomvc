<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'PicoMVC Blade' }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        header { background: #8e44ad; color: white; padding: 1rem 0; }
        header h1 { font-size: 1.5rem; }
        nav { margin-top: 0.5rem; }
        nav a { color: #ecf0f1; text-decoration: none; margin-right: 1rem; }
        nav a:hover { text-decoration: underline; }
        main { padding: 2rem 0; min-height: calc(100vh - 200px); }
        footer { background: #9b59b6; color: #ecf0f1; padding: 1rem 0; text-align: center; }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #8e44ad; color: white; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #7d3c98; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 1.5rem; margin: 1rem 0; }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; background: #8e44ad; color: white; border-radius: 4px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>PicoMVC <span class="badge">Blade</span></h1>
            <nav>
                @php $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') @endphp
                <a href="{{ $baseUrl }}/">Home</a>
                <a href="{{ $baseUrl }}/users">Users</a>
                <a href="{{ $baseUrl }}/about">About</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            @yield('content')
        </div>
    </main>

    <footer>
        <div class="container">
            <p>PicoMVC Blade Example &copy; {{ date('Y') }}</p>
        </div>
    </footer>
</body>
</html>
