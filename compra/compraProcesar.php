<?php

    require_once "../utilidades/conectar_db.php";
    session_start();

    $con = conectar();

    // Comprobar login
    if (!isset($_SESSION["id"])) {
        header("Location: ../index.php?acceso=1");
        exit;
    }

    $idUsuario = $_SESSION["id"];

    // Obtener datos del usuario
    $sql = $con->prepare("SELECT nombre, telefono, direccion, localidad, provincia FROM usuarios WHERE id = :id");
    $sql->execute([":id" => $idUsuario]);
    $usuario = $sql->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        header("Location: ../index.php?existe=1");
        exit;
    }

    // Obtener carrito del usuario
    $sql = $con->prepare("SELECT c.id_producto, c.cantidad, p.precio FROM carrito c JOIN productos p ON c.id_producto = p.id WHERE c.id_usuario = :idUsuario");
    $sql->execute([":idUsuario" => $idUsuario]);
    $carrito = $sql->fetchAll(PDO::FETCH_ASSOC);

    if (empty($carrito)) {
        header("Location: carrito.php?vacio=1");
        exit;
    }

    // Calcular total
    $total = 0;
    foreach ($carrito as $item) {
        $total += $item["precio"] * $item["cantidad"];
    }

    // Crear pedido
    $sql = $con->prepare("INSERT INTO pedidos (id_usuario, fecha, total, estado, tipo_pago, direccion_envio, localidad_envio, provincia_envio, telefono_envio)
                          VALUES (:id_usuario, NOW(), :total, 'En curso', :tipo_pago, :direccion, :localidad, :provincia, :telefono)");

    $sql->execute([
        ":id_usuario" => $idUsuario,
        ":total" => $total,
        ":tipo_pago" => "Stripe",
        ":direccion" => $usuario["direccion"],
        ":localidad" => $usuario["localidad"],
        ":provincia" => $usuario["provincia"],
        ":telefono" => $usuario["telefono"]
    ]);

    $idPedido = $con->lastInsertId();

    // Insertar detalles del pedido
    $sqlDetalles = $con->prepare("INSERT INTO detalles_pedidos (id_pedido, id_producto, cantidad, precio_unitario)
                                  VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario)");

    foreach ($carrito as $item) {
        $sqlDetalles->execute([
            ":id_pedido" => $idPedido,
            ":id_producto" => $item["id_producto"],
            ":cantidad" => $item["cantidad"],
            ":precio_unitario" => $item["precio"] // Precio actual guardado como histórico
        ]);
    }

    // Vaciar carrito
    $sql = $con->prepare("DELETE FROM carrito WHERE id_usuario = :idUsuario");
    $sql->execute([":idUsuario" => $idUsuario]);

    // Redirigir a confirmación
    header("Location: ../pedidos/pedidoConfirmado.php?id=" . $idPedido);
    exit;

?>