<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit();
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        // Check if email already exists
        $check_query = "SELECT COUNT(*) as count FROM newsletter_subscribers WHERE email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

        if ($exists) {
            echo json_encode(['success' => false, 'message' => 'You are already subscribed to our newsletter!']);
        } else {
            $insert_query = "INSERT INTO newsletter_subscribers (email) VALUES (:email)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':email', $email);

            if ($insert_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Thank you for subscribing! You will receive our latest financial tips and insights.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Subscription failed. Please try again.']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>