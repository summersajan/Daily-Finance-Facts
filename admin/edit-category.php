<?php
require_once '../config/database.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $_POST['edit_category_id'];
    $name = trim($_POST['edit_category_name']);
    $slug = trim($_POST['edit_category_slug']);
    $description = trim($_POST['edit_category_description']);
    $color = $_POST['edit_category_color'] ?: '#0153b7';
    $status = $_POST['edit_category_status'] ?? 'active';

    $database = new Database();
    $db = $database->getConnection();

    // Check for unique slug (excluding self)
    $check = $db->prepare('SELECT COUNT(*) as cnt FROM categories WHERE slug = :slug AND id != :id');
    $check->execute([':slug' => $slug, ':id' => $id]);
    if ($check->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
        header("Location: categories.php?error=Slug+already+exists");
        exit();
    }

    $stmt = $db->prepare(
        "UPDATE categories 
         SET name = :name, slug = :slug, description = :description, color = :color, status = :status 
         WHERE id = :id"
    );
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':slug', $slug);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':color', $color);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        header("Location: categories.php?success=Category+updated!");
    } else {
        header("Location: categories.php?error=Update+failed");
    }
    exit;
} else {
    header("Location: categories.php");
    exit;
}
?>