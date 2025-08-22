<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$page_title = "Daily Finance Facts - Your Complete Financial Guide";
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle search input with enhanced search capability
$search_query = trim($_GET['search'] ?? '');
$search_results = [];
if ($search_query !== '') {
    $stmt = $db->prepare("SELECT a.id, a.title, a.excerpt, a.content, a.slug, a.featured_image, 
                                 a.publish_date, a.created_at, a.views,
                                 c.name as category_name, u.full_name as author_name
                          FROM articles a
                          LEFT JOIN categories c ON a.category_id = c.id
                          LEFT JOIN admin_users u ON a.author_id = u.id
                          WHERE a.status = 'published'
                            AND (a.content IS NOT NULL AND TRIM(a.content) != '')
                            AND (a.title IS NOT NULL AND TRIM(a.title) != '')
                            AND (
                                a.title LIKE :q 
                                OR a.excerpt LIKE :q 
                                OR (a.content LIKE :q AND a.content NOT LIKE '%<img%>%')
                            )
                          ORDER BY 
                            CASE 
                                WHEN a.title LIKE :q THEN 1
                                WHEN a.excerpt LIKE :q THEN 2
                                WHEN a.content LIKE :q THEN 3
                                ELSE 4
                            END,
                            a.publish_date DESC
                          LIMIT 50");
    $stmt->execute([':q' => "%$search_query%"]);
    $potential_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Further filter in PHP to ensure no image-related matches
    foreach ($potential_results as $article) {
        $clean_content = preg_replace('/<img[^>]*>/i', '', $article['content']);
        $clean_content = strip_tags($clean_content);

        if (
            stripos($article['title'], $search_query) !== false ||
            stripos($article['excerpt'], $search_query) !== false ||
            stripos($clean_content, $search_query) !== false
        ) {
            $search_results[] = $article;
        }
    }
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
                     ORDER BY a.publish_date DESC LIMIT 4";
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
                                : 'https://via.placeholder.com/340x220/0153b7/ffffff?text=Article'; ?>"
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
                <div class="col-lg-6 mb-4" style="margin-top:7px;">
                    <?php if ($featured_article): ?>
                        <div class="featured-article" data-href="article.php?slug=<?php echo $featured_article['slug']; ?>">
                            <img src="<?php echo $featured_article['featured_image'] ? 'assets/images/articles/' . $featured_article['featured_image'] : 'https://via.placeholder.com/500x300/0153b7/ffffff?text=Featured+Article'; ?>"
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
                                <br> <br>
                                <a href="article.php?slug=<?php echo $featured_article['slug']; ?>"
                                    class="btn btn-outline-primary">Read More</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="featured-article">
                            <img src="https://via.placeholder.com/500x300/0153b7/ffffff?text=Welcome+to+Daily+Finance+Facts"
                                alt="Welcome to Daily Finance Facts" class="img-fluid">
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
                                    <img src="<?php echo $article['featured_image'] ? 'assets/images/articles/' . $article['featured_image'] : 'https://via.placeholder.com/80x80/0153b7/ffffff?text=' . substr($article['title'], 0, 1); ?>"
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

        <!-- Dynamic Category Sections with Load More -->
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
                <section class="category-section mb-5" id="cat-<?php echo htmlspecialchars($cat['slug']); ?>">
                    <div class="section-header">
                        <h2 class="section-title"><?php echo htmlspecialchars(strtoupper($cat['name'])); ?></h2>
                        <div class="title-underline"
                            style="background-color: <?php echo htmlspecialchars($cat['color'] ?: '#0153b7'); ?>"></div>
                    </div>
                    <div class="row articles-container" id="category-<?php echo $cat['id']; ?>-articles">
                        <?php
                        // Get articles for this category (now 6 instead of 3)
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
                                    <div class="card article-card h-100"
                                        data-href="article.php?slug=<?php echo urlencode($article['slug']); ?>">
                                        <img src="<?php echo $article['featured_image'] ? 'assets/images/articles/' . $article['featured_image'] : 'https://via.placeholder.com/400x250/0153b7/ffffff?text=' . urlencode($cat['name']); ?>"
                                            class="card-img-top" alt="<?php echo htmlspecialchars($article['title']); ?>" loading="lazy">
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                                            <p class="card-text flex-grow-1">
                                                <?php echo htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 100) . '...'); ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center mt-auto">
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

                    <!-- Load More Button -->
                    <?php
                    // Check if there are more articles
                    $count_stmt = $db->prepare("SELECT COUNT(*) as total FROM articles WHERE status = 'published' AND category_id = :catid");
                    $count_stmt->bindValue(':catid', $cat['id'], PDO::PARAM_INT);
                    $count_stmt->execute();
                    $total_articles = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    if ($total_articles > 6): ?>
                        <div class="text-center mt-3">
                            <button class="btn btn-outline-primary load-more-btn" data-category-id="<?php echo $cat['id']; ?>"
                                data-offset="6">
                                <i class="fas fa-plus"></i> Load More Articles
                            </button>
                        </div>
                    <?php endif; ?>
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

