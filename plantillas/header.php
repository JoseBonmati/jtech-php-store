<?php

    require_once "utilidades/conectar_db.php";
    $con = conectar();

    session_start();

    if (!isset($_SESSION["carrito_token"])) {
        $_SESSION["carrito_token"] = bin2hex(random_bytes(16));
    }

    $token = $_SESSION["carrito_token"] ?? null;
    $idUsuario = $_SESSION["id"] ?? null;

    if ($idUsuario) {
        $sql = $con->prepare("SELECT SUM(cantidad) AS total FROM carrito WHERE id_usuario = :id");
        $sql->execute([":id" => $idUsuario]);
    } else {
        $sql = $con->prepare("SELECT SUM(cantidad) AS total FROM carrito WHERE token = :token");
        $sql->execute([":token" => $token]);
    }

    $totalCarrito = $sql->fetchColumn() ?: 0;

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jtech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg container-fluid">
            <a class="navbar-brand ms-3" href="../index.php">
                <img src="../assets/logos/logo-pequenyo.png" alt="Logo Jtech" style="max-height: 60px;">
            </a>

            <button class="navbar-toggler me-3" type="button" data-bs-toggle="collapse" data-bs-target="#navPrincipal" aria-controls="navPrincipal" aria-expanded="false" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div id="navPrincipal" class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto align-items-center fs-5 fw-semibold gap-lg-4">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Sobre nosotros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Contacto</a>
                    </li>
                    <li class="nav-item me-lg-3">
                        <a href="../carrito/carrito.php" class="nav-link">
                            <i class="bi bi-cart fs-3"></i> (<?= $totalCarrito ?>)
                        </a>
                    </li>


                </ul>
            </div>
        </nav>
    </header>
