<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>About Us • Daily Finance Facts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .dd-gradient {
            background: linear-gradient(135deg, #0046a3 0%, #0073e6 100%);
        }

        .rounded-2xl {
            border-radius: 1.25rem;
        }

        .shadow-soft {
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
        }

        .container-xxl {
            max-width: 900px;
        }
    </style>
</head>

<body>
    <header class="dd-gradient text-white py-5">
        <div class="container-xxl d-flex flex-column flex-md-row align-items-center justify-content-between">
            <div>
                <h1 class="display-5 fw-bold mb-2">About Us</h1>
                <p class="lead mb-0">Your trusted source for finance insights and tips.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="/" class="btn btn-light fw-semibold shadow-soft rounded-2xl">🏠 Home</a>
            </div>
        </div>
    </header>

    <section class="py-5">
        <div class="container-xxl bg-white p-5 rounded-2xl shadow-soft">
            <h2 class="h5 fw-bold mb-3">Our Mission</h2>
            <p>Daily Finance Facts provides actionable personal finance, investing, and crypto insights so you can make
                smarter decisions every day.</p>
            <h2 class="h5 fw-bold mt-4 mb-3">What We Offer</h2>
            <ul>
                <li>Clear, digestible finance articles</li>
                <li>Tips for budgeting, saving, and investing</li>
                <li>Trusted insights for all levels</li>
            </ul>
        </div>
    </section>

    <!-- Footer -->
    <footer class="mt-auto text-white text-center py-3"
        style="background: linear-gradient(135deg, #1e3c72, #2a5298); position: fixed; bottom: 0; left: 0; width: 100%;">
        <p class="mb-0">&copy; 2025 DailyFinanceFacts. All rights reserved.</p>
    </footer>

    <script>
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>

</html>