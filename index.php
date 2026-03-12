<?php
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/database/database.php";
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1>Welcome to CK Resort & Events Place</h1>
        <p>Relax, unwind, and enjoy unforgettable moments.</p>
        <a href="/form/index.php" class="btn">Book Now</a>
    </div>
</section>

<!-- Features Section -->
<section class="featured-section">
    <div class="featured-header">
        <h2>Breathe. Relax. Renew</h2>
        <div class="line"></div>
    </div>
    
    <div class="featured-grid">
        <div class="featured-card card-blue">
            <img src="/assets/images/RoomsBG.jpg" alt="Room">
        </div>
        <div class="featured-card card-pink">
            <img src="/assets/images/patio-pic.jpg" alt="Patio Area">
        </div>
        <div class="featured-card card-purple">
            <img src="/assets/images/bonfire.jpg" alt="Bonfire">
        </div>
    </div>
</section>

<!-- About Section -->
<section class="about">
    <div class="about-content">
        <h2>Welcome to Your Perfect Escape</h2>
        <p>
            Discover the ultimate retreat at CK Resort,
            where fun, comfort, and relaxation come 
            together in the heart of St. Duyong, San Simon, 
            Pampanga. Enjoy refreshing waters, 
            relaxing spaces, and a warm atmosphere 
            that makes every moment unforgettable. 
            Whether you're here for adventure or pure 
            leisure, CK Resort offers the perfect setting for 
            families, friends, and travelers seeking 
            a memorable experience.
        </p>
    </div>

    <div class="about-gallery">
        <div class="about-image">
            <img src="/assets/images/villa-room.jpg" alt="Villa Room">
            <h3>Comfortable Rooms</h3>
        </div>
        <div class="about-image">
            <img src="/assets/images/event-place.jpg" alt="Events Place">
            <h3>Events Place</h3>
        </div>
        <div class="about-image">
            <img src="/assets/images/bonfire-view.jpg" alt="Bonfire View">
            <h3>Exiting Activities</h3>
        </div>
        <div class="about-image">
            <img src="/assets/images/garden-view.jpg" alt="Garden View">
            <h3>Beautiful Spots</h3>
        </div>
    </div>
</section>

<!-- Sneak Peek Section -->
<section class="sneak-peek">
    <video src="/assets/video/trailer-vid.mp4" controls playsinline preload="metadata"></video>
</section>

<!-- Facilities Section -->
<section class="facilities">
    <div class="facility-card">
        <img src="/assets/images/mini-bar.jpg" alt="Mini Bar">
        <h3>Mini Bar</h3>
    </div>
    <div class="facility-card">
        <img src="/assets/images/basketball-court2.jpg" alt="Basketball Court">
        <h3>Basketball Court</h3>
    </div>
    <div class="billiards">
        <img src="/assets/images/billard2.jpg" alt="Billiards">
        <h3>Billiards</h3>
    </div>
    <div class="bonfire-area">
        <img src="/assets/images/bonfire.jpg" alt="Bonfire Area">
        <h3>Bonfire Area</h3>
    </div>
</section>

<!-- Map Section -->    
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

<!-- Pool Rules Section -->
<section class="pool-rules">
    <h2>Pool Rules</h2>
    <ul>
        <li>Swimming is allowed only in designated areas.</li>
        <li>Children must be supervised at all times.</li>
        <li>No diving is permitted in the pool area.</li>
        <li>Food and drinks are not allowed near the pool.</li>
        <li>Respect all posted signs and instructions.</li>
    </ul>
</section>

<!-- Terms and Services Section -->
<section class="terms-services">
    <h2>Terms and Services</h2>
    <p>
        By using our facilities, you agree to abide by all resort rules and regulations.<br>
        The resort is not responsible for any personal belongings lost or damaged on the premises.<br>
        Guests are expected to conduct themselves in a respectful manner towards staff and other guests.<br>
        Any violations of these terms may result in removal from the resort without refund.<br>
    </p>
</section>

