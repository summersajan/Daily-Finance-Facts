<footer class="custom-footer mt-5">
    <div class="container">
        <div class="row justify-content-between">
            <!-- Logo and About Column -->
            <div class="col-md-4 mb-4 mb-md-0">
                <a href="index.php" class="d-inline-block mb-2">
                    <img src="/assets/images/logo.svg" alt="Daily Finance Facts Logo"
                        style="height:48px;max-width:220px;">

                </a>
                <p class="mb-2" style="max-width:300px;">
                    Daily Finance Facts brings you actionable tips and reliable insights on saving, making, and
                    investing money. Your partner for smarter decisions.
                </p>
                <div class="social-links mt-2">
                    <a href="https://facebook.com/" target="_blank" rel="noopener" aria-label="Facebook"><i
                            class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com/" target="_blank" rel="noopener" aria-label="Twitter"><i
                            class="fab fa-twitter"></i></a>
                    <a href="https://linkedin.com/" target="_blank" rel="noopener" aria-label="LinkedIn"><i
                            class="fab fa-linkedin-in"></i></a>
                    <a href="https://instagram.com/" target="_blank" rel="noopener" aria-label="Instagram"><i
                            class="fab fa-instagram"></i></a>
                </div>
            </div>

            <!-- Sitemap/Links Column -->
            <div class="col-md-4 mb-4 mb-md-0">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php">Home</a></li>
                    <!-- If categories are needed here, fetch dynamically -->
                    <?php
                    if (isset($db)) {
                        $footer_cat_stmt = $db->prepare("SELECT name, slug FROM categories WHERE status='active' ORDER BY id ASC LIMIT 6");
                        $footer_cat_stmt->execute();
                        $footer_categories = $footer_cat_stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($footer_categories as $cat) {
                            echo '<li><a href="#cat-' . htmlspecialchars($cat['slug']) . '">' . htmlspecialchars($cat['name']) . '</a></li>';
                        }
                    }
                    ?>
                    <li><a href="#newsletter">Newsletter</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="privacy-policy.php">Privacy Policy</a></li>
                </ul>
            </div>

            <!-- Newsletter Column -->
            <div class="col-md-4">
                <h5>Subscribe</h5>
                <form id="footerNewsletterForm" class="mb-2">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Your email" required>
                        <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </form>
                <div class="small text-muted">Get weekly financial insights & tips.</div>
            </div>
        </div>
        <hr>
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                &copy; <?php echo date('Y'); ?> Daily Finance Facts. All rights reserved.
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="small">Powered by Daily Finance Facts </span>
            </div>
        </div>
    </div>
</footer>


<script>
    document.querySelectorAll('#footerNewsletterForm').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var input = form.querySelector('input[type="email"]');
            var email = input.value;
            var btn = form.querySelector('button');
            var orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('Please enter a valid email address.');
                btn.innerHTML = orig;
                btn.disabled = false;
                return;
            }

            fetch('subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email)
            })
                .then(r => r.json())
                .then(data => {
                    alert(data.message || (data.success ? 'Subscribed!' : 'Subscription failed!'));
                    if (data.success) form.reset();
                })
                .catch(() => alert('Subscription failed. Please try again.'))
                .finally(() => {
                    btn.innerHTML = orig;
                    btn.disabled = false;
                });
        });
    });
</script>



<!-- Custom JavaScript -->
<script>
    $(document).ready(function () {
        // Fast scroll for navigation links
        // Fast scroll for navigation links with offset
        $('a[href^="#cat-"]').on('click', function (event) {
            var href = $(this).attr('href');
            var target = $(href);
            if (target.length) {
                event.preventDefault();

                // Calculate offset (navbar height + extra spacing)
                var offset = 100; // Adjust this value as needed
                var targetPosition = target.offset().top - offset;

                $('html, body').animate({
                    scrollTop: targetPosition
                }, 50); // 500ms smooth scroll
            }
        });

        // Fast navbar scroll effect
        $(window).scroll(function () {
            if ($(this).scrollTop() > 50) {
                $('.custom-navbar').addClass('scrolled');
            } else {
                $('.custom-navbar').removeClass('scrolled');
            }
        });

        // Newsletter subscription
        $('#newsletterForm').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var email = form.find('input[type="email"]').val();
            var submitBtn = form.find('button[type="submit"]');
            var originalText = submitBtn.html();

            if (email && validateEmail(email)) {
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Subscribing...');

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

        // Fast article navigation using data-href
        $(document).on('click', '[data-href]', function () {
            window.location.href = $(this).data('href');
        });

        // Clear search functionality
        const searchInput = document.querySelector('input[name="search"]');
        /* if (searchInput) {
             let timeout;
              searchInput.addEventListener('input', function () {
                  clearTimeout(timeout);
                  if (this.value.trim() === '') {
                      timeout = setTimeout(() => {
                          if (this.value.trim() === '') {
                              window.location.href = 'index.php';
                          }
                      }, 300);
                  }
              });
 
             searchInput.addEventListener('keydown', function (e) {
                 if (e.key === 'Escape') {
                     this.value = '';
                     window.location.href = 'index.php';
                 }
             });
         }*/

        // Article card hover effects
        $('.article-card').hover(
            function () {
                $(this).find('.card-title').css('color', 'var(--primary-blue)');
            },
            function () {
                $(this).find('.card-title').css('color', 'var(--text-dark)');
            }
        );

        // Back to top button
        $(window).scroll(function () {
            if ($(this).scrollTop() > 300) {
                if (!$('#backToTop').length) {
                    $('body').append('<button id="backToTop" class="btn btn-primary position-fixed" style="bottom: 30px; right: 30px; z-index: 1000; border-radius: 50%; width: 50px; height: 50px; box-shadow: 0 4px 12px rgba(1, 83, 183, 0.3);"><i class="fas fa-arrow-up"></i></button>');
                }
            } else {
                $('#backToTop').remove();
            }
        });

        // Handle back to top button click
        $(document).on('click', '#backToTop', function () {
            $('html, body').animate({ scrollTop: 0 }, 600);
        });
    });

    // Email validation function
    function validateEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Social sharing functions (for future use)
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

    // Search form validation
    document.querySelectorAll('form[action="index.php"]').forEach(form => {
        form.addEventListener('submit', function (e) {
            const val = this.querySelector('input[name="search"]').value.trim();
            if (!val) {
                e.preventDefault();
                alert("Please enter a search term.");
            }
        });
    });
</script>


</body>

</html>