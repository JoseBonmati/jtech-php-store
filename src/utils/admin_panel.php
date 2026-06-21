<?php

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    if (!isset($_SESSION["id"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    $sessionId = $_SESSION["id"];

    $query = $con->prepare("SELECT nombre FROM usuarios WHERE id = :id");
    $query->execute([":id" => $sessionId]);
    $admin = $query->fetch();
    $adminName = $admin ? $admin["nombre"] : "Usuario";

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
    <title>Panel Administración Jtech</title>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center py-5 jtech-bg">
        <div class="p-4 text-center jtech-card">
            <h2 class="fw-bold mb-4">Panel de Administración</h2>
            <p class="fw-semibold mb-4">Bienvenido/a, <?= htmlspecialchars($adminName) ?></p>
            <div class="d-grid gap-3">

                <?php if ($_SESSION["rol"] === "administrador"): ?>
                    <a href="../usuarios/usuarioConsulta.php" class="btn fw-semibold btn-jtech">Gestión de Usuarios</a>
                <?php endif; ?>

                <a href="../productos/productoConsulta.php" class="btn fw-semibold btn-jtech">Gestión de Productos</a>
                <a href="../categorias/categoriaConsulta.php" class="btn fw-semibold btn-jtech">Gestión de Categorías</a>
                <a href="../pedidos/pedidoConsulta.php" class="btn fw-semibold btn-jtech">Gestión de Pedidos</a>

                <?php if ($_SESSION["rol"] === "administrador"): ?>
                    <a href="../informes/informes.php" class="btn fw-semibold btn-success">Ver informes</a>
                <?php endif; ?>

                <hr class="jtech-divider">

                <a href="../index.php" class="btn btn-outline-secondary">Volver</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