<!-- Availability Section -->
<section class="policy-wrapper">
    <div class="policy-box resort-policy">
    <h2>Our Resort Policy</h2>
    <ol>
        <li>First come first serve.</li>
        <li>We give priority to 22hrs slots bookings during weekend.</li>
        <li>You can pay via Cash, GCash or thru online banking.</li>
        <li>
            For online banking and GCash please upload the proof of payment
            to the check status or send it to our FB page CK Resort & Events Place.
        </li>
        <li>
            Balance is payable to the staff upon check-in.
            Only cash, online banking, or GCash transfer will be accepted.
            Proof of transfer is required.
        </li>
        <li>
            Any outstanding fees (e.g. time extension) must be paid upon
            check-out before leaving the resort.
        </li>
    </ol>
</div>

</section>


<!-- Amenities Section -->
<section class="amenities-section">
    <div class="amenities-container">
        <div class="amenities-header">
            <h2>AMENITIES</h2>
        </div>

        <div class="amenities-grid">
            <div class="amenity-card card-orange">
                <div class="image-frame">
                    <img src="/assets/images/villa-room.jpg" alt="Rooms">
                </div>
                <div class="amenity-content">
                    <p>We also offer rooms for</p>
                    <ul>
                        <li>Single or Couple</li>
                        <li>Family or Barkada</li>
                    </ul>
                </div>
            </div>

            <div class="amenity-card card-yellow">
                <div class="image-frame">
                    <img src="/assets/images/garden.jpg" alt="Open Area">
                </div>
                <div class="amenity-content">
                    <p>We have a 1500 square meter</p>
                    <ul>
                        <li>Open Space Garden</li>
                        <li>Gazebo View Area</li>
                        <li>Picnic & Bonfire Area</li>
                    </ul>
                </div>
            </div>

            <div class="amenity-card card-green">
                <div class="image-frame">
                    <img src="/assets/images/morning-view5.jpg" alt="Big Pool">
                </div>
                <div class="amenity-content">
                    <p>We have a Big Pool measuring 3-5½ feet</p>
                    <ul>
                        <li>2 Jacuzzi round & square shape</li>
                        <li>Trilles w/ Falls & Fountain</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Resort Hours Section -->
<section class="resort-hours">
    <div class="day-tour">
        <h2>Day Tour</h2>
        <p>Monday to Sunday</p>
        <p>8:00 AM &ndash; 4:00 PM</p>
        <p>8 Hours Stay</p>
        <p>Maximum of 30 &ndash; 50 Persons</p>
        <p class="price-info">Up to 20 pax: <strong>&#8369;6,000</strong></p>
        <p class="price-info">30+ pax: <strong>&#8369;7,000</strong></p>
    </div>
    <div class="night-tour">
        <h2>Night Tour</h2>
        <p>Monday to Sunday</p>
        <p>4:00 PM &ndash; 12:00 AM</p>
        <p>8 Hours Stay</p>
        <p>Maximum of 30 &ndash; 50 Persons</p>
        <p class="price-info">Up to 20 pax: <strong>&#8369;7,000</strong></p>
        <p class="price-info">30+ pax: <strong>&#8369;8,000</strong></p>
    </div>
    <div class="overnight-tour">
        <h2>Overnight</h2>
        <p>Monday to Sunday</p>
        <p>2:00 PM &ndash; 12:00 NOON (Next Day)</p>
        <p>22 Hours Stay</p>
        <p>Maximum of 70 Persons</p>
        <p class="price-info">10 pax: <strong>&#8369;12,000</strong></p>
        <p class="price-info">70 pax: <strong>&#8369;50,000</strong></p>
    </div>
</section>


<!-- Rooms Section -->
<div class="rooms">
    <div class="room-card">
        <img src="/assets/images/cabin-room.jpg" alt="Cabin Room">
        <h3>Cabin Room</h3>
        <p>Ideal for families, offering extra space and comfort for everyone.</p>
    </div>

    <div class="room-card">
        <img src="/assets/images/villa-room2.jpg" alt="Villa Room">
        <h3>Villa Room</h3>
        <p>Comfortable and affordable rooms perfect for solo travelers and couples.</p>
    </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>