<?php

    require_once "../utilidades/conectar_db.php";
    session_start();

    $con = conectar();

    // Solo usuarios logueados
    if (!isset($_SESSION["id"])) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    $idUsuario = $_SESSION["id"];

    if (!isset($_GET["id"])) {
        header("Location: pedidoConsulta.php");
        exit;
    }

    $idPedido = (int) $_GET["id"];

    // Obtener pedido y comprobar que pertenece al usuario
    $sql = $con->prepare("SELECT estado FROM pedidos WHERE id = :id AND id_usuario = :idUsuario");
    $sql->execute([
        ":id" => $idPedido,
        ":idUsuario" => $idUsuario
    ]);

    $pedido = $sql->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        header("Location: pedidoConsulta.php?error=noAutorizado");
        exit;
    }

    // Solo se puede cancelar si está en curso
    if ($pedido["estado"] !== "En curso") {
        header("Location: pedidoConsulta.php?error=noCancelable");
        exit;
    }

    // Cancelar pedido
    $update = $con->prepare("UPDATE pedidos SET estado = 'Cancelado' WHERE id = :id");
    $update->execute([":id" => $idPedido]);

    header("Location: pedidoConsulta.php?pedidoCancelado=1");
    exit;

?>
