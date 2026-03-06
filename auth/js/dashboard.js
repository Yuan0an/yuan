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

// Auto-refresh dashboard every 60 seconds
setTimeout(function () {
    window.location.reload();
}, 60000);
