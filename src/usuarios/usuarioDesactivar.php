<?php

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    // Solo usuarios logueados
    if (!isset($_SESSION["id"])) {
        header("Location: ../usuarios/login.php?acceso=denegado");
        exit;
    }

    $admin = ($_SESSION["rol"] === "administrador");

    if ($admin) {
        $id = $_GET["id"] ?? null;
    } else {
        $id = $_SESSION["id"];
    }
    
    $accion = $_GET["accion"] ?? null;

    if (!$id || !$accion) {
        header("Location: ../index.php");
        exit;
    }

    // Restricciones de acceso:
    // 1. Un usuario normal solo puede desactivarse a sí mismo.
    // 2. Un administrador NO puede desactivarse a sí mismo.
    if ((!$admin && $_SESSION["id"] != $id) ||
        ($admin && $_SESSION["id"] == $id && $accion === "desactivar")) {

        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    // Determinar nuevo estado
    if ($accion === "desactivar") {
        $estadoNuevo = "inactivo";
    } elseif ($accion === "activar" && $admin) {
        $estadoNuevo = "activo";
    } else {
        header("Location: ../index.php");
        exit;
    }

    // Actualizar estado
    $query = $con->prepare("UPDATE usuarios SET estado = :estado WHERE id = :id");
    $query->execute([
        ":estado" => $estadoNuevo,
        ":id" => $id
    ]);

    // Si un usuario normal se desactiva → cerrar sesión
    if (!$admin && $accion === "desactivar") {
        session_unset();
        session_destroy();
        header("Location: ../index.php?cuentaDesactivada=1");
        exit;
    }

    // Volver a edición del usuario
    header("Location: usuarioEditar.php?id=" . urlencode($id) . "&estadoCambiado=1");
    exit;

?>
