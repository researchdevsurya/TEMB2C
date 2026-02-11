<?php
session_start();
require 'db.php';

/* AUTH */
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

/* ANTI-RESUBMISSION TOKEN */
if (empty($_SESSION['booking_token'])) {
    $_SESSION['booking_token'] = bin2hex(random_bytes(32));
}

/* GET ACTIVE EVENT */
$schedule = $pdo->query("SELECT * FROM schedule_master ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$schedule) die("No active event");

/* CHECK IF ALREADY BOOKED */
$chkBooking = $pdo->prepare("SELECT id FROM student_bookings WHERE student_id=? AND schedule_id=?");
$chkBooking->execute([$_SESSION['student_id'], $schedule['id']]);
$alreadyBooked = $chkBooking->rowCount() > 0;

/* AJAX: FETCH AVAILABLE SLOTS FOR STUDENT */
if (isset($_GET['get_slots'], $_GET['date'])) {
    $date = $_GET['date'];
    $q = $pdo->prepare("SELECT id, start_time, counsellor_id FROM counsellor_slots WHERE slot_date=? AND is_available=1 AND booked=0 ORDER BY start_time");
    $q->execute([$date]);
    $allSlots = $q->fetchAll(PDO::FETCH_ASSOC);

    $slotsArr = [];
    foreach ($allSlots as $s) {
        if (!isset($slotsArr[$s['start_time']])) {
            $slotsArr[$s['start_time']] = [
                'slot_id' => $s['id'],
                'counsellor_id' => $s['counsellor_id']
            ];
        }
    }

    echo json_encode($slotsArr);
    exit;
}

/* SUBMIT BOOKING */
if ($_SERVER['REQUEST_METHOD'] === "POST" && !$alreadyBooked) {

    $psyDate   = $_POST['psy_date'] ?? null;
    $group1    = $_POST['group1'] ?? null;
    $group2    = $_POST['group2'] ?? null;
    $slotDate  = $_POST['slot_date'] ?? null;
    $slotTime  = $_POST['slot'] ?? null;

    if (!$psyDate || !$group1 || !$group2 || !$slotDate || !$slotTime) {
        $error = "Please complete all steps.";
    } else {
        try {
            $pdo->beginTransaction();

            // Prevent double booking
            $chk = $pdo->prepare("SELECT id FROM student_bookings WHERE student_id=? AND schedule_id=? FOR UPDATE");
            $chk->execute([$_SESSION['student_id'], $schedule['id']]);
            if ($chk->rowCount()) throw new Exception("You already booked this event.");

            // Fetch available counsellor slot (Randomly select one if multiple available)
            $coun = $pdo->prepare("SELECT id, counsellor_id FROM counsellor_slots WHERE slot_date=? AND start_time=? AND is_available=1 AND booked=0 ORDER BY RAND() LIMIT 1 FOR UPDATE");
            $coun->execute([$slotDate, $slotTime]);
            $slotRow = $coun->fetch(PDO::FETCH_ASSOC);

            if (!$slotRow) throw new Exception("Selected slot is no longer available.");

            // Determine group session names & dates
            $g1Name = $schedule['session1_name'];
            $g1Date = $group1; // This is the date the student picked

            $g2Name = $schedule['session2_name'];
            $g2Date = $group2;

            // Fetch counsellor name
            $counsellorName = '';
            $cn = $pdo->prepare("SELECT username FROM counsellors WHERE id=?");
            $cn->execute([$slotRow['counsellor_id']]);
            $counsellorRow = $cn->fetch();
            if ($counsellorRow) $counsellorName = $counsellorRow['username'];

            // Insert booking
            $ins = $pdo->prepare("
                INSERT INTO student_bookings (
                    student_id, schedule_id,
                    selected_psychometric_date,
                    group_session1, group_session1_date,
                    group_session2, group_session2_date,
                    one_to_one_slot, booked_date,
                    counsellor_name, counsellor_id, counsellor_slot_id
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $ins->execute([
                $_SESSION['student_id'],
                $schedule['id'],
                $psyDate,
                $g1Name, $g1Date,
                $g2Name, $g2Date,
                $slotTime,
                $slotDate,
                $counsellorName,
                $slotRow['counsellor_id'],
                $slotRow['id']
            ]);

            // Mark counsellor slot as booked
            $upd = $pdo->prepare("UPDATE counsellor_slots SET booked=1 WHERE id=?");
            $upd->execute([$slotRow['id']]);

            $pdo->commit();

            header("Location: payment.php?booking_id=" . $pdo->lastInsertId());
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

function fmt($d) {
    return $d ? date('D, d M Y', strtotime($d)) : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'header.php'; ?>
<title>Book Session | TEM Portal</title>
</head>

<body class="bg-gradient-page">

<?php include 'sidebar.php'; ?>

<main class="max-w-3xl mx-auto px-4 sm:px-6 py-8">

<?php if ($alreadyBooked): ?>
  <div class="glass-card-static p-10 text-center animate-fade-in-up">
    <div class="w-20 h-20 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-6">
      <i class="fas fa-check-circle text-green-500 text-3xl"></i>
    </div>
    <h2 class="text-xl font-bold text-gray-800 mb-2">Already Booked!</h2>
    <p class="text-gray-500 mb-6">You have already booked a session for this event.</p>
    <a href="schedule.php" class="btn-primary"><i class="fas fa-calendar-check"></i> View My Booking</a>
  </div>
<?php else: ?>

<div class="glass-card-static p-8 animate-fade-in-up">

  <!-- EVENT NAME -->
  <div class="text-center mb-6">
    <span class="badge badge-info mb-2"><i class="fas fa-calendar-alt"></i> Active Event</span>
    <h2 class="text-2xl font-extrabold text-gray-800"><?= htmlspecialchars($schedule['event_name']) ?></h2>
  </div>

  <?php if (isset($error)): ?>
  <div class="alert alert-error flex items-center gap-2">
    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- STEP INDICATOR -->
  <div class="step-indicator" id="stepIndicator">
    <div class="step-item">
      <div class="step-circle active" data-step="1">1</div>
    </div>
    <div class="step-line" data-line="1"></div>
    <div class="step-item">
      <div class="step-circle inactive" data-step="2">2</div>
    </div>
    <div class="step-line" data-line="2"></div>
    <div class="step-item">
      <div class="step-circle inactive" data-step="3">3</div>
    </div>
    <div class="step-line" data-line="3"></div>
    <div class="step-item">
      <div class="step-circle inactive" data-step="4">4</div>
    </div>
  </div>

  <form method="POST" id="bookingForm">
    <input type="hidden" name="booking_token" value="<?= $_SESSION['booking_token'] ?? '' ?>">

    <!-- ============ STEP 1: PSYCHOMETRIC ============ -->
    <div class="wizard-step" data-step="1">
      <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center mx-auto mb-3">
          <i class="fas fa-brain text-blue-600 text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-1">Psychometric Test</h3>
        <p class="text-gray-500 text-sm">Choose your preferred date for the psychometric assessment</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <label class="radio-card" onclick="selectRadioCard(this)">
          <input type="radio" name="psy_date" value="<?= $schedule['psychometric_date_1'] ?>" required>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0">
              <i class="fas fa-calendar-day text-blue-500"></i>
            </div>
            <div>
              <p class="font-bold text-gray-800">Option A</p>
              <p class="text-sm text-gray-500"><?= fmt($schedule['psychometric_date_1']) ?></p>
            </div>
          </div>
        </label>

        <label class="radio-card" onclick="selectRadioCard(this)">
          <input type="radio" name="psy_date" value="<?= $schedule['psychometric_date_2'] ?>">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0">
              <i class="fas fa-calendar-day text-blue-500"></i>
            </div>
            <div>
              <p class="font-bold text-gray-800">Option B</p>
              <p class="text-sm text-gray-500"><?= fmt($schedule['psychometric_date_2']) ?></p>
            </div>
          </div>
        </label>
      </div>

      <button type="button" class="btn-primary w-full py-3.5" onclick="nextStep(2)">
        Continue <i class="fas fa-arrow-right"></i>
      </button>
    </div>

    <!-- ============ STEP 2: GROUP SESSION 1 ============ -->
    <div class="wizard-step hidden" data-step="2">
      <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-xl bg-green-100 flex items-center justify-center mx-auto mb-3">
          <i class="fas fa-users text-green-600 text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($schedule['session1_name'] ?? 'Group Session 1') ?></h3>
        <p class="text-gray-500 text-sm">Choose one date for group session 1</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <label class="radio-card" onclick="selectRadioCard(this)">
          <input type="radio" name="group1" value="<?= $schedule['session1_slot_1'] ?>" required>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center flex-shrink-0">
              <i class="fas fa-calendar-day text-green-500"></i>
            </div>
            <div>
              <p class="font-bold text-gray-800">Date Option A</p>
              <p class="text-sm text-gray-500"><?= fmt($schedule['session1_slot_1']) ?></p>
            </div>
          </div>
        </label>

        <label class="radio-card" onclick="selectRadioCard(this)">
          <input type="radio" name="group1" value="<?= $schedule['session1_slot_2'] ?>">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center flex-shrink-0">
              <i class="fas fa-calendar-day text-green-500"></i>
            </div>
            <div>
              <p class="font-bold text-gray-800">Date Option B</p>
              <p class="text-sm text-gray-500"><?= fmt($schedule['session1_slot_2']) ?></p>
            </div>
          </div>
        </label>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <button type="button" class="btn-secondary py-3" onclick="prevStep(1)">
          <i class="fas fa-arrow-left"></i> Back
        </button>
        <button type="button" class="btn-primary py-3" onclick="nextStep(3)">
          Continue <i class="fas fa-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- ============ STEP 3: GROUP SESSION 2 ============ -->
    <div class="wizard-step hidden" data-step="3">
      <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-xl bg-emerald-100 flex items-center justify-center mx-auto mb-3">
          <i class="fas fa-user-friends text-emerald-600 text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($schedule['session2_name'] ?? 'Group Session 2') ?></h3>
        <p class="text-gray-500 text-sm">Choose one date for group session 2</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <label class="radio-card" onclick="selectRadioCard(this)">
          <input type="radio" name="group2" value="<?= $schedule['session2_slot_1'] ?>" required>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center flex-shrink-0">
              <i class="fas fa-calendar-day text-emerald-500"></i>
            </div>
            <div>
              <p class="font-bold text-gray-800">Date Option A</p>
              <p class="text-sm text-gray-500"><?= fmt($schedule['session2_slot_1']) ?></p>
            </div>
          </div>
        </label>

        <label class="radio-card" onclick="selectRadioCard(this)">
          <input type="radio" name="group2" value="<?= $schedule['session2_slot_2'] ?>">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center flex-shrink-0">
              <i class="fas fa-calendar-day text-emerald-500"></i>
            </div>
            <div>
              <p class="font-bold text-gray-800">Date Option B</p>
              <p class="text-sm text-gray-500"><?= fmt($schedule['session2_slot_2']) ?></p>
            </div>
          </div>
        </label>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <button type="button" class="btn-secondary py-3" onclick="prevStep(2)">
          <i class="fas fa-arrow-left"></i> Back
        </button>
        <button type="button" class="btn-primary py-3" onclick="nextStep(4)">
          Continue <i class="fas fa-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- ============ STEP 4: 1:1 COUNSELLING ============ -->
    <div class="wizard-step hidden" data-step="4">
      <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-xl bg-purple-100 flex items-center justify-center mx-auto mb-3">
          <i class="fas fa-comments text-purple-600 text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-1">1:1 Counselling</h3>
        <p class="text-gray-500 text-sm">Pick a date and available time slot</p>
      </div>

      <div class="mb-5">
        <label class="form-label">Select Date</label>
        <input type="date" id="slot_date" name="slot_date" required
               min="<?= $schedule['counselling_from'] ?>" max="<?= $schedule['counselling_to'] ?>"
               class="form-input">
        <p class="text-xs text-gray-400 mt-1">
          Available: <?= fmt($schedule['counselling_from']) ?> to <?= fmt($schedule['counselling_to']) ?>
        </p>
      </div>

      <div class="mb-6">
        <label class="form-label">Select Time Slot</label>
        <div id="slotsContainer" class="slot-grid">
          <p class="text-gray-400 text-sm col-span-full text-center py-6">
            <i class="fas fa-calendar-day mr-2"></i> Select a date to see available slots
          </p>
        </div>
        <input type="hidden" name="slot" id="selectedSlot">
        <div id="slotLoading" class="hidden text-center py-6">
          <div class="spinner mx-auto mb-2" style="border-color: rgba(99,102,241,0.2); border-top-color: #6366f1; width: 28px; height: 28px;"></div>
          <p class="text-sm text-gray-500">Loading slots...</p>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <button type="button" class="btn-secondary py-3" onclick="prevStep(3)">
          <i class="fas fa-arrow-left"></i> Back
        </button>
        <button type="submit" id="submitBtn" class="btn-primary py-3">
          <i class="fas fa-check-circle"></i> Confirm & Pay
        </button>
      </div>
    </div>

  </form>
</div>

<?php endif; ?>

</main>

<script>
let currentStep = 1;

function selectRadioCard(label) {
  const name = label.querySelector('input[type=radio]').name;
  document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
    r.closest('.radio-card').classList.remove('selected');
  });
  label.classList.add('selected');
  label.querySelector('input[type=radio]').checked = true;
}

function updateStepIndicator(step) {
  for(let i = 1; i <= 4; i++) {
    const circle = document.querySelector(`.step-circle[data-step="${i}"]`);
    const line = document.querySelector(`.step-line[data-line="${i}"]`);

    circle.classList.remove('active','completed','inactive');

    if (i < step) {
      circle.classList.add('completed');
      circle.innerHTML = '<i class="fas fa-check text-xs"></i>';
    } else if (i === step) {
      circle.classList.add('active');
      circle.textContent = i;
    } else {
      circle.classList.add('inactive');
      circle.textContent = i;
    }

    if (line) {
      line.classList.remove('active','completed');
      if (i < step) line.classList.add('completed');
      else if (i === step) line.classList.add('active');
    }
  }
}

function nextStep(step) {
  // Validate current step
  const curStepEl = document.querySelector(`.wizard-step[data-step="${currentStep}"]`);
  const radio = curStepEl.querySelector('input[type=radio]:checked');
  if (!radio && currentStep <= 3) {
    alert('Please select an option to continue.');
    return;
  }

  showStep(step);
}

function prevStep(step) {
  showStep(step);
}

function showStep(step) {
  document.querySelectorAll('.wizard-step').forEach(el => el.classList.add('hidden'));
  const target = document.querySelector(`.wizard-step[data-step="${step}"]`);
  target.classList.remove('hidden');
  target.style.animation = 'fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) both';
  currentStep = step;
  updateStepIndicator(step);
}

// SLOT LOADING
const slotDateInput = document.getElementById('slot_date');
const slotsContainer = document.getElementById('slotsContainer');
const slotLoading = document.getElementById('slotLoading');
const selectedSlotInput = document.getElementById('selectedSlot');

slotDateInput?.addEventListener('change', function() {
  if (!this.value) return;

  slotsContainer.innerHTML = '';
  slotLoading.classList.remove('hidden');

  fetch(`?get_slots=1&date=${this.value}`)
    .then(r => r.json())
    .then(data => {
      slotLoading.classList.add('hidden');
      slotsContainer.innerHTML = '';

      const times = Object.keys(data);
      if (times.length === 0) {
        slotsContainer.innerHTML = '<p class="text-gray-400 text-sm col-span-full text-center py-6"><i class="fas fa-exclamation-circle mr-1"></i> No slots available for this date</p>';
        return;
      }

      times.forEach(time => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'slot-btn';
        btn.textContent = time;
        btn.addEventListener('click', () => selectSlot(btn, time));
        slotsContainer.appendChild(btn);
      });
    })
    .catch(() => {
      slotLoading.classList.add('hidden');
      slotsContainer.innerHTML = '<p class="text-red-400 text-sm col-span-full text-center py-6">Error loading slots</p>';
    });
});

function selectSlot(btn, time) {
  document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('slot-selected'));
  btn.classList.add('slot-selected');
  selectedSlotInput.value = time;
}

// Validate before submit
document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
  if (!selectedSlotInput.value) {
    e.preventDefault();
    alert('Please select a time slot.');
    return;
  }
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>