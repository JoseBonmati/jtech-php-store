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

    // Retrieve Cart ID from POST request
    $cartId = $_POST["id"] ?? null;

    if (!$cartId) {
        header("Location: /cart/cart.php");
        exit;
    }

    // Delete item based on whether it's a registered user or a guest
    if ($userId) {
        $stmt = $db->prepare("DELETE FROM carrito WHERE id = :id AND id_usuario = :userId");
        $stmt->execute([
            ":id" => $cartId,
            ":userId" => $userId
        ]);
    } else {
        $stmt = $db->prepare("DELETE FROM carrito WHERE id = :id AND token = :token AND id_usuario IS NULL");
        $stmt->execute([
            ":id" => $cartId,
            ":token" => $cartToken
        ]);
    }

    // Redirect to cart with the translated success parameter
    header("Location: /cart/cart.php?deleted=1");
    exit;

?>