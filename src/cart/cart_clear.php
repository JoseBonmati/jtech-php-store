<?php

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    // Detectar si es usuario o invitado
    $idUsuario = $_SESSION["id"] ?? null;
    $token = $_SESSION["carrito_token"] ?? null;

    if (!$idUsuario && !$token) {
        header("Location: carrito.php");
        exit;
    }

    // Vaciar carrito
    if ($idUsuario) {
        $sql = $con->prepare("DELETE FROM carrito WHERE id_usuario = :idUsuario");
        $sql->execute([":idUsuario" => $idUsuario]);
    } else {
        $sql = $con->prepare("DELETE FROM carrito WHERE token = :token AND id_usuario IS NULL");
        $sql->execute([":token" => $token]);
    }

    header("Location: carrito.php?vaciado=1");
    exit;

?>