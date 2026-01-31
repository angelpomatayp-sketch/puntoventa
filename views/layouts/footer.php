    </div> <!-- End Page Content -->

    <?php if (isLoggedIn()): ?>
    </div> <!-- End Main Content -->
    <?php endif; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery (opcional pero útil) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

    <!-- Sidebar Toggle Script -->
    <?php if (isLoggedIn()): ?>
    <script>
        // Toggle sidebar for mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    mainContent.classList.toggle('sidebar-open');
                });

                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    if (window.innerWidth <= 768) {
                        if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                            sidebar.classList.remove('active');
                            mainContent.classList.remove('sidebar-open');
                        }
                    }
                });
            }

            // Highlight active menu item
            const currentPath = window.location.pathname;
            document.querySelectorAll('.sidebar-link').forEach(link => {
                if (link.getAttribute('href') === currentPath ||
                    currentPath.includes(link.getAttribute('href')) &&
                    link.getAttribute('href') !== '<?php echo BASE_URL; ?>/index.php') {
                    link.classList.add('active');
                }
            });
        });
    </script>
    <?php endif; ?>

    <?php if (isset($custom_js)): ?>
        <?php echo $custom_js; ?>
    <?php endif; ?>
</body>
</html>
