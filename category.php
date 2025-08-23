<?php
$page_title = "Category - Daily Finance Facts";
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get category slug from URL
$category_slug = $_GET['category'] ?? '';

if (empty($category_slug)) {
    header("Location: index.php");
    exit();
}

// Get category details
$cat_query = "SELECT * FROM categories WHERE slug = :slug AND status = 'active'";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->bindParam(':slug', $category_slug);
$cat_stmt->execute();
$category = $cat_stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header("Location: index.php");
    exit();
}

// Pagination
$articles_per_page = 12;
$current_page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $articles_per_page;

// Get total articles count for this category
$count_query = "SELECT COUNT(*) as total FROM articles WHERE category_id = :cat_id AND status = 'published'";
$count_stmt = $db->prepare($count_query);
$count_stmt->bindParam(':cat_id', $category['id']);
$count_stmt->execute();
$total_articles = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_articles / $articles_per_page);

// Get articles for this category
$articles_query = "SELECT a.*, c.name as category_name, c.color as category_color, u.full_name as author_name
                   FROM articles a
                   LEFT JOIN categories c ON a.category_id = c.id
                   LEFT JOIN admin_users u ON a.author_id = u.id
                   WHERE a.category_id = :cat_id AND a.status = 'published'
                   ORDER BY a.publish_date DESC
                   LIMIT :limit OFFSET :offset";

$articles_stmt = $db->prepare($articles_query);
$articles_stmt->bindParam(':cat_id', $category['id'], PDO::PARAM_INT);
$articles_stmt->bindParam(':limit', $articles_per_page, PDO::PARAM_INT);
$articles_stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$articles_stmt->execute();
$articles = $articles_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = $category['name'] . " - Daily Finance Facts";

include 'header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent px-0 mb-3">
            <li class="breadcrumb-item">
                <a href="../../" style="color: var(--primary-blue); text-decoration: none;">
                    <i class="fas fa-home"></i> Home
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page" style="color: var(--primary-blue);">
                <?php echo htmlspecialchars($category['name']); ?>
            </li>
        </ol>
    </nav>

    <!-- Category Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-2 display-5" style="color: var(--primary-blue); font-weight: 700; text-transform: uppercase;">
                <?php echo htmlspecialchars($category['name']); ?>
            </h1>
            <?php if ($category['description']): ?>
                <p class="text-muted mb-3" style="font-size: 1.1rem;">
                    <?php echo htmlspecialchars($category['description']); ?>
                </p>
            <?php endif; ?>
            <p class="text-muted">
                Showing <?php echo number_format($total_articles); ?>
                article<?php echo $total_articles != 1 ? 's' : ''; ?>
                <?php if ($total_pages > 1): ?>
                    (Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>)
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="../../" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Homepage
            </a>
        </div>
    </div>

    <!-- Articles Grid -->
    <?php if ($articles): ?>
        <div class="row">
            <?php foreach ($articles as $article): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card article-card h-100" data-href="../../article/<?php echo urlencode($article['slug']); ?>"
                        style="cursor: pointer;">
                        <img src="<?php echo $article['featured_image'] ? '/assets/images/articles/' . $article['featured_image'] : 'https://via.placeholder.com/400x250/0153b7/ffffff?text=Article'; ?>"
                            alt="<?php echo htmlspecialchars($article['title']); ?>" class="card-img-top"
                            style="height: 220px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title" style="color: var(--primary-blue); font-weight: 600; min-height: 3em;">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </h5>
                            <p class="card-text flex-grow-1 text-muted">
                                <?php echo htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 120) . '...'); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="text-muted small">
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($article['publish_date'] ?: $article['created_at'])); ?>
                                </span>
                                <span class="text-muted small">
                                    <i class="fas fa-eye"></i> <?php echo number_format($article['views']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 pt-0">
                            <a href="../../article/<?php echo urlencode($article['slug']); ?>"
                                class="btn btn-outline-primary btn-sm w-100">
                                Read More <i class="fas fa-arrow-right"></i>
                            </a>

                        </div>
                        <br>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Category pagination" class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="?category=<?php echo urlencode($category_slug); ?>&page=<?php echo $current_page - 1; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="?category=<?php echo urlencode($category_slug); ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="?category=<?php echo urlencode($category_slug); ?>&page=<?php echo $current_page + 1; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-info text-center">
            <h4>No Articles Found</h4>
            <p>No articles have been published in this category yet. Check back soon!</p>
            <a href="../../" class="btn btn-primary">Browse All Categories</a>
        </div>
    <?php endif; ?>

    <!-- Newsletter Signup -->
    <section class="newsletter-signup my-5">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3><i class="fas fa-envelope-open"></i> Stay Updated with Daily Finance Facts</h3>
                <p class="mb-0">Get the latest financial tips and insights delivered to your inbox.</p>
            </div>
            <div class="col-md-4">
                <form id="newsletterForm">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Enter your email" required>
                        <button class="btn btn-light" type="submit">
                            <i class="fas fa-paper-plane"></i> Subscribe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
    $(document).ready(function () {
        // Article card navigation
        $(document).on('click', '[data-href]', function () {
            window.location.href = $(this).data('href');
        });

        // Newsletter subscription
        $('#newsletterForm').on('submit', function (e) {
            e.preventDefault();
            var email = $(this).find('input[type="email"]').val();
            var btn = $(this).find('button[type="submit"]');
            var originalText = btn.html();

            if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                btn.html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: 'subscribe.php',
                    method: 'POST',
                    data: { email: email },
                    dataType: 'json',
                    success: function (response) {
                        alert(response.message || 'Subscribed successfully!');
                        if (response.success) $(this)[0].reset();
                    },
                    complete: function () {
                        btn.html(originalText);
                    }
                });
            } else {
                alert('Please enter a valid email address.');
            }
        });
    });
</script>

<?php include 'footer.php'; ?>