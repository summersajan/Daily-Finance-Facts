<?php
$page_title = "Daily Finance Facts - Your Complete Financial Guide";
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

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

// Get articles by category
function getArticlesByCategory($db, $category_slug, $limit = 3)
{
    $query = "SELECT a.*, c.name as category_name, c.color as category_color
              FROM articles a 
              LEFT JOIN categories c ON a.category_id = c.id 
              WHERE a.status = 'published' AND c.slug = :category_slug 
              ORDER BY a.publish_date DESC LIMIT :limit";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':category_slug', $category_slug);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$saving_articles = getArticlesByCategory($db, 'saving-money');
$making_articles = getArticlesByCategory($db, 'making-money');
$investing_articles = getArticlesByCategory($db, 'investing');

include 'header.php';
?>

    <!-- Main Content -->
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
                        <div class="featured-article loading" onclick="location.href='article.php?slug=<?php echo $featured_article['slug']; ?>'">
                            <img src="<?php echo $featured_article['featured_image'] ? 'assets/images/articles/' . $featured_article['featured_image'] : 'https://via.placeholder.com/500x300/4a5568/ffffff?text=Featured+Article'; ?>" 
                                 alt="<?php echo htmlspecialchars($featured_article['title']); ?>" class="img-fluid">
                            <div class="article-content">
                                <h3 class="article-title"><?php echo htmlspecialchars($featured_article['title']); ?></h3>
                                <p class="article-date">
                                    <i class="far fa-calendar"></i> 
                                    <?php echo date('M d, Y', strtotime($featured_article['publish_date'] ?: $featured_article['created_at'])); ?>
                                    <?php if ($featured_article['category_name']): ?>
                                            <span class="ms-2">
                                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($featured_article['category_name']); ?>
                                            </span>
                                    <?php endif; ?>
                                </p>
                                <p class="article-excerpt">
                                    <?php echo htmlspecialchars($featured_article['excerpt'] ?: substr(strip_tags($featured_article['content']), 0, 150) . '...'); ?>
                                </p>
                                <a href="article.php?slug=<?php echo $featured_article['slug']; ?>" class="btn btn-outline-success">Read More</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="featured-article loading">
                            <img src="https://via.placeholder.com/500x300/4a5568/ffffff?text=No+Featured+Article" 
                                 alt="No Featured Article" class="img-fluid">
                            <div class="article-content">
                                <h3 class="article-title">Welcome to Daily Finance Facts</h3>
                                <p class="article-date"><i class="far fa-calendar"></i> <?php echo date('M d, Y'); ?></p>
                                <p class="article-excerpt">Your trusted source for financial advice, investment tips, and money management strategies. Check back soon for our latest articles!</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Articles List -->
                <div class="col-lg-6">
                    <div class="article-list loading">
                        <?php if (!empty($recent_articles)): ?>
                                <?php foreach ($recent_articles as $article): ?>
                                    <div class="article-item d-flex mb-3" onclick="location.href='article.php?slug=<?php echo $article['slug']; ?>'">
                                        <img src="<?php echo $article['featured_image'] ? 'assets/images/articles/' . $article['featured_image'] : 'https://via.placeholder.com/80x80/4a5568/ffffff?text=' . substr($article['title'], 0, 1); ?>" 
                                             alt="<?php echo htmlspecialchars($article['title']); ?>" class="article-thumb rounded">
                                        <div class="article-info ms-3">
                                            <h5 class="article-title-small"><?php echo htmlspecialchars(substr($article['title'], 0, 60) . (strlen($article['title']) > 60 ? '...' : '')); ?></h5>
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
                            <a href="articles.php" class="btn btn-success">View All Articles</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Save Money Section -->
        <section class="save-money mb-5" id="save">
            <div class="section-header">
                <h2 class="section-title">SAVE MONEY</h2>
                <div class="title-underline"></div>
            </div>
            
            <div class="row">
                <?php if (!empty($saving_articles)): ?>
                        <?php foreach ($saving_articles as $article): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card article-card loading" onclick="location.href='article.php?slug=<?php echo $article['slug']; ?>'">
                                    <img src="<?php echo $article['featured_image'] ? 'assets/images/articles/' . $article['featured_image'] : 'https://via.placeholder.com/400x250/2d5b4f/ffffff?text=Save+Money'; ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 100) . '...'); ?></p>
                                        <a href="article.php?slug=<?php echo $article['slug']; ?>" class="btn btn-sm btn-outline-success">Learn More</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                <?php else: ?>
                        <!-- Default placeholder articles -->
                        <div class="col-md-4 mb-4">
                            <div class="card article-card loading">
                                <img src="https://via.placeholder.com/400x250/2d5b4f/ffffff?text=Save+Money" class="card-img-top" alt="Save Money">
                                <div class="card-body">
                                    <h5 class="card-title">Smart Saving Strategies for 2025</h5>
                                    <p class="card-text">Discover effective ways to save money and build your emergency fund with these proven strategies.</p>
                                    <a href="#" class="btn btn-sm btn-outline-success">Coming Soon</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card article-card loading">
                                <img src="https://via.placeholder.com/400x250/2d5b4f/ffffff?text=Budget+Tips" class="card-img-top" alt="Budget Tips">
                                <div class="card-body">
                                    <h5 class="card-title">Budgeting Tips That Actually Work</h5>
                                    <p class="card-text">Learn practical budgeting techniques that will help you take control of your finances.</p>
                                    <a href="#" class="btn btn-sm btn-outline-success">Coming Soon</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card article-card loading">
                                <img src="https://via.placeholder.com/400x250/2d5b4f/ffffff?text=Cut+Expenses" class="card-img-top" alt="Cut Expenses">
                                <div class="card-body">
                                    <h5 class="card-title">50 Ways to Cut Your Monthly Expenses</h5>
                                    <p class="card-text">Simple and effective methods to reduce your monthly spending without sacrificing quality of life.</p>
                                    <a href="#" class="btn btn-sm btn-outline-success">Coming Soon</a>
                                </div>
                            </div>
                        </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Make Money Section -->
        <section class="make-money mb-5" id="make">
            <div class="section-header">
                <h2 class="section-title">MAKE MONEY</h2>
                <div class="title-underline"></div>
            </div>
            
            <div class="row">
                <?php if (!empty($making_articles)): ?>
                        <?php foreach ($making_articles as $article): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card article-card loading" onclick="location.href='article.php?slug=<?php echo $article['slug']; ?>'">
                                    <img src="<?php echo $article['featured_image'] ? 'assets/images/articles/' . $article['featured_image'] : 'https://via.placeholder.com/400x250/1a365d/ffffff?text=Make+Money'; ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 100) . '...'); ?></p>
                                        <a href="article.php?slug=<?php echo $article['slug']; ?>" class="btn btn-sm btn-outline-success">Learn More</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                <?php else: ?>
                        <!-- Default placeholder articles -->
                        <div class="col-md-4 mb-4">
                            <div class="card article-card loading">
                                <img src="https://via.placeholder.com/400x250/1a365d/ffffff?text=Side+Hustles" class="card-img-top" alt="Side Hustles">
                                <div class="card-body">
                                    <h5 class="card-title">Top 10 Side Hustles for 2025</h5>
                                    <p class="card-text">Explore profitable side hustle opportunities that can boost your income significantly.</p>
                                    <a href="#" class="btn btn-sm btn-outline-success">Coming Soon</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card article-card loading">
                                <img src="https://via.placeholder.com/400x250/1a365d/ffffff?text=Remote+Work" class="card-img-top" alt="Remote Work">
                                <div class="card-body">
                                    <h5 class="card-title">High-Paying Remote Jobs You Can Start Today</h5>
                                    <p class="card-text">Discover remote work opportunities that offer competitive salaries and flexible schedules.</p>
                                    <a href="#" class="btn btn-sm btn-outline-success">Coming Soon</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card article-card loading">
                                <img src="https://via.placeholder.com/400x250/1a365d/ffffff?text=Freelancing" class="card-img-top" alt="Freelancing">
                                <div class="card-body">
                                    <h5 class="card-title">Complete Guide to Freelancing Success</h5>
                                    <p class="card-text">Learn how to build a successful freelancing career from scratch with proven strategies.</p>
                                    <a href="#" class="btn btn-sm btn-outline-success">Coming Soon</a>
                                </div>
                            </div>
                        </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Invest Money Section -->
        <section class="invest-money mb-5" id="invest">
            <div class="section-header">
                <h2 class="section-title">INVEST MONEY</h2>
                <div class="title-underline"></div>
            </div>
            
            <div class="row">
                <?php if (!empty($investing_articles)): ?>
                        <?php foreach ($investing_articles as $article): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card article-card loading" onclick="location.href='article.php?slug=<?php echo $article['slug']; ?>'">
                                    <img src="<?php echo $article['featured_image'] ? 'assets/images/articles/' . $article['featured_image'] : 'https://via.placeholder.com/400x250/8b2635/ffffff?text=Invest+Money'; ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 100) . '...'); ?></p>
                                        <a href="article.php?slug=<?php echo $article['slug']; ?>" class="btn btn-sm btn-outline-success">Learn More</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                <?php else: ?>
                        <!-- Default placeholder articles -->
                        <div class="col-md-4 mb-4">
                            <div class="card article-card loading">
                                <img src="https://via.placeholder.com/400x250/8b2635/ffffff?text=Stock+Market" class="card-img-top" alt="Stock Market">
                                <div class="card-body">
                                    <h5 class="card-title">Beginner's Guide to Stock Market Investing</h5>
                                    <p class="card-text">Start your investment journey with this comprehensive guide to stock market basics and strategies.</p>
                                    <a href="#" class="btn btn-sm btn-outline-success">Coming Soon</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card article-card loading">
                                <img src="https://via.placeholder.com/400x250/8b2635/ffffff?text=ETF+Investing" class="card-img-top" alt="ETF Investing">
                                <div class="card-body">
                                    <h5 class="card-title">ETF Investing for Beginners</h5>
                                    <p class="card-text">Learn how to build a diversified portfolio using Exchange-Traded Funds (ETFs).</p>
                                    <a href="#" class="btn btn-sm btn-outline-success">Coming Soon</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card article-card loading">
                                <img src="https://via.placeholder.com/400x250/8b2635/ffffff?text=Retirement" class="card-img-top" alt="Retirement Planning">
                                <div class="card-body">
                                    <h5 class="card-title">Retirement Planning in Your 20s and 30s</h5>
                                    <p class="card-text">Why starting early matters and how to build a solid retirement foundation.</p>
                                    <a href="#" class="btn btn-sm btn-outline-success">Coming Soon</a>
                                </div>
                            </div>
                        </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Newsletter Signup Section -->
        <section class="newsletter-signup mb-5">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3><i class="fas fa-envelope-open"></i> Stay Updated with Daily Finance Facts</h3>
                    <p class="mb-0">Get the latest financial tips, investment strategies, and money management advice delivered to your inbox weekly.</p>
                </div>
                <div class="col-md-4">
                    <form id="newsletterForm">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Enter your email" id="newsletterEmail" required>
                            <button class="btn btn-light" type="submit" id="subscribeBtn">
                                <i class="fas fa-paper-plane"></i> Subscribe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>

<?php include 'footer.php'; ?>
