<?php
    include_once __DIR__ . '/../database/database.php';
    include_once __DIR__ . '/../includes/header.php';
?>
<section class="about-hero">
    <div class="about-hero-content">
        <h1>Welcome to Ck Resort & Events Place</h1>
        <p>Relax, unwind, and enjoy unforgettable moments.</p>
        <a href="/form/index.php" class="btn">Book Now</a>
    </div>
</section>

<section>
    <div class="about-page">
        <div class="about-page-content">
            <h2>Welcome to Your Perfect Escape</h2>
            <p>
                Looking for a peaceful and spacious place to celebrate your
                special moments? Whether it's a birthday, anniversary, team building,
                or a simple getaway with loved ones, CK Resort and Events Place is the
                perfect spot. Enjoy relaxing atmospheres, refreshing pool, and a venue
                designed for memorabe gatherings. Celebrate and unwind at CK Resort and Event Place.
            </p>
        </div>
        <div class="about-gallery">
            <div class="about-image">
                <img src="/assets/images/villa-room.jpg" alt="Villa Room">
                <h3>Villa Rooms</h3>
            </div>
            <div class="about-image">
                <img src="/assets/images/event-place.jpg" alt="Events Place">
                <h3>Events Place</h3>
            </div>
            <div class="about-image">
                <img src="/assets/images/morning-view4.jpg" alt="Pool Area">
                <h3>Refreshing Pools</h3>
            </div>
            <div class="about-image">
                <img src="/assets/images/bonfire.jpg" alt="Bonfire Area">
                <h3>Bonfire Area</h3>
        </div>
    </div>
</section>
<section class="map">
    <h2>Find Us Here</h2>
    <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3853.972952412072!2d120.83524736490934!3d14.99420660377715!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3396ff9d40a8062d%3A0xc7be9e46a5444f68!2sCK%20Resort%20%26%20Events%20Place!5e0!3m2!1sen!2sph!4v1767878778387!5m2!1sen!2sph" 
        width="100%" 
        height="450" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>
</section>

<?php include __DIR__ . "/../includes/footer.php"; ?>