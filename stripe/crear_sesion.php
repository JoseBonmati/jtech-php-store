<?php

    session_start();
    require_once "../utilidades/conectar_db.php";
    require_once "init.php";

    $con = conectar();

    // Comprobar login
    if (!isset($_SESSION["id"])) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    $idUsuario = $_SESSION["id"];

    // Obtener carrito
    $sql = $con->prepare("SELECT c.cantidad, p.nombre, p.precio FROM carrito c JOIN productos p ON c.id_producto = p.id WHERE c.id_usuario = :idUsuario");
    $sql->execute([":idUsuario" => $idUsuario]);
    $carrito = $sql->fetchAll(PDO::FETCH_ASSOC);

    if (empty($carrito)) {
        header("Location: ../carrito/carrito.php?vacio=1");
        exit;
    }

    // Crear línea de productos para Stripe
    $line_items = [];

    foreach ($carrito as $item) {
        $line_items[] = [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => $item['nombre'],
                ],
                'unit_amount' => intval($item['precio'] * 100),
            ],
            'quantity' => $item['cantidad'],
        ];
    }

    // Crear sesión de pago
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => 'https://jtech.kesug.com/compra/compraExitosa.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://jtech.kesug.com/compra/compraFinalizar.php',

    ]);

    header("Location: " . $session->url);
    exit;

?>