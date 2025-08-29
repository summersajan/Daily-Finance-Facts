<?php include 'header.php'; ?>
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


    <section class="py-5">
        <div class="container-xxl bg-white p-5 rounded-2xl shadow-soft">
            <h1 class="display-5 fw-bold mb-2">About Us</h1>
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

    <?php include 'footer.php'; ?>