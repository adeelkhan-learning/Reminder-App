<?php
require 'db.php';
$config = require 'config.php';

// 1. Get all users who have an email address
$userStmt = $pdo->query("SELECT id, email, full_name FROM users WHERE email IS NOT NULL");
$users = $userStmt->fetchAll();

foreach ($users as $user) {
    $userId = $user['id'];
    $userEmail = $user['email'];
    $userName = $user['full_name'];

    // 2. Fetch all reminders for THIS user, sorted by date
    $stmt = $pdo->prepare("SELECT nickname, relation, doc_type, country, expiry_date 
                           FROM expiry_reminders WHERE user_id = ? ORDER BY expiry_date ASC");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    if (!$rows) continue; // Skip if user has no data

    // 3. Build the Excel-compatible HTML content
        $content = '<table border="1">
                    <tr style="background-color: #d9d9d9;">
                        <th>Nick Name</th><th>Relation</th><th>Doc Type</th><th>Country</th><th>Expiry Date</th><th>Days Left</th>
                    </tr>';
    
        foreach ($rows as $row) {
            $expiry_ts = strtotime($row['expiry_date']);
            $is_urgent = ($expiry_ts <= strtotime('+30 days'));
            $style = $is_urgent ? 'style="color: #FF0000; font-weight: bold;"' : '';
            
            // Calculate the exact days left from today
            $today_ts = time();
            $days_left = floor(($expiry_ts - $today_ts) / 86400);
            
            $content .= "<tr>
                <td>{$row['nickname']}</td>
                <td>{$row['relation']}</td>
                <td>{$row['doc_type']}</td>
                <td>{$row['country']}</td>
                <td $style>" . date('d M Y', $expiry_ts) . "</td>
                <td $style>{$days_left}</td>
            </tr>";
        }
        $content .= '</table>';

    // 4. Email logic with Attachment
    $boundary = md5(time());
    $subject = "Weekly Document Summary: " . date('d M Y');
    $filename = "Weekly_Summary_" . date('Y-m-d') . ".xls";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "From: Expiry App <reminders@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    // Email Body
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\n";
    $body .= "Hello $userName,\n\nPlease find attached your weekly summary of all document expiries.\n\n";

    // Attachment
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: application/vnd.ms-excel; name=\"$filename\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\n";
    $body .= chunk_split(base64_encode($content)) . "\r\n";
    $body .= "--$boundary--";

    mail($userEmail, $subject, $body, $headers);
}