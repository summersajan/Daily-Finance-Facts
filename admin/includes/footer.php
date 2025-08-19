</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        // Optimize DataTables initialization
        if ($.fn.DataTable) {
            $('.data-table').DataTable({
                pageLength: 25,
                responsive: true,
                processing: true,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries"
                },
                columnDefs: [
                    { orderable: false, targets: -1 }
                ]
            });
        }

        // Rest of your existing JavaScript...
        $('#sidebarToggle').click(function () {
            $('.sidebar').toggleClass('show');
        });

        $('.delete-btn').click(function () {
            return confirm('Are you sure you want to delete this item? This action cannot be undone.');
        });

        $('.alert').delay(5000).fadeOut();

        setTimeout(function () {
            $('.lazy-load').each(function (index) {
                $(this).delay(index * 100).queue(function () {
                    $(this).addClass('loaded').dequeue();
                });
            });
        }, 100);
    });

    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        let container = document.getElementById('alerts-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'alerts-container';
            document.querySelector('.main-content').insertBefore(container, document.querySelector('.main-content').firstChild);
        }

        container.innerHTML = alertHtml;
        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if (alert) alert.remove();
        }, 5000);
    }
</script>
</body>

</html>