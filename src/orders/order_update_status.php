<?php

    require_once "../utilidades/conectar_db.php";
    session_start();

    $con = conectar();

    // Solo administradores y empleados
    if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    if (!isset($_GET["id"]) || !isset($_GET["estado"])) {
        header("Location: pedidoConsulta.php");
        exit;
    }

    $idPedido = (int) $_GET["id"];
    $nuevoEstado = $_GET["estado"];

    // Estados permitidos
    $estadosValidos = ["En curso", "Enviado", "En reparto", "Recogido", "Cancelado"];

    if (!in_array($nuevoEstado, $estadosValidos)) {
        header("Location: pedidoConsulta.php?error=estadoInvalido");
        exit;
    }

    // Comprobar que el pedido existe
    $sql = $con->prepare("SELECT COUNT(*) FROM pedidos WHERE id = :id");
    $sql->execute([":id" => $idPedido]);

    if ($sql->fetchColumn() == 0) {
        header("Location: pedidoConsulta.php?error=pedidoNoExiste");
        exit;
    }

    // Actualizar estado
    $update = $con->prepare("UPDATE pedidos SET estado = :estado WHERE id = :id");
    $update->execute([
        ":estado" => $nuevoEstado,
        ":id" => $idPedido
    ]);

    header("Location: pedidoConsulta.php?estadoCambiado=1");
    exit;

?>
