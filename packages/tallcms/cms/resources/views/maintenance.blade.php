<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('tallcms::frontend.site_maintenance') }} - {{ $siteName ?? config('app.name', __('tallcms::frontend.our_website')) }}</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            line-height: 1.6;
        }
        .maintenance-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem;
        }
        .maintenance-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
        }
        .maintenance-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 2rem;
            background: #fbbf24;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s ease-in-out infinite;
        }
        .maintenance-title {
            font-size: 2rem;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        .maintenance-message {
            color: #6b7280;
            line-height: 1.6;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        .maintenance-footer {
            color: #9ca3af;
            font-size: 0.9rem;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-card">
            <div class="maintenance-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
            </div>

            <h1 class="maintenance-title">
                We'll Be Back Soon!
            </h1>

            <div class="maintenance-message">
                {{ $maintenanceMessage ?? "We're currently performing scheduled maintenance to improve your experience. Please check back soon!" }}
            </div>

            @if($siteName ?? config('app.name'))
                <div class="maintenance-footer">
                    {{ __('tallcms::frontend.thank_you_for_your_patience') }}<br>
                    <strong>{{ $siteName ?? config('app.name') }}</strong>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
