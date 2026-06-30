<?php

    // Session Management
    // Ensure session is started only once to prevent notices
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Initialize Cart Token if the user is a guest
    if (!isset($_SESSION["cart_token"])) {
        $_SESSION["cart_token"] = bin2hex(random_bytes(16));
    }

    $cartToken = $_SESSION["cart_token"] ?? null;
    $userId = $_SESSION["id"] ?? null;

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    // Fetch Global Data (Cart Total)
    if ($userId) {
        $stmt = $db->prepare("SELECT SUM(cantidad) AS total FROM carrito WHERE id_usuario = :id");
        $stmt->execute([":id" => $userId]);
    } else {
        $stmt = $db->prepare("SELECT SUM(cantidad) AS total FROM carrito WHERE token = :token");
        $stmt->execute([":token" => $cartToken]);
    }

    $cartTotal = $stmt->fetchColumn() ?: 0;

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jtech</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg container-fluid">
            <a class="navbar-brand ms-3" href="/index.php">
                <img src="/assets/brand/logo-pequenyo.png" alt="Logo Jtech" style="max-height: 60px;">
            </a>

            <button class="navbar-toggler me-3" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div id="mainNav" class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto align-items-center fs-5 fw-semibold gap-lg-4">
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Sobre nosotros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Contacto</a>
                    </li>
                    <li class="nav-item me-lg-3">
                        <a href="/cart/cart.php" class="nav-link">
                            <i class="bi bi-cart fs-3"></i> (<?= htmlspecialchars((string)$cartTotal) ?>)
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>