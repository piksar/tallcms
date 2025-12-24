<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #1f2937;
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        .field {
            margin-bottom: 15px;
            padding: 12px;
            background-color: #f9fafb;
            border-radius: 6px;
        }
        .field-label {
            font-weight: 600;
            color: #374151;
            display: block;
            margin-bottom: 4px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .field-value {
            color: #1f2937;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .meta {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #6b7280;
        }
        .meta p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>New Contact Form Submission</h2>

        @foreach($submission->form_data as $field)
            <div class="field">
                <span class="field-label">{{ $field['label'] }}</span>
                <span class="field-value">{{ e($field['value']) }}</span>
            </div>
        @endforeach

        <div class="meta">
            <p><strong>Submitted from:</strong> {{ $submission->page_url }}</p>
            <p><strong>Date:</strong> {{ $submission->created_at->format('M j, Y \a\t g:i A') }}</p>
            <p><strong>IP Address:</strong> {{ request()->ip() }}</p>
        </div>
    </div>
</body>
</html>
