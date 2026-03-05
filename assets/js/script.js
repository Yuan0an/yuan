function openLightbox(element) {
    const lightbox = document.getElementById("lightbox");
    const lightboxImg = document.getElementById("lightbox-img");
    const captionText = document.getElementById("caption");
    const clickedImg = element.querySelector("img");

    lightbox.style.display = "flex";
    lightboxImg.src = clickedImg.src;
    captionText.innerHTML = clickedImg.alt;
}

function closeLightbox() {
    document.getElementById("lightbox").style.display = "none";
}

const burger = document.getElementById("burger");
const navMenu = document.getElementById("navMenu");

burger.addEventListener("click", () => {
    navMenu.classList.toggle("active");
});