<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $counsellor_id = $_POST['counsellor_id'];
    $slot_date = $_POST['slot_date'];
    $slot_time = $_POST['slot_time'];

    try {
        $stmt = $pdo->prepare("INSERT INTO counsellor_slots (counsellor_id, slot_date, slot_time) VALUES (?,?,?)");
        $stmt->execute([$counsellor_id, $slot_date, $slot_time]);
        $success = "Slot created successfully!";
    } catch (Exception $e) {
        $error = "Slot already exists or error: " . $e->getMessage();
    }
}

// Fetch counsellors
$cstmt = $pdo->query("SELECT * FROM counsellors ORDER BY username");
$counsellors = $cstmt->fetchAll();
?>

<h2>Create Slot</h2>
<?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
<?php if(isset($success)) echo "<p style='color:green'>$success</p>"; ?>

<form method="POST">
    <label>Counsellor:</label>
    <select username="counsellor_id" required>
        <option value="">Select</option>
        <?php foreach($counsellors as $c): ?>
            <option value="<?= $c['id'] ?>"><?= $c['username'] ?></option>
        <?php endforeach; ?>
    </select><br>

    <label>Date:</label>
    <input type="date" username="slot_date" required><br>

    <label>Time:</label>
    <input type="time" username="slot_time" required><br>

    <button type="submit">Create Slot</button>
</form>