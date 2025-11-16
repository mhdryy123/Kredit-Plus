<!-- includes/footer.php -->
    <footer class="bg-dark text-white mt-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-hand-holding-usd me-2"></i>KreditPlus</h5>
                    <p class="mt-3">Platform kredit terpercaya untuk mewujudkan impian Anda.</p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-light text-decoration-none">Home</a></li>
                        <li><a href="login.php" class="text-light text-decoration-none">Login</a></li>
                        <li><a href="register.php" class="text-light text-decoration-none">Daftar</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Kontak</h6>
                    <p class="mb-1"><i class="fas fa-phone me-2"></i>(021) 1234-5678</p>
                    <p class="mb-0"><i class="fas fa-envelope me-2"></i>info@kreditplus.com</p>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; 2025 KreditPlus. All rights reserved.</p>
            </div>
        </div>
    </footer>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/chart.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>