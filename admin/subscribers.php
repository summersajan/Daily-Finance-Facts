<?php
$page_title = 'Newsletter Subscribers';
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    switch ($action) {
        case 'delete':
            $query = "DELETE FROM newsletter_subscribers WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                $success_message = "Subscriber deleted successfully!";
            } else {
                $error_message = "Error deleting subscriber.";
            }
            break;

        case 'unsubscribe':
            $query = "UPDATE newsletter_subscribers SET status = 'unsubscribed' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                $success_message = "Subscriber unsubscribed successfully!";
            } else {
                $error_message = "Error updating subscriber status.";
            }
            break;

        case 'resubscribe':
            $query = "UPDATE newsletter_subscribers SET status = 'active' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                $success_message = "Subscriber reactivated successfully!";
            } else {
                $error_message = "Error updating subscriber status.";
            }
            break;
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $subscriber_ids = $_POST['subscriber_ids'] ?? [];

    if (!empty($subscriber_ids) && in_array($action, ['delete', 'unsubscribe', 'resubscribe'])) {
        $placeholders = str_repeat('?,', count($subscriber_ids) - 1) . '?';

        switch ($action) {
            case 'delete':
                $query = "DELETE FROM newsletter_subscribers WHERE id IN ($placeholders)";
                break;
            case 'unsubscribe':
                $query = "UPDATE newsletter_subscribers SET status = 'unsubscribed' WHERE id IN ($placeholders)";
                break;
            case 'resubscribe':
                $query = "UPDATE newsletter_subscribers SET status = 'active' WHERE id IN ($placeholders)";
                break;
        }

        $stmt = $db->prepare($query);

        if ($stmt->execute($subscriber_ids)) {
            $count = count($subscriber_ids);
            $success_message = "$count subscribers " . ($action == 'delete' ? 'deleted' : ($action == 'unsubscribe' ? 'unsubscribed' : 'reactivated')) . " successfully!";
        } else {
            $error_message = "Error processing bulk action.";
        }
    }
}

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $query = "SELECT email, status, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subscribers_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Email', 'Status', 'Subscribed Date']);

    foreach ($subscribers as $subscriber) {
        fputcsv($output, [
            $subscriber['email'],
            ucfirst($subscriber['status']),
            date('M d, Y H:i', strtotime($subscriber['subscribed_at']))
        ]);
    }

    fclose($output);
    exit();
}

// Pagination and filtering
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$per_page = 25;
$current_page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $per_page;

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($filter != 'all') {
    $where_conditions[] = "status = :filter";
    $params[':filter'] = $filter;
}

if (!empty($search)) {
    $where_conditions[] = "email LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM newsletter_subscribers $where_clause";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_subscribers = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_subscribers / $per_page);

// Get subscribers
$query = "SELECT * FROM newsletter_subscribers $where_clause ORDER BY subscribed_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                  COUNT(*) as total,
                  SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                  SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed
                FROM newsletter_subscribers";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

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
    <h1 class="h3 mb-0">Newsletter Subscribers</h1>
    <div class="btn-group">
        <a href="?export=csv<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $filter != 'all' ? '&filter=' . $filter : ''; ?>"
            class="btn btn-outline-success">
            <i class="fas fa-download"></i> Export CSV
        </a>
        <!--
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newsletterModal">
            <i class="fas fa-envelope"></i> Send Newsletter
        </button>-->
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="text-primary"><?php echo number_format($stats['total']); ?></h3>
                        <p class="text-muted mb-0">Total Subscribers</p>
                    </div>
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="text-success"><?php echo number_format($stats['active']); ?></h3>
                        <p class="text-muted mb-0">Active Subscribers</p>
                    </div>
                    <div class="stat-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="text-warning"><?php echo number_format($stats['unsubscribed']); ?></h3>
                        <p class="text-muted mb-0">Unsubscribed</p>
                    </div>
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-user-times"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter 
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search Email</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Search by email..."
                    value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <label for="filter" class="form-label">Filter by Status</label>
                <select class="form-select" id="filter" name="filter">
                    <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Subscribers</option>
                    <option value="active" <?php echo $filter == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="unsubscribed" <?php echo $filter == 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed
                    </option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search"></i> Filter
                </button>
                <?php if (!empty($search) || $filter != 'all'): ?>
                    <a href="subscribers.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
