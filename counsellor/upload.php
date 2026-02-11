<?php
session_start();
require '../db.php';

if (!isset($_SESSION['counsellor_id'])) {
    header("Location: login.php");
    exit;
}

$cid = $_SESSION['counsellor_id'];
$msg = '';
$err = '';

// SAMPLE CSV DOWNLOAD
if (isset($_GET['download_sample'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sample_slots.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['date', 'start_time', 'end_time']);
    fputcsv($out, ['15-02-2026', '10:00', '10:30']);
    fputcsv($out, ['15-02-2026', '10:30', '11:00']);
    fputcsv($out, ['15-02-2026', '11:00', '11:30']);
    fputcsv($out, ['16-02-2026', '14:00', '14:30']);
    fputcsv($out, ['16-02-2026', '14:30', '15:00']);
    fclose($out);
    exit;
}

// Get events for dropdown
$events = $pdo->query("SELECT id, event_name FROM schedule_master ORDER BY id DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $event_id = $_POST['event_id'] ?? '';
    $file = $_FILES['csv_file'];

    if (!$event_id) {
        $err = "Please select an event.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $err = "File upload failed.";
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        $header = fgetcsv($handle); // skip header row
        $count = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) continue;

            $date_str = trim($row[0]);
            $start_time = trim($row[1]);
            $end_time = trim($row[2]);

            // Try multiple date formats
            $date_obj = DateTime::createFromFormat('d-m-Y', $date_str);
            if (!$date_obj) $date_obj = DateTime::createFromFormat('Y-m-d', $date_str);
            if (!$date_obj) $date_obj = DateTime::createFromFormat('d/m/Y', $date_str);

            if (!$date_obj) {
                $errors[] = "Invalid date: $date_str";
                continue;
            }
            $date = $date_obj->format('Y-m-d');

            // Skip if duplicate slot already exists
            $dup = $pdo->prepare("SELECT id FROM counsellor_slots WHERE counsellor_id=? AND event_id=? AND slot_date=? AND start_time=?");
            $dup->execute([$cid, $event_id, $date, $start_time]);
            if ($dup->rowCount() > 0) {
                $skipped++;
                continue;
            }

            $stmt = $pdo->prepare("
                INSERT INTO counsellor_slots
                (counsellor_id, event_id, slot_date, start_time, end_time, is_available, booked)
                VALUES (?, ?, ?, ?, ?, 1, 0)
            ");
            $stmt->execute([$cid, $event_id, $date, $start_time, $end_time]);
            $count++;
        }
        fclose($handle);

        if ($count) $msg = "$count slots uploaded successfully!";
        if ($skipped) $msg .= " ($skipped duplicate slots skipped)";
        if ($errors) $err = implode('; ', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload Slots | Counsellor</title>
<script src="https://cdn.tailwindcss.com"></script>
<?php include 'sidebar.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex">

<div class="flex-1 overflow-y-auto">

  <header class="bg-white border-b px-8 py-6">
    <h1 class="text-2xl font-extrabold text-gray-800 flex items-center gap-3">
      <i class="fas fa-upload text-purple-600"></i> Upload Availability
    </h1>
    <p class="text-sm text-gray-500 mt-1">Upload your available time slots via CSV</p>
  </header>

  <div class="p-8 max-w-2xl">

    <?php if ($msg): ?>
    <div class="alert alert-success flex items-center gap-2"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
    <div class="alert alert-error flex items-center gap-2"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div>
    <?php endif; ?>

    <!-- FORMAT GUIDE -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
      <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center"><i class="fas fa-info-circle text-blue-600 text-sm"></i></div>
        CSV Format Guide
      </h3>
      <p class="text-sm text-gray-500 mb-3">Your CSV file must have the following columns:</p>
      <div class="bg-gray-50 rounded-xl p-4 font-mono text-sm text-gray-700">
        <p class="text-gray-400 mb-1"># Header row (required)</p>
        <p class="font-bold">date, start_time, end_time</p>
        <p class="text-gray-400 mt-2 mb-1"># Data rows</p>
        <p>15-02-2026, 10:00, 10:30</p>
        <p>15-02-2026, 10:30, 11:00</p>
        <p>16-02-2026, 14:00, 14:30</p>
      </div>
      <p class="text-xs text-gray-400 mt-3">Supported date formats: DD-MM-YYYY, YYYY-MM-DD, DD/MM/YYYY</p>

      <a href="?download_sample=1" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-blue-50 text-blue-700 font-semibold text-sm rounded-xl hover:bg-blue-100 transition">
        <i class="fas fa-download"></i> Download Sample CSV
      </a>
    </div>

    <!-- UPLOAD FORM -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
      <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center"><i class="fas fa-file-csv text-purple-600 text-sm"></i></div>
        Upload CSV
      </h3>

      <form method="POST" enctype="multipart/form-data" class="space-y-5">

        <div>
          <label class="form-label">Select Event</label>
          <select name="event_id" required class="form-input">
            <option value="">— Choose Event —</option>
            <?php foreach ($events as $ev): ?>
              <option value="<?= $ev['id'] ?>"><?= htmlspecialchars($ev['event_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label">CSV File</label>
          <div id="dropZone" class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center hover:border-purple-400 transition cursor-pointer">
            <i class="fas fa-cloud-upload-alt text-gray-300 text-3xl mb-3"></i>
            <p class="text-gray-500 text-sm mb-2">Drop your CSV file here or click to browse</p>
            <p id="fileName" class="text-purple-600 font-semibold text-sm hidden"></p>
            <input type="file" name="csv_file" accept=".csv" required class="hidden" id="csvInput">
          </div>
        </div>

        <button class="btn-primary w-full py-3.5 bg-gradient-to-r from-purple-600 to-pink-600">
          <i class="fas fa-upload"></i> Upload Slots
        </button>
      </form>
    </div>

  </div>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const csvInput = document.getElementById('csvInput');
const fileName = document.getElementById('fileName');

dropZone.addEventListener('click', () => csvInput.click());

csvInput.addEventListener('change', () => {
  if (csvInput.files.length) {
    fileName.textContent = csvInput.files[0].name;
    fileName.classList.remove('hidden');
    dropZone.classList.add('border-purple-400', 'bg-purple-50/30');
  }
});

dropZone.addEventListener('dragover', e => {
  e.preventDefault();
  dropZone.classList.add('border-purple-400', 'bg-purple-50/30');
});

dropZone.addEventListener('dragleave', () => {
  dropZone.classList.remove('border-purple-400', 'bg-purple-50/30');
});

dropZone.addEventListener('drop', e => {
  e.preventDefault();
  csvInput.files = e.dataTransfer.files;
  if (csvInput.files.length) {
    fileName.textContent = csvInput.files[0].name;
    fileName.classList.remove('hidden');
  }
});
</script>

</body>
</html>