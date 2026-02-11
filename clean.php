<?php
/**
 * clean.php
 * ⚠️ This file will permanently delete ALL data from selected tables.
 */

session_start();
require 'db.php';

/*
|--------------------------------------------------------------------------
| SECURITY CHECK (OPTIONAL BUT RECOMMENDED)
|--------------------------------------------------------------------------
| Uncomment this block if you want only admin users to run this
| Example: if (!isset($_SESSION['is_admin'])) { exit('Unauthorized'); }
|
| if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
|     die('Unauthorized access');
| }
|
*/

try {
    // Start transaction
    $pdo->beginTransaction();

    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Truncate tables
    $tables = [
        'students',
        'student_bookings',
        'payments',
        'payment_logs'
    ];

    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE `$table`");
    }

    // Enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Commit transaction
    $pdo->commit();

    $message = "✅ All tables truncated successfully.";

} catch (Exception $e) {

    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $message = "❌ Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Database Cleanup</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">

<div class="max-w-lg w-full bg-white shadow-lg rounded-2xl p-6 text-center">

  <h1 class="text-xl font-bold text-gray-800 mb-3">
    Database Cleanup
  </h1>

  <p class="text-gray-600 mb-4 text-sm">
    This action permanently deletes all records from selected tables.
  </p>

  <div class="p-4 rounded-xl text-sm
    <?= str_contains($message, '✅') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
    <?= htmlspecialchars($message) ?>
  </div>

</div>

</body>
</html>