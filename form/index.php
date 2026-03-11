<?php
require 'config.php';
include_once __DIR__ . '/../includes/header.php';

// Get all events
$sql = "SELECT id, name, start_time, end_time, max_persons, is_overnight, pricing_logic FROM events";
$result = $conn->query($sql);

// Get all active addons
$addons_sql = "SELECT * FROM addons WHERE is_active = 1";
$addons_result = $conn->query($addons_sql);
$addons = [];
while ($row = $addons_result->fetch_assoc()) {
    $addons[] = $row;
}

// Get all active payment methods
$pm_sql = "SELECT * FROM payment_methods WHERE is_active = 1";
$pm_result = $conn->query($pm_sql);
$payment_methods = [];
while ($row = $pm_result->fetch_assoc()) {
    $payment_methods[] = $row;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Event Reservation System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container" id="container">
        <h1><i class="fas fa-calendar-check"></i> Event Reservation System</h1>
        <p class="subtitle">Reserve your desired event. First to submit down payment gets approval.</p>

        <div class="main-content">
            <!-- Left: Event Selection -->
            <div class="left-panel">
                <h2><i class="fas fa-calendar-alt"></i> 1. Select Event Type</h2>
                <div class="event-list">
                    <?php while ($event = $result->fetch_assoc()):
                        $is_overnight = $event['is_overnight'];
                        $end_time_display = $is_overnight ?
                            "Next day " . date('g:i A', strtotime($event['end_time'])) :
                            date('g:i A', strtotime($event['end_time']));
                        ?>
                        <div class="event-item" data-id="<?php echo $event['id']; ?>"
                            data-name="<?php echo htmlspecialchars($event['name'] ?? ''); ?>"
                            data-start="<?php echo $event['start_time']; ?>" data-end="<?php echo $event['end_time']; ?>"
                            data-overnight="<?php echo $is_overnight; ?>" data-max="<?php echo $event['max_persons']; ?>"
                            data-pricing='<?php echo htmlspecialchars($event['pricing_logic'] ?? "{}"); ?>'>
                            <h3><?php echo htmlspecialchars($event['name'] ?? ''); ?></h3>
                            <p><i class="far fa-clock"></i> <?php echo date('g:i A', strtotime($event['start_time'])); ?> to
                                <?php echo $end_time_display; ?>
                            </p>
                            <p><i class="fas fa-users"></i> Capacity: <?php echo $event['max_persons']; ?> persons</p>
                            <?php if ($is_overnight): ?>
                                <span class="overnight-badge"><i class="fas fa-moon"></i> Overnight</span>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Right: Calendar -->
            <div class="right-panel">
                <h2><i class="far fa-calendar"></i> 2. Select Date</h2>

                <div class="calendar-controls">
                    <button id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                    <h3 id="currentMonth"></h3>
                    <button id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                </div>

                <div id="calendar"></div>

                <!-- Time Slots with Pending Info -->
                <div class="time-slots" id="timeSlots" style="display:none;">
                    <h3><i class="fas fa-clock"></i> 3. Available Time Slot for <span id="selectedDateText"></span></h3>
                    <div class="pending-info" id="pendingInfo" style="display:none;">
                        <div class="pending-alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span id="pendingCount">0</span> pending reservation(s) for this time slot.
                            First to submit down payment gets approval.
                        </div>
                    </div>
                    <div id="slotsContainer"></div>
                </div>

                <div class="availability-info" id="availabilityInfo"></div>
            </div>
        </div>

        <!-- Reservation Form -->
        <div class="reservation-form" id="reservationForm" style="display:none;">
            <h2><i class="fas fa-edit"></i> 4. Submit Reservation Request</h2>
            <p class="form-subtitle">Complete this form to request a reservation. Your booking will be pending until
                down payment is received.</p>

            <form id="reservationFormElement" method="POST" action="submit_reservation.php">
                <input type="hidden" name="event_id" id="formEventId">
                <input type="hidden" name="booking_date" id="formDate">
                <input type="hidden" name="start_time" id="formStartTime">
                <input type="hidden" name="end_time" id="formEndTime">

                <!-- Customer Information Section -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Customer Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>

                        <div class="form-group">
                            <label for="alt_phone">Alternative Contact Number (Optional)</label>
                            <input type="tel" id="alt_phone" name="alt_phone">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="company">Organization/Company (Optional)</label>
                            <input type="text" id="company" name="company">
                        </div>
                    </div>
                </div>

                <!-- Event Details Section -->
                <div class="form-section">
                    <h3><i class="fas fa-calendar-check"></i> Event Details</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Selected Event:</label>
                            <div class="display-field" id="displayEventName"></div>
                        </div>

                        <div class="form-group">
                            <label>Selected Date & Time:</label>
                            <div class="display-field" id="displayDateTime"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="event_title">Event Name/Title *</label>
                            <input type="text" id="event_title" name="event_title" required>
                        </div>

                        <div class="form-group">
                            <label for="event_type">Type of Event *</label>
                            <select id="event_type" name="event_type" required>
                                <option value="">Select event type</option>
                                <option value="Corporate">Corporate Event</option>
                                <option value="Birthday">Birthday Party</option>
                                <option value="Wedding">Wedding</option>
                                <option value="Family">Family Gathering</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="guests">Expected Number of Guests *</label>
                            <input type="number" id="guests" name="guests" min="1" value="1" required>
                        </div>
                    </div>
                </div>

                <!-- Add-ons Section -->
                <div class="form-section">
                    <h3><i class="fas fa-plus-circle"></i> Add-ons</h3>
                    <p class="subtitle">Select additional items for your event.</p>

                    <div class="addons-grid">
                        <?php foreach ($addons as $addon): ?>
                            <?php if ($addon['type'] === 'counter'): ?>
                                <div class="addon-card">
                                    <div class="addon-header"><?php echo htmlspecialchars($addon['name']); ?></div>
                                    <?php if (!empty($addon['description'])): ?>
                                        <div class="addon-desc"><?php echo htmlspecialchars($addon['description']); ?></div>
                                    <?php endif; ?>
                                    <div class="addon-price">P<?php echo number_format($addon['price'], 0); ?></div>
                                    <input type="hidden" name="addon_<?php echo $addon['id']; ?>_price" value="<?php echo $addon['price']; ?>">
                                    <div class="addon-control">
                                        <button type="button" class="counter-btn" onclick="updateCounter('<?php echo $addon['id']; ?>', -1)">-</button>
                                        <input type="number" id="addon_<?php echo $addon['id']; ?>" name="addon_<?php echo $addon['id']; ?>" value="0" min="0" readonly
                                            class="counter-input" data-price="<?php echo $addon['price']; ?>">
                                        <button type="button" class="counter-btn" onclick="updateCounter('<?php echo $addon['id']; ?>', 1)">+</button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="addon-card checkbox-card" onclick="toggleCheckbox('<?php echo $addon['id']; ?>')">
                                    <div class="addon-header"><?php echo htmlspecialchars($addon['name']); ?></div>
                                    <?php if (!empty($addon['description'])): ?>
                                        <div class="addon-desc"><?php echo htmlspecialchars($addon['description']); ?></div>
                                    <?php endif; ?>
                                    <div class="addon-price">P<?php echo number_format($addon['price'], 0); ?></div>
                                    <input type="hidden" name="addon_<?php echo $addon['id']; ?>_price" value="<?php echo $addon['price']; ?>">
                                    <div class="addon-control">
                                        <input type="checkbox" id="addon_<?php echo $addon['id']; ?>" name="addon_<?php echo $addon['id']; ?>" value="1" data-price="<?php echo $addon['price']; ?>">
                                        <label for="addon_<?php echo $addon['id']; ?>">Add</label>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-section summary-section">
                    <h3><i class="fas fa-file-invoice-dollar"></i> Reservation Summary</h3>
                    <div class="summary-details">
                        <div class="summary-item">
                            <span>Base Rate (<span id="summary-event-type">Event</span>)</span>
                            <span id="summary-base-rate">P0.00</span>
                        </div>
                        <div class="summary-item">
                            <span>Add-ons Total</span>
                            <span id="summary-addons-total">P0.00</span>
                        </div>
                        <div class="summary-item total-row">
                            <span>Grand Total</span>
                            <span id="summary-grand-total">P0.00</span>
                        </div>
                        <div class="summary-item downpayment-row">
                            <span>Downpayment Required (50%)</span>
                            <span id="summary-downpayment">P0.00</span>
                        </div>
                        <p class="summary-note"><i class="fas fa-info-circle"></i> Remaining balance will be settled at
                            the venue.</p>
                    </div>
                </div>

                <!-- Payment Method Section -->
                <div class="form-section">
                    <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
                    <p class="payment-note"><i class="fas fa-info-circle"></i> Your reservation will be
                        <strong>pending</strong> until down payment is received and approved by admin.
                    </p>

                    <div class="payment-grid">
                        <?php foreach ($payment_methods as $pm): ?>
                            <div class="payment-card" onclick="selectPayment('<?php echo htmlspecialchars($pm['name']); ?>', 'pm_<?php echo $pm['id']; ?>')">
                                <input type="radio" id="pm_<?php echo $pm['id']; ?>" name="payment_method" value="<?php echo htmlspecialchars($pm['name']); ?>" required
                                    style="display:none;">
                                <div class="payment-card-icon"><i class="<?php echo htmlspecialchars($pm['icon']); ?>"></i></div>
                                <div class="payment-card-title"><?php echo htmlspecialchars($pm['name']); ?></div>
                                <div class="payment-card-details">
                                    <?php echo nl2br(htmlspecialchars($pm['details'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Terms & Conditions -->
                <div class="form-section">
                    <h3><i class="fas fa-file-contract"></i> Terms & Conditions</h3>

                    <div class="terms-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            I agree to the <a href="#" onclick="openModal('terms.html', 'Terms & Conditions'); return false;">Terms & Conditions</a>
                        </label>
                    </div>

                    <div class="terms-group">
                        <input type="checkbox" id="cancellation" name="cancellation" required>
                        <label for="cancellation">
                            I understand the <a href="#" onclick="openModal('cancellation.html', 'Cancellation Policy'); return false;">Cancellation Policy</a>
                        </label>
                    </div>
                </div>

                <input type="hidden" name="total_price_hidden" id="total_price_hidden" value="0">

                <!-- Form Buttons -->
                <div class="form-buttons">
                    <button type="button" class="btn cancel-btn" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn submit-btn" id="submitBtn" disabled>Submit Reservation
                        Request</button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <!-- Generic Modal -->
    <div id="infoModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Page Title</h3>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <iframe id="modalIframe" class="modal-iframe" src=""></iframe>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Modal Functions
        function openModal(url, title) {
            $('#modalTitle').text(title);
            $('#modalIframe').attr('src', url);
            $('#infoModal').css('display', 'flex');
            // Prevent body scrolling
            $('body').css('overflow', 'hidden');
        }

        function closeModal() {
            $('#infoModal').hide();
            $('#modalIframe').attr('src', '');
            // Restore body scrolling
            $('body').css('overflow', '');
        }

        // Close modal when clicking outside
        $(document).ready(function() {
            $('#infoModal').click(function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
        });

        // Global variables for reservation state
        let selectedEventId = null;
        let selectedEventName = '';
        let selectedEventStart = '';
        let selectedEventEnd = '';
        let selectedEventPricing = null;
        let isOvernight = false;

        $(document).ready(function () {
            let currentMonth = new Date().getMonth() + 1;
            let currentYear = new Date().getFullYear();
            let selectedDate = null;

            // Event selection
            $('.event-item').click(function () {
                $('.event-item').removeClass('selected');
                $(this).addClass('selected');

                selectedEventId = $(this).data('id');
                selectedEventName = $(this).data('name');
                selectedEventStart = $(this).data('start');
                selectedEventEnd = $(this).data('end');
                selectedEventPricing = $(this).data('pricing');
                isOvernight = $(this).data('overnight') == 1;

                loadCalendar();
                $('#timeSlots').hide();
                $('#reservationForm').hide();
            });

            // Month navigation
            $('#prevMonth').click(function () {
                currentMonth--;
                if (currentMonth < 1) {
                    currentMonth = 12;
                    currentYear--;
                }
                loadCalendar();
            });

            $('#nextMonth').click(function () {
                currentMonth++;
                if (currentMonth > 12) {
                    currentMonth = 1;
                    currentYear++;
                }
                loadCalendar();
            });

            // Load calendar
            function loadCalendar() {
                if (!selectedEventId) {
                    return;
                }

                $.post('get_calendar.php', {
                    month: currentMonth,
                    year: currentYear,
                    event_id: selectedEventId
                }, function (data) {
                    $('#calendar').html(data.calendar);
                    $('#currentMonth').text(data.monthName);

                    // Add click event to dates
                    $('.calendar-day:not(.past):not(.approved)').click(function () {
                        $('.calendar-day').removeClass('selected');
                        $(this).addClass('selected');

                        selectedDate = $(this).data('date');
                        loadTimeSlotInfo(selectedDate);
                    });
                }, 'json');
            }

            // Load time slot with pending info
            function loadTimeSlotInfo(date) {
                $('#selectedDateText').text(date);
                $('#timeSlots').show();
                $('#slotsContainer').html('<p>Checking availability...</p>');

                $.post('check_time_slot.php', {
                    date: date,
                    event_id: selectedEventId,
                    start_time: selectedEventStart,
                    end_time: selectedEventEnd,
                    is_overnight: isOvernight
                }, function (data) {
                    $('#slotsContainer').html(data.slot_html);

                    // Show pending reservations info
                    if (data.pending_count > 0) {
                        $('#pendingCount').text(data.pending_count);
                        $('#pendingInfo').show();
                    } else {
                        $('#pendingInfo').hide();
                    }

                    // Check if approved reservation exists
                    if (data.has_approved) {
                        $('#slotsContainer').html(
                            '<div class="unavailable-slot">' +
                            '<i class="fas fa-ban"></i> This time slot has an approved reservation.' +
                            '</div>'
                        );
                        return;
                    }

                    // Add click event to time slot
                    $('.time-slot').click(function () {
                        $('.time-slot').removeClass('selected');
                        $(this).addClass('selected');
                        showReservationForm(date);
                    });

                }, 'json');
            }

            // Show reservation form
            function showReservationForm(date) {
                // Set form values
                $('#formEventId').val(selectedEventId);
                $('#formDate').val(date);
                $('#formStartTime').val(selectedEventStart);
                $('#formEndTime').val(selectedEventEnd);

                // Format display
                let startDisplay = formatTime(selectedEventStart);
                let endDisplay = isOvernight ?
                    "Next day " + formatTime(selectedEventEnd) :
                    formatTime(selectedEventEnd);

                $('#displayEventName').text(selectedEventName);
                $('#displayDateTime').text(date + ' from ' + startDisplay + ' to ' + endDisplay);

                $('#reservationForm').show();
                $('html, body').animate({
                    scrollTop: $('#reservationForm').offset().top
                }, 500);
            }

            // Helper function to format time
            function formatTime(timeStr) {
                return new Date('2000-01-01 ' + timeStr).toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }

            // Update submit button based on checkboxes
            function updateSubmitButton() {
                const termsChecked = $('#terms').is(':checked');
                const cancellationChecked = $('#cancellation').is(':checked');

                if (termsChecked && cancellationChecked) {
                    $('#submitBtn').prop('disabled', false);
                } else {
                    $('#submitBtn').prop('disabled', true);
                }
            }

            // Checkbox change events
            $('#terms, #cancellation').change(function () {
                updateSubmitButton();
            });

            // Cancel button
            $('#cancelBtn').click(function () {
                $('#reservationForm').hide();
                $('.calendar-day').removeClass('selected');
                $('.time-slot').removeClass('selected');
                $('#timeSlots').hide();
                $('#availabilityInfo').html('');
            });

            // Form submission
            $('#reservationFormElement').submit(function (e) {
                e.preventDefault();

                // Show loading
                $('#submitBtn').html('Submitting...').prop('disabled', true);

                // Submit form
                $.post('submit_reservation.php', $(this).serialize(), function (response) {
                    if (response.success) {
                        // Redirect to detailed success page
                        window.location.href = 'reservation_success.php?id=' + response.reservation_id;
                    } else {
                        alert('Error: ' + response.message);
                        $('#submitBtn').html('Submit Reservation Request').prop('disabled', false);
                    }
                }, 'json').fail(function () {
                    alert('Network error. Please try again.');
                    $('#submitBtn').html('Submit Reservation Request').prop('disabled', false);
                });
            });

            function resetAll() {
                $('#reservationFormElement')[0].reset();
                $('#reservationForm').hide();
                $('.event-item').removeClass('selected');
                $('.calendar-day').removeClass('selected');
                $('.time-slot').removeClass('selected');
                $('#timeSlots').hide();
                $('#availabilityInfo').html('');
                selectedEventId = null;
                selectedDate = null;
            }

            // Initial load deleted - user must select event type first
        });

        // Add-on Helper Functions (Global Scope)
        function updateCounter(type, change) {
            let input = document.getElementById('addon_' + type);
            let currentValue = parseInt(input.value) || 0;
            let newValue = currentValue + change;

            if (newValue < 0) newValue = 0;

            input.value = newValue;

            // Highlight card if value > 0
            let card = input.closest('.addon-card');
            if (newValue > 0) {
                card.style.borderColor = '#4CAF50';
                card.style.backgroundColor = '#f9fff9';
            } else {
                card.style.borderColor = '';
                card.style.backgroundColor = '';
            }
            calculateTotal();
        }

        function toggleCheckbox(type) {
            let checkbox = document.getElementById('addon_' + type);
            // Toggle only if the click didn't come from the checkbox itself (to avoid double toggle)
            if (event.target !== checkbox && event.target.tagName !== 'LABEL') {
                checkbox.checked = !checkbox.checked;
            }

            updateCheckboxStyle(checkbox);
        }

        // Add event listener to checkboxes to handle direct clicks
        $(document).ready(function () {
            $('.checkbox-card input[type="checkbox"]').change(function () {
                updateCheckboxStyle(this);
            });
        });
        function updateCheckboxStyle(checkbox) {
            let card = checkbox.closest('.addon-card');
            if (checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
            calculateTotal();
        }
        function selectPayment(method, id) {
            // Unselect all cards
            $('.payment-card').removeClass('selected');
            
            // Uncheck all radios
            $('.payment-grid input[type="radio"]').prop('checked', false);

            // Select the clicked card and its radio button
            $('#' + id).prop('checked', true);
            $('#' + id).closest('.payment-card').addClass('selected');
        }

        // Pricing Calculation Logic
        function calculateTotal() {
            if (!selectedEventName) return;

            let pax = parseInt($('#guests').val()) || 0;
            let baseRate = 0;

            // 1. Calculate Base Rate
            if (selectedEventPricing && selectedEventPricing.tiers) {
                // Find matching tier
                for (let tier of selectedEventPricing.tiers) {
                    if (pax <= tier.max) {
                        baseRate = tier.price;
                        break;
                    }
                }
                // If no tier found but pax exists, take the last (highest) tier
                if (baseRate === 0 && selectedEventPricing.tiers.length > 0) {
                    baseRate = selectedEventPricing.tiers[selectedEventPricing.tiers.length - 1].price;
                }
            }

            // 2. Calculate Add-ons
            let addonsTotal = 0;

            // Loop through all counter inputs and checkboxes that have a data-price attribute
            $('.counter-input, .addons-grid input[type="checkbox"]').each(function() {
                let price = parseFloat($(this).data('price')) || 0;
                if ($(this).attr('type') === 'checkbox') {
                    if ($(this).is(':checked')) addonsTotal += price;
                } else {
                    addonsTotal += (parseInt($(this).val()) || 0) * price;
                }
            });

            // 3. Update UI
            let grandTotal = baseRate + addonsTotal;
            let downpayment = grandTotal * 0.5;

            $('#summary-event-type').text(selectedEventName);
            $('#summary-base-rate').text('P' + baseRate.toLocaleString());
            $('#summary-addons-total').text('P' + addonsTotal.toLocaleString());
            $('#summary-grand-total').text('P' + grandTotal.toLocaleString());
            $('#summary-downpayment').text('P' + downpayment.toLocaleString());

            // Set hidden field for submission
            $('#total_price_hidden').val(grandTotal);
        }

        // Bind inputs to recalculate
        $(document).ready(function () {
            $('#guests').on('input change', calculateTotal);
            // Trigger initial calculation when form is shown
            const originalShowForm = showReservationForm;
            window.showReservationForm = function (date) {
                originalShowForm(date);
                calculateTotal();
            };
        });
    </script>
    <script src="/assets/js/script.js"></script>
</body>

</html>