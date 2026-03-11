<?php
// includes/footer.php
if (isset($conn)) {
    $footer_settings = [];
    $fs_res = $conn->query("SELECT * FROM site_settings WHERE setting_key LIKE 'footer_%'");
    if ($fs_res) {
        while ($fs_row = $fs_res->fetch_assoc()) {
            $footer_settings[$fs_row['setting_key']] = $fs_row['setting_value'];
        }
    }
}
?>
<footer class="site-footer-simple">
    <div class="footer-simple-container">
        <div class="footer-info">
            <p class="footer-brand">CK RESORT & EVENT PLACE</p>
            <p class="footer-address"><?php echo htmlspecialchars($footer_settings['footer_address'] ?? '123 Resort Drive, Event City, Philippines'); ?></p>
            <p class="footer-contact">Contact: <?php echo htmlspecialchars($footer_settings['footer_contact'] ?? '09209502510 | 09693226114'); ?></p>
        </div>
        <div class="footer-bottom-simple">
            <p>&copy; <?php echo date("Y"); ?> CK Resort & Events Place. All rights reserved.</p>
        </div>
    </div>
</footer>

<style>
.site-footer-simple {
    background-color: #1a1a1a;
    color: #ffffff;
    padding: 40px 20px;
    font-family: 'Inter', sans-serif;
    text-align: center;
}
.footer-simple-container {
    max-width: 1200px;
    margin: 0 auto;
}
.footer-brand {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 10px;
    letter-spacing: 1px;
}
.footer-address, .footer-contact {
    font-size: 0.95rem;
    color: #a0a0a0;
    margin-bottom: 5px;
}
.footer-bottom-simple {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #333;
    font-size: 0.85rem;
    color: #777;
}
</style>
<script src="/assets/js/carousel.js"></script>
<script src="/assets/js/script.js"></script>
</body>
</html>