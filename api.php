<?php
// If running from command line (Cron), convert arguments to $_GET
if (php_sapi_name() === 'cli') {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}
require 'db.php';
$config = require 'config.php'; // Load the secrets

// 1. SECURITY CHECK using the config
if (!isset($_GET['key']) || $_GET['key'] !== $config['secret_key']) {
    header('HTTP/1.0 403 Forbidden');
    die("Access Denied");
}

// --- 2. DATABASE QUERY (Sorted by Expiry) ---
$user_filter = isset($_GET['user_id']) ? "AND r.user_id = " . intval($_GET['user_id']) : "";

$sql = "SELECT r.*, u.email as user_email, u.full_name 
        FROM expiry_reminders r
        JOIN users u ON r.user_id = u.id
        WHERE (r.expiry_date = DATE_ADD(CURDATE(), INTERVAL r.reminder_1_days DAY) 
        OR r.expiry_date = DATE_ADD(CURDATE(), INTERVAL r.reminder_2_days DAY))
        $user_filter
        ORDER BY r.expiry_date ASC"; // <--- Added this to sort ascending

$stmt = $pdo->query($sql);
$results = $stmt->fetchAll();

// --- 3. LOGIC EXECUTION (Grouped by User) ---
if ($results) {
    $user_notifications = [];

    // First, organize the data by email
    foreach ($results as $row) {
        $email = $row['user_email'];
        if (!isset($user_notifications[$email])) {
            $user_notifications[$email] = [
                'full_name' => $row['full_name'],
                'items' => []
            ];
        }
        $user_notifications[$email]['items'][] = $row;
    }

    // Now, send ONE email per user
    foreach ($user_notifications as $email => $data) {
        $message = "--- DOCUMENT EXPIRY ALERTS ---\n\n";

        foreach ($data['items'] as $item) {
            $expiry = new DateTime($item['expiry_date']);
            $today = new DateTime();
            $diff = $today->diff($expiry)->days;
            // Handle past dates
            if ($expiry < $today) $diff = "-" . $diff;
        
            // Condensed One-Line Format
            $message .= "* " . $item['nickname'] . " | " . $item['relation'] . " | " . $item['doc_type'] . " | " . $item['country'] . " | " . $diff . " days | " . $expiry->format('d/m/y') . " *\n";
        }
        $message .= "-----";
        
        // Output for Shortcut (This will now show ALL grouped items)
        echo $message;

        // Send the single grouped email
        $subject = "Action Required: " . count($data['items']) . " Document Expiries";
        $from = "reminders@" . $_SERVER['HTTP_HOST'];
        $headers = "From: Expiry App <$from>\r\nContent-Type: text/plain; charset=UTF-8";
        
    
        // Inside your foreach ($user_notifications as $email => $data) loop:
        if (isset($_GET['action']) && $_GET['action'] == 'email') {
            mail($email, $subject, $message, $headers);
        }
    }
} else {
    // Output nothing if no expiries today
    echo "";
}
?>