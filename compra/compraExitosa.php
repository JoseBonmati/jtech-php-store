<?php

    session_start();
    require_once "../utilidades/conectar_db.php";
    require_once "../stripe/init.php";

    $con = conectar();

    // Comprobar login
    if (!isset($_SESSION["id"])) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    $idUsuario = $_SESSION["id"];

    // Verificar sesión de Stripe
    if (!isset($_GET["session_id"])) {
        header("Location: ../index.php?pagoError=1");
        exit;
    }

    $session = \Stripe\Checkout\Session::retrieve($_GET["session_id"]);

    if ($session->payment_status !== "paid") {
        header("Location: ../index.php?pagoError=2");
        exit;
    }

    // Obtener datos del usuario
    $sql = $con->prepare("SELECT nombre, telefono, direccion, localidad, provincia FROM usuarios WHERE id = :id");
    $sql->execute([":id" => $idUsuario]);
    $usuario = $sql->fetch(PDO::FETCH_ASSOC);

    // Obtener carrito
    $sql = $con->prepare("SELECT c.id_producto, c.cantidad, p.precio FROM carrito c JOIN productos p ON c.id_producto = p.id WHERE c.id_usuario = :idUsuario");
    $sql->execute([":idUsuario" => $idUsuario]);
    $carrito = $sql->fetchAll(PDO::FETCH_ASSOC);

    // Calcular total
    $total = 0;
    foreach ($carrito as $item) {
        $total += $item["precio"] * $item["cantidad"];
    }

    // Crear pedido
    $sql = $con->prepare("INSERT INTO pedidos (id_usuario, fecha, total, estado, tipo_pago, direccion_envio, localidad_envio, provincia_envio, telefono_envio)
                          VALUES (:id_usuario, NOW(), :total, 'En curso', 'Stripe', :direccion, :localidad, :provincia, :telefono)");

    $sql->execute([
        ":id_usuario" => $idUsuario,
        ":total" => $total,
        ":direccion" => $usuario["direccion"],
        ":localidad" => $usuario["localidad"],
        ":provincia" => $usuario["provincia"],
        ":telefono" => $usuario["telefono"]
    ]);

    $idPedido = $con->lastInsertId();

    // Insertar detalles
    $sqlDetalles = $con->prepare("INSERT INTO detalles_pedidos (id_pedido, id_producto, cantidad, precio_unitario)
                                  VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario)");

    foreach ($carrito as $item) {
        $sqlDetalles->execute([
            ":id_pedido" => $idPedido,
            ":id_producto" => $item["id_producto"],
            ":cantidad" => $item["cantidad"],
            ":precio_unitario" => $item["precio"]
        ]);
    }

    // Vaciar carrito
    $sql = $con->prepare("DELETE FROM carrito WHERE id_usuario = :idUsuario");
    $sql->execute([":idUsuario" => $idUsuario]);

    // Redirigir a confirmación
    header("Location: ../pedidos/pedidoConfirmado.php?id=" . $idPedido);
    exit;

?>