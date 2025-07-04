<?php
session_start(); // Start the session at the very beginning
header('Content-Type: application/json');

// --- Database Configuration (MOVE TO A SEPARATE, NON-WEB-ACCESSIBLE FILE FOR PRODUCTION) ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Replace with your DB username
define('DB_PASSWORD', ''); // Replace with your DB password
define('DB_NAME', 'ecoverse');   // Replace with your DB name

// --- Establish Database Connection ---
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    error_log("Database Connection Error: " . $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
    exit();
}

// --- Security Functions ---

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a new token
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        unset($_SESSION['csrf_token']); // Invalidate the token after use or on mismatch
        return false;
    }
    unset($_SESSION['csrf_token']); // Invalidate the token after successful validation
    return true;
}

// --- Handle GET request for CSRF token ---
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['action']) && $_GET['action'] === 'get_csrf_token') {
    echo json_encode(['csrf_token' => generateCsrfToken()]);
    exit();
}

// --- Handle POST request for saving feedback ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please refresh the page.']);
        exit();
    }

    // 2. Honeypot Field Check (Basic Bot Detection)
    if (!empty($_POST['website'])) { // 'website' is the honeypot field name
        error_log("Bot detected via honeypot field: " . $_SERVER['REMOTE_ADDR']);
        echo json_encode(['success' => false, 'message' => 'Submission detected as spam.']);
        exit();
    }

    // 3. Input Sanitization and Validation
    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = trim($_POST['subject'] ?? '');
    $sub_project = trim($_POST['sub_project'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Basic server-side validation
    if (empty($name) || !$email || empty($subject) || empty($sub_project) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled and email must be valid.']);
        exit();
    }

    // Further sanitize outputs that will be stored
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $sub_project = htmlspecialchars($sub_project, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    $image_path = null;
    $upload_dir = __DIR__ . "/uploads/"; // Absolute path to the uploads directory

    // 4. Handle File Upload (Securely)
    if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['project_image']['tmp_name'];
        $file_original_name = $_FILES['project_image']['name'];
        $file_size = $_FILES['project_image']['size'];
        $file_type = mime_content_type($file_tmp_name); // Get actual MIME type

        // Generate a unique filename to prevent overwrites and provide obscurity
        $unique_filename = uniqid('img_', true) . '.' . pathinfo($file_original_name, PATHINFO_EXTENSION);
        $target_file = $upload_dir . $unique_filename;

        // Allowed image MIME types
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 5 * 1024 * 1024; // 5 MB

        if (!in_array($file_type, $allowed_mime_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image file type. Only JPEG, PNG, GIF allowed.']);
            exit();
        }

        if ($file_size > $max_file_size) {
            echo json_encode(['success' => false, 'message' => 'Image file is too large. Max 5MB allowed.']);
            exit();
        }

        if (!is_writable($upload_dir)) {
            error_log("Upload directory is not writable: " . $upload_dir);
            echo json_encode(['success' => false, 'message' => 'Server error: Upload directory not writable.']);
            exit();
        }

        if (move_uploaded_file($file_tmp_name, $target_file)) {
            $image_path = '/home/uploads/' . $unique_filename; // Path to store in DB
        } else {
            error_log("Failed to move uploaded file. Error: " . $_FILES['project_image']['error']);
            echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
            exit();
        }
    }

    // 5. Get additional client info for logging
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';

    // 6. Prepare and Execute SQL Insert using Prepared Statements (Prevents SQL Injection)
    $stmt = $conn->prepare("INSERT INTO feedback_messages (name, email, subject, sub_project, message, image_path, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        error_log("Prepare statement failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Database error during preparation.']);
        exit();
    }

    $stmt->bind_param("ssssssss", $name, $email, $subject, $sub_project, $message, $image_path, $ip_address, $user_agent);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Your feedback has been successfully submitted.']);
    } else {
        error_log("Execute statement failed: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Error saving feedback to database.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>