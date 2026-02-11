<?php
session_start();
require 'db.php';

if(!isset($_SESSION['student_id'])){
    header("Location: login.php");
    exit;
}

/* GET LATEST EVENT */
$schedule = $pdo->query("SELECT * FROM schedule_master ORDER BY id DESC LIMIT 1")->fetch();

if(!$schedule){
    die("No active event available");
}

/* GENERATE TIME SLOTS */
function generateSlots($start="10:00",$end="17:00",$interval=30){
    $slots=[];
    $current=strtotime($start);
    $endTime=strtotime($end);

    while($current < $endTime){
        $slots[] = date("H:i",$current);
        $current = strtotime("+$interval minutes",$current);
    }
    return $slots;
}

$slots = generateSlots();

/* AJAX: RETURN BOOKED SLOTS BY DATE + COUNSELLOR */
if(isset($_GET['get_slots']) && isset($_GET['date']) && isset($_GET['counsellor'])){

    $date = $_GET['date'];
    $counsellor = $_GET['counsellor'];

    $q = $pdo->prepare("
        SELECT one_to_one_slot 
        FROM student_bookings
        WHERE schedule_id=?
        AND booked_date=?
        AND counsellor_name=?
    ");
    $q->execute([$schedule['id'], $date, $counsellor]);

    echo json_encode($q->fetchAll(PDO::FETCH_COLUMN));
    exit;
}

/* HANDLE BOOKING */
if($_SERVER['REQUEST_METHOD']=="POST"){

    $psy = $_POST['psy_date'] ?? "";
    $sessions = $_POST['sessions'] ?? [];
    $slot = $_POST['slot'] ?? "";
    $slot_date = $_POST['slot_date'] ?? "";
    $counsellor = $_POST['counsellor_name'] ?? "";

    if(!$psy){
        $error="Select psychometric date";
    }
    elseif(count($sessions) != 2){
        $error="Select exactly 2 group sessions";
    }
    elseif(!$slot || !$slot_date){
        $error="Select counselling date & slot";
    }
    elseif(!$counsellor){
        $error="Select counsellor";
    }
    else{

        /* CHECK STUDENT ALREADY BOOKED */
        $already = $pdo->prepare("
            SELECT id FROM student_bookings 
            WHERE student_id=? AND schedule_id=?
        ");
        $already->execute([$_SESSION['student_id'],$schedule['id']]);

        if($already->rowCount() > 0){
            $error = "You have already booked this event.";
        }
        else{

            /* CHECK SLOT FOR SAME COUNSELLOR */
            $slotCheck = $pdo->prepare("
                SELECT id FROM student_bookings 
                WHERE schedule_id=?
                AND booked_date=?
                AND one_to_one_slot=?
                AND counsellor_name=?
            ");
            $slotCheck->execute([
                $schedule['id'],
                $slot_date,
                $slot,
                $counsellor
            ]);

            if($slotCheck->rowCount()>0){
                $error = "This time slot is already booked for selected counsellor.";
            }
            else{

                /* INSERT BOOKING */
                $stmt=$pdo->prepare("INSERT INTO student_bookings
                (student_id,schedule_id,selected_psychometric_date,
                 group_session1,group_session2,
                 one_to_one_slot,booked_date,counsellor_name)
                VALUES (?,?,?,?,?,?,?,?)");

                $stmt->execute([
                    $_SESSION['student_id'],
                    $schedule['id'],
                    $psy,
                    $sessions[0],
                    $sessions[1],
                    $slot,
                    $slot_date,
                    $counsellor
                ]);

                $booking_id = $pdo->lastInsertId();

                header("Location: payment.php?booking_id=".$booking_id);
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen p-8">

<div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow">

<h2 class="text-2xl font-bold mb-6">
<?= htmlspecialchars($schedule['event_name']) ?>
</h2>

<?php if(isset($error)): ?>
<div class="bg-red-100 text-red-700 p-3 mb-4 rounded">
<?= $error ?>
</div>
<?php endif; ?>

<form method="POST" class="space-y-6">

<!-- SECTION 1 -->
<div>
<h3 class="text-lg font-semibold mb-2">Section 1 — Psychometric Test</h3>

<label class="block">
<input type="radio" name="psy_date" value="<?= $schedule['psychometric_date1'] ?>" required>
<?= $schedule['psychometric_date1'] ?>
</label>

<label class="block">
<input type="radio" name="psy_date" value="<?= $schedule['psychometric_date2'] ?>">
<?= $schedule['psychometric_date2'] ?>
</label>
</div>

<!-- SECTION 2 -->
<div>
<h3 class="text-lg font-semibold mb-2">Section 2 — Select 2 Group Sessions</h3>

<label class="block">
<input type="checkbox" class="session-check" name="sessions[]" value="<?= $schedule['block1_session1'] ?>">
<?= $schedule['block1_session1'] ?> (<?= $schedule['block1_date1'] ?>)
</label>

<label class="block">
<input type="checkbox" class="session-check" name="sessions[]" value="<?= $schedule['block1_session2'] ?>">
<?= $schedule['block1_session2'] ?> (<?= $schedule['block1_date2'] ?>)
</label>

<label class="block">
<input type="checkbox" class="session-check" name="sessions[]" value="<?= $schedule['block2_session1'] ?>">
<?= $schedule['block2_session1'] ?> (<?= $schedule['block2_date1'] ?>)
</label>

<label class="block">
<input type="checkbox" class="session-check" name="sessions[]" value="<?= $schedule['block2_session2'] ?>">
<?= $schedule['block2_session2'] ?> (<?= $schedule['block2_date2'] ?>)
</label>

<p class="text-sm text-gray-500 mt-1">Select exactly 2 sessions</p>
</div>

<!-- SECTION 3 -->
<div>
<h3 class="text-lg font-semibold mb-2">Section 3 — 1:1 Counselling</h3>


<select name="counsellor_name" id="counsellor_select" required class="w-full mb-3 p-3 border rounded">
    <option value="">Select Counsellor</option>
    <option value="David Ipe">David Ipe</option>
    <option value="Medhavi Mam">Medhavi Mam</option>
</select>

<br>

<input type="date" id="slot_date" name="slot_date"
min="<?= $schedule['counselling_from'] ?>"
max="<?= $schedule['counselling_to'] ?>"
required
class="w-full p-3 border rounded mb-3">

<select name="slot" id="slot_select" class="w-full p-3 border rounded mb-3">
<?php foreach($slots as $s): ?>
<option value="<?= $s ?>"><?= $s ?></option>
<?php endforeach; ?>
</select>



<p class="text-xs text-gray-500 mt-2">🔴 Booked slots are disabled</p>
</div>

<button class="bg-blue-600 text-white w-full p-3 rounded font-semibold">
Proceed to Payment
</button>

</form>
</div>

</body>

<script>
/* LIMIT 2 GROUP SESSION CHECKBOX */
const checks = document.querySelectorAll('.session-check');
checks.forEach(chk => {
    chk.addEventListener('change', () => {
        const checked = document.querySelectorAll('.session-check:checked');
        if (checked.length > 2) {
            chk.checked = false;
            alert("You can select only 2 sessions");
        }
    });
});

/* LOAD BOOKED SLOTS BY DATE + COUNSELLOR */
const dateInput = document.getElementById('slot_date');
const counsellorSelect = document.getElementById('counsellor_select');
const slotSelect = document.getElementById('slot_select');

function loadSlots(){
    const date = dateInput.value;
    const counsellor = counsellorSelect.value;

    if(!date || !counsellor) return;

    fetch(`?get_slots=1&date=${date}&counsellor=${encodeURIComponent(counsellor)}`)
    .then(res => res.json())
    .then(booked => {

        [...slotSelect.options].forEach(opt => {
            opt.textContent = opt.value;
            opt.disabled = false;

            if(booked.includes(opt.value)){
                opt.textContent = '🔴 ' + opt.value + ' (Booked)';
                opt.disabled = true;
            }
        });

    });
}

dateInput.addEventListener('change', loadSlots);
counsellorSelect.addEventListener('change', loadSlots);
</script>

</html>
