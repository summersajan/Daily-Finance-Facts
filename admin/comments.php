<?php
$page_title = 'Comments';
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle comment actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    $allowed_actions = ['approve', 'reject', 'delete'];

    if (in_array($action, $allowed_actions)) {
        if ($action == 'delete') {
            $query = "DELETE FROM comments WHERE id = :id";
        } else {
            $status = $action == 'approve' ? 'approved' : 'rejected';
            $query = "UPDATE comments SET status = :status WHERE id = :id";
        }

        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($action != 'delete') {
            $stmt->bindParam(':status', $status);
        }

        if ($stmt->execute()) {
            $success_message = "Comment " . ($action == 'delete' ? 'deleted' : $action . 'd') . " successfully!";
        } else {
            $error_message = "Error processing comment.";
        }
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $comment_ids = $_POST['comment_ids'] ?? [];

    if (!empty($comment_ids) && in_array($action, ['approve', 'reject', 'delete'])) {
        $placeholders = str_repeat('?,', count($comment_ids) - 1) . '?';

        if ($action == 'delete') {
            $query = "DELETE FROM comments WHERE id IN ($placeholders)";
        } else {
            $status = $action == 'approve' ? 'approved' : 'rejected';
            $query = "UPDATE comments SET status = '$status' WHERE id IN ($placeholders)";
        }

        $stmt = $db->prepare($query);

        if ($stmt->execute($comment_ids)) {
            $success_message = count($comment_ids) . " comments " . ($action == 'delete' ? 'deleted' : $action . 'd') . " successfully!";
        } else {
            $error_message = "Error processing comments.";
        }
    }
}

// Get comments with article info
$filter = $_GET['filter'] ?? 'all';
$where_clause = '';

switch ($filter) {
    case 'pending':
        $where_clause = "WHERE c.status = 'pending'";
        break;
    case 'approved':
        $where_clause = "WHERE c.status = 'approved'";
        break;
    case 'rejected':
        $where_clause = "WHERE c.status = 'rejected'";
        break;
}

