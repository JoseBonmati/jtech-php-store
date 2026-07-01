<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection and Entity Inclusion
    require_once __DIR__ . "/../utils/Database.php";
    require_once __DIR__ . "/Report.php";
    $db = Database::getConnection();

    // Restrict access: only administrators can view reports
    if (!isset($_SESSION["id"]) || $_SESSION["rol"] !== "administrador") {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    // Fetch reporting data utilizing the updated OOP methodology
    $totalSales = getTotalSales($db);
    $topSellingProducts = getTopSellingProducts($db);
    $monthlyRevenue = getMonthlyRevenue($db);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informes - Jtech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
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
                    <p class="fs-3 fw-semibold text-success"><?= number_format($totalSales, 2) ?> €</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 text-center jtech-card">
                    <i class="bi bi-box-seam fs-1 text-primary"></i>
                    <h4 class="fw-bold mt-3">Productos Vendidos</h4>
                    <p class="fs-3 fw-semibold text-primary">
                        <?php 
                            // Array mapping to sum up private object properties safely
                            echo array_sum(array_map(fn($p) => $p->getQuantitySold(), $topSellingProducts)); 
                        ?>
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 text-center jtech-card">
                    <i class="bi bi-graph-up-arrow fs-1 text-warning"></i>
                    <h4 class="fw-bold mt-3">Ingresos Último Mes</h4>
                    <p class="fs-3 fw-semibold text-warning">
                        <?php
                            $lastMonth = end($monthlyRevenue);
                            echo $lastMonth ? number_format($lastMonth->getRevenue(), 2) . " €" : "0.00 €";
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
                    <?php foreach ($topSellingProducts as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$p->getProductName()) ?></td>
                            <td><?= htmlspecialchars((string)$p->getQuantitySold()) ?></td>
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
                    <?php foreach ($monthlyRevenue as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$m->getMonth()) ?></td>
                            <td><?= number_format($m->getRevenue(), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center">
            <a href="/utils/admin_panel.php" class="btn btn-outline-jtech fw-semibold">Volver</a>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>