<?php
// includes/footer.php
// Ensure $conn is available if this is included in pages that have it
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
<footer class="resort-footer">
    <div class="footer-container">
        <div class="footer-header">
            <h1>CK RESORT & EVENT PLACE</h1>
        </div>

        <div class="footer-body">
            <div class="contact-section">
                <p class="label">About Us:</p>
                <p class="details" style="margin-bottom: 15px;"><?php echo nl2br(htmlspecialchars($footer_settings['footer_about'] ?? 'Our resort provides a premium booking experience for your special events.')); ?></p>
                
                <p class="label">Find Us:</p>
                <p class="details" style="margin-bottom: 15px;"><i class="fas fa-map-marker-alt" style="color: #4CAF50; margin-right: 8px;"></i> <?php echo htmlspecialchars($footer_settings['footer_address'] ?? '123 Resort Drive, Event City, Philippines'); ?></p>

                <p class="label">Contact Number / Info:</p>
                <p class="details"><?php echo htmlspecialchars($footer_settings['footer_contact'] ?? '09209502510 | 09693226114 | 09667760680'); ?></p>
                
                <ul class="social-list">
                    <li><i class="fab fa-google"></i> Email: ckresortandeventsplace@gmail.com</li>
                    <li><i class="fab fa-airbnb"></i> Airbnb: CK Resort And Events Place</li>
                    <li><i class="fab fa-facebook"></i> Facebook: CK Resort & Events Place</li>
                </ul>
            </div>
        </div>

        <div class="footer-footer">
            <a href="/form/index.php" class="btn-book">BOOK NOW</a>
            <p class="copyright">&copy; <?php echo date("Y"); ?> Ck Resort & Events Place</p>
        </div>
    </div>
</footer>
<script src="/assets/js/carousel.js"></script>
<script src="/assets/js/script.js"></script>
</body>
</html>