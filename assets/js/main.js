// Mobile Menu Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navbarNav = document.querySelector('.navbar-nav');

    if (mobileMenuToggle && navbarNav) {
        mobileMenuToggle.addEventListener('click', function() {
            // Toggle active classes
            mobileMenuToggle.classList.toggle('active');
            navbarNav.classList.toggle('active');

            // Prevent body scroll when menu is open
            document.body.style.overflow = navbarNav.classList.contains('active') ? 'hidden' : '';
        });

        // Close menu when clicking on a link
        const navLinks = navbarNav.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenuToggle.classList.remove('active');
                navbarNav.classList.remove('active');
                document.body.style.overflow = '';
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenuToggle.contains(event.target) && !navbarNav.contains(event.target)) {
                mobileMenuToggle.classList.remove('active');
                navbarNav.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
});

// Review toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const reviewToggles = document.querySelectorAll('.review-toggle');

    reviewToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();

            const reviewText = this.closest('.review-text');
            const shortText = reviewText.querySelector('.review-text-short');
            const fullText = reviewText.querySelector('.review-text-full');
            const isExpanded = this.getAttribute('data-expanded') === 'true';

            if (isExpanded) {
                // Show short text, hide full text
                shortText.style.display = 'inline';
                fullText.style.display = 'none';
                this.textContent = 'View More';
                this.setAttribute('data-expanded', 'false');
            } else {
                // Show full text, hide short text
                shortText.style.display = 'none';
                fullText.style.display = 'inline';
                this.textContent = 'View Less';
                this.setAttribute('data-expanded', 'true');
            }
        });
    });
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
