<?php
$page_title = 'Add New Article';
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get categories for dropdown
$query = "SELECT id, name FROM categories WHERE status = 'active' ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $content = $_POST['content'];
    $excerpt = trim($_POST['excerpt']);
    $category_id = (int)$_POST['category_id'];
    $status = $_POST['status'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $meta_title = trim($_POST['meta_title']);
    $meta_description = trim($_POST['meta_description']);
    $publish_date = $_POST['publish_date'] ?: null;
    
    // Handle file upload
    $featured_image = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        $upload_dir = '../assets/images/articles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
        $featured_image = 'article_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $featured_image;
        
        if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
            $featured_image = '';
        }
    }
    
    if (!empty($title) && !empty($slug) && !empty($content)) {
        $query = "INSERT INTO articles (title, slug, content, excerpt, featured_image, category_id, 
                 author_id, status, is_featured, meta_title, meta_description, publish_date) 
                 VALUES (:title, :slug, :content, :excerpt, :featured_image, :category_id, 
                 :author_id, :status, :is_featured, :meta_title, :meta_description, :publish_date)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':excerpt', $excerpt);
        $stmt->bindParam(':featured_image', $featured_image);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':author_id', $_SESSION['admin_id']);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':is_featured', $is_featured);
        $stmt->bindParam(':meta_title', $meta_title);
        $stmt->bindParam(':meta_description', $meta_description);
        $stmt->bindParam(':publish_date', $publish_date);
        
        if ($stmt->execute()) {
            $success_message = "Article created successfully!";
            $_POST = array(); // Clear form
        } else {
            $error_message = "Error creating article.";
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

include 'includes/header.php';
?>

<div id="alerts-container"></div>

<?php if (!empty($success_message)): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $success_message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?php echo $error_message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Add New Article</h1>
    <a href="articles.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Articles
    </a>
</div>

<form method="POST" action="" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Article Content</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required 
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug *</label>
                        <input type="text" class="form-control" id="slug" name="slug" required
                               value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
                        <div class="form-text">URL-friendly version of the title</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="excerpt" class="form-label">Excerpt</label>
                        <textarea class="form-control" id="excerpt" name="excerpt" rows="3"
                                  placeholder="Short description of the article"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Content *</label>
                        <div id="quill-editor" style="height: 400px;"></div>
                        <textarea name="content" id="content" style="display: none;" required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- SEO Settings -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">SEO Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="meta_title" class="form-label">Meta Title</label>
                        <input type="text" class="form-control" id="meta_title" name="meta_title" 
                               value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="meta_description" class="form-label">Meta Description</label>
                        <textarea class="form-control" id="meta_description" name="meta_description" rows="2"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Publish Settings -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Publish Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="draft" <?php echo ($_POST['status'] ?? '') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo ($_POST['status'] ?? '') == 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="scheduled" <?php echo ($_POST['status'] ?? '') == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="publish_date" class="form-label">Publish Date</label>
                        <input type="datetime-local" class="form-control" id="publish_date" name="publish_date"
                               value="<?php echo $_POST['publish_date'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured"
                                   <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">
                                Featured Article
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Article
                        </button>
                        <a href="articles.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </div>
            
            <!-- Category & Featured Image -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Article Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="0">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="featured_image" class="form-label">Featured Image</label>
                        <input type="file" class="form-control" id="featured_image" name="featured_image" 
                               accept="image/*">
                        <div class="form-text">Recommended size: 800x600px</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Replace the existing script section at the bottom with this: -->
<script>
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if Quill is available
    if (typeof Quill === 'undefined') {
        console.error('Quill is not loaded. Please check your CDN links.');
        // Show fallback textarea
        document.getElementById('quill-editor').innerHTML = '<div class="quill-loading"><i class="fas fa-exclamation-triangle text-warning"></i> <span class="ms-2">Quill editor failed to load. Please refresh the page.</span></div>';
        return;
    }

    // Initialize Quill editor with error handling
    try {
        var quill = new Quill('#quill-editor', {
            theme: 'snow',
            placeholder: 'Write your article content here...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'font': [] }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'direction': 'rtl' }],
                    [{ 'align': [] }],
                    ['blockquote', 'code-block'],
                    ['link', 'image', 'video'],
                    ['clean']
                ]
            }
        });

        // Sync Quill content with textarea
        quill.on('text-change', function() {
            document.getElementById('content').value = quill.root.innerHTML;
        });

        // Set initial content if editing
        var initialContent = document.getElementById('content').value;
        if (initialContent && initialContent.trim() !== '') {
            quill.root.innerHTML = initialContent;
        }

        console.log('Quill editor initialized successfully');
        
    } catch (error) {
        console.error('Error initializing Quill:', error);
        document.getElementById('quill-editor').innerHTML = '<div class="quill-loading"><i class="fas fa-exclamation-triangle text-danger"></i> <span class="ms-2">Error loading editor: ' + error.message + '</span></div>';
    }

    // Auto-generate slug from title
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    if (titleInput && slugInput) {
        titleInput.addEventListener('keyup', function() {
            var title = this.value;
            var slug = title.toLowerCase()
                           .replace(/[^\w ]+/g, '')
                           .replace(/ +/g, '-')
                           .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
            slugInput.value = slug;
        });
    }

    // Form validation before submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const contentInput = document.getElementById('content');
            const quillContent = document.querySelector('.ql-editor').innerHTML;
            
            // Update hidden textarea with Quill content
            contentInput.value = quillContent;
            
            // Basic validation
            if (!contentInput.value || contentInput.value.trim() === '<p><br></p>' || contentInput.value.trim() === '') {
                e.preventDefault();
                alert('Please add some content to your article.');
                return false;
            }
        });
    }
});
</script>


<?php include 'includes/footer.php'; ?>
