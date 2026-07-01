<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection and Entity Inclusion
    require_once __DIR__ . "/../utils/Database.php";
    require_once __DIR__ . "/Order.php";
    $db = Database::getConnection();

    // Restrict access: only administrators and employees
    if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    if (!isset($_GET["id"])) {
        header("Location: /orders/order_list.php");
        exit;
    }

    $orderId = (int) $_GET["id"];

    // Fetch order data using aliases to hydrate the Order class correctly
    $sql = "SELECT p.id, p.id_usuario AS user_id, p.fecha AS date, p.total, p.estado AS status, p.tipo_pago AS payment_method, p.direccion_envio AS shipping_address, 
            p.localidad_envio AS shipping_city, p.provincia_envio AS shipping_province, p.telefono_envio AS shipping_phone, u.nombre AS user_name FROM pedidos p 
            JOIN usuarios u ON p.id_usuario = u.id WHERE p.id = :id";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([":id" => $orderId]);
    $stmt->setFetchMode(PDO::FETCH_CLASS, "Order");
    $order = $stmt->fetch();

    if (!$order) {
        header("Location: /orders/order_list.php?error=orderNotFound");
        exit;
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar estado del pedido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="jtech-bg d-flex justify-content-center align-items-start py-5">
        <div class="jtech-card p-4" style="max-width: 600px;">

            <h2 class="fw-bold text-center mb-4">Cambiar estado del pedido #<?= htmlspecialchars((string)$order->getId()) ?></h2>

            <p><strong>Usuario:</strong> <?= htmlspecialchars((string)$order->getUserName()) ?></p>
            <p><strong>Fecha:</strong> <?= date("d/m/Y H:i", strtotime($order->getDate())) ?></p>
            <p><strong>Total:</strong> <?= number_format($order->getTotal(), 2) ?> €</p>
            <p><strong>Estado actual:</strong> <?= htmlspecialchars((string)$order->getStatus()) ?></p>

            <hr class="jtech-divider">

            <h5 class="fw-bold mb-3">Selecciona el nuevo estado:</h5>

            <div class="d-grid gap-3">
                <a href="/orders/order_update_status.php?id=<?= $order->getId() ?>&status=En curso" class="btn btn-jtech fw-semibold">En curso</a>

                <a href="/orders/order_update_status.php?id=<?= $order->getId() ?>&status=Enviado" class="btn btn-jtech fw-semibold">Enviado</a>

                <a href="/orders/order_update_status.php?id=<?= $order->getId() ?>&status=En reparto" class="btn btn-jtech fw-semibold">En reparto</a>

                <a href="/orders/order_update_status.php?id=<?= $order->getId() ?>&status=Recogido" class="btn btn-jtech fw-semibold">Recogido</a>

                <a href="/orders/order_update_status.php?id=<?= $order->getId() ?>&status=Cancelado" class="btn btn-outline-danger fw-semibold"
                onclick="return confirm('¿Seguro que quieres cancelar este pedido?');">Cancelado</a>
            </div>

            <div class="text-center mt-4">
                <a href="/orders/order_list.php" class="btn btn-outline-secondary">Volver</a>
            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>