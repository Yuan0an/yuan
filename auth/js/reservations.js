// Dropdown toggle
function toggleDropdown(button) {
    // Close other dropdowns
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu !== button.nextElementSibling) {
            menu.classList.remove('show');
        }
    });
    button.nextElementSibling.classList.toggle('show');
}

// Close dropdown when clicking outside
window.onclick = function (event) {
    if (!event.target.matches('.btn-actions') && !event.target.closest('.btn-actions')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
}

// Auto-hide success message after 5 seconds with fade animation
setTimeout(function () {
    var alerts = document.querySelectorAll('.alert-flash');
    alerts.forEach(function (alert) {
        alert.classList.add('fade-out');
        setTimeout(() => {
            alert.style.display = 'none';
        }, 500);
    });
}, 5000);

// Modal Logic
function openReceiptModal(src, caption) {
    const modal = document.getElementById('receiptModal');
    const modalImg = document.getElementById('modalImage');
    const modalCaption = document.getElementById('modalCaption');

    modal.classList.add('show');
    modalImg.src = src;
    modalCaption.textContent = caption;
    document.body.style.overflow = 'hidden'; // Prevent scroll
}

function closeReceiptModal() {
    const modal = document.getElementById('receiptModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// Close modal on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeReceiptModal();
});
