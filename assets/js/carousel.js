document.addEventListener('DOMContentLoaded', () => {
    const carousels = document.querySelectorAll('.carousel');
    
    if (carousels.length === 0) {
        console.error("Troubleshooter: No elements with class '.carousel' found!");
        return;
    }

    carousels.forEach((carousel, index) => {
        const slides = carousel.querySelectorAll('.carousel-track img');
        const nextBtn = carousel.querySelector('.next');
        const prevBtn = carousel.querySelector('.prev');
        let currentIndex = 0;

        if (slides.length === 0) {
            console.warn(`Troubleshooter: Carousel #${index} has no images!`);
            return;
        }

        function changeSlide(newIndex) {
            slides[currentIndex].classList.remove('active');
            currentIndex = (newIndex + slides.length) % slides.length;
            slides[currentIndex].classList.add('active');
        }

        if (nextBtn) {
            nextBtn.onclick = () => changeSlide(currentIndex + 1);
        } else {
            console.warn(`Troubleshooter: Carousel #${index} is missing a Next button!`);
        }

        if (prevBtn) {
            prevBtn.onclick = () => changeSlide(currentIndex - 1);
        }
    });
    
    console.log("Troubleshooter: Carousel script loaded successfully!");
});