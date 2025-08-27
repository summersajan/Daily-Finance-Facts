<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Privacy Policy • Daily Finance Facts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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

        footer {
            margin-top: auto;
        }
    </style>
</head>

<body>
    <header class="dd-gradient text-white py-5">
        <div class="container-xxl d-flex flex-column flex-md-row align-items-center justify-content-between">
            <div>
                <h1 class="display-5 fw-bold mb-2">Privacy Policy</h1>
                <p class="lead mb-0">Your privacy matters to us.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="/" class="btn btn-light fw-semibold shadow-soft rounded-2xl">🏠 Home</a>
            </div>
        </div>
    </header>

    <section class="py-5">
        <div class="container-xxl bg-white p-5 rounded-2xl shadow-soft">
            <p><strong>Last updated:</strong> August 27, 2025</p>
            <p>We at Daily Finance Facts value your privacy. We only collect information necessary to improve our
                services, such as anonymous analytics data. We never sell or share your personal details with third
                parties without your consent.</p>
            <p>By using our site, you agree to this Privacy Policy. If you have questions, please contact us at <a
                    href="mailto:contact@dailyfinancefacts.com">contact@dailyfinancefacts.com</a>.</p>
        </div>
    </section>

    <footer class="mt-auto text-white text-center py-3"
        style="background: linear-gradient(135deg, #1e3c72, #2a5298); position: fixed; bottom: 0; left: 0; width: 100%;">
        <p class="mb-0">&copy; 2025 DailyFinanceFacts. All rights reserved.</p>
    </footer>


    <script>
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>

</html>