<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $post->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak] { display: none !important; }</style>
    
    <style>
        .preview-container {
            background: #f3f4f6;
            min-height: 100vh;
        }

        .preview-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .preview-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .device-selector {
            display: flex;
            background: #f9fafb;
            border-radius: 8px;
            padding: 4px;
        }

        .device-btn {
            padding: 8px 16px;
            border: none;
            background: transparent;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            color: #6b7280;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .device-btn.active {
            background: white;
            color: #1f2937;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .device-btn:hover {
            color: #1f2937;
        }

        .preview-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #6b7280;
            font-size: 14px;
        }

        .preview-viewport {
            margin: 1rem auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            background: white;
        }

        /* Device-specific dimensions */
        .viewport-desktop {
            width: 100%;
            max-width: 1200px;
        }

        .viewport-tablet {
            width: 768px;
            max-width: 90vw;
        }

        .viewport-mobile {
            width: 375px;
            max-width: 90vw;
        }

        .viewport-indicator {
            position: absolute;
            top: 70px;
            right: 12px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 10;
        }

        /* Hide indicator on mobile viewport to avoid interference */
        .viewport-mobile .viewport-indicator {
            display: none;
        }

        /* Responsive adjustments for small screens */
        @media (max-width: 768px) {
            .viewport-tablet,
            .viewport-mobile {
                width: 100%;
                margin: 1rem 0.5rem;
            }
            
            .preview-header {
                padding: 0.75rem;
            }
            
            .device-selector {
                order: 2;
                width: 100%;
                justify-content: center;
            }
            
            .preview-info {
                order: 1;
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <!-- Preview Controls Header -->
        <div class="preview-header">
            <div class="preview-controls">
                <div class="preview-info">
                    <span><strong>Preview:</strong> {{ $post->title }}</span>
                    <span class="device-dimensions"></span>
                </div>
                
                <div class="device-selector">
                    <button class="device-btn {{ $device === 'desktop' ? 'active' : '' }}" 
                            onclick="switchDevice('desktop')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="2" y="4" width="20" height="12" rx="2" ry="2" stroke="currentColor" stroke-width="2" fill="none"/>
                            <line x1="2" y1="20" x2="22" y2="20" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        Desktop
                    </button>
                    <button class="device-btn {{ $device === 'tablet' ? 'active' : '' }}" 
                            onclick="switchDevice('tablet')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2" stroke="currentColor" stroke-width="2" fill="none"/>
                            <circle cx="12" cy="18" r="1" fill="currentColor"/>
                        </svg>
                        Tablet
                    </button>
                    <button class="device-btn {{ $device === 'mobile' ? 'active' : '' }}" 
                            onclick="switchDevice('mobile')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="7" y="2" width="10" height="20" rx="2" ry="2" stroke="currentColor" stroke-width="2" fill="none"/>
                            <line x1="12" y1="18" x2="12.01" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Mobile
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview Viewport -->
        <div id="preview-viewport" class="preview-viewport viewport-{{ $device }}">
            <div class="viewport-indicator" id="viewport-indicator">
                <!-- Updated by JavaScript -->
            </div>
            
            <!-- Actual Post Content in Full Layout -->
            <div class="page-content">
                @include('preview.post-content', [
                    'post' => $post, 
                    'renderedContent' => $renderedContent
                ])
            </div>
        </div>
    </div>

    <script>
        const deviceDimensions = {
            desktop: { width: '100%', height: 'auto' },
            tablet: { width: '768px', height: 'auto' },
            mobile: { width: '375px', height: 'auto' }
        };

        function switchDevice(device) {
            const url = new URL(window.location);
            url.searchParams.set('device', device);
            window.location.href = url.toString();
        }

        function updateViewportIndicator() {
            const device = '{{ $device }}';
            const indicator = document.getElementById('viewport-indicator');
            const dimensions = deviceDimensions[device];
            
            if (device === 'desktop') {
                indicator.textContent = 'Desktop View';
            } else {
                indicator.textContent = `${device.charAt(0).toUpperCase() + device.slice(1)} - ${dimensions.width}`;
            }

            // Update dimensions display
            const dimensionsEl = document.querySelector('.device-dimensions');
            if (dimensionsEl) {
                dimensionsEl.textContent = dimensions.width;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', updateViewportIndicator);
    </script>
</body>
</html>