<?php
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get statistics with optimized queries
$stats = [];

try {
    // Single query for article statistics
    $query = "SELECT 
                COUNT(*) as total_articles,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_articles,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_articles,
                SUM(views) as total_views
              FROM articles";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $article_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stats = array_merge($stats, $article_stats);

    // Other statistics
    $queries = [
        'total_categories' => "SELECT COUNT(*) as total FROM categories WHERE status = 'active'",
        'subscribers' => "SELECT COUNT(*) as total FROM newsletter_subscribers WHERE status = 'active'",
        'pending_comments' => "SELECT COUNT(*) as total FROM comments WHERE status = 'pending'"
    ];

    foreach ($queries as $key => $query) {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Recent articles - optimized query
    $query = "SELECT a.id, a.title, a.status, a.created_at, a.views, c.name as category_name, u.full_name as author_name 
              FROM articles a 
              LEFT JOIN categories c ON a.category_id = c.id 
              LEFT JOIN admin_users u ON a.author_id = u.id 
              ORDER BY a.created_at DESC LIMIT 8";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $stats = [
        'total_articles' => 0,
        'published_articles' => 0,
        'draft_articles' => 0,
        'total_views' => 0,
        'total_categories' => 0,
        'subscribers' => 0,
        'pending_comments' => 0
    ];
    $recent_articles = [];
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="quick-stats text-center mb-4">
        <h1 class="display-4 mb-2">Welcome Back!</h1>
        <p class="lead mb-0"><?php echo $_SESSION['admin_name']; ?> • Last login: <?php echo date('M d, Y - H:i'); ?>
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card lazy-load" onclick="location.href='articles.php'">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h2 class="text-primary mb-1"><?php echo number_format($stats['total_articles']); ?></h2>
                            <p class="text-muted mb-0">Total Articles</p>
                            <small class="text-success">
                                <i class="fas fa-eye"></i> <?php echo number_format($stats['total_views']); ?> views
                            </small>
                        </div>
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-newspaper"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card lazy-load">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h2 class="text-success mb-1"><?php echo number_format($stats['published_articles']); ?>
                            </h2>
                            <p class="text-muted mb-0">Published</p>
                            <small class="text-info">
                                <?php echo $stats['total_articles'] > 0 ? round(($stats['published_articles'] / $stats['total_articles']) * 100) : 0; ?>%
                                of total
                            </small>
                        </div>
                        <div class="stat-icon bg-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card lazy-load">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h2 class="text-warning mb-1"><?php echo number_format($stats['draft_articles']); ?></h2>
                            <p class="text-muted mb-0">Drafts</p>
                            <small class="text-warning">
                                <i class="fas fa-clock"></i> Pending publish
                            </small>
                        </div>
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-edit"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card lazy-load" onclick="location.href='subscribers.php'">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h2 class="text-info mb-1"><?php echo number_format($stats['subscribers']); ?></h2>
                            <p class="text-muted mb-0">Subscribers</p>
                            <small class="text-success">
                                <i class="fas fa-envelope"></i> Newsletter ready
                            </small>
                        </div>
                        <div class="stat-icon bg-info">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="row">
        <!-- Recent Articles -->
        <div class="col-lg-8">
            <div class="card lazy-load">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-newspaper text-primary"></i> Recent Articles
                    </h5>
                    <a href="add-article.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> New Article
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($recent_articles)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Views</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_articles as $article): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars(substr($article['title'], 0, 50)) . (strlen($article['title']) > 50 ? '...' : ''); ?></strong>
                                                <br><small class="text-muted">by
                                                    <?php echo htmlspecialchars($article['author_name'] ?? 'Unknown'); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo htmlspecialchars($article['category_name'] ?? 'Uncategorized'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-<?php echo $article['status'] == 'published' ? 'success' : ($article['status'] == 'draft' ? 'warning' : 'info'); ?>">
                                                    <?php echo ucfirst($article['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <i class="fas fa-eye text-muted"></i>
                                                <?php echo number_format($article['views']); ?>
                                            </td>
                                            <td><?php echo date('M d', strtotime($article['created_at'])); ?></td>
                                            <td>
                                                <a href="edit-article.php?id=<?php echo $article['id']; ?>"
                                                    class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                            <h5>No Articles Yet</h5>
                            <p class="text-muted">Start by creating your first article</p>
                            <a href="add-article.php" class="btn btn-primary">Create Article</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Stats -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card lazy-load">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt text-warning"></i> Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="add-article.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Article
                        </a>
                        <a href="categories.php" class="btn btn-outline-primary">
                            <i class="fas fa-list"></i> Manage Categories
                        </a>
                        <a href="comments.php" class="btn btn-outline-warning">
                            <i class="fas fa-comments"></i> Review Comments
                            <?php if ($stats['pending_comments'] > 0): ?>
                                <span class="badge bg-warning"><?php echo $stats['pending_comments']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="subscribers.php" class="btn btn-outline-success">
                            <i class="fas fa-users"></i> View Subscribers
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Overview -->
            <div class="card mt-3 lazy-load">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie text-success"></i> System Overview
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                        <span><i class="fas fa-list text-primary"></i> Categories</span>
                        <span class="badge bg-primary fs-6"><?php echo $stats['total_categories']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                        <span><i class="fas fa-comments text-warning"></i> Pending Comments</span>
                        <span
                            class="badge bg-<?php echo $stats['pending_comments'] > 0 ? 'warning' : 'success'; ?> fs-6">
                            <?php echo $stats['pending_comments']; ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                        <span><i class="fas fa-envelope text-success"></i> Subscribers</span>
                        <span class="badge bg-success fs-6"><?php echo $stats['subscribers']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                        <span><i class="fas fa-eye text-info"></i> Total Views</span>
                        <span class="badge bg-info fs-6"><?php echo number_format($stats['total_views']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>