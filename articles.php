<?php
$page_title = "All Articles - Daily Finance Facts";
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Pagination setup
$articles_per_page = 12;
$current_page = (int) ($_GET['page'] ?? 1);
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $articles_per_page;

// Search and filter
$search_query = trim($_GET['search'] ?? '');
$category_filter = (int) ($_GET['category'] ?? 0);
$sort_by = $_GET['sort'] ?? 'latest';

// WHERE clause
$where_conditions = ["a.status = 'published'"];
$params = [];

if (!empty($search_query)) {
    $where_conditions[] = "(a.title LIKE :search OR a.content LIKE :search OR a.excerpt LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}
if ($category_filter > 0) {
    $where_conditions[] = "a.category_id = :category_id";
    $params[':category_id'] = $category_filter;
}
$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// ORDER BY clause
switch ($sort_by) {
    case 'oldest':
        $order_clause = 'ORDER BY a.publish_date ASC, a.created_at ASC';
        break;
    case 'popular':
        $order_clause = 'ORDER BY a.views DESC, a.publish_date DESC';
        break;
    case 'alphabetical':
        $order_clause = 'ORDER BY a.title ASC';
        break;
    default:
        $order_clause = 'ORDER BY a.publish_date DESC, a.created_at DESC';
        break;
}

// Total count for pagination
$count_query = "SELECT COUNT(*) as total 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    $where_clause";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value)
    $count_stmt->bindValue($key, $value);
$count_stmt->execute();
$total_articles = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_articles / $articles_per_page);

// Get articles
$articles_query = "SELECT a.*, c.name as category_name, c.color as category_color, u.full_name as author_name 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    LEFT JOIN admin_users u ON a.author_id = u.id 
    $where_clause 
    $order_clause 
    LIMIT :limit OFFSET :offset";
$articles_stmt = $db->prepare($articles_query);
foreach ($params as $key => $value)
    $articles_stmt->bindValue($key, $value);
$articles_stmt->bindValue(':limit', $articles_per_page, PDO::PARAM_INT);
$articles_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$articles_stmt->execute();
$articles = $articles_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter dropdown
$categories_query = "SELECT id, name FROM categories WHERE status = 'active' ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div class="container my-5">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h1 class="mb-2 h2" style="color:var(--primary-blue);font-weight:700;">
                <?php if (!empty($search_query)): ?>
                    Search results for "<?php echo htmlspecialchars($search_query); ?>"
                <?php elseif ($category_filter > 0): ?>
                    <?php
                    $selected_category = array_filter(
                        $categories,
                        fn($cat) => $cat['id'] == $category_filter
                    );
                    $selected_category = reset($selected_category);
                    echo htmlspecialchars($selected_category['name']) . " Articles";
                    ?>
                <?php else: ?>
                    All Articles
                <?php endif; ?>
            </h1>
            <p class="text-muted mb-0">
                Showing <?php echo number_format($total_articles); ?>
                article<?php echo $total_articles != 1 ? 's' : ''; ?>
                <?php if ($total_pages > 1): ?>
                    (Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>)
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>


    <?php if ($articles): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($articles as $article): ?>
                <div class="col">
                    <div class="card article-card h-100 shadow-sm border-0"
                        onclick="window.location.href='article.php?slug=<?php echo urlencode($article['slug']); ?>'">
                        <div class="position-relative">
                            <img src="<?php echo $article['featured_image'] ? 'assets/images/articles/' . $article['featured_image'] : 'https://via.placeholder.com/400x250/0153b7/ffffff?text=' . substr($article['title'], 0, 2); ?>"
                                alt="<?php echo htmlspecialchars($article['title']); ?>" class="card-img-top"
                                style="height:210px;object-fit:cover;">
                            <?php if ($article['category_name']): ?>
                                <span class="badge position-absolute top-0 start-0 m-2"
                                    style="background:<?php echo $article['category_color'] ?: '#0153b7'; ?>">
                                    <?php echo htmlspecialchars($article['category_name']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($article['is_featured']): ?>
                                <span class="badge bg-warning position-absolute top-0 end-0 m-2">
                                    <i class="fas fa-star"></i> Featured
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title" style="color:var(--primary-blue);font-weight:600;">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </h5>
                            <p class="card-text flex-grow-1 text-muted" style="font-size:1rem;">
                                <?php
                                $ex = $article['excerpt'] ?: substr(strip_tags($article['content']), 0, 120) . '...';
                                echo htmlspecialchars($ex);
                                ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center small text-muted mt-3">
                                <span>
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($article['publish_date'] ?: $article['created_at'])); ?>
                                </span>
                                <span>
                                    <i class="fas fa-eye"></i> <?php echo number_format($article['views']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 pt-0">
                            <a href="article.php?slug=<?php echo urlencode($article['slug']); ?>"
                                class="btn btn-outline-primary btn-sm w-100 mt-2">
                                Read More <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-5" aria-label="Articles pagination">
                <ul class="pagination justify-content-center">
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
                    if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif;
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor;
                    if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                <?php echo $total_pages; ?>
                            </a>
                        </li>
                    <?php endif; ?>
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
        <?php endif; ?>

    <?php else: ?>
        <!-- No Articles Found -->
        <div class="text-center py-5">
            <i class="fas fa-search fa-4x text-muted mb-4"></i>
            <h3 class="text-muted mb-3">No Articles Found</h3>
            <p class="text-muted mb-4">
                <?php if (!empty($search_query)): ?>
                    No articles match your search. Try changing your search term.
                <?php elseif ($category_filter > 0): ?>
                    No articles published for this category yet.
                <?php else: ?>
                    No articles have been published yet.
                <?php endif; ?>
            </p>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <?php if (!empty($search_query) || $category_filter > 0): ?>
                    <a href="articles.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All Articles
                    </a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    <?php endif; ?>


</div>
<style>
    /* Card grid and articles styling */
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

    .article-card .badge {
        font-size: 0.77rem;
        letter-spacing: 0.01em;
    }

    .card-footer {
        background: #f8fbff;
        border: none;
    }

    .newsletter-signup {
        background: linear-gradient(87deg, #e6f0fa 0, #fff 100%);
        border-radius: 14px;
        padding: 2.2rem 1.6rem 2rem 1.6rem;
    }

    @media (max-width: 768px) {
        .article-card .card-img-top {
            height: 170px;
        }

        .newsletter-signup {
            padding: 1.2rem;
        }
    }
</style>

<?php include 'footer.php'; ?>