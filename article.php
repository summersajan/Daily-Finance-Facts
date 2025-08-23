<?php
require_once 'config/database.php';

// Get article slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get article with category and author info
$query = "SELECT a.*, c.name as category_name, c.color as category_color, u.full_name as author_name 
          FROM articles a 
          LEFT JOIN categories c ON a.category_id = c.id 
          LEFT JOIN admin_users u ON a.author_id = u.id 
          WHERE a.slug = :slug AND a.status = 'published'";
$stmt = $db->prepare($query);
$stmt->bindParam(':slug', $slug);
$stmt->execute();
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit();
}

// Update view count
$update_views = "UPDATE articles SET views = views + 1 WHERE id = :id";
$update_stmt = $db->prepare($update_views);
$update_stmt->bindParam(':id', $article['id']);
$update_stmt->execute();

// Get related articles from same category
$related_query = "SELECT a.*, c.name as category_name 
                  FROM articles a 
                  LEFT JOIN categories c ON a.category_id = c.id 
                  WHERE a.category_id = :category_id AND a.id != :current_id AND a.status = 'published' 
                  ORDER BY a.publish_date DESC LIMIT 3";
$related_stmt = $db->prepare($related_query);
$related_stmt->bindParam(':category_id', $article['category_id']);
$related_stmt->bindParam(':current_id', $article['id']);
$related_stmt->execute();
$related_articles = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = $article['meta_title'] ?: $article['title'] . ' - Daily Finance Facts';
$meta_description = $article['meta_description'] ?: substr(strip_tags($article['content']), 0, 160);

include 'header.php';
?>
<style>
    /* Fix excessive vertical spacing around images in article content */
    .article-content-page img {
        margin: 1rem 0 !important;
        display: block;
        max-width: 100%;
        height: auto;
        border-radius: 8px;
    }

    /* Reduce paragraph margins */
    .article-content-page p {
        margin-top: 0;
        margin-bottom: 1rem;
        line-height: 1.6;
    }

    /* Remove extra margin on first and last images */
    .article-content-page img:first-child {
        margin-top: 0 !important;
    }

    .article-content-page img:last-child {
        margin-bottom: 1rem !important;
    }

    /* Handle figure elements if present */
    .article-content-page figure {
        margin: 1rem 0;
    }

    .article-content-page figure img {
        margin: 0 !important;
    }

    /* Handle floated images if any */
    .article-content-page img.alignleft,
    .article-content-page img.alignright {
        margin: 0.5rem;
    }
</style>


