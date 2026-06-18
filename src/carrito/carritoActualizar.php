<?php

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    $idCarrito = $_POST["id"] ?? null;
    $cantidad = (int) ($_POST["cantidad"] ?? 1);

    $idUsuario = $_SESSION["id"] ?? null;
    $token = $_SESSION["carrito_token"];

    if (!$idCarrito || $cantidad < 1) {
        header("Location: carrito.php");
        exit;
    }

    // Obtener el producto y su stock
    $sql = $con->prepare("SELECT c.id_producto, p.stock FROM carrito c JOIN productos p ON c.id_producto = p.id WHERE c.id = :id");
    $sql->execute([":id" => $idCarrito]);
    $producto = $sql->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        header("Location: carrito.php?errorC=itemNoExiste");
        exit;
    }

    // Comprobar stock
    if ($cantidad > $producto["stock"]) {
        header("Location: carrito.php?errorC=stockInsuficiente");
        exit;
    }

    // Actualizar según usuario o invitado
    if ($idUsuario) {
        $sql = $con->prepare("UPDATE carrito SET cantidad = :cantidad WHERE id = :id AND id_usuario = :idUsuario");
        $sql->execute([
            ":cantidad" => $cantidad,
            ":id" => $idCarrito,
            ":idUsuario" => $idUsuario
        ]);
    } else {
        $sql = $con->prepare("UPDATE carrito SET cantidad = :cantidad WHERE id = :id AND token = :token AND id_usuario IS NULL");
        $sql->execute([
            ":cantidad" => $cantidad,
            ":id" => $idCarrito,
            ":token" => $token
        ]);
    }

    header("Location: carrito.php?actualizado=1");
    exit;

?>