<?php
$page_title = 'Categories';
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Check if category has articles
    $check_query = "SELECT COUNT(*) as count FROM articles WHERE category_id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $id);
    $check_stmt->execute();
    $article_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($article_count > 0) {
        $error_message = "Cannot delete category. It has {$article_count} articles assigned to it.";
    } else {
        $query = "DELETE FROM categories WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            $success_message = "Category deleted successfully!";
        } else {
            $error_message = "Error deleting category.";
        }
    }
}

// Handle add category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $color = $_POST['color'];

    if (!empty($name) && !empty($slug)) {
        $query = "INSERT INTO categories (name, slug, description, color) VALUES (:name, :slug, :description, :color)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':color', $color);

        if ($stmt->execute()) {
            $success_message = "Category added successfully!";
        } else {
            $error_message = "Error adding category.";
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Get all categories with article count
$query = "SELECT c.*, COUNT(a.id) as article_count 
          FROM categories c 
          LEFT JOIN articles a ON c.id = a.category_id 
          GROUP BY c.id 
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div id="alerts-container"></div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Categories Management</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="fas fa-plus"></i> Add New Category
    </button>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">All Categories</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($categories)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Articles</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2"
                                                    style="width: 20px; height: 20px; background-color: <?php echo $category['color']; ?>; border-radius: 3px;">
                                                </div>
                                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                            </div>
                                            <?php if ($category['description']): ?>
                                                <small
                                                    class="text-muted"><?php echo htmlspecialchars(substr($category['description'], 0, 50)) . (strlen($category['description']) > 50 ? '...' : ''); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($category['slug']); ?></code></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $category['article_count']; ?>
                                                articles</span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-<?php echo $category['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($category['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="editCategory(
        <?php echo $category['id']; ?>,
        '<?php echo addslashes(htmlspecialchars($category['name'])); ?>',
        '<?php echo addslashes(htmlspecialchars($category['slug'])); ?>',
        '<?php echo addslashes(htmlspecialchars($category['description'])); ?>',
        '<?php echo addslashes(htmlspecialchars($category['color'])); ?>',
        '<?php echo addslashes(htmlspecialchars($category['status'])); ?>'
    )" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <a href="?action=delete&id=<?php echo $category['id']; ?>"
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
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-list fa-3x text-muted mb-3"></i>
                        <h5>No Categories Found</h5>
                        <p class="text-muted">Start by creating your first category</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            Create Category
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Category Statistics</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Total Categories</small>
                    <h4 class="text-primary"><?php echo count($categories); ?></h4>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Active Categories</small>
                    <h4 class="text-success">
                        <?php echo count(array_filter($categories, function ($cat) {
                            return $cat['status'] == 'active';
                        })); ?>
                    </h4>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Total Articles</small>
                    <h4 class="text-info"><?php echo array_sum(array_column($categories, 'article_count')); ?></h4>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                    <a href="articles.php" class="btn btn-outline-primary">
                        <i class="fas fa-newspaper"></i> Manage Articles
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug *</label>
                        <input type="text" class="form-control" id="slug" name="slug" required>
                        <div class="form-text">URL-friendly version of the name</div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="color" class="form-label">Color</label>
                        <input type="color" class="form-control form-control-color" id="color" name="color"
                            value="#2d5b4f">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_category" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="edit-category.php" id="editCategoryForm">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_category_id" id="editCategoryId">
                    <div class="mb-3">
                        <label for="editCategoryName" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="editCategoryName" name="edit_category_name"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="editCategorySlug" class="form-label">Slug *</label>
                        <input type="text" class="form-control" id="editCategorySlug" name="edit_category_slug"
                            required>
                        <div class="form-text">URL-friendly version of the name</div>
                    </div>
                    <div class="mb-3">
                        <label for="editCategoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editCategoryDescription" name="edit_category_description"
                            rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editCategoryColor" class="form-label">Color</label>
                        <input type="color" class="form-control form-control-color" id="editCategoryColor"
                            name="edit_category_color">
                    </div>
                    <div class="mb-3">
                        <label for="editCategoryStatus" class="form-label">Status</label>
                        <select class="form-select" id="editCategoryStatus" name="edit_category_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function editCategory(id, name, slug, description, color, status) {
        document.getElementById('editCategoryId').value = id;
        document.getElementById('editCategoryName').value = name;
        document.getElementById('editCategorySlug').value = slug;
        document.getElementById('editCategoryDescription').value = description;
        document.getElementById('editCategoryColor').value = color || '#0153b7';
        document.getElementById('editCategoryStatus').value = status || 'active';
        var modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        modal.show();
    }
</script>


<?php include 'includes/footer.php'; ?>