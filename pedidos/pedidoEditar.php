<?php

    require_once "../utilidades/conectar_db.php";
    require_once "Pedido.php";
    session_start();

    $con = conectar();

    // Solo administradores y empleados
    if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    if (!isset($_GET["id"])) {
        header("Location: pedidoConsulta.php");
        exit;
    }

    $idPedido = (int) $_GET["id"];

    // Obtener datos del pedido
    $sql = $con->prepare("SELECT p.id, p.id_usuario, p.fecha, p.total, p.estado, p.tipo_pago, p.direccion_envio, p.localidad_envio, p.provincia_envio, p.telefono_envio,
                          u.nombre AS usuarioNombre FROM pedidos p JOIN usuarios u ON p.id_usuario = u.id WHERE p.id = :id");
    $sql->execute([":id" => $idPedido]);
    $sql->setFetchMode(PDO::FETCH_CLASS, "Pedido");
    $pedido = $sql->fetch();

    if (!$pedido) {
        header("Location: pedidoConsulta.php?error=pedidoNoExiste");
        exit;
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar estado del pedido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../estilos.css">
</head>
<body>
    <div class="jtech-bg d-flex justify-content-center align-items-start py-5">
        <div class="jtech-card p-4" style="max-width: 600px;">

            <h2 class="fw-bold text-center mb-4">Cambiar estado del pedido #<?= $pedido->getId() ?></h2>

            <p><strong>Usuario:</strong> <?= htmlspecialchars($pedido->getUsuarioNombre()) ?></p>
            <p><strong>Fecha:</strong> <?= date("d/m/Y H:i", strtotime($pedido->getFecha())) ?></p>
            <p><strong>Total:</strong> <?= number_format($pedido->getTotal(), 2) ?> €</p>
            <p><strong>Estado actual:</strong> <?= htmlspecialchars($pedido->getEstado()) ?></p>

            <hr class="jtech-divider">

            <h5 class="fw-bold mb-3">Selecciona el nuevo estado:</h5>

            <div class="d-grid gap-3">
                <a href="pedidoCambiarEstado.php?id=<?= $pedido->getId() ?>&estado=En curso" class="btn btn-jtech fw-semibold">En curso</a>

                <a href="pedidoCambiarEstado.php?id=<?= $pedido->getId() ?>&estado=Enviado" class="btn btn-jtech fw-semibold">Enviado</a>

                <a href="pedidoCambiarEstado.php?id=<?= $pedido->getId() ?>&estado=En reparto" class="btn btn-jtech fw-semibold">En reparto</a>

                <a href="pedidoCambiarEstado.php?id=<?= $pedido->getId() ?>&estado=Recogido" class="btn btn-jtech fw-semibold">Recogido</a>

                <a href="pedidoCambiarEstado.php?id=<?= $pedido->getId() ?>&estado=Cancelado" class="btn btn-outline-danger fw-semibold"
                onclick="return confirm('¿Seguro que quieres cancelar este pedido?');">Cancelado</a>
            </div>

            <div class="text-center mt-4">
                <a href="pedidoConsulta.php" class="btn btn-outline-secondary">Volver</a>
            </div>

        </div>
    </div>
</body>
</html>
