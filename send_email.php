<?php
session_start(); // Start the session to store and retrieve CSRF token

// Set content type header to JSON for API responses
header('Content-Type: application/json');

// --- CSRF Token Management ---
// Generate a new CSRF token if one doesn't exist in the session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a random, cryptographically secure token
}

// Handle request to get CSRF token (for initial page load)
if (isset($_GET['action']) && $_GET['action'] === 'get_csrf_token') {
    echo json_encode(['csrf_token' => $_SESSION['csrf_token']]);
    exit; // Exit immediately after sending the token
}

// --- PHPMailer Library Includes ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include PHPMailer autoloader with adjusted paths
// The 'home' folder is inside 'htdocs'. PHPMailer-master is also inside 'htdocs'.
// So, from 'home', we go up one level (../) to 'htdocs', then into 'PHPMailer-master'.
require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

// Define the email address where you want to receive messages
$receiving_email_address = 'albinshajiofficial@gmail.com'; // **IMPORTANT: CHANGE THIS TO YOUR ACTUAL EMAIL ADDRESS**

// --- Handle Form Submission (POST Request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Security Checks ---
    // 1. CSRF Token Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // Log the potential CSRF attack attempt for your records
        error_log("CSRF token mismatch or missing from IP: " . $_SERVER['REMOTE_ADDR']);
        echo json_encode(['success' => false, 'message' => 'Security check failed. Please refresh the page and try again.']);
        exit;
    }

    // After successful CSRF check, regenerate token to prevent replay attacks (optional, but good practice)
    unset($_SESSION['csrf_token']); // Invalidate the token used
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a new one for future requests

    // 2. Honeypot Check
    // If the 'website' field is filled, it's likely a bot.
    if (!empty($_POST['website'])) {
        // Log the honeypot trigger for your records
        error_log("Honeypot triggered by IP: " . $_SERVER['REMOTE_ADDR'] . " with value: " . $_POST['website']);
        echo json_encode(['success' => false, 'message' => 'Form submission detected as spam.']);
        exit; // Exit silently or with a generic error message
    }

    // --- Input Sanitization and Validation ---
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $message_content = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message_content)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }

    // --- PHPMailer Email Sending Logic ---
    $mail = new PHPMailer(true); // Passing `true` enables exceptions for more detailed error reporting

    try {
        // Server settings for SMTP
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through (for Gmail: smtp.gmail.com)
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'yourname@gmail.com';         // **YOUR GMAIL ADDRESS (e.g., yourname@gmail.com)**
        $mail->Password   = 'password';                    // **YOUR GENERATED GMAIL APP PASSWORD (or actual password for other services)**
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption (use PHPMailer::ENCRYPTION_STARTTLS for port 587)
        $mail->Port       = 465;                                    // TCP port to connect to; use 587 for PHPMailer::ENCRYPTION_STARTTLS

        // For Gmail, if using 2-Step Verification, an "App password" is required.
        // Go to your Google Account -> Security -> App passwords.
        // Using your regular Gmail password will likely fail.

        // Recipients
        $mail->setFrom('albinshaji39k@gmail.com', 'EcoVerse Contact Form'); // Sender email (should typically match your SMTP Username for Gmail)
        $mail->addAddress($receiving_email_address, 'EcoVerse Admin');     // Add recipient (your target email address)
        $mail->addReplyTo($email, $name);                               // Set reply-to address to the user's email

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject; // <-- ADDED THIS LINE TO SET THE EMAIL SUBJECT
        
        // Construct the professional HTML email body with new font styling
        $html_body = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>New EcoVerse Contact Message</title>
            <style>
                /* Basic reset */
                body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
                table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
                img { -ms-interpolation-mode: bicubic; }

                /* Styles */
                body {
                    margin: 0;
                    padding: 0;
                    /* Updated font-family to prioritize Poppins, then common sans-serif fallbacks */
                    font-family: \'Poppins\', \'Helvetica Neue\', Helvetica, Arial, sans-serif;
                    background-color: #f7fafc;
                    color: #124364;
                }
                .container {
                    width: 100%;
                    max-width: 600px;
                    background-color: #ffffff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .header {
                    background-color: #2586ce; /* Accent color from your site */
                    color: #ffffff;
                    padding: 20px;
                    text-align: center;
                }
                .content {
                    padding: 20px 30px;
                }
                .content p {
                    font-size: 15px;
                    line-height: 1.6;
                    margin-bottom: 15px;
                }
                .details-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                .details-table th, .details-table td {
                    padding: 12px 0;
                    border-bottom: 1px solid #eeeeee;
                    text-align: left;
                    font-size: 15px;
                }
                .details-table th {
                    font-weight: bold;
                    color: #555555;
                    width: 25%;
                }
                .details-table tr:last-child td {
                    border-bottom: none;
                }
                .footer {
                    background-color: #ffffff;
                    padding: 15px 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #888888;
                }
                .button {
                    display: inline-block;
                    padding: 10px 20px;
                    margin-top: 20px;
                    background-color: #007bff;
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 5px;
                    font-size: 14px;
                }
                @media screen and (max-width: 520px) {
                    .content {
                        padding: 15px;
                    }
                    .header {
                        padding: 15px;
                    }
                    .details-table th, .details-table td {
                        font-size: 14px;
                    }
                }
            </style>
        </head>
        <body>
            <table width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f4f4f4;">
                <tr>
                    <td align="center" style="padding: 20px 10px;">
                        <table class="container" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <!-- Header -->
                            <tr>
                                <td class="header">
                                    <h1 style="margin: 0; font-size: 28px; font-weight: bold; line-height: 1.2;">EcoVerse Contact Message</h1>
                                </td>
                            </tr>
                            <!-- Content -->
                            <tr>
                                <td class="content">
                                    <p>Hello Albin,</p>
                                    <p>You have received a new message from the EcoVerse contact form. Here are the details:</p>
                                    <table class="details-table" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <th>Name:</th>
                                            <td>' . htmlspecialchars($name) . '</td>
                                        </tr>
                                        <tr>
                                            <th>Email:</th>
                                            <td><a href="mailto:' . htmlspecialchars($email) . '" style="color: #007bff; text-decoration: none;">' . htmlspecialchars($email) . '</a></td>
                                        </tr>
                                        <tr>
                                            <th>Subject:</th>
                                            <td>' . htmlspecialchars($subject) . '</td>
                                        </tr>
                                        <tr>
                                            <th style="vertical-align: top;">Message:</th>
                                            <td>' . nl2br(htmlspecialchars($message_content)) . '</td>
                                        </tr>
                                    </table>
                                    <p style="margin-top: 30px; font-size: 14px; color: #777;">This automated message was sent via the EcoVerse contact form.</p>
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td class="footer">
                                    &copy; ' . date("Y") . ' EcoVerse. All Rights Reserved.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';

        $mail->Body = $html_body;

        // Set alternative plain text body for email clients that do not support HTML
        $mail->AltBody = "You have received a new message from your EcoVerse website contact form.\n\n"
                       . "Name: " . $name . "\n"
                       . "Email: " . $email . "\n"
                       . "Subject: " . $subject . "\n"
                       . "Message:\n" . $message_content . "\n"
                       . "--- EcoVerse Contact Form ---";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully.']);
        exit;
    } catch (Exception $e) {
        // Log the detailed error for debugging (check your PHP error logs for more info)
        error_log("EcoVerse Contact Form Error: Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        // Return a JSON error response with a more user-friendly message
        echo json_encode(['success' => false, 'message' => "Message could not be sent. Please try again later."]);
        exit;
    }
}
// If not a POST request and not asking for token, just exit
exit;
?>
