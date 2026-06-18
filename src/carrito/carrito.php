<?php

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    // Detectar si es usuario o invitado
    $idUsuario = $_SESSION["id"] ?? null;
    $token = $_SESSION["carrito_token"];

    // Obtener productos del carrito
    if ($idUsuario) {
        $sql = $con->prepare("SELECT c.id, c.cantidad, p.nombre, p.precio, p.imagen FROM carrito c JOIN productos p ON c.id_producto = p.id 
                              WHERE c.id_usuario = :idUsuario");
        $sql->execute([":idUsuario" => $idUsuario]);
    } else {
        $sql = $con->prepare("SELECT c.id, c.cantidad, p.nombre, p.precio, p.imagen FROM carrito c JOIN productos p ON c.id_producto = p.id 
                              WHERE c.token = :token AND c.id_usuario IS NULL");
        $sql->execute([":token" => $token]);
    }

    $carrito = $sql->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi carrito</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>

    <?php

        if (isset($_GET["agregado"])) {
            echo "<p class='alert alert-success text-center mb-4'>Producto añadido al carrito correctamente.</p>";
        }
        if (isset($_GET["actualizado"])) {
            echo "<p class='alert alert-success text-center mb-4'>La cantidad del producto se ha actualizado correctamente.</p>";
        }
        if (isset($_GET["eliminado"])) {
            echo "<p class='alert alert-success text-center mb-4'>El producto ha sido eliminado del carrito.</p>";
        }
        if (isset($_GET["vaciado"])) {
            echo "<p class='alert alert-success text-center mb-4'>Tu carrito ha sido vaciado.</p>";
        }
        if (isset($_GET["vacio"])) {
            echo "<p class='alert alert-danger text-center mb-4'>Debe tener productos en el carrito para hacer un pedido.</p>";
        }
        if (isset($_GET["errorC"])) {
            $errorC = htmlspecialchars($_GET["errorC"]);

            if ($errorC === "stockInsuficiente") {
                echo "<p class='alert alert-danger text-center mb-4'>No hay suficiente stock para esa cantidad.</p>";
            }
            if ($errorC === "sinStock") {
                echo "<p class='alert alert-danger text-center mb-4'>Este producto está agotado.</p>";
            }
            if ($errorC === "productoNoDisponible") {
                echo "<p class='alert alert-danger text-center mb-4'>Este producto ya no está disponible.</p>";
            }
            if ($errorC === "itemNoExiste") {
                echo "<p class='alert alert-danger text-center mb-4'>El producto no existe en tu carrito.</p>";
            }
        }

    ?>

    <div class="container py-4">
        <div class="row justify-content-center">

            <!-- Carrito principal -->
            <div class="col-lg-8">
                <div class="jtech-card-wide p-4 mb-4">

                    <h2 class="fw-bold mb-4">Mi carrito</h2>

                    <?php 
                        $total = 0;
                        foreach ($carrito as $item): 
                            $subtotal = $item["precio"] * $item["cantidad"];
                            $total += $subtotal;
                    ?>

                    <!-- Producto -->
                    <div class="d-flex align-items-center mb-4 border-bottom pb-3 cart-item">
                        <img src="../<?= $item['imagen'] ?>" class="jtech-cart-img me-3">
                        
                        <div class="flex-grow-1 cart-item-info">
                            <h5 class="mb-1 fw-semibold"><?= htmlspecialchars($item["nombre"]) ?></h5>
                            <p class="mb-0">
                                <span class="text-success fw-bold fs-5"><?= number_format($item["precio"], 2) ?> €</span>
                            </p>
                        </div>

                        <div class="product-actions d-flex align-items-center">
                            <form action="carritoActualizar.php" method="post" class="d-flex align-items-center">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <div class="input-group quantity-group">
                                    <button type="button" class="btn btn-outline-jtech qty-minus">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <input type="text" name="cantidad" value="<?= $item['cantidad'] ?>" class="form-control text-center qty-input" readonly>
                                    <button type="button" class="btn btn-outline-jtech qty-plus">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </form>

                            <form action="carritoEliminar.php" method="post" class="ms-3">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-delete-small">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php endforeach; ?>

                    <!-- Botones inferiores -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="carritoVaciar.php" class="btn btn-outline-jtech">
                            <i class="bi bi-x-circle me-1"></i> Vaciar carrito
                        </a>
                        <a href="../index.php" class="btn btn-outline-jtech">
                            <i class="bi bi-arrow-left me-1"></i> Seguir comprando
                        </a>
                    </div>

                </div>
            </div>

            <!-- Resumen lateral -->
            <div class="col-lg-4">
                <div class="jtech-card p-4">
                    <div class="d-flex justify-content-between jtech-cart-total mb-3">
                        <span>Total</span>
                        <span><?= number_format($total, 2) ?> €</span>
                    </div>

                    <?php if ($idUsuario): ?>
                        <a href="../compra/compraFinalizar.php" class="btn btn-jtech w-100">Finalizar compra</a>
                    <?php else: ?>
                        <a href="../index.php" class="btn btn-jtech w-100">Iniciar sesión</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // Botón +
            document.querySelectorAll(".qty-plus").forEach(btn => {
                btn.addEventListener("click", function() {
                    const input = this.closest(".quantity-group").querySelector(".qty-input");
                    input.value = parseInt(input.value) + 1;
                    this.closest("form").submit();
                });
            });

            // Botón -
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