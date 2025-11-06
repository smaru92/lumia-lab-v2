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
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburgerMenu');
    const navMenu = document.getElementById('navMenu');

    if (hamburger && navMenu) {
        console.log('Hamburger menu initialized');

        hamburger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Hamburger clicked');

            const isActive = navMenu.classList.contains('active');
            console.log('Current active state:', isActive);
            console.log('navMenu element:', navMenu);
            const styles = window.getComputedStyle(navMenu);
            console.log('navMenu styles:', styles.display);
            console.log('navMenu position:', styles.position);
            console.log('navMenu z-index:', styles.zIndex);
            console.log('navMenu visibility:', styles.visibility);
            console.log('navMenu opacity:', styles.opacity);
            console.log('navMenu top:', styles.top);
            console.log('navMenu right:', styles.right);
            console.log('navMenu width:', styles.width);
            console.log('navMenu height:', styles.height);

            if (isActive) {
                navMenu.classList.remove('active');
                hamburger.classList.remove('active');
                // Force hidden with inline styles
                navMenu.style.setProperty('visibility', 'hidden', 'important');
                navMenu.style.setProperty('opacity', '0', 'important');
                navMenu.style.setProperty('pointer-events', 'none', 'important');
                console.log('Menu closed');
            } else {
                navMenu.classList.add('active');
                hamburger.classList.add('active');
                // Force visibility with inline styles
                navMenu.style.setProperty('display', 'flex', 'important');
                navMenu.style.setProperty('visibility', 'visible', 'important');
                navMenu.style.setProperty('opacity', '1', 'important');
                navMenu.style.setProperty('pointer-events', 'auto', 'important');
                console.log('Menu opened');

                // Check after a brief delay to ensure styles are applied
                setTimeout(() => {
                    const afterStyles = window.getComputedStyle(navMenu);
                    console.log('After open - display:', afterStyles.display);
                    console.log('After open - visibility:', afterStyles.visibility);
                    console.log('After open - opacity:', afterStyles.opacity);
                    console.log('Inline styles:', navMenu.style.cssText);
                }, 50);
            }
        });

        // 메뉴 링크 클릭시 메뉴 닫기
        const navLinks = document.querySelectorAll('.nav-links');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
                hamburger.classList.remove('active');
            });
        });

        // 메뉴 외부 클릭시 메뉴 닫기
        document.addEventListener('click', function(event) {
            if (!hamburger.contains(event.target) && !navMenu.contains(event.target)) {
                navMenu.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });
    } else {
        console.error('Hamburger or navMenu not found');
    }
});
</script>