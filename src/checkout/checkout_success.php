<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection and Stripe Initialization via absolute paths
    require_once __DIR__ . "/../utils/Database.php";
    require_once __DIR__ . "/../stripe/init.php"; 

    $db = Database::getConnection();

    // Check login status
    if (!isset($_SESSION["id"])) {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    $userId = $_SESSION["id"];

    // Verify Stripe session existence
    if (!isset($_GET["session_id"])) {
        header("Location: /index.php?error=paymentError");
        exit;
    }

    // Retrieve Stripe Session and gracefully handle potential API exceptions
    try {
        $session = \Stripe\Checkout\Session::retrieve($_GET["session_id"]);

        if ($session->payment_status !== "paid") {
            header("Location: /index.php?error=paymentError");
            exit;
        }
    } catch (\Exception $e) {
        // Log the actual error internally if needed, but show standard error to the user
        header("Location: /index.php?error=paymentError");
        exit;
    }

    // Fetch User shipping data using aliases
    $userStmt = $db->prepare("SELECT telefono AS phone, direccion AS address, localidad AS city, provincia AS province FROM usuarios WHERE id = :id");
    $userStmt->execute([":id" => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch Cart data freezing the current product price
    $cartStmt = $db->prepare("SELECT c.id_producto AS product_id, c.cantidad AS quantity, p.precio AS price FROM carrito c 
                              JOIN productos p ON c.id_producto = p.id WHERE c.id_usuario = :userId");
    $cartStmt->execute([":userId" => $userId]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate order total
    $totalAmount = 0;
    foreach ($cartItems as $item) {
        $totalAmount += $item["price"] * $item["quantity"];
    }

    // Insert Base Order Record
    $insertOrderStmt = $db->prepare("INSERT INTO pedidos (id_usuario, fecha, total, estado, tipo_pago, direccion_envio, localidad_envio, provincia_envio, telefono_envio)
                                     VALUES (:userId, NOW(), :total, 'En curso', 'Stripe', :address, :city, :province, :phone)");

    $insertOrderStmt->execute([
        ":userId" => $userId,
        ":total" => $totalAmount,
        ":address" => $user["address"],
        ":city" => $user["city"],
        ":province" => $user["province"],
        ":phone" => $user["phone"]
    ]);

    $orderId = $db->lastInsertId();

    // Insert Order Details line by line (Historical record of prices and quantities)
    $insertDetailsStmt = $db->prepare("INSERT INTO detalles_pedidos (id_pedido, id_producto, cantidad, precio_unitario)
                                       VALUES (:order_id, :product_id, :quantity, :unit_price)");

    foreach ($cartItems as $item) {
        $insertDetailsStmt->execute([
            ":order_id" => $orderId,
            ":product_id" => $item["product_id"],
            ":quantity" => $item["quantity"],
            ":unit_price" => $item["price"]
        ]);
    }

    // Empty user cart after successful order creation
    $clearCartStmt = $db->prepare("DELETE FROM carrito WHERE id_usuario = :userId");
    $clearCartStmt->execute([":userId" => $userId]);

    // Redirect to standardized order confirmation view
    header("Location: /orders/order_confirmation.php?id=" . $orderId);
    exit;

?>