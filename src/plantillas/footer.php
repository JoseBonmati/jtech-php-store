    <footer class="pt-5">
        <div class="container border-bottom border-3 pb-3">
            <div class="row text-center text-lg-start gy-4 align-items-center fs-5">
                <div class="col-lg-4">
                    <h6 class="text-uppercase fw-bold mb-3 fs-5">Correo</h6>
                    <p class="mb-1">contacto@jtech.com</p>
                    <h6 class="text-uppercase fw-bold mt-4 mb-3 fs-5">Dirección</h6>
                    <address class="mb-0">
                        Avenida Tecnología, 42<br>
                        03201 Elche, Alicante
                    </address>
                </div>

                <div class="col-lg-4 text-center">
                    <img src="../assets/logos/logo-grande.png" alt="Logo grande" class="img-fluid" style="max-width: 300px;">
                </div>

                <div class="col-lg-4 text-lg-end text-center">
                    <h6 class="text-uppercase fw-bold mb-3 fs-5">Teléfono</h6>
                    <p class="mb-1">900 123 456</p>
                    <div class="mt-4 d-flex gap-3 justify-content-lg-end justify-content-center">
                        <a href="https://www.instagram.com" target="_blank" class="fs-4">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="https://www.facebook.com" target="_blank" class="fs-4">
                            <i class="bi bi-facebook"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center py-3 mt-1">
            <div class="container small">
                <div class="row justify-content-between align-items-center">
                    <div class="col-lg-auto mb-2 mb-lg-0">
                        © <?= date("Y") ?> Jtech | Diseñado y desarrollado por Jose
                    </div>
                    <div class="col-lg-auto">
                        <a href="#legal" class="me-3">Aviso legal</a>
                        <a href="#privacidad" class="me-3">Política de privacidad</a>
                        <a href="#cookies">Política de cookies</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Activar tooltips -->
    <script>

        document.addEventListener("DOMContentLoaded", function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        })

    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
