    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">
                <!-- Company Info -->
                <div>
                    <h3 style="color: white; margin-bottom: 20px;">TMM ACADEMY</h3>
                    <p style="color: rgba(255,255,255,0.8); margin-bottom: 20px;">
                        Learn programming through expert-led lessons and hands-on projects.
                    </p>
                    <div style="display: flex; gap: 15px;">
                        <a href="#" style="color: white; font-size: 1.2rem;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: white; font-size: 1.2rem;"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="color: white; font-size: 1.2rem;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: white; font-size: 1.2rem;"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px;">Quick Links</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li><a href="<?php echo SITE_URL; ?>/index.php" style="color: rgba(255,255,255,0.8);">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/courses.php" style="color: rgba(255,255,255,0.8);">Courses</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/faq.php" style="color: rgba(255,255,255,0.8);">FAQ</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/login.php" style="color: rgba(255,255,255,0.8);">Login</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/register.php" style="color: rgba(255,255,255,0.8);">Register</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px;">Contact Us</h4>
                    <ul style="list-style: none; padding: 0; color: rgba(255,255,255,0.8);">
                        <li style="margin-bottom: 10px;"><i class="fas fa-envelope"></i> info@tmmacademy.com</li>
                        <li style="margin-bottom: 10px;"><i class="fas fa-phone"></i> +880 1715 457892</li>
                        <li><i class="fas fa-map-marker-alt"></i> Dhaka, Bangladesh</li>
                    </ul>
                </div>
            </div>

            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; text-align: center; color: rgba(255,255,255,0.6);">
                <p>&copy; <?php echo date('Y'); ?> TMM Academy. All rights reserved.</p>
                <p>Develop By team "Binary Coder"</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
