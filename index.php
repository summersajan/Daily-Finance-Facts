<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$page_title = "Daily Finance Facts - Your Complete Financial Guide";
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

/**
 * Helper: Fallback slugify (only used if DB slug missing)
 */
function slugify($text)
{
    $text = trim($text);
    $text = preg_replace('/[^\pL\pN]+/u', '-', $text); // non letters/digits → hyphen
    $text = trim($text, '-');
    $text = preg_replace('/-+/', '-', $text);
    return strtolower($text ?: 'category');
}

// Handle search input with enhanced search capability
$search_query = trim($_GET['search'] ?? '');
$search_results = [];
$categories = [];
$featured_article = null;
$recent_articles = [];

if ($search_query !== '') {
    // SEARCH: Fetch matching results with ranking
    $stmt = $db->prepare("
        SELECT a.id, a.title, a.excerpt, a.content, a.slug, a.featured_image, 
               a.publish_date, a.created_at, a.views,
               c.name as category_name, c.slug as category_slug,
               u.full_name as author_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN admin_users u ON a.author_id = u.id
        WHERE a.status = 'published'
          AND (a.content IS NOT NULL AND TRIM(a.content) != '')
          AND (a.title IS NOT NULL AND TRIM(a.title) != '')
          AND (
              LOWER(a.title) LIKE LOWER(:q) 
              OR LOWER(a.excerpt) LIKE LOWER(:q) 
              OR LOWER(c.name) LIKE LOWER(:q)
              OR (LOWER(a.content) LIKE LOWER(:q) AND a.content NOT LIKE '%<img%>%')
          )
        ORDER BY 
          CASE 
              WHEN LOWER(a.title) LIKE LOWER(:q) THEN 1
              WHEN LOWER(a.excerpt) LIKE LOWER(:q) THEN 2
              WHEN LOWER(c.name) LIKE LOWER(:q) THEN 3
              WHEN LOWER(a.content) LIKE LOWER(:q) THEN 4
              ELSE 5
          END,
          a.publish_date DESC
        LIMIT 50
    ");
    $stmt->execute([':q' => "%$search_query%"]);
    $potential_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Post-filter: remove image tag noise in content matches
    foreach ($potential_results as $article) {
        $match = false;

        if (stripos($article['title'] ?? '', $search_query) !== false)
            $match = true;
        if (stripos($article['excerpt'] ?? '', $search_query) !== false)
            $match = true;
        if (stripos($article['category_name'] ?? '', $search_query) !== false)
            $match = true;

        if (!$match) {
            $clean = preg_replace('/<img[^>]*>/i', '', $article['content'] ?? '');
            $clean = strip_tags($clean);
            if (stripos($clean, $search_query) !== false)
                $match = true;
        }

        if ($match)
            $search_results[] = $article;
    }
} else {
    // NOT SEARCH: Load homepage content
    // Categories
    $cat_stmt = $db->prepare("SELECT id, name, slug, color FROM categories WHERE status='active' ORDER BY id ASC");
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Featured article
    $featured_query = "
        SELECT a.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name 
        FROM articles a 
        LEFT JOIN categories c ON a.category_id = c.id 
        LEFT JOIN admin_users u ON a.author_id = u.id 
        WHERE a.status = 'published' AND a.is_featured = 1 
        ORDER BY a.publish_date DESC 
        LIMIT 1";
    $featured_stmt = $db->prepare($featured_query);
    $featured_stmt->execute();
    $featured_article = $featured_stmt->fetch(PDO::FETCH_ASSOC);

    // Recent articles (exclude featured)
    $recent_query = "
        SELECT a.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name 
        FROM articles a 
        LEFT JOIN categories c ON a.category_id = c.id 
        LEFT JOIN admin_users u ON a.author_id = u.id 
        WHERE a.status = 'published' " . ($featured_article ? "AND a.id != " . (int) $featured_article['id'] : "") . "
        ORDER BY a.publish_date DESC 
        LIMIT 4";
    $recent_stmt = $db->prepare($recent_query);
    $recent_stmt->execute();
    $recent_articles = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
}

include 'header.php';
?>

<?php if ($search_query !== ''): ?>
    <!-- =================== SEARCH RESULTS VIEW =================== -->
    <div class="container mt-4">
        <div class="mb-3">
            <nav style="--bs-breadcrumb-divider: '»';" aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent px-0 mb-1">
                    <li class="breadcrumb-item">
                        <a href="/" style="font-weight:700;color:var(--primary-blue);">Home</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" style="color:var(--primary-blue);">
                        You searched fors
                    </li>
                </ol>
            </nav>
            <h1 style="color:var(--primary-blue);font-weight:800;margin-bottom:2.5rem;text-transform:uppercase;">
                <?php echo htmlspecialchars($search_query); ?>
            </h1>
        </div>

        <?php if (!empty($search_results)): ?>
            <?php foreach ($search_results as $article): ?>
                <div class="row mb-5 pb-4 border-bottom">
                    <div class="col-md-4">
                        <a href="/article/<?php echo htmlspecialchars($article['slug']); ?>">
                            <img src="<?php echo !empty($article['featured_image'])
                                ? '/assets/images/articles/' . htmlspecialchars($article['featured_image'])
                                : 'https://via.placeholder.com/340x220/0153b7/ffffff?text=Article'; ?>"
                                alt="<?php echo htmlspecialchars($article['title']); ?>" class="img-fluid rounded"
                                style="width:100%;max-width:340px;height:220px;object-fit:cover;">
                        </a>
                    </div>
                    <div class="col-md-8 d-flex flex-column justify-content-center">
                        <a href="/article/<?php echo htmlspecialchars($article['slug']); ?>" class="text-decoration-none">
                            <h3 class="fw-bold mb-2" style="color:var(--primary-blue); font-size:1.3rem;">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </h3>
                        </a>
                        <p class="mb-2 text-dark" style="font-size:1rem;">
                            <?php
                            $ex = $article['excerpt'] ?: substr(strip_tags($article['content']), 0, 160) . '...';
                            echo htmlspecialchars($ex);
                            ?>
                        </p>
                        <span class="text-muted small">
                            <i class="far fa-calendar"></i>
                            <?php echo date('M d, Y', strtotime($article['publish_date'] ?: $article['created_at'])); ?>
                            <?php if (!empty($article['category_name'])):
                                $catSlug = $article['category_slug'] ?: slugify($article['category_name']); ?>
                                <span class="ms-2">
                                    <i class="fas fa-tag"></i>
                                    <a href="/articles/<?php echo htmlspecialchars($catSlug); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($article['category_name']); ?>
                                    </a>
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info mb-5">
                <i class="fas fa-info-circle"></i> Sorry, no articles matched your search for
                "<strong><?php echo htmlspecialchars($search_query); ?></strong>".
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="/articles" class="btn btn-primary"><i class="fas fa-list"></i> View All Articles</a>
            <a href="/" class="btn btn-outline-primary ms-2"><i class="fas fa-home"></i> Back to Home</a>
        </div>
    </div>

<?php else: ?>
    <!-- =================== HOMEPAGE VIEW =================== -->
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
                        <div class="featured-article"
                            data-href="/article/<?php echo htmlspecialchars($featured_article['slug']); ?>">
                            <img src="<?php echo !empty($featured_article['featured_image'])
                                ? '/assets/images/articles/' . htmlspecialchars($featured_article['featured_image'])
                                : 'https://via.placeholder.com/500x300/0153b7/ffffff?text=Featured+Article'; ?>"
                                alt="<?php echo htmlspecialchars($featured_article['title']); ?>" class="img-fluid">
                            <div class="article-content">
                                <h3 class="article-title"><?php echo htmlspecialchars($featured_article['title']); ?></h3>
                                <p class="article-date">
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($featured_article['publish_date'] ?: $featured_article['created_at'])); ?>
                                    <?php if (!empty($featured_article['category_name'])):
                                        $catSlug = $featured_article['category_slug'] ?: slugify($featured_article['category_name']); ?>
                                        <span class="ms-2">
                                            <i class="fas fa-tag"></i>
                                            <a class="text-decoration-none"
                                                href="/articles/<?php echo htmlspecialchars($catSlug); ?>">
                                                <?php echo htmlspecialchars($featured_article['category_name']); ?>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                </p>
                                <p class="article-excerpt">
                                    <?php echo htmlspecialchars($featured_article['excerpt'] ?: substr(strip_tags($featured_article['content']), 0, 150) . '...'); ?>
                                </p>
                                <br><br>
                                <a href="/article/<?php echo htmlspecialchars($featured_article['slug']); ?>"
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
                                <a href="/articles" class="btn btn-outline-primary">Browse Articles</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Articles List -->
                <div class="col-lg-6">
                    <div class="article-list">
                        <?php if (!empty($recent_articles)): ?>
                            <?php foreach ($recent_articles as $article): ?>
                                <div class="article-item d-flex mb-3"
                                    data-href="/article/<?php echo htmlspecialchars($article['slug']); ?>">
                                    <img src="<?php echo !empty($article['featured_image'])
                                        ? '/assets/images/articles/' . htmlspecialchars($article['featured_image'])
                                        : 'https://via.placeholder.com/80x80/0153b7/ffffff?text=' . urlencode(substr($article['title'], 0, 1)); ?>"
                                        alt="<?php echo htmlspecialchars($article['title']); ?>" class="article-thumb rounded">
                                    <div class="article-info ms-3">
                                        <h5 class="article-title-small mb-1">
                                            <a class="text-decoration-none"
                                                href="/article/<?php echo htmlspecialchars($article['slug']); ?>">
                                                <?php echo htmlspecialchars(substr($article['title'], 0, 60) . (strlen($article['title']) > 60 ? '...' : '')); ?>
                                            </a>
                                        </h5>
                                        <p class="article-date-small mb-0">
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
                            <a href="/articles" class="btn btn-primary">View All Articles</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Dynamic Category Sections with Load More -->
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat):
                $catSlug = !empty($cat['slug']) ? $cat['slug'] : slugify($cat['name']); ?>
                <section class="category-section mb-5" id="cat-<?php echo htmlspecialchars($catSlug); ?>">
                    <div class="section-header d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="section-title mb-1"><?php echo htmlspecialchars(strtoupper($cat['name'])); ?></h2>
                            <div class="title-underline"
                                style="background-color: <?php echo htmlspecialchars($cat['color'] ?: '#0153b7'); ?>"></div>
                        </div>
                        <div>
                            <a href="/articles/<?php echo htmlspecialchars($catSlug); ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list"></i> More <?php echo htmlspecialchars($cat['name']); ?>
                            </a>
                        </div>
                    </div>

                    <div class="row articles-container" id="category-<?php echo (int) $cat['id']; ?>-articles">
                        <?php
                        $art_stmt = $db->prepare("
                            SELECT a.*, c.name as category_name, c.color as category_color, c.slug as category_slug
                            FROM articles a
                            LEFT JOIN categories c ON a.category_id = c.id
                            WHERE a.status = 'published' AND a.category_id = :catid
                            ORDER BY a.publish_date DESC
                            LIMIT 6
                        ");
                        $art_stmt->bindValue(':catid', $cat['id'], PDO::PARAM_INT);
                        $art_stmt->execute();
                        $cat_articles = $art_stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php if ($cat_articles): ?>
                            <?php foreach ($cat_articles as $article): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card article-card h-100"
                                        data-href="/article/<?php echo htmlspecialchars($article['slug']); ?>">
                                        <img src="<?php echo !empty($article['featured_image'])
                                            ? '/assets/images/articles/' . htmlspecialchars($article['featured_image'])
                                            : 'https://via.placeholder.com/400x250/0153b7/ffffff?text=' . urlencode($cat['name']); ?>"
                                            class="card-img-top" alt="<?php echo htmlspecialchars($article['title']); ?>" loading="lazy">
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                                            <p class="card-text flex-grow-1">
                                                <?php echo htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 100) . '...'); ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                                <a href="/article/<?php echo htmlspecialchars($article['slug']); ?>"
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
                                    No articles yet in <?php echo htmlspecialchars($cat['name']); ?>. Check back soon!
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Load More Button -->
                    <?php
                    $count_stmt = $db->prepare("SELECT COUNT(*) as total FROM articles WHERE status = 'published' AND category_id = :catid");
                    $count_stmt->bindValue(':catid', $cat['id'], PDO::PARAM_INT);
                    $count_stmt->execute();
                    $total_articles = (int) ($count_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
                    if ($total_articles > 6): ?>
                        <div class="text-center mt-3">
                            <button class="btn btn-outline-primary load-more-btn" data-category-id="<?php echo (int) $cat['id']; ?>"
                                data-offset="6">
                                <i class="fas fa-plus"></i> Load More Articles
                            </button>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        <?php else: ?>
            <section class="no-categories mb-5">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h4>Welcome to Daily Finance Facts!</h4>
                    <p>We're setting up our content categories. Please check back soon for fresh insights.</p>
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
    // Requires jQuery (assumed loaded in header)
    $(document).ready(function () {
        // Fast article navigation using data-href
        $(document).on('click', '[data-href]', function () {
            window.location.href = $(this).data('href');
        });

        // Load More Articles
        $(document).on('click', '.load-more-btn', function () {
            const btn = $(this);
            const categoryId = btn.data('category-id');
            const offset = parseInt(btn.data('offset'), 10) || 6;
            const originalText = btn.html();

            btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...');
            btn.prop('disabled', true);

            $.ajax({
                url: '/load-more-posts.php',
                method: 'POST',
                data: { category_id: categoryId, offset: offset },
                dataType: 'json',
                success: function (response) {
                    if (response.success && response.html) {
                        $('#category-' + categoryId + '-articles').append(response.html);
                        btn.data('offset', offset + 6);
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

        // Article card hover effects
        $('.article-card').hover(
            function () { $(this).find('.card-title').css('color', 'var(--primary-blue)'); },
            function () { $(this).find('.card-title').css('color', 'var(--text-dark)'); }
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

        $(document).on('click', '#backToTop', function () {
            $('html, body').animate({ scrollTop: 0 }, 600);
        });
    });

    // Email validation (optional, if you plan to use it)
    function validateEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
</script>

<style>
    /* Card grid / visuals */
    .article-card {
        cursor: pointer;
        transition: all 0.12s ease;
        border: none;
        box-shadow: 0 2px 10px rgba(1, 83, 183, 0.06);
        border-radius: 15px;
        background: #fff;
    }

    .article-card:hover {
        transform: translateY(-5px) scale(1.01);
        box-shadow: 0 8px 26px rgba(1, 83, 183, 0.15);
    }

    .article-card .card-img-top {
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
    }



    .section-title {
        font-weight: 800;
        color: var(--primary-blue);
        letter-spacing: .02em;
    }

    .title-underline {
        height: 4px;
        width: 70px;
        border-radius: 3px;
        background: #0153b7;
        margin-top: 6px;
    }

    .article-title-small a {
        color: var(--primary-blue);
    }

    .article-title-small a:hover {
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .article-card .card-img-top {
            height: 170px;
            object-fit: cover;
        }


    }
</style>

<?php include 'footer.php'; ?>