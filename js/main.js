// EcoSprout – Main JavaScript

document.addEventListener('DOMContentLoaded', () => {

    const siteUrl = (window.ECOSPROUT_SITE_URL || '').replace(/\/$/, '');

    // ── Navbar scroll effect ──
    const navbar = document.getElementById('mainNav');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 20);
        });
    }

    // ── Mobile nav toggle ──
    const navToggle = document.getElementById('navToggle');
    const navLinks  = document.getElementById('navLinks');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => {
            navLinks.classList.toggle('open');
        });
    }

    // ── User dropdown ──
    const userMenuBtn  = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });
        document.addEventListener('click', () => userDropdown.classList.remove('open'));
    }

    // ── Active nav link ──
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-links a').forEach(link => {
        if (currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });

    // ── Auto-dismiss flash messages ──
    const flash = document.getElementById('flashMsg');
    if (flash) {
        setTimeout(() => flash.style.opacity = '0', 4000);
        setTimeout(() => flash.remove(), 4500);
    }

    // ── Quantity buttons (cart) ──
    document.querySelectorAll('.qty-btn').forEach(btn => {
        if (btn.hasAttribute('onclick')) return;
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('.qty-input');
            let val = parseInt(input.value) || 1;
            if (btn.dataset.action === 'increase') val = Math.min(val + 1, 99);
            if (btn.dataset.action === 'decrease') val = Math.max(val - 1, 1);
            input.value = val;
            input.dispatchEvent(new Event('change'));
        });
    });

    // ── Filter tabs ──
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const group = tab.dataset.group || 'default';
            document.querySelectorAll(`.filter-tab[data-group="${group}"]`).forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
        });
    });

    // ── Intersection Observer for fade-in ──
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.plant-card, .service-card, .workshop-card, .blog-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity .5s ease, transform .5s ease';
        observer.observe(card);
    });

    // ── Form validation helpers ──
    window.validateForm = function(formId) {
        const form = document.getElementById(formId);
        if (!form) return true;
        let valid = true;
        form.querySelectorAll('[required]').forEach(field => {
            const error = field.parentElement.querySelector('.form-error');
            if (!field.value.trim()) {
                field.classList.add('error');
                if (error) error.style.display = 'flex';
                valid = false;
            } else {
                field.classList.remove('error');
                if (error) error.style.display = 'none';
            }
        });
        // Email validation
        form.querySelectorAll('[type="email"]').forEach(field => {
            if (field.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                field.classList.add('error');
                valid = false;
            }
        });
        return valid;
    };

    // ── Add to cart AJAX ──
    document.querySelectorAll('.btn-cart').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const itemId   = btn.dataset.id;
            const itemType = btn.dataset.type || 'plant';
            if (!itemId) return;

            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            try {
                const res = await fetch(`${siteUrl}/cart_action.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=add&item_id=${itemId}&item_type=${itemType}&quantity=1`
                });
                const data = await res.json();
                if (data.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Added!';
                    btn.style.background = 'var(--success)';
                    // Update cart badge
                    const badge = document.querySelector('.cart-badge');
                    if (badge) badge.textContent = data.cart_count;
                    else {
                        const cartIcon = document.querySelector('.cart-icon');
                        if (cartIcon) {
                            const b = document.createElement('span');
                            b.className = 'cart-badge';
                            b.textContent = data.cart_count;
                            cartIcon.appendChild(b);
                        }
                    }
                } else {
                    btn.innerHTML = '<i class="fas fa-exclamation"></i> ' + (data.message || 'Error');
                    btn.style.background = 'var(--error)';
                }
            } catch {
                btn.innerHTML = '<i class="fas fa-exclamation"></i> Error';
                btn.style.background = 'var(--error)';
            }

            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.style.background = '';
                btn.disabled = false;
            }, 2000);
        });
    });

    // ── Modal helper ──
    window.openModal  = (id) => document.getElementById(id)?.classList.add('open');
    window.closeModal = (id) => document.getElementById(id)?.classList.remove('open');
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });

    // ── Password toggle ──
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling;
            const isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            btn.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    });

    // ── Image preview ──
    document.querySelectorAll('.image-input').forEach(input => {
        input.addEventListener('change', (e) => {
            const preview = document.getElementById(input.dataset.preview);
            if (preview && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = (ev) => { preview.src = ev.target.result; };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    });

});
