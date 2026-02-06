<?php 
// Notification functions
function add_notification($conn, $user_id, $title, $message, $type = 'general', $reference_id = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $user_id, $title, $message, $type, $reference_id);
    $stmt->execute();
    $stmt->close();
}

// Get count of unread notifications
function get_unread_count($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    $stmt->close();
    return $count;
}

// Get current shift based on time
function get_current_shift() {
    $hour = (int)date('H');
    return $hour >= 6 && $hour < 14 ? 'morning' :
           ($hour >= 14 && $hour < 22 ? 'afternoon' : 'night');
}

// Get recent notifications
function get_recent_notifications($conn, $user_id, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT id, title, message, type, reference_id, created_at, is_read 
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifs = [];
    while ($row = $result->fetch_assoc()) {
        $notifs[] = $row;
    }
    $stmt->close();
    return $notifs;
}


// Mark notifications as read
function mark_notifications_read($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// function to sanitize input data
// function testData($data) {
//     if ($data === null) {
//         return '';
//     }
//     global $conn;
//     $data = trim($data);
//     $data = stripslashes($data);
//     $data = htmlspecialchars($data);
//     $data = mysqli_real_escape_string($conn, $data);
//     return $data;
// }

// Advanced sanitization function
function testData($data) { 
    if ($data === null) {
        return '';
    }
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Advanced sanitization and validation function
/**
 * Sanitize and validate input data based on type.
 * 
 * @param mixed $data   Input value (string, int, null, etc.)
 * @param string $type  'string', 'username', 'email', 'password', 'number', 'int'
 * @param array $options Extra rules (optional)
 * @return array [ 'value' => cleaned value or null, 'error' => error message or empty ]
 */

// function testData($data, string $type = 'string', array $options = []): array {
//     // Handle null or empty early
//     if ($data === null || $data === '') {
//         return ['value' => '', 'error' => ''];
//     }

//     // Force to string for safety
//     $data = (string) $data;

//     // Common sanitization (always apply)
//     $data = trim($data);
//     $data = stripslashes($data);
//     $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

//     $error = '';

//     switch (strtolower($type)) {
//         case 'username':
//             // Letters, numbers, hyphen, underscore only (no space, no special chars)
//             if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data)) {
//                 $error = 'Username inaruhusiwa letters, numbers, hyphen (-) na underscore (_) tu. Hakuna space au special chars.';
//             }
//             // Optional: min/max length
//             $min = $options['min_length'] ?? 3;
//             $max = $options['max_length'] ?? 30;
//             if (strlen($data) < $min || strlen($data) > $max) {
//                 $error .= $error ? ' ' : '';
//                 $error .= "Username iwe kati ya herufi $min na $max.";
//             }
//             break;
//         // Add validation for description or notes if needed

//         case 'description':
//         case 'notes':
//             // Optional: max length
//             if (isset($options['max_length']) && strlen($data) > $options['max_length']) {
//                 $error = "Maelezo yasizidi herufi {$options['max_length']}.";
//                 $data = substr($data, 0, $options['max_length']);
//             } elseif (!preg_match('/^[a-zA-Z0-9_ ]+$/', $data)) {
//                 $error = 'Only numbers, letters and space are allowed.';
//             }
//             break;

//         case 'email':
//             $data = filter_var($data, FILTER_SANITIZE_EMAIL);
//             if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
//                 $error = 'Email si sahihi (mfano: name@example.com)';
//             }
//             break;

//         case 'password':
//             $data = trim($data); // Passwords hazihitaji stripslashes au htmlspecialchars
//             $min = $options['min_length'] ?? 8;
//             if (strlen($data) < $min) {
//                 $error = "Password iwe na herufi $min au zaidi.";
//             }
//             // Optional: strong password check
//             if (!empty($options['strong']) && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $data)) {
//                 $error = 'Password iwe na herufi kubwa, ndogo, namba, na special character moja.';
//             }
//             break;

//         case 'number':
//         case 'int':
//             $data = filter_var($data, FILTER_SANITIZE_NUMBER_INT);
//             if (!is_numeric($data)) {
//                 $error = 'Lazima iwe namba tu.';
//             }
//             // Optional: range check
//             if (isset($options['min']) && $data < $options['min']) {
//                 $error = "Namba iwe zaidi ya au sawa na {$options['min']}.";
//             }
//             if (isset($options['max']) && $data > $options['max']) {
//                 $error = "Namba iwe chini ya au sawa na {$options['max']}.";
//             }
//             break;

//         case 'string':
//         default:
//             $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
//             // Optional: max length
//             if (isset($options['max_length']) && strlen($data) > $options['max_length']) {
//                 $error = "Herufi zisiwe zaidi ya {$options['max_length']}.";
//                 $data = substr($data, 0, $options['max_length']);
//             }
//             break;
//     }

//     return [
//         'value' => $data,
//         'error' => $error
//     ];
// }

// Audit log function
function log_audit($conn, $action, $details = '', $reference = '') {
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, reference, ip_address) VALUES (?, ?, ?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->bind_param("issss", $_SESSION['user_id'], $action, $details, $reference, $ip);
    $stmt->execute();
    $stmt->close();
}

// Email sending function using PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/PHPMailer/src/Exception.php';
require_once '../vendor/PHPMailer/src/PHPMailer.php';
require_once '../vendor/PHPMailer/src/SMTP.php';

function send_guest_email($conn, $guest_email, $subject, $body_html) {
    // Check if emails are enabled
    $settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT enable_guest_email FROM hotel_settings WHERE id = 1"));
    if (!$settings || !$settings['enable_guest_email']) {
        return false; // Emails disabled
    }

    if (empty($guest_email)) return false;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'msafirijesmail@gmail.com';          // ← My EMail
        $mail->Password   = 'qdynnnbegrcvieqh';            // ← App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('msafirijesmail@gmail.com', 'Sea Breeze Hotel');
        $mail->addAddress($guest_email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body_html;
        $mail->AltBody = strip_tags($body_html);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>