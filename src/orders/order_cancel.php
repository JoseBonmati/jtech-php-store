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

    // Validate request parameter
    if (!isset($_GET["id"])) {
        header("Location: /orders/order_list.php");
        exit;
    }

    $orderId = (int) $_GET["id"];

    // Fetch order status and ownership validation
    $orderStmt = $db->prepare("SELECT estado AS status FROM pedidos WHERE id = :id AND id_usuario = :userId");
    $orderStmt->execute([
        ":id" => $orderId,
        ":userId" => $userId
    ]);

    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    // If order does not exist or does not belong to the user
    if (!$order) {
        header("Location: /orders/order_list.php?error=unauthorized");
        exit;
    }

    // Business Logic: Order can only be canceled if status is 'En curso'
    if ($order["status"] !== "En curso") {
        header("Location: /orders/order_list.php?error=notCancelable");
        exit;
    }

    // Execute cancellation
    $updateStmt = $db->prepare("UPDATE pedidos SET estado = 'Cancelado' WHERE id = :id");
    $updateStmt->execute([":id" => $orderId]);

    // Redirect to list with success parameter
    header("Location: /orders/order_list.php?order_canceled=1");
    exit;

?>