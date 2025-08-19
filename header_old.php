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
            --primary-green: #2d5b4f;
            --dark-green: #1a4037;
            --light-gray: #f8f9fa;
            --text-dark: #2d3748;
            --text-light: #718096;
            --border-color: #e2e8f0;
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
            background-color: var(--primary-green);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .custom-navbar.scrolled {
            background-color: rgba(45, 91, 79, 0.95);
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            color: white !important;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .navbar-nav .nav-link {
            color: white !important;
            font-weight: 500;
            margin: 0 15px;
            font-size: 0.9rem;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            position: relative;
        }

        .navbar-nav .nav-link:hover {
            color: #b8f2e6 !important;
            transform: translateY(-2px);
        }

        .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 50%;
            background-color: #b8f2e6;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover::after {
            width: 100%;
            left: 0;
        }

        .search-icon {
            font-size: 1.1rem;
        }

        /* Section Headers */
        .section-header {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }

        .title-underline {
            width: 60px;
            height: 3px;
            background-color: var(--primary-green);
            margin-bottom: 1rem;
        }

        /* Featured Article */
        .featured-article {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .featured-article:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
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
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            height: fit-content;
        }

        .article-item {
            transition: all 0.3s ease;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
        }

        .article-item:hover {
            background-color: var(--light-gray);
            transform: translateX(5px);
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
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            cursor: pointer;
        }

        .article-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .article-card img {
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .article-card:hover img {
            transform: scale(1.05);
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

        /* Newsletter Section */
        .newsletter-signup {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            border-radius: 15px;
            padding: 3rem;
        }

        .newsletter-signup h3 {
            font-weight: 700;
            margin-bottom: 1rem;
        }

        /* Loading Animation */
        .loading {
            opacity: 0;
            animation: fadeIn 0.6s ease-in-out forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Focus States for Accessibility */
        .nav-link:focus,
        .article-card:focus {
            outline: 2px solid var(--primary-green);
            outline-offset: 2px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-nav .nav-link {
                margin: 5px 0;
                text-align: center;
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
        }
    </style>
</head>

<body>
    <!-- Navigation Header -->
    <nav class="navbar navbar-expand-lg custom-navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <strong>DAILY FINANCE FACTS</strong>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#about">ABOUT US</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#save">SAVING MONEY</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#make">MAKE MONEY</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#invest">INVEST MONEY</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link search-icon" href="#" id="searchBtn"><i class="fas fa-search"></i></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>