$query = "SELECT c.*, a.title as article_title, a.slug as article_slug 
          FROM comments c 
          LEFT JOIN articles a ON c.article_id = a.id 
          $where_clause
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get comment statistics
$stats_query = "SELECT 
                  COUNT(*) as total,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                  SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                  SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM comments";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<style>
    .comment-content {
        max-width: 100%;
        word-wrap: break-word;
        white-space: pre-wrap;
    }

    .comment-meta {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .status-pending {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
    }

    .status-approved {
        background-color: #d1edff;
        border-left: 4px solid #0d6efd;
    }

    .status-rejected {
        background-color: #f8d7da;
        border-left: 4px solid #dc3545;
    }
</style>

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
    <h1 class="h3 mb-0">Comments Management</h1>
    <div class="btn-group">
        <a href="?filter=all" class="btn btn-outline-primary <?php echo $filter == 'all' ? 'active' : ''; ?>">
            All (<?php echo $stats['total']; ?>)
        </a>
        <a href="?filter=pending" class="btn btn-outline-warning <?php echo $filter == 'pending' ? 'active' : ''; ?>">
            Pending (<?php echo $stats['pending']; ?>)
        </a>
        <a href="?filter=approved" class="btn btn-outline-success <?php echo $filter == 'approved' ? 'active' : ''; ?>">
            Approved (<?php echo $stats['approved']; ?>)
        </a>
        <a href="?filter=rejected" class="btn btn-outline-danger <?php echo $filter == 'rejected' ? 'active' : ''; ?>">
            Rejected (<?php echo $stats['rejected']; ?>)
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Comments List</h5>
                    <div class="d-flex gap-2">
                        <form method="POST" action="" class="d-flex gap-2" id="bulkActionForm">
                            <select name="bulk_action" class="form-select form-select-sm" style="width: auto;">
                                <option value="">Bulk Actions</option>
                                <option value="approve">Approve</option>
                                <option value="reject">Reject</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary"
                                onclick="return confirmBulkAction()">Apply</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($comments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="30">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Comment</th>
                                    <th>Article</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comments as $comment): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="comment_ids[]" value="<?php echo $comment['id']; ?>"
                                                class="form-check-input comment-checkbox" form="bulkActionForm">
                                        </td>
                                        <td>
                                            <div class="mb-1">
                                                <strong><?php echo htmlspecialchars($comment['name']); ?></strong>
                                                <small
                                                    class="text-muted"><?php echo htmlspecialchars($comment['email']); ?></small>
                                            </div>
                                            <p class="mb-0 text-muted">
                                                <?php echo htmlspecialchars(substr($comment['content'], 0, 100)) . (strlen($comment['content']) > 100 ? '...' : ''); ?>
                                            </p>
                                        </td>
                                        <td>
                                            <a href="../article.php?slug=<?php echo $comment['article_slug']; ?>"
                                                target="_blank" class="text-decoration-none">
                                                <?php echo htmlspecialchars(substr($comment['article_title'], 0, 30)) . (strlen($comment['article_title']) > 30 ? '...' : ''); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php
                                            echo $comment['status'] == 'approved' ? 'success' :
                                                ($comment['status'] == 'pending' ? 'warning' : 'danger');
                                            ?>">
                                                <?php echo ucfirst($comment['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($comment['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($comment['status'] == 'pending'): ?>
                                                    <a href="?action=approve&id=<?php echo $comment['id']; ?>&filter=<?php echo $filter; ?>"
                                                        class="btn btn-outline-success" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="?action=reject&id=<?php echo $comment['id']; ?>&filter=<?php echo $filter; ?>"
                                                        class="btn btn-outline-warning" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-info"
                                                    onclick="viewComment(<?php echo $comment['id']; ?>)" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="?action=delete&id=<?php echo $comment['id']; ?>&filter=<?php echo $filter; ?>"
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
                    <div class="text-center py-5">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h5>No Comments Found</h5>
                        <p class="text-muted">
                            <?php echo $filter == 'all' ? 'No comments have been submitted yet' : 'No ' . $filter . ' comments found'; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Comment Statistics</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                        <span><i class="fas fa-comments text-primary"></i> Total</span>
                        <span class="badge bg-primary fs-6"><?php echo $stats['total']; ?></span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                        <span><i class="fas fa-clock text-warning"></i> Pending</span>
                        <span class="badge bg-warning fs-6"><?php echo $stats['pending']; ?></span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                        <span><i class="fas fa-check text-success"></i> Approved</span>
                        <span class="badge bg-success fs-6"><?php echo $stats['approved']; ?></span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                        <span><i class="fas fa-times text-danger"></i> Rejected</span>
                        <span class="badge bg-danger fs-6"><?php echo $stats['rejected']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?filter=pending" class="btn btn-warning">
                        <i class="fas fa-clock"></i> Review Pending
                    </a>
                    <a href="articles.php" class="btn btn-outline-primary">
                        <i class="fas fa-newspaper"></i> Manage Articles
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Comment View Modal -->
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Comment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="commentModalBody">
                <!-- Comment details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
    // Select all functionality
    document.getElementById('selectAll').addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('.comment-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Confirm bulk action
    function confirmBulkAction() {
        const selected = document.querySelectorAll('.comment-checkbox:checked').length;
        const action = document.querySelector('select[name="bulk_action"]').value;

        if (selected === 0) {
            alert('Please select at least one comment.');
            return false;
        }

        if (!action) {
            alert('Please select an action.');
            return false;
        }

        return confirm(`Are you sure you want to ${action} ${selected} comment(s)?`);
    }

    // View comment details
    function viewComment(commentId) {
        // This would typically load comment details via AJAX
        // For now, we'll just show a placeholder
        document.getElementById('commentModalBody').innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading comment details...</p>
        </div>
    `;

        const modal = new bootstrap.Modal(document.getElementById('commentModal'));
        modal.show();

        // Simulate loading (replace with actual AJAX call)
        setTimeout(() => {
            document.getElementById('commentModalBody').innerHTML = `
            <p><strong>Comment ID:</strong> ${commentId}</p>
            <p>Comment details would be loaded here via AJAX in a real implementation.</p>
        `;
        }, 1000);
    }
</script>

<?php include 'includes/footer.php'; ?>