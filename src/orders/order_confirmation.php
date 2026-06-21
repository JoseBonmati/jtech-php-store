<?php

    require_once "../utilidades/conectar_db.php";
    session_start();

    $con = conectar();

    // Comprobar login
    if (!isset($_SESSION["id"])) {
        header("Location: ../index.php?acceso=1");
        exit;
    }

    $idUsuario = $_SESSION["id"];

    // Comprobar que llega un ID de pedido
    if (!isset($_GET["id"])) {
        header("Location: ../index.php");
        exit;
    }

    $idPedido = (int) $_GET["id"];

    // Obtener datos del pedido
    $sql = $con->prepare("SELECT id, fecha, total, tipo_pago, direccion_envio, localidad_envio, provincia_envio, telefono_envio FROM pedidos WHERE id = :id AND id_usuario = :idUsuario");
    $sql->execute([
        ":id" => $idPedido,
        ":idUsuario" => $idUsuario
    ]);

    $pedido = $sql->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        header("Location: ../index.php?pedidoError=1");
        exit;
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido confirmado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="jtech-card-wide p-5 text-center">

                    <h2 class="fw-bold mb-3">¡Gracias por tu compra!</h2>
                    <p class="text-muted mb-4">Tu pedido ha sido procesado correctamente.</p>

                    <hr class="jtech-divider my-4">

                    <h4 class="fw-bold mb-3">Detalles del pedido</h4>

                    <p class="mb-1"><strong>Número de pedido:</strong> <?= $pedido["id"] ?></p>
                    <p class="mb-1"><strong>Fecha:</strong> <?= date("d/m/Y H:i", strtotime($pedido["fecha"])) ?></p>
                    <p class="mb-1"><strong>Total:</strong> <?= number_format($pedido["total"], 2) ?> €</p>
                    <p class="mb-3"><strong>Método de pago:</strong> <?= htmlspecialchars($pedido["tipo_pago"]) ?></p>

                    <hr class="jtech-divider my-4">

                    <h4 class="fw-bold mb-3">Dirección de envío</h4>

                    <p class="mb-1"><?= htmlspecialchars($pedido["direccion_envio"]) ?></p>
                    <p class="mb-1"><?= htmlspecialchars($pedido["localidad_envio"]) ?>, <?= htmlspecialchars($pedido["provincia_envio"]) ?></p>
                    <p class="mb-4">Teléfono: <?= htmlspecialchars($pedido["telefono_envio"]) ?></p>

                    <div class="d-grid gap-3 mt-4">
                        <a href="../index.php" class="btn btn-jtech fw-semibold">Volver a la tienda</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
