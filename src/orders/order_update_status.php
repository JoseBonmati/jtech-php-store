<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    // Restrict access: only administrators and employees
    if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    // Validate required parameters
    if (!isset($_GET["id"]) || !isset($_GET["status"])) {
        header("Location: /orders/order_list.php");
        exit;
    }

    $orderId = (int) $_GET["id"];
    $newStatus = $_GET["status"];

    // Allowed statuses
    $validStatuses = ["En curso", "Enviado", "En reparto", "Recogido", "Cancelado"];

    if (!in_array($newStatus, $validStatuses)) {
        header("Location: /orders/order_list.php?error=invalidStatus");
        exit;
    }

    // Check if the order exists
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE id = :id");
    $checkStmt->execute([":id" => $orderId]);

    if ($checkStmt->fetchColumn() == 0) {
        header("Location: /orders/order_list.php?error=orderNotFound");
        exit;
    }

    // Update order status
    $updateStmt = $db->prepare("UPDATE pedidos SET estado = :status WHERE id = :id");
    $updateStmt->execute([
        ":status" => $newStatus,
        ":id" => $orderId
    ]);

    // Redirect to list with standardized success parameter
    header("Location: /orders/order_list.php?status_updated=1");
    exit;

?>