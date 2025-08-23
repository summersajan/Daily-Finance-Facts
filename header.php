<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Daily Finance Facts - Your Complete Financial Guide'; ?>
    </title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary-blue: #0153b7;
            --dark-blue: #043061;
            --light-blue: #e6f0fa;
            --text-dark: #063974;
            --text-light: #6197d3;
            --border-color: #b3c9e9;
            --hover-blue: #2d9fff;
            --success-blue: #0153b7;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background-color: #ffffff;
        }

        /* Navigation Styles */
        .custom-navbar {
            background-color: var(--primary-blue);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.1s ease;
        }

        .custom-navbar.scrolled {
            background-color: rgba(1, 83, 183, 0.95);
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            color: white !important;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 0 !important;
        }

        .navbar-brand img {
            height: 50px;
            max-width: 250px;
            transition: transform 0.1s ease;
        }

        .navbar-brand:hover img {
            transform: scale(1.05);
        }

        .navbar-nav .nav-link {
            color: white !important;
            font-weight: 500;
            margin: 0 10px;
            font-size: 0.85rem;
            letter-spacing: 1px;
            transition: all 0.1s ease;
            position: relative;
            padding: 0.5rem 0.5rem !important;
        }

        .navbar-nav .nav-link:hover {
            color: var(--hover-blue) !important;
            transform: translateY(-1px);
        }

        .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 50%;
            background-color: var(--hover-blue);
            transition: all 0.1s ease;
        }

        .navbar-nav .nav-link:hover::after {
            width: 100%;
            left: 0;
        }

        .search-icon {
            font-size: 1.1rem;
        }

        /* Search Form Styles */
        .search-form {
            margin: 0;
        }

        .search-form .form-control {
            border: 2px solid rgba(255, 255, 255, 0.3);
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.85rem;
            border-radius: 25px;
            transition: all 0.1s ease;
        }

        .search-form .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-form .form-control:focus {
            border-color: var(--hover-blue);
            background-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 0 0.2rem rgba(45, 159, 255, 0.25);
            color: white;
        }

        .search-form .btn {
            border-radius: 25px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.1s ease;
        }

        .search-form .btn:hover {
            background-color: var(--hover-blue);
            border-color: var(--hover-blue);
            transform: scale(1.05);
        }

        /* Section Headers */
        .section-header {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-blue);
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }

        .title-underline {
            width: 60px;
            height: 3px;
            background-color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        /* Featured Article */
        .featured-article {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(1, 83, 183, 0.1);
            transition: transform 0.1s ease, box-shadow 0.1s ease;
            cursor: pointer;
        }

        .featured-article:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(1, 83, 183, 0.15);
        }

        .featured-article img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .article-content {
            padding: 1.5rem;
        }

        .article-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .article-date {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .article-excerpt {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Article List */
        .article-list {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(1, 83, 183, 0.08);
            height: fit-content;
        }

        .article-item {
            transition: all 0.1s ease;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
        }

        .article-item:hover {
            background-color: var(--light-blue);
            transform: translateX(3px);
        }

        .article-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .article-title-small {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.3rem;
            line-height: 1.3;
        }

        .article-date-small {
            color: var(--text-light);
            font-size: 0.8rem;
            margin-bottom: 0;
        }

        /* Article Cards */
        .article-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(1, 83, 183, 0.08);
            transition: all 0.1s ease;
            height: 100%;
            cursor: pointer;
        }

        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(1, 83, 183, 0.15);
        }

        .article-card img {
            height: 200px;
            object-fit: cover;
            transition: transform 0.1s ease;
        }

        .article-card:hover img {
            transform: scale(1.02);
        }

        .article-card .card-body {
            padding: 1.5rem;
        }

        .article-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            line-height: 1.4;
            margin-bottom: 1rem;
        }

        .article-card .card-text {
            color: var(--text-light);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        /* Buttons */
        .btn-primary,
        .btn-success {
            background-color: var(--primary-blue) !important;
            border-color: var(--primary-blue) !important;
            color: white !important;
            transition: all 0.1s ease;
        }

        .btn-primary:hover,
        .btn-success:hover {
            background-color: var(--dark-blue) !important;
            border-color: var(--dark-blue) !important;
            transform: translateY(-1px);
        }

        .btn-outline-primary,
        .btn-outline-success {
            color: var(--primary-blue) !important;
            border-color: var(--primary-blue) !important;
            background-color: transparent !important;
            transition: all 0.1s ease;
        }

        .btn-outline-primary:hover,
        .btn-outline-success:hover {
            background-color: var(--primary-blue) !important;
            border-color: var(--primary-blue) !important;
            color: white !important;
            transform: translateY(-1px);
        }

        /* Newsletter Section */
        .newsletter-signup {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            color: white;
            border-radius: 15px;
            padding: 3rem;
        }

        .newsletter-signup h3 {
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .newsletter-signup .btn-light {
            background-color: rgba(255, 255, 255, 0.9);
            border-color: rgba(255, 255, 255, 0.9);
            color: var(--primary-blue);
            font-weight: 600;
            transition: all 0.1s ease;
        }

        .newsletter-signup .btn-light:hover {
            background-color: white;
            color: var(--dark-blue);
            transform: scale(1.02);
        }

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Focus States for Accessibility */
        .nav-link:focus,
        .article-card:focus {
            outline: 2px solid var(--primary-blue);
            outline-offset: 2px;
        }

        /* Search Results Styling */
        .breadcrumb-item a {
            color: var(--primary-blue) !important;
            text-decoration: none;
            font-weight: 600;
        }

        .breadcrumb-item.active {
            color: var(--text-light) !important;
        }

        /* Mobile navbar toggler */
        .navbar-toggler {
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 4px 8px;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* Category Section Styles */
        .category-section {
            margin-bottom: 4rem;
        }

        /* Alert Styles */
        .alert-info {
            background-color: var(--light-blue);
            border-color: var(--border-color);
            color: var(--text-dark);
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            .navbar-nav .nav-link {
                margin: 5px 0;
                text-align: center;
                padding: 0.5rem 1rem !important;
            }

            .search-form {
                margin-top: 1rem;
                justify-content: center;
            }

            .search-form .form-control {
                margin-bottom: 0.5rem;
                width: 200px !important;
            }
        }

        @media (max-width: 768px) {
            .navbar-brand img {
                height: 40px;
                max-width: 200px;
            }

            .section-title {
                font-size: 1.3rem;
            }

            .article-title {
                font-size: 1.2rem;
            }

            .featured-article img {
                height: 200px;
            }

            .article-list {
                margin-top: 2rem;
            }

            .newsletter-signup {
                padding: 2rem;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }

            .article-item {
                flex-direction: column;
                text-align: center;
            }

            .article-thumb {
                width: 100%;
                height: 150px;
                margin-bottom: 1rem;
            }

            .article-info {
                margin-left: 0 !important;
            }

            .navbar-brand img {
                height: 35px;
                max-width: 180px;
            }

            .newsletter-signup {
                text-align: center;
            }

            .newsletter-signup .col-md-8,
            .newsletter-signup .col-md-4 {
                text-align: center;
                margin-bottom: 1rem;
            }

            .search-form .form-control {
                width: 150px !important;
            }
        }

        /* Custom Footer Styles */
        .custom-footer {
            background-color: var(--dark-blue);
            color: white;
            padding: 3rem 0 1rem;
        }

        .custom-footer h5 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: #398de2;
        }

        .custom-footer a {
            color: #d3e7fa;
            text-decoration: none;
            transition: color 0.1s ease;
        }

        .custom-footer a:hover {
            color: #70b6fa;
        }

        .custom-footer hr {
            border-color: rgba(255, 255, 255, 0.2);
            margin: 2rem 0 1rem;
        }

        .social-links a {
            display: inline-block;
            width: 35px;
            height: 35px;
            line-height: 35px;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.12);
            border-radius: 50%;
            color: #bfe1ff;
            transition: all 0.1s ease;
        }

        .social-links a:hover {
            background-color: #70b6fa;
            color: var(--dark-blue) !important;
            transform: translateY(-2px);
        }

        /* Article content styling */
        .article-content-page {
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .article-content-page h1,
        .article-content-page h2,
        .article-content-page h3 {
            color: var(--primary-blue);
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .article-content-page p {
            margin-bottom: 1.5rem;
        }

        .article-content-page img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .navbar-nav .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2) !important;
            color: #fff !important;
            font-weight: 600;
            border-radius: 4px;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar-nav .nav-link.active::after {
            width: 100%;
            left: 0;
            background-color: #fff;
        }

        /* On hover for active link */
        .navbar-nav .nav-link.active:hover {
            background-color: rgba(255, 255, 255, 0.3) !important;
            transform: translateY(-2px);
        }
    </style>
    <link rel="icon" type="image/png" href="assets/images/favicon.svg">

</head>

<body>
    <!-- Navigation Header -->
    <nav class="navbar navbar-expand-lg custom-navbar">
        <div class="container">

            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo.svg" alt="Daily Finance Facts Logo">
            </a>


            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">HOME</a>
                    </li>
                    <?php
                    // Get the current URL path and extract category from it
                    $current_path = $_SERVER['REQUEST_URI'] ?? '';
                    $current_category = '';

                    // Detect current category
                    if (strpos($current_path, 'category.php?category=') !== false) {
                        // Old query-string style
                        parse_str(parse_url($current_path, PHP_URL_QUERY), $params);
                        $current_category = $params['category'] ?? '';
                    } elseif (preg_match('#/category/([^/]+)#', $current_path, $matches)) {
                        // Clean URL style
                        $current_category = $matches[1];
                    }

                    // Fetch categories for navigation
                    if (isset($db) && empty($_GET['search'])) {
                        try {
                            $nav_cat_stmt = $db->prepare("
            SELECT name, slug 
            FROM categories 
            WHERE status='active' 
            ORDER BY id ASC 
            LIMIT 4
        ");
                            $nav_cat_stmt->execute();
                            $nav_categories = $nav_cat_stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($nav_categories as $nav_cat) {
                                $is_active = ($current_category === $nav_cat['slug']) ? 'active' : '';

                                echo '<li class="nav-item">';
                                // Use clean URL instead of category.php?category=
                                echo '<a class="nav-link ' . $is_active . '" href="/category/' . htmlspecialchars($nav_cat['slug']) . '">';
                                echo htmlspecialchars(strtoupper($nav_cat['name']));
                                echo '</a>';
                                echo '</li>';
                            }
                        } catch (Exception $e) {
                            // Silent fail - just don't show dynamic categories
                        }
                    }
                    ?>


                    <li class="nav-item">
                        <form method="GET" action="index.php" class="d-flex search-form ms-2">
                            <input type="text" name="search" class="form-control form-control-sm me-1"
                                placeholder="Search..." style="width: 150px;"
                                value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            <button type="submit" class="btn btn-sm">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>