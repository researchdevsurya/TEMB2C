<?php
session_start();
require '../db.php';

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

/* IF FORM SUBMITTED */
if(isset($_POST['event_id'])){

    $event_id = $_POST['event_id'];

    /* GET EVENT DETAILS */
    $stmt = $pdo->prepare("SELECT * FROM schedule_master WHERE id=?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if(!$event){
        $_SESSION['error_msg'] = "Event not found.";
        header("Location: export_event_excel.php");
        exit;
    }

    $filename = "TEM_Event_" . preg_replace('/\s+/', '_', $event['event_name']) . "_Full_Report.csv";

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=$filename");

    $output = fopen("php://output", "w");

    /* HEADERS */
    fputcsv($output, [
        'Student Name',
        'Email',
        'Contact Number',
        'Gender',
        'Class',
        'Board',
        'Stream',
        'Specialization',
        'School Name',
        'Date of Birth',
        'Address',

        'Event Name',
        'Psychometric Date',

        'Group Session 1',
        'Group Session 1 Date',

        'Group Session 2',
        'Group Session 2 Date',

        'Counselling Date',
        'Time Slot',
        'Counsellor',
        'Payment Status',
        'Booking Created'
    ]);

    /* FETCH BOOKINGS WITH FULL STUDENT DETAILS */
    $stmt = $pdo->prepare("
        SELECT 
            sb.*,
            s.username,
            s.email,
            s.contact_number,
            s.gender,
            s.std as s_std,
            s.board as s_board,
            s.stream as s_stream,
            s.specialization as s_specialization,
            s.school_name,
            s.dob,
            s.address,
            sm.event_name
        FROM student_bookings sb
        JOIN students s ON s.id = sb.student_id
        JOIN schedule_master sm ON sm.id = sb.schedule_id
        WHERE sb.schedule_id=?
        ORDER BY sb.booked_date ASC
    ");
    $stmt->execute([$event_id]);

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        
        // Use data from booking if available, otherwise fallback to student record
        $class = $row['class_std'] ?: $row['s_std'];
        $board = $row['board'] ?: $row['s_board'];
        $stream = $row['stream'] ?: $row['s_stream'];
        $spec = $row['specialization'] ?: $row['s_specialization'];

        fputcsv($output, [
            $row['username'],
            $row['email'],
            $row['contact_number'],
            $row['gender'],
            $class,
            $board,
            $stream,
            $spec,
            $row['school_name'],
            $row['dob'],
            $row['address'],

            $row['event_name'],
            $row['selected_psychometric_date'],

            $row['group_session1'],
            $row['group_session1_date'],

            $row['group_session2'],
            $row['group_session2_date'],

            $row['booked_date'],
            $row['one_to_one_slot'],
            $row['counsellor_name'],
            $row['payment_status'],
            $row['created_at']
        ]);
    }

    fclose($output);
    exit;
}

/* LOAD EVENTS */
$events = $pdo->query("SELECT id,event_name FROM schedule_master ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<title>Export Event Excel</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex">

<?php include 'sidebar.php'; ?>

<div class="flex-1 p-8">

<h1 class="text-2xl font-bold mb-6">Export Full Event Report</h1>

<div class="bg-white p-8 rounded-xl shadow max-w-lg">

<form method="POST" class="space-y-4">

<label class="font-semibold">Select Event</label>

<select name="event_id" required class="w-full p-3 border rounded">
    <option value="">-- Choose Event --</option>
    <?php foreach($events as $e): ?>
        <option value="<?= $e['id'] ?>">
            <?= htmlspecialchars($e['event_name']) ?>
        </option>
    <?php endforeach; ?>
</select>

<button class="bg-green-600 text-white px-6 py-3 rounded w-full font-semibold">
Download Full Excel Report
</button>

</form>

</div>
</div>
</body>
</html>
