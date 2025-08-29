<?php include 'header.php' ?>
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


    <section class="py-5">
        <h3 class="container-xxl">Contact Us</h3>
        <div class="container-xxl bg-white p-5 rounded-2xl shadow-soft">

            <h2 class="h5 fw-bold mb-3">Get in Touch</h2>
            <p>If you have any questions, suggestions, or feedback, please reach out to us:</p>
            <ul>
                <li>Email: <a href="mailto:support@dailyfinancefacts.com">support@dailyfinancefacts.com</a></li>
                <li>Response time: Within 1–2 business days</li>
            </ul>
        </div>
    </section>

    <?php include 'footer.php'; ?>