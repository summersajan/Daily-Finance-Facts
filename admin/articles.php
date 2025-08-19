<?php
$page_title = 'Articles';
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $query = "DELETE FROM articles WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        $success_message = "Article deleted successfully!";
    } else {
        $error_message = "Error deleting article.";
    }
}

// Get all articles with category and author info
$query = "SELECT a.*, c.name as category_name, u.full_name as author_name 
          FROM articles a 
          LEFT JOIN categories c ON a.category_id = c.id 
          LEFT JOIN admin_users u ON a.author_id = u.id 
          ORDER BY a.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div id="alerts-container"></div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Articles Management</h1>
    <a href="add-article.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Article
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Views</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($article['title']); ?></strong>
                                <?php if ($article['is_featured']): ?>
                                    <span class="badge bg-warning ms-1">Featured</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($article['category_name'] ?? 'Uncategorized'); ?></td>
                            <td><?php echo htmlspecialchars($article['author_name'] ?? 'Unknown'); ?></td>
                            <td>
                                <span class="badge bg-<?php
                                echo $article['status'] == 'published' ? 'success' :
                                    ($article['status'] == 'draft' ? 'warning' : 'info');
                                ?>">
                                    <?php echo ucfirst($article['status']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($article['views']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($article['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="../article.php?slug=<?php echo $article['slug']; ?>"
                                        class="btn btn-outline-info" target="_blank" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit-article.php?id=<?php echo $article['id']; ?>"
                                        class="btn btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?action=delete&id=<?php echo $article['id']; ?>"
                                        class="btn btn-outline-danger delete-btn" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>