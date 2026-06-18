<?php

    require_once "../utilidades/conectar_db.php";
    require_once "Informe.php";
    $con = conectar();

    session_start();

    // Solo administradores pueden acceder
    if (!isset($_SESSION["id"]) || $_SESSION["rol"] !== "administrador") {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    // Obtener datos
    $ventasTotales = obtenerVentasTotales($con);
    $productosMasVendidos = obtenerProductosMasVendidos($con);
    $ingresosMensuales = obtenerIngresosMensuales($con);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informes - Jtech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body class="jtech-bg">
    <div class="container py-5">

        <h1 class="text-center fw-bold mb-2">Informes de Gestión</h1>
        <p class="text-center text-muted mb-5">Panel de análisis para administradores</p>

        <div class="row g-4 mb-5 justify-content-center">
            <div class="col-md-4">
                <div class="p-4 text-center jtech-card">
                    <i class="bi bi-cash-coin fs-1 text-success"></i>
                    <h4 class="fw-bold mt-3">Ventas Totales</h4>
                    <p class="fs-3 fw-semibold text-success"><?= number_format($ventasTotales, 2) ?> €</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 text-center jtech-card">
                    <i class="bi bi-box-seam fs-1 text-primary"></i>
                    <h4 class="fw-bold mt-3">Productos Vendidos</h4>
                    <p class="fs-3 fw-semibold text-primary"><?= array_sum(array_column($productosMasVendidos, "cantidadVendida")) ?></p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 text-center jtech-card">
                    <i class="bi bi-graph-up-arrow fs-1 text-warning"></i>
                    <h4 class="fw-bold mt-3">Ingresos Último Mes</h4>
                    <p class="fs-3 fw-semibold text-warning">
                        <?php
                            $ultimoMes = end($ingresosMensuales);
                            echo $ultimoMes ? number_format($ultimoMes["ingresos"], 2) . " €" : "0 €";
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <hr class="jtech-divider my-5">

        <div class="jtech-card-wide p-4 mb-5 mx-auto">
            <h3 class="fw-bold mb-3">Productos más vendidos</h3>

            <table class="table jtech-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad vendida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productosMasVendidos as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p["producto"]) ?></td>
                            <td><?= $p["cantidadVendida"] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="jtech-card-wide p-4 mb-5 mx-auto">
            <h3 class="fw-bold mb-3">Ingresos mensuales</h3>

            <table class="table jtech-table">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th>Ingresos (€)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ingresosMensuales as $m): ?>
                        <tr>
                            <td><?= $m["mes"] ?></td>
                            <td><?= number_format($m["ingresos"], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center">
            <a href="../utilidades/panelAdministrador.php" class="btn btn-outline-jtech fw-semibold">Volver</a>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
