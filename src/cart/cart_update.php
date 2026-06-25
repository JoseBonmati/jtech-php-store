<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    // Fetch POST and Session variables
    $cartId = $_POST["id"] ?? null;
    $quantity = (int) ($_POST["quantity"] ?? 1);

    $userId = $_SESSION["id"] ?? null;
    $cartToken = $_SESSION["cart_token"] ?? null;

    if (!$cartId || $quantity < 1) {
        header("Location: /cart/cart.php");
        exit;
    }

    // Fetch the product and its current stock
    $stmt = $db->prepare("SELECT c.id_producto, p.stock FROM carrito c JOIN productos p ON c.id_producto = p.id WHERE c.id = :id");
    $stmt->execute([":id" => $cartId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: /cart/cart.php?cart_error=item_not_found");
        exit;
    }

    // Check if the requested quantity exceeds available stock
    if ($quantity > $product["stock"]) {
        header("Location: /cart/cart.php?cart_error=insufficient_stock");
        exit;
    }

    // Update cart quantity based on whether the user is logged in or a guest
    if ($userId) {
        $updateStmt = $db->prepare("UPDATE carrito SET cantidad = :quantity WHERE id = :id AND id_usuario = :userId");
        $updateStmt->execute([
            ":quantity" => $quantity,
            ":id" => $cartId,
            ":userId" => $userId
        ]);
    } else {
        $updateStmt = $db->prepare("UPDATE carrito SET cantidad = :quantity WHERE id = :id AND token = :token AND id_usuario IS NULL");
        $updateStmt->execute([
            ":quantity" => $quantity,
            ":id" => $cartId,
            ":token" => $cartToken
        ]);
    }

    // Redirect with success parameter
    header("Location: /cart/cart.php?updated=1");
    exit;

?>