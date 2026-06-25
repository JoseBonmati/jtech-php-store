<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    // Detect if the user is logged in or a guest
    $userId = $_SESSION["id"] ?? null;
    $cartToken = $_SESSION["cart_token"] ?? null;

    // Fetch cart products using aliases for an English data structure
    if ($userId) {
        $stmt = $db->prepare("SELECT c.id, c.cantidad AS quantity, p.nombre AS name, p.precio AS price, p.imagen AS image FROM carrito c 
                              JOIN productos p ON c.id_producto = p.id WHERE c.id_usuario = :userId");
        $stmt->execute([":userId" => $userId]);
    } else {
        $stmt = $db->prepare("SELECT c.id, c.cantidad AS quantity, p.nombre AS name, p.precio AS price, p.imagen AS image FROM carrito c 
                              JOIN productos p ON c.id_producto = p.id WHERE c.token = :token AND c.id_usuario IS NULL");
        $stmt->execute([":token" => $cartToken]);
    }

    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi carrito</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>

    <?php

        // Alert messages mapping
        if (isset($_GET["added"])) {
            echo "<p class='alert alert-success text-center mb-4'>Producto añadido al carrito correctamente.</p>";
        }
        if (isset($_GET["updated"])) {
            echo "<p class='alert alert-success text-center mb-4'>La cantidad del producto se ha actualizado correctamente.</p>";
        }
        if (isset($_GET["deleted"])) {
            echo "<p class='alert alert-success text-center mb-4'>El producto ha sido eliminado del carrito.</p>";
        }
        if (isset($_GET["emptied"])) {
            echo "<p class='alert alert-success text-center mb-4'>Tu carrito ha sido vaciado.</p>";
        }
        if (isset($_GET["empty_cart"])) {
            echo "<p class='alert alert-danger text-center mb-4'>Debe tener productos en el carrito para hacer un pedido.</p>";
        }
        
        // Specific cart error handling
        if (isset($_GET["cart_error"])) {
            $cartError = htmlspecialchars($_GET["cart_error"]);

            if ($cartError === "insufficient_stock") {
                echo "<p class='alert alert-danger text-center mb-4'>No hay suficiente stock para esa cantidad.</p>";
            }
            if ($cartError === "out_of_stock") {
                echo "<p class='alert alert-danger text-center mb-4'>Este producto está agotado.</p>";
            }
            if ($cartError === "product_unavailable") {
                echo "<p class='alert alert-danger text-center mb-4'>Este producto ya no está disponible.</p>";
            }
            if ($cartError === "item_not_found") {
                echo "<p class='alert alert-danger text-center mb-4'>El producto no existe en tu carrito.</p>";
            }
        }

    ?>

    <div class="container py-4">
        <div class="row justify-content-center">

            <!-- Main cart -->
            <div class="col-lg-8">
                <div class="jtech-card-wide p-4 mb-4">

                    <h2 class="fw-bold mb-4">Mi carrito</h2>

                    <?php 
                        $total = 0;
                        foreach ($cartItems as $item): 
                            $subtotal = $item["price"] * $item["quantity"];
                            $total += $subtotal;
                    ?>

                    <!-- Product -->
                    <div class="d-flex align-items-center mb-4 border-bottom pb-3 cart-item">
                        <img src="/<?= htmlspecialchars($item['image']) ?>" class="jtech-cart-img me-3" alt="<?= htmlspecialchars($item['name']) ?>">
                        
                        <div class="flex-grow-1 cart-item-info">
                            <h5 class="mb-1 fw-semibold"><?= htmlspecialchars($item["name"]) ?></h5>
                            <p class="mb-0">
                                <span class="text-success fw-bold fs-5"><?= number_format($item["price"], 2) ?> €</span>
                            </p>
                        </div>

                        <div class="product-actions d-flex align-items-center">
                            <form action="/cart/cart_update.php" method="post" class="d-flex align-items-center">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$item['id']) ?>">
                                <div class="input-group quantity-group">
                                    <button type="button" class="btn btn-outline-jtech qty-minus">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <input type="text" name="quantity" value="<?= htmlspecialchars((string)$item['quantity']) ?>" class="form-control text-center qty-input" readonly>
                                    <button type="button" class="btn btn-outline-jtech qty-plus">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </form>

                            <form action="/cart/cart_remove.php" method="post" class="ms-3">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$item['id']) ?>">
                                <button type="submit" class="btn btn-danger btn-delete-small">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php endforeach; ?>

                    <!-- Bottom buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="/cart/cart_clear.php" class="btn btn-outline-jtech">
                            <i class="bi bi-x-circle me-1"></i> Vaciar carrito
                        </a>
                        <a href="/index.php" class="btn btn-outline-jtech">
                            <i class="bi bi-arrow-left me-1"></i> Seguir comprando
                        </a>
                    </div>

                </div>
            </div>

            <!-- Side summary -->
            <div class="col-lg-4">
                <div class="jtech-card p-4">
                    <div class="d-flex justify-content-between jtech-cart-total mb-3">
                        <span>Total</span>
                        <span><?= number_format($total, 2) ?> €</span>
                    </div>

                    <?php if ($userId): ?>
                        <a href="/checkout/checkout.php" class="btn btn-jtech w-100">Finalizar compra</a>
                    <?php else: ?>
                        <a href="/users/login.php" class="btn btn-jtech w-100">Iniciar sesión</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // Plus Button
            document.querySelectorAll(".qty-plus").forEach(btn => {
                btn.addEventListener("click", function() {
                    const input = this.closest(".quantity-group").querySelector(".qty-input");
                    input.value = parseInt(input.value) + 1;
                    this.closest("form").submit();
                });
            });

            // Minus Button
            document.querySelectorAll(".qty-minus").forEach(btn => {
                btn.addEventListener("click", function() {
                    const input = this.closest(".quantity-group").querySelector(".qty-input");
                    if (parseInt(input.value) > 1) {
                        input.value = parseInt(input.value) - 1;
                        this.closest("form").submit();
                    }
                });
            });

        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>