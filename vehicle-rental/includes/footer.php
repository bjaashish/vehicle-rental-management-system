</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <h4>🚘 <?= e(APP_NAME) ?></h4>
            <p>Affordable, reliable vehicle rentals. Cars, SUVs, bikes, scooters, vans and trucks — booked in minutes.</p>
        </div>
        <div>
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?= $base ?>index.php">Home</a></li>
                <li><a href="<?= $base ?>vehicles.php">Browse Vehicles</a></li>
                <li><a href="<?= $base ?>contact.php">Contact Us</a></li>
            </ul>
        </div>
        <div>
            <h4>Contact</h4>
            <p>📍 New Delhi, India<br>📞 +91 98765 43210<br>✉️ support@driveeasy.com</p>
        </div>
    </div>
    <p class="copyright">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> — Vehicle Rental Management System</p>
</footer>
<script src="<?= $base ?>assets/js/main.js"></script>
</body>
</html>
