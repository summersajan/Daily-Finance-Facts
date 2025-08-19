<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_id'])) {
    $database = new Database();
    $db = $database->getConnection();

    $category_id = (int) $_POST['category_id'];
    $offset = (int) ($_POST['offset'] ?? 6);
    $limit = 6;

    $stmt = $db->prepare("SELECT a.*, c.name as category_name, c.color as category_color
                          FROM articles a
                          LEFT JOIN categories c ON a.category_id = c.id
                          WHERE a.status = 'published' AND a.category_id = :catid
                          ORDER BY a.publish_date DESC
                          LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':catid', $category_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if there are more articles
    $count_stmt = $db->prepare("SELECT COUNT(*) as total FROM articles WHERE status = 'published' AND category_id = :catid");
    $count_stmt->bindValue(':catid', $category_id, PDO::PARAM_INT);
    $count_stmt->execute();
    $total_articles = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $has_more = ($offset + $limit) < $total_articles;

    $html = '';
    foreach ($articles as $article) {
        $html .= '<div class="col-md-4 mb-4">
                    <div class="card article-card" data-href="article.php?slug=' . urlencode($article['slug']) . '">
                        <img src="' . ($article['featured_image'] ? 'assets/images/articles/' . $article['featured_image'] : 'https://via.placeholder.com/400x250/0153b7/ffffff?text=' . urlencode($article['category_name'])) . '"
                             class="card-img-top" alt="' . htmlspecialchars($article['title']) . '" loading="lazy">
                        <div class="card-body">
                            <h5 class="card-title">' . htmlspecialchars($article['title']) . '</h5>
                            <p class="card-text">' . htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 100) . '...') . '</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="article.php?slug=' . urlencode($article['slug']) . '" class="btn btn-sm btn-outline-primary">Learn More</a>
                                <small class="text-muted">
                                    <i class="far fa-calendar"></i> 
                                    ' . date('M d', strtotime($article['publish_date'] ?: $article['created_at'])) . '
                                </small>
                            </div>
                        </div>
                    </div>
                  </div>';
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'html' => $html,
        'hasMore' => $has_more
    ]);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => false]);
?>