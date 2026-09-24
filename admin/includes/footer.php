            </main>

            <footer class="admin-footer">
                &copy; <?php echo date('Y'); ?> TourBan Admin
                <span class="text-muted">· v1.0</span>
            </footer>
        </div>
    </div>

    <div class="admin-overlay" id="adminOverlay"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            var burger = document.getElementById('adminBurger');
            var sidebar = document.getElementById('adminSidebar');
            var overlay = document.getElementById('adminOverlay');

            function toggleSidebar() {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('show');
            }
            if (burger) burger.addEventListener('click', toggleSidebar);
            if (overlay) overlay.addEventListener('click', toggleSidebar);

            var logoutBtn = document.getElementById('adminLogoutBtn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!confirm('Log out of the admin panel?')) return;
                    fetch((window.BASE_URL || '') + '/api/logout.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': window.CSRF_TOKEN || ''
                            }
                        })
                        .then(function(r) { return r.json(); })
                        .then(function() {
                            window.location.href = (window.BASE_URL || '') + '/login.php';
                        })
                        .catch(function() {
                            window.location.href = (window.BASE_URL || '') + '/login.php';
                        });
                });
            }
        })();
    </script>
    </body>

</html>
