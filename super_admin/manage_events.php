<?php
// super_admin/manage_events.php
require_once 'auth_check.php';
require_once '../form/config.php';

$message = '';

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $name = $_POST['name'];
            $start = $_POST['start_time'];
            $end = $_POST['end_time'];
            $max = $_POST['max_persons'];
            $overnight = isset($_POST['is_overnight']) ? 1 : 0;
            $pricing = $_POST['pricing_logic'];

            if ($_POST['action'] === 'add') {
                $stmt = $conn->prepare("INSERT INTO events (name, start_time, end_time, max_persons, is_overnight, pricing_logic) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiis", $name, $start, $end, $max, $overnight, $pricing);
            } else {
                $id = $_POST['id'];
                $stmt = $conn->prepare("UPDATE events SET name=?, start_time=?, end_time=?, max_persons=?, is_overnight=?, pricing_logic=? WHERE id=?");
                $stmt->bind_param("sssiisi", $name, $start, $end, $max, $overnight, $pricing, $id);
            }
            
            if ($stmt->execute()) {
                $message = "Event " . ($_POST['action'] === 'add' ? "added" : "updated") . " successfully!";
            } else {
                $message = "Error: " . $conn->error;
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM events WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "Event deleted successfully!";
            } else {
                $message = "Error: " . $conn->error;
            }
        }
    }
}

$events = $conn->query("SELECT * FROM events");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Event Types</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .event-card { background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .btn { padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; border: none; font-weight: 600; }
        .btn-primary { background: var(--sa-primary); color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; padding: 2rem; border-radius: 12px; width: 500px; max-height: 90vh; overflow-y: auto; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        input, select, textarea { width: 100%; padding: 0.6rem; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; }
        .pricing-help { font-size: 0.8rem; color: #64748b; margin-top: 0.2rem; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1>Manage Event Types</h1>
                <p>Configure pricing and capacity for each event type.</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('add')"><i class="fas fa-plus"></i> Add New Event</button>
        </header>

        <?php if ($message): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="event-list">
            <?php while($row = $events->fetch_assoc()): ?>
                <div class="event-card">
                    <div>
                        <h3 style="margin:0;"><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p style="margin:5px 0; color:#64748b;">
                            <i class="far fa-clock"></i> <?php echo date('g:i A', strtotime($row['start_time'])); ?> - <?php echo date('g:i A', strtotime($row['end_time'])); ?>
                            <?php if ($row['is_overnight']): ?> <span style="color:#3b82f6;">(Overnight)</span><?php endif; ?>
                            | <i class="fas fa-users"></i> Max: <?php echo $row['max_persons']; ?>
                        </p>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-primary" onclick='editEvent(<?php echo json_encode($row); ?>)'>Edit</button>
                        <form method="POST" onsubmit="return confirm('Are you sure?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <div id="eventModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add Event</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="eventId">
                <div class="form-group">
                    <label>Event Name</label>
                    <input type="text" name="name" id="eventName" required>
                </div>
                <div class="form-row" style="display: flex; gap: 1rem;">
                    <div class="form-group" style="flex:1;">
                        <label>Start Time</label>
                        <input type="time" name="start_time" id="eventStart" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>End Time</label>
                        <input type="time" name="end_time" id="eventEnd" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Max Persons</label>
                    <input type="number" name="max_persons" id="eventMax" required>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="is_overnight" id="eventOvernight" style="width: auto;">
                    <label style="margin:0;">Is Overnight?</label>
                </div>
                <div class="form-group">
                    <label>Pricing Logic (JSON)</label>
                    <textarea name="pricing_logic" id="eventPricing" rows="8" placeholder='{"tiers": [{"max": 20, "price": 6000}, {"max": 30, "price": 7000}]}' required></textarea>
                    <p class="pricing-help">Example: {"tiers": [{"max": 20, "price": 6000}, {"max": 70, "price": 7000}]}</p>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Save Event</button>
                    <button type="button" class="btn" style="flex:1; background: #e2e8f0;" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(action) {
            document.getElementById('eventModal').style.display = 'flex';
            document.getElementById('formAction').value = action;
            document.getElementById('modalTitle').innerText = action === 'add' ? 'Add Event' : 'Edit Event';
            if (action === 'add') {
                document.getElementById('eventId').value = '';
                document.getElementById('eventName').value = '';
                document.getElementById('eventStart').value = '';
                document.getElementById('eventEnd').value = '';
                document.getElementById('eventMax').value = '';
                document.getElementById('eventOvernight').checked = false;
                document.getElementById('eventPricing').value = '';
            }
        }
        function closeModal() { document.getElementById('eventModal').style.display = 'none'; }
        function editEvent(data) {
            openModal('edit');
            document.getElementById('eventId').value = data.id;
            document.getElementById('eventName').value = data.name;
            document.getElementById('eventStart').value = data.start_time;
            document.getElementById('eventEnd').value = data.end_time;
            document.getElementById('eventMax').value = data.max_persons;
            document.getElementById('eventOvernight').checked = data.is_overnight == 1;
            document.getElementById('eventPricing').value = data.pricing_logic || '';
        }
    </script>
</body>
</html>
