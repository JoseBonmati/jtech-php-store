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

    if (!$userId && !$cartToken) {
        header("Location: /cart/cart.php");
        exit;
    }

    // Empty cart based on user status
    if ($userId) {
        $stmt = $db->prepare("DELETE FROM carrito WHERE id_usuario = :userId");
        $stmt->execute([":userId" => $userId]);
    } else {
        $stmt = $db->prepare("DELETE FROM carrito WHERE token = :token AND id_usuario IS NULL");
        $stmt->execute([":token" => $cartToken]);
    }

    // Redirect to cart with the translated success parameter
    header("Location: /cart/cart.php?emptied=1");
    exit;

?>