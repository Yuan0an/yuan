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
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #ffffff;
    padding: 20px 0;
    font-family: 'Inter', sans-serif;
    text-align: center;
    width: 100% !important;
    margin-top: 60px;
    box-sizing: border-box;
}
.footer-simple-container {
    max-width: 100%;
    padding: 0 20px;
}
.footer-brand {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 5px;
    letter-spacing: 1px;
}
.footer-address, .footer-contact {
    font-size: 0.85rem;
    color: #cccccc;
    margin-bottom: 3px;
}
.footer-bottom-simple {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 0.75rem;
    color: #999;
}
</style>
<script src="/assets/js/carousel.js"></script>
<script src="/assets/js/script.js"></script>
</body>
</html>