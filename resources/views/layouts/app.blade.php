<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '아글라이아 연구소')</title>
    <link rel="icon" type="image/png" href="{{ asset('storage/Common/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}?v={{ time() }}">
    @stack('styles')
</head>
<body>
    @include('layouts.header')

    @yield('content')

    @include('layouts.footer')
    @stack('scripts')

    <script>
    // Tooltip auto-hide functionality
    document.addEventListener('DOMContentLoaded', function() {
        let tooltipTimeout;
        let activeIcon = null;

        // Handle click/touch on info icons
        document.addEventListener('click', function(e) {
            const infoIcon = e.target.closest('.info-icon');

            if (infoIcon) {
                e.preventDefault();
                e.stopPropagation();

                // Remove active class from previous icon
                if (activeIcon && activeIcon !== infoIcon) {
                    activeIcon.classList.remove('tooltip-active');
                }

                // Clear any existing timeout
                if (tooltipTimeout) {
                    clearTimeout(tooltipTimeout);
                }

                // Toggle or add active class
                if (infoIcon.classList.contains('tooltip-active')) {
                    infoIcon.classList.remove('tooltip-active');
                    activeIcon = null;
                } else {
                    infoIcon.classList.add('tooltip-active');
                    activeIcon = infoIcon;

                    // Set timeout to hide tooltip after 2 seconds
                    tooltipTimeout = setTimeout(function() {
                        infoIcon.classList.remove('tooltip-active');
                        activeIcon = null;
                    }, 2000);
                }
            } else {
                // Click outside - hide all tooltips
                if (activeIcon) {
                    activeIcon.classList.remove('tooltip-active');
                    activeIcon = null;
                }
                if (tooltipTimeout) {
                    clearTimeout(tooltipTimeout);
                }
            }
        });

        // Handle mouse enter on desktop
        const infoIcons = document.querySelectorAll('.info-icon');
        infoIcons.forEach(function(icon) {
            icon.addEventListener('mouseenter', function() {
                // Only for desktop (non-touch devices)
                if (!('ontouchstart' in window)) {
                    if (tooltipTimeout) {
                        clearTimeout(tooltipTimeout);
                    }

                    if (activeIcon && activeIcon !== icon) {
                        activeIcon.classList.remove('tooltip-active');
                    }

                    icon.classList.add('tooltip-active');
                    activeIcon = icon;

                    tooltipTimeout = setTimeout(function() {
                        icon.classList.remove('tooltip-active');
                        activeIcon = null;
                    }, 2000);
                }
            });

            icon.addEventListener('mouseleave', function() {
                // Only for desktop (non-touch devices)
                if (!('ontouchstart' in window)) {
                    if (tooltipTimeout) {
                        clearTimeout(tooltipTimeout);
                    }
                    icon.classList.remove('tooltip-active');
                    if (activeIcon === icon) {
                        activeIcon = null;
                    }
                }
            });
        });
    });
    </script>
</body>
</html>