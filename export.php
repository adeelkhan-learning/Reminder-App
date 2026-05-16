<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set headers to force download as an Excel file
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Expiry_Report_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch the data
$stmt = $pdo->prepare("SELECT * FROM expiry_reminders WHERE user_id = ? ORDER BY expiry_date ASC");
$stmt->execute([$_SESSION['user_id']]);
$rows = $stmt->fetchAll();

// Start the HTML Table for Excel
echo '<table border="1">';
echo '<tr style="background-color: #d9d9d9; font-weight: bold;">
        <th>Who</th>
        <th>Relation</th>
        <th>Document</th>
        <th>Country</th>
        <th>Expiry Date</th>
        <th>Days Left</th>
      </tr>';

foreach ($rows as $row) {
    $expiry_ts = strtotime($row['expiry_date']);
    $today_ts = time();
    $days_left = floor(($expiry_ts - $today_ts) / 86400);

    // Color logic: Red and Bold if less than 30 days remain
    $is_urgent = ($days_left <= 30);
    $style = $is_urgent ? 'style="color: #FF0000; font-weight: bold;"' : '';

    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['nickname']) . '</td>';
    echo '<td>' . htmlspecialchars($row['relation']) . '</td>';
    echo '<td>' . htmlspecialchars($row['doc_type']) . '</td>';
    echo '<td>' . htmlspecialchars($row['country']) . '</td>';
    echo '<td ' . $style . '>' . date('d M Y', $expiry_ts) . '</td>';
    echo '<td ' . $style . '>' . $days_left . '</td>';
    echo '</tr>';
}

echo '</table>';
?>