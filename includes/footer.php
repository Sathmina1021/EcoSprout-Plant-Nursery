</main>

<!-- Footer -->
<footer class="footer">
    <div class="footer-top">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="footer-logo">
                    <span class="logo-icon">🌿</span>
                    <span class="logo-text">Eco<strong>Sprout</strong></span>
                </div>
                <p>Sri Lanka's premier plant nursery and gardening services company, bringing nature closer to your home since 2024.</p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/plants.php">Plant Catalogue</a></li>
                    <li><a href="<?= SITE_URL ?>/services.php">Gardening Services</a></li>
                    <li><a href="<?= SITE_URL ?>/workshops.php">Workshops & Events</a></li>
                    <li><a href="<?= SITE_URL ?>/blog.php">Gardening Blog</a></li>
                    <li><a href="<?= SITE_URL ?>/contact.php">Contact Us</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>Plant Categories</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/plants.php?category=1">Indoor Plants</a></li>
                    <li><a href="<?= SITE_URL ?>/plants.php?category=2">Outdoor Plants</a></li>
                    <li><a href="<?= SITE_URL ?>/plants.php?category=3">Ornamental Plants</a></li>
                    <li><a href="<?= SITE_URL ?>/plants.php?category=4">Edible Plants</a></li>
                </ul>
            </div>

            <div class="footer-contact">
                <h4>Find Us</h4>
                <p><i class="fas fa-map-marker-alt"></i> No. 42, Kandy Road, Kegalle, Sri Lanka</p>
                <p><i class="fas fa-phone"></i> +94 35 222 3456</p>
                <p><i class="fas fa-envelope"></i> info@ecosprout.lk</p>
                <p><i class="fas fa-clock"></i> Mon–Sat: 8:00 AM – 6:00 PM</p>
                <p><i class="fas fa-clock"></i> Sunday: 9:00 AM – 4:00 PM</p>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> EcoSprout Nursery. All rights reserved. | Designed for CSE4206 Web Application Development</p>
    </div>
</footer>

<script>window.ECOSPROUT_SITE_URL = <?= json_encode(SITE_URL) ?>;</script>
<script src="<?= SITE_URL ?>/js/main.js"></script>
<?= isset($extraJS) ? $extraJS : '' ?>
</body>
</html>
