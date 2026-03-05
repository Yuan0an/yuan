<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../database/database.php';
?>
<section class="rooms-hero">
    <div class="rooms-hero-content">
        <h1>Welcome to Ck Resort & Events Place</h1>
        <p>Relax, unwind, and enjoy unforgettable moments.</p>
        <a href="/resort-website/form/index.php" class="btn">Book Now</a>
    </div>
</section>

<section class="rooms-page">
    <div class="rooms-content">
        <h2>Our Rooms</h2>
        <p>Discover our comfortable and luxurious rooms designed for your perfect stay.</p>
    </div>
    <div class="room-image">
        <h3>Cabin Room</h3>
        <div class="carousel">
            <div class="carousel-track">
                <img src="/resort-website/assets/images/cabin-room.jpg" alt="Cabin Room" class="active">
                <img src="/resort-website/assets/images/cabin-room2.jpg" alt="Cabin Room">
                <img src="/resort-website/assets/images/cabin-room3.jpg" alt="Cabin Room">
                <img src="/resort-website/assets/images/GalleryBG.jpg" alt="Cabin Room">
            </div>
            <button class="carousel-btn prev">&#10094;</button>
            <button class="carousel-btn next">&#10095;</button>
        </div>
    </div>

    <div class="room-image">
        <h3>Villa Room</h3>
        <div class="carousel">
            <div class="carousel-track">
                <img src="/resort-website/assets/images/villa-room.jpg" alt="Villa Room" class="active">
                <img src="/resort-website/assets/images/villa-room2.jpg" alt="Villa Room">
                <img src="/resort-website/assets/images/villa-room3.jpg" alt="Villa Room">
                <img src="/resort-website/assets/images/villa-room4.jpg" alt="Villa Room">
            </div>
            <button class="carousel-btn prev">&#10094;</button>
            <button class="carousel-btn next">&#10095;</button>
        </div>
    </div>

    <div class="room-image">
        <h3>Other Room</h3>
        <div class="carousel">
            <div class="carousel-track">
                <img src="/resort-website/assets/images/room.jpg" alt="Other Room" class="active">
                <img src="/resort-website/assets/images/room2.jpg" alt="Other Room">
                <img src="/resort-website/assets/images/attic-room.jpg" alt="Other Room">
                <img src="/resort-website/assets/images/attic-room2.jpg" alt="Other Room">
            </div>
            <button class="carousel-btn prev">&#10094;</button>
            <button class="carousel-btn next">&#10095;</button>
        </div>
    </div>

</section>


<?php include __DIR__ . "/../includes/footer.php"; ?>