<div class="container mt-4">
    <!-- Back Button and Breadcrumb -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent px-0 mb-0">
                <li class="breadcrumb-item">
                    <a href="../../" style="color: var(--primary-blue); text-decoration: none;">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <?php if ($article['category_name']): ?>
                    <li class="breadcrumb-item">
                        <a href="../../category/<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $article['category_name']))); ?>"
                            style="color: var(--primary-blue); text-decoration: none;">
                            <?php echo htmlspecialchars($article['category_name']); ?>
                        </a>
                    </li>
                <?php endif; ?>


                <li class="breadcrumb-item active" aria-current="page" style="color: var(--text-light);">
                    <?php echo htmlspecialchars(substr($article['title'], 0, 50) . (strlen($article['title']) > 50 ? '...' : '')); ?>
                </li>
            </ol>
        </nav>
        <button onclick="history.back()" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-arrow-left"></i> Back
        </button>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Article Content -->
            <article class="card shadow-sm">
                <?php if ($article['featured_image']): ?>
                    <img src="/assets/images/articles/<?php echo $article['featured_image']; ?>" class="card-img-top"
                        alt="<?php echo htmlspecialchars($article['title']); ?>" style="height: 400px; object-fit: cover;">
                <?php endif; ?>

                <div class="card-body">
                    <!-- Article Meta -->
                    <div class="mb-3">
                        <?php if ($article['category_name']): ?>
                            <span class="badge rounded-pill"
                                style="background-color: <?php echo $article['category_color'] ?: 'var(--primary-blue)'; ?>">
                                <?php echo htmlspecialchars($article['category_name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Article Title -->
                    <h1 class="card-title mb-3" style="color: var(--primary-blue); font-weight: 700;">
                        <?php echo htmlspecialchars($article['title']); ?>
                    </h1>

                    <!-- Article Meta Info -->
                    <div class="article-meta mb-4 text-muted border-bottom pb-3">
                        <div class="row">
                            <div class="col-md-8">
                                <i class="fas fa-calendar-alt" style="color: var(--primary-blue);"></i>
                                <?php echo date('F d, Y', strtotime($article['publish_date'] ?: $article['created_at'])); ?>

                                <?php if ($article['author_name']): ?>
                                    <span class="ms-3">
                                        <i class="fas fa-user" style="color: var(--primary-blue);"></i>
                                        <?php echo htmlspecialchars($article['author_name']); ?>
                                    </span>
                                <?php endif; ?>

                                <span class="ms-3">
                                    <i class="fas fa-eye" style="color: var(--primary-blue);"></i>
                                    <?php echo number_format($article['views'] + 1); ?> views
                                </span>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <small class="text-muted">
                                    <i class="fas fa-clock" style="color: var(--primary-blue);"></i>
                                    <?php echo ceil(str_word_count(strip_tags($article['content'])) / 200); ?> min read
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Article Content -->
                    <div class="article-content-page">
                        <?php echo $article['content']; ?>
                    </div>

                    <!-- Article Tags (if you have them) -->


                    <!-- Social Sharing -->
                    <div class="social-sharing mt-4 pt-4 border-top">
                        <h6 style="color: var(--primary-blue);">
                            <i class="fas fa-share-alt"></i> Share this article:
                        </h6>
                        <div class="btn-group" role="group">
                            <a href="javascript:shareArticle('facebook', '<?php echo urlencode('https://dailyfinancefacts.com/article.php?slug=' . $article['slug']); ?>', '<?php echo urlencode($article['title']); ?>')"
                                class="btn btn-outline-primary btn-sm">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </a>
                            <a href="javascript:shareArticle('twitter', '<?php echo urlencode('https://dailyfinancefacts.com/article.php?slug=' . $article['slug']); ?>', '<?php echo urlencode($article['title']); ?>')"
                                class="btn btn-outline-info btn-sm">
                                <i class="fab fa-twitter"></i> Twitter
                            </a>
                            <a href="javascript:shareArticle('linkedin', '<?php echo urlencode('https://dailyfinancefacts.com/article.php?slug=' . $article['slug']); ?>', '<?php echo urlencode($article['title']); ?>')"
                                class="btn btn-outline-primary btn-sm">
                                <i class="fab fa-linkedin-in"></i> LinkedIn
                            </a>
                            <a href="javascript:void(0)"
                                onclick="copyToClipboard('<?php echo 'https://dailyfinancefacts.com/article.php?slug=' . $article['slug']; ?>')"
                                class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-link"></i> Copy Link
                            </a>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Comments Section -->
            <div class="card mt-4 shadow-sm">
                <div class="card-header"
                    style="background-color: var(--light-blue); border-bottom: 1px solid var(--border-color);">
                    <h5 class="mb-0" style="color: var(--primary-blue);">
                        <i class="fas fa-comments"></i> Comments
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Comment Form -->
                    <form id="commentForm" class="mb-4">
                        <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="comment-name" class="form-label">Name *</label>
                                <input type="text" class="form-control" id="comment-name" name="name"
                                    placeholder="Your full name" required minlength="2" maxlength="50">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="comment-email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="comment-email" name="email"
                                    placeholder="your@email.com" required maxlength="100">
                                <div class="form-text">Your email will not be published</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="comment-content" class="form-label">Comment *</label>
                            <textarea class="form-control" id="comment-content" name="content" rows="4"
                                placeholder="Share your thoughts about this article..." required minlength="10"
                                maxlength="1000"></textarea>
                            <div class="form-text">
                                <span id="char-count">0</span>/1000 characters
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="comment-terms" required>
                                <label class="form-check-label" for="comment-terms">
                                    I agree to the <a href="terms-of-service.php" target="_blank"
                                        style="color: var(--primary-blue);">Terms of Service</a>
                                    and understand that my comment will be reviewed before publication.
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="comment-submit-btn">
                            <i class="fas fa-paper-plane"></i> Post Comment
                        </button>

                        <div id="comment-alert" class="alert d-none mt-3" role="alert"></div>
                    </form>

                    <!-- Approved Comments Display -->
                    <div id="comments-list">
                        <?php
                        // Get approved comments for this article
                        $comments_query = "SELECT name, content, created_at FROM comments 
                             WHERE article_id = :article_id AND status = 'approved' 
                             ORDER BY created_at DESC";
                        $comments_stmt = $db->prepare($comments_query);
                        $comments_stmt->bindParam(':article_id', $article['id']);
                        $comments_stmt->execute();
                        $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if (!empty($comments)): ?>
                            <h6 class="border-top pt-3 mt-4" style="color: var(--primary-blue);">
                                Reader Comments (<?php echo count($comments); ?>)
                            </h6>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong style="color: var(--primary-blue);">
                                                <?php echo htmlspecialchars($comment['name']); ?>
                                            </strong>
                                            <small class="text-muted ms-2">
                                                <?php echo date('M d, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 border-top mt-4">
                                <i class="fas fa-comments fa-2x text-muted mb-2"></i>
                                <p class="text-muted">Be the first to comment on this article!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Back to Categories -->
            <div class="card mb-3 shadow-sm">
                <div class="card-body text-center">
                    <a href="../../" class="btn btn-primary w-100">
                        <i class="fas fa-arrow-left"></i> Back to Homepage
                    </a>
                    <?php if ($article['category_name']): ?>
                        <?php
                        $category_slug = str_replace(' ', '-', strtolower($article['category_name']));
                        ?>
                        <a href="../articles/<?php echo $category_slug; ?>" class="btn btn-outline-primary w-100 mt-2">
                            <i class="fas fa-list"></i> More <?php echo htmlspecialchars($article['category_name']); ?>
                            Articles
                        </a>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Related Articles -->
            <?php if (!empty($related_articles)): ?>
                <div class="card shadow-sm">
                    <div class="card-header"
                        style="background-color: var(--light-blue); border-bottom: 1px solid var(--border-color);">
                        <h5 class="mb-0" style="color: var(--primary-blue);">
                            <i class="fas fa-newspaper"></i> Related Articles
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($related_articles as $related): ?>
                            <div class="d-flex mb-3 pb-3 border-bottom">
                                <img src="<?php echo $related['featured_image'] ? '/assets/images/articles/' . $related['featured_image'] : 'https://via.placeholder.com/80x80/0153b7/ffffff?text=' . substr($related['title'], 0, 1); ?>"
                                    alt="<?php echo htmlspecialchars($related['title']); ?>" class="rounded me-3"
                                    style="width: 80px; height: 80px; object-fit: cover;">
                                <div>
                                    <h6>
                                        <a href="../article/<?php echo $related['slug']; ?>" class="text-decoration-none"
                                            style="color: var(--primary-blue);">
                                            <?php echo htmlspecialchars(substr($related['title'], 0, 60) . (strlen($related['title']) > 60 ? '...' : '')); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('M d, Y', strtotime($related['publish_date'] ?: $related['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Newsletter Signup -->
            <div class="card mt-3 shadow-sm">
                <div class="card-body text-center"
                    style="background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%); color: white;">
                    <h5><i class="fas fa-envelope"></i> Stay Updated</h5>
                    <p>Get our latest financial insights delivered to your inbox.</p>
                    <form id="sidebarNewsletterForm">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Your email" required>
                            <button class="btn btn-light" type="submit">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Article Stats -->
            <div class="card mt-3 shadow-sm">
                <div class="card-header" style="background-color: var(--light-blue);">
                    <h6 class="mb-0" style="color: var(--primary-blue);">
                        <i class="fas fa-chart-bar"></i> Article Stats
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Views:</span>
                        <strong style="color: var(--primary-blue);">
                            <?php echo number_format($article['views'] + 1); ?>
                        </strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Published:</span>
                        <span><?php echo date('M d, Y', strtotime($article['publish_date'] ?: $article['created_at'])); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Reading Time:</span>
                        <span><?php echo ceil(str_word_count(strip_tags($article['content'])) / 200); ?> min</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Social sharing function
    function shareArticle(platform, url, title) {
        let shareUrl = '';

        switch (platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                break;
            case 'linkedin':
                shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
                break;
        }

        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=600,height=400');
        }
    }

    // Copy to clipboard function
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function () {
            alert('Article link copied to clipboard!');
        }, function (err) {
            console.error('Could not copy text: ', err);
            // Fallback for older browsers
            const textArea = document.createElement("textarea");
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                alert('Article link copied to clipboard!');
            } catch (err) {
                alert('Unable to copy to clipboard');
            }
            document.body.removeChild(textArea);
        });
    }

    // Enhanced comment form handling
    document.addEventListener('DOMContentLoaded', function () {
        const commentForm = document.getElementById('commentForm');
        const commentContent = document.getElementById('comment-content');
        const charCount = document.getElementById('char-count');
        const submitBtn = document.getElementById('comment-submit-btn');
        const alertDiv = document.getElementById('comment-alert');

        // Character counter
        commentContent.addEventListener('input', function () {
            const length = this.value.length;
            charCount.textContent = length;

            if (length > 900) {
                charCount.style.color = '#dc3545';
            } else if (length > 800) {
                charCount.style.color = '#ffc107';
            } else {
                charCount.style.color = '#6c757d';
            }
        });

        // Form submission
        commentForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Show loading state
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            // Hide previous alerts
            alertDiv.classList.add('d-none');

            const formData = new FormData(this);

            fetch('submit-comment.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        commentForm.reset();
                        charCount.textContent = '0';
                        charCount.style.color = '#6c757d';
                    } else {
                        showAlert('danger', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'An error occurred while submitting your comment. Please try again.');
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        });

        // Show alert function
        function showAlert(type, message) {
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${message}`;
            alertDiv.classList.remove('d-none');

            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    alertDiv.classList.add('d-none');
                }, 5000);
            }
        }
    });

    // Newsletter form handling
    $('#sidebarNewsletterForm').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var email = form.find('input[type="email"]').val();
        var submitBtn = form.find('button[type="submit"]');
        var originalText = submitBtn.html();

        if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: 'subscribe.php',
                method: 'POST',
                data: { email: email },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        alert('Subscription successful! ' + response.message);
                        form[0].reset();
                    } else {
                        alert(response.message);
                    }
                },
                error: function () {
                    alert('Subscription failed. Please try again.');
                },
                complete: function () {
                    submitBtn.html(originalText);
                }
            });
        } else {
            alert('Please enter a valid email address.');
        }
    });
</script>


<?php include 'footer.php'; ?>