<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    $productId = $_POST["product_id"] ?? null;
    $userId = $_SESSION["id"] ?? null;
    $cartToken = $_SESSION["cart_token"] ?? null;

    if (!$productId) {
        header("Location: /index.php?error=invalidProduct");
        exit;
    }

    // Check if the product exists and is active (Database columns remain in Spanish)
    $stmt = $db->prepare("SELECT stock, estado FROM productos WHERE id = :id");
    $stmt->execute([":id" => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product || $product["estado"] !== "activo") {
        header("Location: /index.php?error=invalidProduct");
        exit;
    }

    if ($product["stock"] <= 0) {
        header("Location: /index.php?error=outOfStock");
        exit;
    }

    // Check if the item is already present in the cart
    if ($userId) {
        $stmt = $db->prepare("SELECT id, cantidad FROM carrito WHERE id_usuario = :userId AND id_producto = :productId");
        $stmt->execute([
            ":userId" => $userId,
            ":productId" => $productId
        ]);
    } else {
        $stmt = $db->prepare("SELECT id, cantidad FROM carrito WHERE token = :token AND id_usuario IS NULL AND id_producto = :productId");
        $stmt->execute([
            ":token" => $cartToken,
            ":productId" => $productId
        ]);
    }

    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

    // If it already exists, increment quantity without exceeding available warehouse stock
    if ($cartItem) {
        $newQuantity = $cartItem["cantidad"] + 1;

        if ($newQuantity > $product["stock"]) {
            header("Location: /index.php?error=insufficientStock");
            exit;
        }

        $updateStmt = $db->prepare("UPDATE carrito SET cantidad = :quantity WHERE id = :id");
        $updateStmt->execute([
            ":quantity" => $newQuantity,
            ":id" => $cartItem["id"]
        ]);

    } else {
        // Insert new product into the cart database table
        $insertStmt = $db->prepare("INSERT INTO carrito (id_usuario, token, id_producto, cantidad) VALUES (:userId, :token, :productId, 1)");
        $insertStmt->execute([
            ":userId" => $userId,
            ":token" => $cartToken,
            ":productId" => $productId
        ]);
    }

    $redirectTo = $_POST["redirect"] ?? "/index.php";
    $redirectTo .= (str_contains($redirectTo, '?') ? '&' : '?') . "added=1";

    header("Location: " . $redirectTo);
    exit;

?>