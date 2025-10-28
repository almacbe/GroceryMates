<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Status</title>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f3f4f6; margin: 0; }
        .wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .panel { background: #ffffff; padding: 2.5rem 3rem; border-radius: 0.75rem; box-shadow: 0 20px 35px -15px rgba(6, 95, 70, 0.3); text-align: center; border: 1px solid rgba(16, 185, 129, 0.2); }
        .panel h1 { margin: 0 0 0.5rem; font-size: 1.5rem; color: #047857; font-weight: 600; }
        .panel p { margin: 0; color: #4b5563; font-size: 0.95rem; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="panel">
        <h1>✅ Service is running</h1>
        <p>Laravel + Inertia deployment check page.</p>
    </div>
</div>
</body>
</html>
