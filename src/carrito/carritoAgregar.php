<?php

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    $idProducto = $_POST["id_producto"] ?? null;
    $idUsuario = $_SESSION["id"] ?? null;
    $token = $_SESSION["carrito_token"];

    if (!$idProducto) {
        header("Location: ../index.php?errorC=productoInvalido");
        exit;
    }

    // Comprobar que el producto existe y tiene stock
    $sql = $con->prepare("SELECT stock, estado FROM productos WHERE id = :id");
    $sql->execute([":id" => $idProducto]);
    $producto = $sql->fetch(PDO::FETCH_ASSOC);

    if (!$producto || $producto["estado"] !== "activo") {
        header("Location: ../index.php?errorC=productoNoDisponible");
        exit;
    }

    if ($producto["stock"] <= 0) {
        header("Location: ../index.php?errorC=sinStock");
        exit;
    }

    // Comprobar si ya está en el carrito
    if ($idUsuario) {
        $sql = $con->prepare("SELECT id, cantidad FROM carrito WHERE id_usuario = :idUsuario AND id_producto = :idProducto");
        $sql->execute([
            ":idUsuario" => $idUsuario,
            ":idProducto" => $idProducto
        ]);
    } else {
        $sql = $con->prepare("SELECT id, cantidad FROM carrito WHERE token = :token AND id_usuario IS NULL AND id_producto = :idProducto");
        $sql->execute([
            ":token" => $token,
            ":idProducto" => $idProducto
        ]);
    }

    $item = $sql->fetch(PDO::FETCH_ASSOC);

    // Si ya existe, aumentar cantidad sin superar stock
    if ($item) {
        $nuevaCantidad = $item["cantidad"] + 1;

        if ($nuevaCantidad > $producto["stock"]) {
            header("Location: ../index.php?errorC=stockInsuficiente");
            exit;
        }

        $sql = $con->prepare("UPDATE carrito SET cantidad = :cantidad WHERE id = :id");
        $sql->execute([
            ":cantidad" => $nuevaCantidad,
            ":id" => $item["id"]
        ]);

    } else {
        // Insertar nuevo producto en el carrito
        $sql = $con->prepare("INSERT INTO carrito (id_usuario, token, id_producto, cantidad) VALUES (:idUsuario, :token, :idProducto, 1)");
        $sql->execute([
            ":idUsuario" => $idUsuario,
            ":token" => $token,
            ":idProducto" => $idProducto
        ]);
    }

    $redireccionar = $_POST["redireccionar"] ?? "../index.php";
    $redireccionar .= (str_contains($redireccionar, '?') ? '&' : '?') . "agregado=1";

    header("Location: " . $redireccionar);
    exit;


?>
