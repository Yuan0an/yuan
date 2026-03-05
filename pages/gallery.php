<?php
    include_once($_SERVER['DOCUMENT_ROOT'] . '/resort-website/includes/header.php');
    include_once($_SERVER['DOCUMENT_ROOT'] . '/resort-website/database/database.php');
?>

<section class="about-hero">
    <div class="about-hero-content">
        <h1>Welcome to Ck Resort & Events Place</h1>
        <p>Relax, unwind, and enjoy unforgettable moments.</p>
        <a href="/resort-website/form/index.php" class="btn">Book Now</a>
    </div>
</section>

<main class="gallery-container">
        <section class="gallery-intro">
            <h1>📸 Experience Our Oasis</h1>
            <p>Where every corner is a postcard and every moment is a memory.</p>
        </section>

        <div class="gallery-grid">
            <div class="gallery-item wide">
                <img src="/resort-website/assets/images/patio-pic.jpg" alt="The Tranquil Patio">
                <div class="overlay"><span>The Tranquil Patio</span></div>
            </div>

            <div class="gallery-item">
                <img src="/resort-website/assets/images/cabin-room3.jpg" alt="The Cozy Cabin">
                <div class="overlay"><span>The Cozy Cabin</span></div>
            </div>

            <div class="gallery-item large">
                <img src="/resort-website/assets/images/morning-view5.jpg" alt="The Pool">
                <div class="overlay"><span>The Pool</span></div>
            </div>
            
            <div class="gallery-item">
                <img src="/resort-website/assets/images/billard.jpg" alt="Billiards Area">
                <div class="overlay"><span>Billiards Area</span></div>
            </div>

            <div class="gallery-item">
                <img src="/resort-website/assets/images/garden-view.jpg" alt="The Garden Gazebo">
                <div class="overlay"><span>The Garden Gazebo</span></div>
            </div>

            <div class="gallery-item">
                <img src="/resort-website/assets/images/villa-room3.jpg" alt="Villa Room Comfort">
                <div class="overlay"><span>Villa Room Comfort</span></div>
            </div>

            <div class="gallery-item">
                <img src="/resort-website/assets/images/bonfire.jpg" alt="Sunset Serenity">
                <div class="overlay"><span>Bonfire Area</span></div>
            </div>

            <div class="gallery-item wide">
                <img src="/resort-website/assets/images/basketball-court.jpg" alt="Basketball Court">
                <div class="overlay"><span>Basketball Court</span></div>
            </div>

            <div class="gallery-item large">
                <img src="/resort-website/assets/images/mini-bar.jpg" alt="The Mini Bar">
                <div class="overlay"><span>The Mini Bar</span></div>
            </div>

            <div class="gallery-item wide">
                <img src="/resort-website/assets/images/event-place.jpg" alt="Event Area">
                <div class="overlay"><span>Events Area</span></div>
            </div>

            <div class="gallery-item wide">
                <img src="/resort-website/assets/images/ContactUsBG.jpg" alt="Night View">
                <div class="overlay"><span>Night View</span></div>
            </div>

            <div class="gallery-item">
                <img src="/resort-website/assets/images/morning-view4.jpg" alt="Morning Pool View">
                <div class="overlay"><span>Morning Pool View</span></div>
            </div>
        </div>  
        
</main>






<?php include __DIR__ . "/../includes/footer.php"; ?>