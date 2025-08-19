<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $article_id = (int) ($_POST['article_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // Validation
    if ($article_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid article ID.']);
        exit();
    }

    if (empty($name) || strlen($name) < 2) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid name (at least 2 characters).']);
        exit();
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit();
    }

    if (empty($content) || strlen($content) < 10) {
        echo json_encode(['success' => false, 'message' => 'Please enter a comment with at least 10 characters.']);
        exit();
    }

    // Additional validation - prevent spam
    if (strlen($content) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Comment is too long. Please limit to 1000 characters.']);
        exit();
    }

    // Check for spam patterns
    $spam_patterns = [
        '/\b(viagra|cialis|casino|poker|loan|credit)\b/i',
        '/\b(buy now|click here|free money|make money fast)\b/i',
        '/http[s]?:\/\/[^\s]+/i' // Basic URL detection
    ];

    foreach ($spam_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            echo json_encode(['success' => false, 'message' => 'Your comment appears to contain spam content. Please revise and try again.']);
            exit();
        }
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        // Verify article exists and is published
        $verify_query = "SELECT COUNT(*) as count FROM articles WHERE id = :id AND status = 'published'";
        $verify_stmt = $db->prepare($verify_query);
        $verify_stmt->bindParam(':id', $article_id);
        $verify_stmt->execute();
        $article_exists = $verify_stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

        if (!$article_exists) {
            echo json_encode(['success' => false, 'message' => 'Article not found or not available for comments.']);
            exit();
        }

        // Check for duplicate comments (same email and content in last 5 minutes)
        $duplicate_query = "SELECT COUNT(*) as count FROM comments 
                           WHERE email = :email AND content = :content 
                           AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        $duplicate_stmt = $db->prepare($duplicate_query);
        $duplicate_stmt->bindParam(':email', $email);
        $duplicate_stmt->bindParam(':content', $content);
        $duplicate_stmt->execute();
        $is_duplicate = $duplicate_stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

        if ($is_duplicate) {
            echo json_encode(['success' => false, 'message' => 'You have already submitted this comment recently. Please wait before submitting again.']);
            exit();
        }

        // Insert comment
        $insert_query = "INSERT INTO comments (article_id, name, email, content, status) 
                        VALUES (:article_id, :name, :email, :content, 'pending')";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':article_id', $article_id);
        $insert_stmt->bindParam(':name', $name);
        $insert_stmt->bindParam(':email', $email);
        $insert_stmt->bindParam(':content', $content);

        if ($insert_stmt->execute()) {
            // Optional: Send notification email to admin
            $comment_id = $db->lastInsertId();

            // Get article title for notification
            $article_query = "SELECT title FROM articles WHERE id = :id";
            $article_stmt = $db->prepare($article_query);
            $article_stmt->bindParam(':id', $article_id);
            $article_stmt->execute();
            $article_title = $article_stmt->fetch(PDO::FETCH_ASSOC)['title'];

            // Send email notification (optional)
            // $admin_email = 'admin@dailyfinancefacts.com';
            // $subject = 'New Comment on: ' . $article_title;
            // $message = "A new comment has been submitted on your article '$article_title'.\n\n";
            // $message .= "Name: $name\n";
            // $message .= "Email: $email\n";
            // $message .= "Comment: $content\n\n";
            // $message .= "Review at: https://dailyfinancefacts.com/admin/comments.php";
            // mail($admin_email, $subject, $message);

            echo json_encode([
                'success' => true,
                'message' => 'Thank you for your comment! It has been submitted for review and will appear once approved.',
                'comment_id' => $comment_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit comment. Please try again.']);
        }

    } catch (Exception $e) {
        error_log("Comment submission error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while submitting your comment. Please try again later.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>