<script>
    $(document).ready(function () {


        // Load More Articles Functionality
        $(document).on('click', '.load-more-btn', function () {
            const btn = $(this);
            const categoryId = btn.data('category-id');
            const offset = btn.data('offset');
            const originalText = btn.html();

            btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...');
            btn.prop('disabled', true);

            $.ajax({
                url: 'load-more-posts.php',
                method: 'POST',
                data: {
                    category_id: categoryId,
                    offset: offset
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success && response.html) {
                        // Append new articles
                        $('#category-' + categoryId + '-articles').append(response.html);

                        // Update offset
                        btn.data('offset', offset + 6);

                        // Hide button if no more articles
                        if (!response.hasMore) {
                            btn.hide();
                        } else {
                            btn.html(originalText);
                            btn.prop('disabled', false);
                        }
                    } else {
                        btn.hide();
                    }
                },
                error: function () {
                    btn.html(originalText);
                    btn.prop('disabled', false);
                    alert('Error loading more articles. Please try again.');
                }
            });
        });


        // Fast article navigation using data-href
        $(document).on('click', '[data-href]', function () {
            window.location.href = $(this).data('href');
        });

        // Clear search functionality
        /* const searchInput = document.querySelector('input[name="search"]');
         if (searchInput) {
             let timeout;
             searchInput.addEventListener('input', function () {
                 clearTimeout(timeout);
                 if (this.value.trim() === '') {
                     timeout = setTimeout(() => {
                         if (this.value.trim() === '') {
                             window.location.href = 'index.php';
                         }
                     }, 300);
                 }
             });
 
             // Clear on escape key
             searchInput.addEventListener('keydown', function (e) {
                 if (e.key === 'Escape') {
                     this.value = '';
                     window.location.href = 'index.php';
                 }
             });
         }*/

        // Article card hover effects
        $('.article-card').hover(
            function () {
                $(this).find('.card-title').css('color', 'var(--primary-blue)');
            },
            function () {
                $(this).find('.card-title').css('color', 'var(--text-dark)');
            }
        );

        // Back to top button
        $(window).scroll(function () {
            if ($(this).scrollTop() > 300) {
                if (!$('#backToTop').length) {
                    $('body').append('<button id="backToTop" class="btn btn-primary position-fixed" style="bottom: 30px; right: 30px; z-index: 1000; border-radius: 50%; width: 50px; height: 50px; box-shadow: 0 4px 12px rgba(1, 83, 183, 0.3);"><i class="fas fa-arrow-up"></i></button>');
                }
            } else {
                $('#backToTop').remove();
            }
        });

        // Handle back to top button click
        $(document).on('click', '#backToTop', function () {
            $('html, body').animate({ scrollTop: 0 }, 600);
        });
    });

    // Email validation function
    function validateEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }


</script>

<?php include 'footer.php'; ?>