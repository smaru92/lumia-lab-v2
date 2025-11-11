<nav class="navbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <a href="/" class="site-logo-link">
                <img src="{{ asset('storage/Common/logo_white_header.png') }}" alt="아글라이아 연구소" class="site-logo">
                <span class="site-title">아글라이아 연구소</span>
            </a>
        </div>
        <button class="hamburger-menu" id="hamburgerMenu" aria-label="메뉴">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
        <ul class="nav-menu" id="navMenu">
            <li class="nav-item">
                <a href="/" class="nav-links">메인</a>
            </li>
            <li class="nav-item">
                <a href="/character" class="nav-links">캐릭터</a>
            </li>
            <li class="nav-item">
                <a href="/equipment" class="nav-links">장비</a>
            </li>
            <li class="nav-item">
                <a href="/equipment-first" class="nav-links">초기 장비</a>
            </li>
        </ul>
    </div>
</nav>

<script>
(function() {
    'use strict';

    function initHamburgerMenu() {
        const hamburger = document.getElementById('hamburgerMenu');
        const navMenu = document.getElementById('navMenu');

        if (!hamburger || !navMenu) {
            console.warn('Hamburger menu elements not found');
            return;
        }

        // Toggle menu function
        function toggleMenu() {
            const isActive = navMenu.classList.contains('active');

            if (isActive) {
                closeMenu();
            } else {
                openMenu();
            }
        }

        // Open menu
        function openMenu() {
            navMenu.classList.add('active');
            hamburger.classList.add('active');
        }

        // Close menu
        function closeMenu() {
            navMenu.classList.remove('active');
            hamburger.classList.remove('active');
        }

        // Hamburger click handler
        hamburger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMenu();
        });

        // Close menu when clicking nav links
        const navLinks = navMenu.querySelectorAll('.nav-links');
        navLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                closeMenu();
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInsideMenu = navMenu.contains(event.target);
            const isClickOnHamburger = hamburger.contains(event.target);

            if (!isClickInsideMenu && !isClickOnHamburger && navMenu.classList.contains('active')) {
                closeMenu();
            }
        });

        // Close menu on window resize if switching to desktop
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                // Close menu if screen size becomes desktop (1025px+)
                if (window.innerWidth >= 1025 && navMenu.classList.contains('active')) {
                    closeMenu();
                }
            }, 250);
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHamburgerMenu);
    } else {
        initHamburgerMenu();
    }

    // Prevent multiple initializations
    window.hamburgerMenuInitialized = true;
})();
</script>