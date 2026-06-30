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

    // Check if an order ID is provided in the URL
    if (!isset($_GET["id"])) {
        header("Location: /index.php");
        exit;
    }

    $orderId = (int) $_GET["id"];

    // Fetch order details ensuring the order belongs to the logged-in user
    $orderStmt = $db->prepare("SELECT id, fecha AS date, total, tipo_pago AS payment_method, direccion_envio AS shipping_address, localidad_envio AS shipping_city, 
                               provincia_envio AS shipping_province, telefono_envio AS shipping_phone FROM pedidos WHERE id = :id AND id_usuario = :userId");
    
    $orderStmt->execute([
        ":id" => $orderId,
        ":userId" => $userId
    ]);

    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    // If order does not exist or does not belong to the user, redirect with error
    if (!$order) {
        header("Location: /index.php?error=orderNotFound");
        exit;
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido confirmado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="jtech-card-wide p-5 text-center">

                    <h2 class="fw-bold mb-3">¡Gracias por tu compra!</h2>
                    <p class="text-muted mb-4">Tu pedido ha sido procesado correctamente.</p>

                    <hr class="jtech-divider my-4">

                    <h4 class="fw-bold mb-3">Detalles del pedido</h4>

                    <p class="mb-1"><strong>Número de pedido:</strong> <?= htmlspecialchars((string)$order["id"]) ?></p>
                    <p class="mb-1"><strong>Fecha:</strong> <?= date("d/m/Y H:i", strtotime($order["date"])) ?></p>
                    <p class="mb-1"><strong>Total:</strong> <?= number_format($order["total"], 2) ?> €</p>
                    <p class="mb-3"><strong>Método de pago:</strong> <?= htmlspecialchars($order["payment_method"]) ?></p>

                    <hr class="jtech-divider my-4">

                    <h4 class="fw-bold mb-3">Dirección de envío</h4>

                    <p class="mb-1"><?= htmlspecialchars($order["shipping_address"]) ?></p>
                    <p class="mb-1"><?= htmlspecialchars($order["shipping_city"]) ?>, <?= htmlspecialchars($order["shipping_province"]) ?></p>
                    <p class="mb-4">Teléfono: <?= htmlspecialchars($order["shipping_phone"]) ?></p>

                    <div class="d-grid gap-3 mt-4">
                        <a href="/index.php" class="btn btn-jtech fw-semibold">Volver a la tienda</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>