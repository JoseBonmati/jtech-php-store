<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    // Check login status
    if (!isset($_SESSION["id"])) {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    $userId = $_SESSION["id"];

    // Fetch user shipping details using English aliases
    $userStmt = $db->prepare("SELECT nombre AS name, telefono AS phone, direccion AS address, localidad AS city, provincia AS province FROM usuarios WHERE id = :id");
    $userStmt->execute([":id" => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: /index.php?error=userNotFound");
        exit;
    }

    // Validate if mandatory shipping information is missing
    $incompleteData = empty($user["address"]) || empty($user["city"]) || empty($user["province"]);

    // Fetch current user cart items
    $cartStmt = $db->prepare("SELECT c.id, c.cantidad AS quantity, p.nombre AS name, p.precio AS price, p.imagen AS image FROM carrito c 
                              JOIN productos p ON c.id_producto = p.id WHERE c.id_usuario = :userId");
    $cartStmt->execute([":userId" => $userId]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate cart total
    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item["price"] * $item["quantity"];
    }

    // Estimated delivery dates calculation
    $deliveryStart = date("d/m/Y", strtotime("+6 days"));
    $deliveryEnd = date("d/m/Y", strtotime("+11 days"));

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar compra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="jtech-card-wide p-4 mb-4">

                    <h3 class="fw-bold mb-3">Dirección de entrega</h3>
                        <div class="mb-3">
                            <?php
                                if (isset($_GET["updated_name"]) && isset($_GET["updated_email"])) {
                                    $updatedName = htmlspecialchars($_GET["updated_name"]);
                                    $updatedEmail = htmlspecialchars($_GET["updated_email"]);
                                    echo "<p class='alert alert-success'>El usuario <b>$updatedName</b> con email <b>$updatedEmail</b> ha sido modificado correctamente.</p>";
                                }
                            ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div class="me-3">
                                <?php if ($incompleteData): ?>
                                    <div class="alert alert-warning mb-3">
                                        Faltan datos de envío. Por favor complétalos antes de continuar.
                                    </div>
                                <?php else: ?>
                                    <p class="mb-1">
                                        <strong><?= htmlspecialchars((string)$user["name"]) ?></strong> - 
                                        <strong><?= htmlspecialchars((string)$user["phone"]) ?></strong>
                                    </p>
                                    <p class="mb-1"><?= htmlspecialchars((string)$user["address"]) ?></p>
                                    <p class="mb-3">
                                        <?= htmlspecialchars((string)$user["city"]) ?>, 
                                        <?= htmlspecialchars((string)$user["province"]) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="mt-2 mt-lg-0">
                                <a href="/users/user_edit.php?id=<?= $userId ?>&from=checkout" class="btn btn-outline-jtech fw-semibold">Cambiar</a>
                            </div>
                        </div>

                    <hr class="jtech-divider my-4">

                    <h3 class="fw-bold mb-3">Método de pago</h3>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" checked disabled>
                        <label class="form-check-label fw-semibold">Stripe</label>
                    </div>
                </div>

                <div class="jtech-card-wide p-4 mb-4">
                    <h3 class="fw-bold mb-3">Detalles de los artículos</h3>

                    <?php foreach ($cartItems as $item): ?>
                        <div class="d-flex align-items-center mb-3 border-bottom pb-2">
                            <img src="/<?= htmlspecialchars($item['image']) ?>" class="jtech-cart-img me-3" alt="<?= htmlspecialchars($item['name']) ?>">

                            <div class="flex-grow-1">
                                <p class="fw-semibold mb-1"><?= htmlspecialchars($item["name"]) ?></p>
                                <p class="mb-0">Cantidad: <?= htmlspecialchars((string)$item["quantity"]) ?></p>
                            </div>

                            <div class="text-end">
                                <p class="fw-bold text-success mb-0">
                                    <?= number_format($item["price"] * $item["quantity"], 2) ?> €
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>

            <div class="col-lg-4">
                <div class="jtech-card p-4 mb-4">

                    <h3 class="fw-bold mb-3">Método de envío</h3>

                    <p class="mb-1"><strong>Envío gratis</strong></p>
                    <p class="mb-1">Entrega estimada: <?= $deliveryStart ?> - <?= $deliveryEnd ?></p>
                    <p class="mb-0">Empresa de transporte: <strong>Joserreos</strong></p>
                </div>

                <div class="jtech-card p-4">
                    <h3 class="fw-bold mb-3">Resumen</h3>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Total productos:</span>
                        <span><?= number_format($total, 2) ?> €</span>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Gastos de envío:</span>
                        <span class="text-success fw-bold">Gratis</span>
                    </div>

                    <?php if ($incompleteData): ?>
                        <a href="/users/user_edit.php?id=<?= $userId ?>&from=checkout" class="btn btn-jtech w-100 fw-semibold mb-2">Completar datos de envío</a>
                    <?php else: ?>
                        <form action="/stripe/create_session.php" method="POST">
                            <button type="submit" class="btn btn-jtech w-100 fw-semibold mb-2">Completar el pago</button>
                        </form>
                    <?php endif; ?>

                    <a href="/cart/cart.php" class="btn btn-outline-jtech w-100 fw-semibold">Volver al carrito</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>