<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank you for contacting us</title>
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
        }
        .message {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 6px 6px 0;
        }
        .summary {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .summary h3 {
            color: #374151;
            font-size: 16px;
            margin-bottom: 15px;
        }
        .field {
            margin-bottom: 12px;
            padding: 10px;
            background-color: #f9fafb;
            border-radius: 6px;
        }
        .field-label {
            font-weight: 600;
            color: #374151;
            display: block;
            margin-bottom: 3px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .field-value {
            color: #1f2937;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Thank you for reaching out!</h2>

        <div class="message">
            <p>We've received your message and will get back to you as soon as possible.</p>
        </div>

        <p>Hello{{ $submission->name ? ' ' . e($submission->name) : '' }},</p>

        <p>Thank you for contacting {{ config('app.name') }}. This email confirms that we have received your submission. Our team will review your message and respond within 1-2 business days.</p>

        <div class="summary">
            <h3>Your Submission Summary</h3>

            @foreach($submission->form_data as $field)
                <div class="field">
                    <span class="field-label">{{ $field['label'] }}</span>
                    <span class="field-value">{{ e($field['value']) }}</span>
                </div>
            @endforeach
        </div>

        <div class="footer">
            <p>This is an automated response. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>