-->
<!-- Subscribers Table -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                Subscribers List
                <small class="text-muted">
                    (Showing <?php echo number_format($total_subscribers); ?>
                    subscriber<?php echo $total_subscribers != 1 ? 's' : ''; ?>)
                </small>
            </h5>

            <form method="POST" action="" class="d-flex gap-2" id="bulkActionForm">
                <select name="bulk_action" class="form-select form-select-sm" style="width: auto;">
                    <option value="">Bulk Actions</option>
                    <option value="unsubscribe">Unsubscribe</option>
                    <option value="resubscribe">Reactivate</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirmBulkAction()">
                    Apply
                </button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($subscribers)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>Email Address</th>
                            <th>Status</th>
                            <th>Subscribed Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $subscriber): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="subscriber_ids[]" value="<?php echo $subscriber['id']; ?>"
                                        class="form-check-input subscriber-checkbox" form="bulkActionForm">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($subscriber['email']); ?></strong>
                                </td>
                                <td>
                                    <span
                                        class="badge bg-<?php echo $subscriber['status'] == 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($subscriber['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M d, Y H:i', strtotime($subscriber['subscribed_at'])); ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($subscriber['status'] == 'active'): ?>
                                            <a href="?action=unsubscribe&id=<?php echo $subscriber['id']; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"
                                                class="btn btn-outline-warning" title="Unsubscribe">
                                                <i class="fas fa-user-times"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=resubscribe&id=<?php echo $subscriber['id']; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"
                                                class="btn btn-outline-success" title="Reactivate">
                                                <i class="fas fa-user-check"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="?action=delete&id=<?php echo $subscriber['id']; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"
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

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Subscribers pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);

                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <a class="page-link"
                                        href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5>No Subscribers Found</h5>
                <p class="text-muted">
                    <?php if (!empty($search)): ?>
                        No subscribers match your search criteria.
                    <?php elseif ($filter != 'all'): ?>
                        No <?php echo $filter; ?> subscribers found.
                    <?php else: ?>
                        No newsletter subscribers yet. They will appear here when people subscribe.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Newsletter Modal -->
<div class="modal fade" id="newsletterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Newsletter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newsletterForm">
                    <div class="mb-3">
                        <label for="newsletter-subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="newsletter-subject" required
                            placeholder="Enter newsletter subject">
                    </div>

                    <div class="mb-3">
                        <label for="newsletter-content" class="form-label">Content</label>
                        <textarea class="form-control" id="newsletter-content" rows="10" required
                            placeholder="Write your newsletter content here..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Send To</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_to" value="active" id="send-active"
                                checked>
                            <label class="form-check-label" for="send-active">
                                Active Subscribers (<?php echo $stats['active']; ?>)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_to" value="all" id="send-all">
                            <label class="form-check-label" for="send-all">
                                All Subscribers (<?php echo $stats['total']; ?>)
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="send-newsletter">
                    <i class="fas fa-paper-plane"></i> Send Newsletter
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Select all functionality
    document.getElementById('selectAll').addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('.subscriber-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Confirm bulk action
    function confirmBulkAction() {
        const selected = document.querySelectorAll('.subscriber-checkbox:checked').length;
        const action = document.querySelector('select[name="bulk_action"]').value;

        if (selected === 0) {
            alert('Please select at least one subscriber.');
            return false;
        }

        if (!action) {
            alert('Please select an action.');
            return false;
        }

        return confirm(`Are you sure you want to ${action} ${selected} subscriber(s)?`);
    }

    // Newsletter sending
    document.getElementById('send-newsletter').addEventListener('click', function () {
        const subject = document.getElementById('newsletter-subject').value;
        const content = document.getElementById('newsletter-content').value;
        const sendTo = document.querySelector('input[name="send_to"]:checked').value;

        if (!subject || !content) {
            alert('Please fill in all fields.');
            return;
        }

        if (!confirm('Are you sure you want to send this newsletter?')) {
            return;
        }

        // This would be implemented with a separate PHP file
        alert('Newsletter functionality would be implemented with a separate send-newsletter.php file.');
    });
</script>

<?php include 'includes/footer.php'; ?>