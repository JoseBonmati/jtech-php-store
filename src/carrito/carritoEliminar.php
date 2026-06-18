<?php

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    // Detectar si es usuario o invitado
    $idUsuario = $_SESSION["id"] ?? null;
    $token = $_SESSION["carrito_token"];

    // Recoger ID del carrito
    $idCarrito = $_POST["id"] ?? null;

    if (!$idCarrito) {
        header("Location: carrito.php");
        exit;
    }

    // Eliminar según sea usuario o invitado
    if ($idUsuario) {
        $sql = $con->prepare("DELETE FROM carrito WHERE id = :id AND id_usuario = :idUsuario");
        $sql->execute([
            ":id" => $idCarrito,
            ":idUsuario" => $idUsuario
        ]);
    } else {
        $sql = $con->prepare("DELETE FROM carrito WHERE id = :id AND token = :token AND id_usuario IS NULL");
        $sql->execute([
            ":id" => $idCarrito,
            ":token" => $token
        ]);
    }

    header("Location: carrito.php");
    exit;

?>