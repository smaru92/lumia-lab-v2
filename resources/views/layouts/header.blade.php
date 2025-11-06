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
    function initHamburgerMenu() {
        const hamburger = document.getElementById('hamburgerMenu');
        const navMenu = document.getElementById('navMenu');

        if (!hamburger || !navMenu) {
            return;
        }

        // Remove any existing listeners by cloning
        const newHamburger = hamburger.cloneNode(true);
        hamburger.parentNode.replaceChild(newHamburger, hamburger);

        newHamburger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const isActive = navMenu.classList.contains('active');

            if (isActive) {
                navMenu.classList.remove('active');
                newHamburger.classList.remove('active');
                navMenu.style.setProperty('visibility', 'hidden', 'important');
                navMenu.style.setProperty('opacity', '0', 'important');
                navMenu.style.setProperty('pointer-events', 'none', 'important');
            } else {
                navMenu.classList.add('active');
                newHamburger.classList.add('active');
                navMenu.style.setProperty('display', 'flex', 'important');
                navMenu.style.setProperty('visibility', 'visible', 'important');
                navMenu.style.setProperty('opacity', '1', 'important');
                navMenu.style.setProperty('pointer-events', 'auto', 'important');
            }
        });

        // 메뉴 링크 클릭시 메뉴 닫기
        const navLinks = document.querySelectorAll('.nav-links');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
                newHamburger.classList.remove('active');
                navMenu.style.setProperty('visibility', 'hidden', 'important');
                navMenu.style.setProperty('opacity', '0', 'important');
                navMenu.style.setProperty('pointer-events', 'none', 'important');
            });
        });

        // 메뉴 외부 클릭시 메뉴 닫기
        document.addEventListener('click', function(event) {
            if (!newHamburger.contains(event.target) && !navMenu.contains(event.target)) {
                navMenu.classList.remove('active');
                newHamburger.classList.remove('active');
                navMenu.style.setProperty('visibility', 'hidden', 'important');
                navMenu.style.setProperty('opacity', '0', 'important');
                navMenu.style.setProperty('pointer-events', 'none', 'important');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHamburgerMenu);
    } else {
        initHamburgerMenu();
    }
})();
</script>