<?php
require 'db.php';

$pid = $_GET['id'];

$pdo->prepare("
  UPDATE payments SET auto_pay='NO'
  WHERE id=?
")->execute([$pid]);

echo "AutoPay Cancelled";
