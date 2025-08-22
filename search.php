<?php
$page_title = "Search Results - Daily Finance Facts";
require_once 'config/database.php';


$database = new Database();
$db = $database->getConnection();




$search_query = trim($_GET['search'] ?? '');
$search_results = [];
if ($search_query !== '') {
    // Enhanced search including category names and tags (without meta_keywords)
    $stmt = $db->prepare("SELECT DISTINCT a.*, c.name as category_name, u.full_name as author_name
                          FROM articles a
                          LEFT JOIN categories c ON a.category_id = c.id
                          LEFT JOIN admin_users u ON a.author_id = u.id
                          WHERE a.status = 'published'
                            AND (a.title LIKE :q 
                                 OR a.excerpt LIKE :q 
                                 OR a.content LIKE :q
                                 OR c.name LIKE :q
                                 OR a.tags LIKE :q)
                          ORDER BY 
                            CASE 
                                WHEN a.title LIKE :q THEN 1
                                WHEN a.excerpt LIKE :q THEN 2
                                WHEN c.name LIKE :q THEN 3
                                WHEN a.tags LIKE :q THEN 4
                                WHEN a.content LIKE :q THEN 5
                                ELSE 6
                            END,
                            a.publish_date DESC");
    $stmt->execute([':q' => "%$search_query%"]);
    $search_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}




include 'header.php';
?>

<div class="container my-5">
    <!-- Breadcrumb and Query Heading -->
    <div class="mb-4">
        <nav style="--bs-breadcrumb-divider: '»';" aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent px-0">
                <li class="breadcrumb-item">
                    <a href="index.php" style="font-weight:700;color:#236013;">Home</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page" style="color:#236013;">
                    You searched for
                </li>
            </ol>
        </nav>
        <h2 style="color:#236013;font-weight:700;text-transform:uppercase;">SEARCHED FOR</h2>
        <h1 style="color:#236013;font-weight:800;margin-bottom:2.5rem;">
            <?= htmlspecialchars(strtoupper($search)) ?>
        </h1>
    </div>

    <?php if ($search_articles): ?>
        <?php foreach ($search_articles as $article): ?>
            <div class="row mb-5 pb-4 border-bottom" style="background: #fff; border-radius: 8px;">
                <div class="col-md-4">
                    <a href="article.php?slug=<?= urlencode($article['slug']); ?>">
                        <img src="<?= $article['featured_image']
                            ? 'assets/images/articles/' . $article['featured_image']
                            : 'https://via.placeholder.com/450x280/4a5568/ffffff?text=Article'; ?>"
                            alt="<?= htmlspecialchars($article['title']); ?>" class="img-fluid rounded"
                            style="width:100%;max-width:340px;height:220px;object-fit:cover;">
                    </a>
                </div>
                <div class="col-md-8 d-flex flex-column justify-content-center">
                    <a href="article.php?slug=<?= urlencode($article['slug']); ?>" class="text-decoration-none">
                        <h3 class="fw-bold mb-2" style="color:#236013; font-size:1.3rem;">
                            <?= htmlspecialchars($article['title']); ?>
                        </h3>
                    </a>
                    <p class="mb-2 text-dark" style="font-size:1rem;">
                        <?= htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 160) . '...') ?>
                    </p>
                    <span class="text-muted small">
                        <?= date('M d, Y', strtotime($article['publish_date'] ?: $article['created_at'])) ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">
            Sorry, no articles matched your search.
        </div>
    <?php endif; ?>
</div>

<style>
    body {
        background: #f6f7f9;
    }

    .breadcrumb {
        background: none;
    }
</style>

<?php include 'footer.php'; ?>