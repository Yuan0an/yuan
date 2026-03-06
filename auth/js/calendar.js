// Day click handler
document.querySelectorAll('.calendar-day:not(.empty)').forEach(function (day) {
    day.addEventListener('click', function () {
        const calendar = day.closest('.admin-calendar');
        const monthPrefix = calendar.dataset.monthPrefix;
        const yearSuffix = calendar.dataset.yearSuffix;
        const dayNumber = this.querySelector('.day-number').textContent;

        const date = monthPrefix + dayNumber + yearSuffix;
        const reservations = this.querySelector('.day-reservations');

        document.getElementById('modalDate').textContent = 'Bookings for ' + date;

        if (reservations) {
            document.getElementById('modalContent').innerHTML = reservations.innerHTML;
        } else {
            document.getElementById('modalContent').innerHTML =
                '<p>No bookings for this date.</p>';
        }

        document.getElementById('dayDetails').style.display = 'block';
    });
});

// Close modal
document.querySelector('.close').addEventListener('click', function () {
    document.getElementById('dayDetails').style.display = 'none';
});

// Close modal when clicking outside
window.addEventListener('click', function (event) {
    const modal = document.getElementById('dayDetails');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
});
