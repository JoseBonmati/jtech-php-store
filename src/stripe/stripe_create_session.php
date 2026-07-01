<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . "/../utils/Database.php";
    require_once __DIR__ . "/init.php";

    $db = Database::getConnection();

    // Check login status
    if (!isset($_SESSION["id"])) {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    $userId = $_SESSION["id"];

    // Fetch user cart with aliases
    $cartStmt = $db->prepare("SELECT c.cantidad AS quantity, p.nombre AS name, p.precio AS price FROM carrito c JOIN productos p ON c.id_producto = p.id WHERE c.id_usuario = :userId");
    $cartStmt->execute([":userId" => $userId]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        header("Location: /cart/cart.php?empty=1");
        exit;
    }

    // Prepare line items for Stripe
    $lineItems = [];

    foreach ($cartItems as $item) {
        $lineItems[] = [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => $item['name'],
                ],
                'unit_amount' => intval($item['price'] * 100),
            ],
            'quantity' => $item['quantity'],
        ];
    }

    // Dynamic domain calculation for absolute callback URLs
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $domain = $protocol . "://" . $_SERVER['HTTP_HOST'];

    // Create Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => $domain . '/checkout/checkout_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $domain . '/checkout/checkout.php',
    ]);

    header("Location: " . $session->url);
    exit;

?>