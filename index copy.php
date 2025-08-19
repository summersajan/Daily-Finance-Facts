<?php
$page_title = "Daily Finance Facts - Your Complete Financial Guide";
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();


// Handle search input
$search_query = trim($_GET['search'] ?? '');
$search_results = [];
if ($search_query !== '') {
    $stmt = $db->prepare("SELECT a.*, c.name as category_name, u.full_name as author_name
                          FROM articles a
                          LEFT JOIN categories c ON a.category_id = c.id
                          LEFT JOIN admin_users u ON a.author_id = u.id
                          WHERE a.status = 'published'
                            AND (a.title LIKE :q OR a.excerpt LIKE :q OR a.content LIKE :q)
                          ORDER BY a.publish_date DESC");
    $stmt->execute([':q' => "%$search_query%"]);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get featured article and other content only if not searching
if ($search_query === '') {
    // Fetch all active categories
    $cat_stmt = $db->prepare("SELECT id, name, slug, color FROM categories WHERE status='active' ORDER BY id ASC");
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get featured article
    $featured_query = "SELECT a.*, c.name as category_name, u.full_name as author_name 
                       FROM articles a 
                       LEFT JOIN categories c ON a.category_id = c.id 
                       LEFT JOIN admin_users u ON a.author_id = u.id 
                       WHERE a.status = 'published' AND a.is_featured = 1 
                       ORDER BY a.publish_date DESC LIMIT 1";
    $featured_stmt = $db->prepare($featured_query);
    $featured_stmt->execute();
    $featured_article = $featured_stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent articles (excluding featured)
    $recent_query = "SELECT a.*, c.name as category_name, u.full_name as author_name 
                     FROM articles a 
                     LEFT JOIN categories c ON a.category_id = c.id 
                     LEFT JOIN admin_users u ON a.author_id = u.id 
                     WHERE a.status = 'published'" .
        ($featured_article ? " AND a.id != " . $featured_article['id'] : "") . "
                     ORDER BY a.publish_date DESC LIMIT 6";
    $recent_stmt = $db->prepare($recent_query);
    $recent_stmt->execute();
    $recent_articles = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
}

include 'header.php';
?>

<?php if ($search_query !== ''): ?>
    <!-- SEARCH RESULTS VIEW -->
    <div class="container mt-4">
        <div class="mb-3">
            <nav style="--bs-breadcrumb-divider: '»';" aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent px-0 mb-1">
                    <li class="breadcrumb-item">
                        <a href="index.php" style="font-weight:700;color:var(--primary-blue);">Home</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" style="color:var(--primary-blue);">
                        You searched for
                    </li>
                </ol>
            </nav>
            <h1 style="color:var(--primary-blue);font-weight:800;margin-bottom:2.5rem;text-transform:uppercase;">
                <?php echo htmlspecialchars($search_query); ?>
            </h1>
        </div>
        <?php if ($search_results): ?>
            <?php foreach ($search_results as $article): ?>
                <div class="row mb-5 pb-4 border-bottom">
                    <div class="col-md-4">
                        <a href="article.php?slug=<?php echo urlencode($article['slug']); ?>">
                            <img src="<?php echo $article['featured_image']
                                ? 'assets/images/articles/' . $article['featured_image']
                                : 'assets/images/logo.jpeg'; ?>"
                                alt="<?php echo htmlspecialchars($article['title']); ?>" class="img-fluid rounded"
                                style="width:100%;max-width:340px;height:220px;object-fit:cover;">
                        </a>
                    </div>
                    <div class="col-md-8 d-flex flex-column justify-content-center">
                        <a href="article.php?slug=<?php echo urlencode($article['slug']); ?>" class="text-decoration-none">
                            <h3 class="fw-bold mb-2" style="color:var(--primary-blue); font-size:1.3rem;">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </h3>
                        </a>
                        <p class="mb-2 text-dark" style="font-size:1rem;">
                            <?php echo htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 160) . '...'); ?>
                        </p>
                        <span class="text-muted small">
                            <i class="far fa-calendar"></i>
                            <?php echo date('M d, Y', strtotime($article['publish_date'] ?: $article['created_at'])); ?>
                            <?php if ($article['category_name']): ?>
                                <span class="ms-2">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($article['category_name']); ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info mb-5">
                <i class="fas fa-info-circle"></i> Sorry, no articles matched your search for
                "<?php echo htmlspecialchars($search_query); ?>".
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- ORIGINAL HOMEPAGE CONTENT -->
    <div class="container mt-5">
        <!-- Latest Articles Section -->
        <section class="latest-articles mb-5">
            <div class="section-header">
                <h2 class="section-title">LATEST ARTICLES</h2>
                <div class="title-underline"></div>
            </div>

            <div class="row">
                <!-- Featured Article -->
                <div class="col-lg-6 mb-4">
                    <?php if ($featured_article): ?>
                        <div class="featured-article" data-href="article.php?slug=<?php echo $featured_article['slug']; ?>">
                            <img src="<?php echo $featured_article['featured_image'] ? 'assets/images/articles/' . $featured_article['featured_image'] : 'assets/images/logo.jpeg'; ?>"
                                alt="<?php echo htmlspecialchars($featured_article['title']); ?>" class="img-fluid">
                            <div class="article-content">
                                <h3 class="article-title"><?php echo htmlspecialchars($featured_article['title']); ?></h3>
                                <p class="article-date">
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($featured_article['publish_date'] ?: $featured_article['created_at'])); ?>
                                    <?php if ($featured_article['category_name']): ?>
                                        <span class="ms-2">
                                            <i class="fas fa-tag"></i>
                                            <?php echo htmlspecialchars($featured_article['category_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                                <p class="article-excerpt">
                                    <?php echo htmlspecialchars($featured_article['excerpt'] ?: substr(strip_tags($featured_article['content']), 0, 150) . '...'); ?>
                                </p>
                                <a href="article.php?slug=<?php echo $featured_article['slug']; ?>"
                                    class="btn btn-outline-primary">Read More</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="featured-article">
                            <img src="assets/images/logo.jpeg" alt="Welcome to Daily Finance Facts" class="img-fluid">
                            <div class="article-content">
                                <h3 class="article-title">Welcome to Daily Finance Facts</h3>
                                <p class="article-date"><i class="far fa-calendar"></i> <?php echo date('M d, Y'); ?></p>
                                <p class="article-excerpt">Your trusted source for financial advice, investment tips, and money
                                    management strategies. Check back soon for our latest articles!</p>
                                <a href="articles.php" class="btn btn-outline-primary">Browse Articles</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Articles List -->
                <div class="col-lg-6">
                    <div class="article-list">
                        <?php if (!empty($recent_articles)): ?>
                            <?php foreach ($recent_articles as $article): ?>
                                <div class="article-item d-flex mb-3" data-href="article.php?slug=<?php echo $article['slug']; ?>">
                                    <img src="<?php echo $article['featured_image'] ? 'assets/images/articles/' . $article['featured_image'] : 'assets/images/logo.jpeg' ?>"
                                        alt="<?php echo htmlspecialchars($article['title']); ?>" class="article-thumb rounded">
                                    <div class="article-info ms-3">
                                        <h5 class="article-title-small">
                                            <?php echo htmlspecialchars(substr($article['title'], 0, 60) . (strlen($article['title']) > 60 ? '...' : '')); ?>
                                        </h5>
                                        <p class="article-date-small">
                                            <i class="far fa-calendar"></i>
                                            <?php echo date('M d, Y', strtotime($article['publish_date'] ?: $article['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-newspaper fa-2x text-muted mb-3"></i>
                                <p class="text-muted">No recent articles available yet.</p>
                            </div>
                        <?php endif; ?>

                        <div class="text-center mt-3">
                            <a href="articles.php" class="btn btn-primary">View All Articles</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Dynamic Category Sections -->
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
                <section class="category-section mb-5" id="cat-<?php echo htmlspecialchars($cat['slug']); ?>">
                    <div class="section-header">
                        <h2 class="section-title"><?php echo htmlspecialchars(strtoupper($cat['name'])); ?></h2>
                        <div class="title-underline"
                            style="background-color: <?php echo htmlspecialchars($cat['color'] ?: '#0153b7'); ?>"></div>
                    </div>
                    <div class="row">
                        <?php
                        // Get articles for this category
                        $art_stmt = $db->prepare("SELECT a.*, c.name as category_name, c.color as category_color
                                                  FROM articles a
                                                  LEFT JOIN categories c ON a.category_id = c.id
                                                  WHERE a.status = 'published' AND a.category_id = :catid
                                                  ORDER BY a.publish_date DESC
                                                  LIMIT 6");
                        $art_stmt->bindValue(':catid', $cat['id'], PDO::PARAM_INT);
                        $art_stmt->execute();
                        $cat_articles = $art_stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php if ($cat_articles): ?>
                            <?php foreach ($cat_articles as $article): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card article-card" data-href="article.php?slug=<?php echo urlencode($article['slug']); ?>">
                                        <img src="<?php echo $article['featured_image'] ? 'assets/images/articles/' . $article['featured_image'] : 'assets/images/logo.jpeg'; ?>"
                                            class="card-img-top" alt="<?php echo htmlspecialchars($article['title']); ?>" loading="lazy">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                                            <p class="card-text">
                                                <?php echo htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 100) . '...'); ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <a href="article.php?slug=<?php echo urlencode($article['slug']); ?>"
                                                    class="btn btn-sm btn-outline-primary">Learn More</a>
                                                <small class="text-muted">
                                                    <i class="far fa-calendar"></i>
                                                    <?php echo date('M d', strtotime($article['publish_date'] ?: $article['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    No articles yet in <?php echo htmlspecialchars($cat['name']); ?>. Check back soon for new content!
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Show message if no categories exist -->
            <section class="no-categories mb-5">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h4>Welcome to Daily Finance Facts!</h4>
                    <p>We're setting up our content categories. Please check back soon for financial tips, investment advice,
                        and money management strategies.</p>
                </div>
            </section>
        <?php endif; ?>

        <!-- Newsletter Signup Section -->
        <section class="newsletter-signup mb-5">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3><i class="fas fa-envelope-open"></i> Stay Updated with Daily Finance Facts</h3>
                    <p class="mb-0">Get the latest financial tips, investment strategies, and money management advice
                        delivered to your inbox weekly.</p>
                </div>
                <div class="col-md-4">
                    <form id="newsletterForm">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Enter your email" id="newsletterEmail"
                                required>
                            <button class="btn btn-light" type="submit" id="subscribeBtn">
                                <i class="fas fa-paper-plane"></i> Subscribe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
<?php endif; ?>



<?php include 'footer.php'; ?>