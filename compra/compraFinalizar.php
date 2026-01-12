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

    // Obtener datos del usuario
    $sql = $con->prepare("SELECT nombre, telefono, direccion, localidad, provincia FROM usuarios WHERE id = :id");
    $sql->execute([":id" => $idUsuario]);
    $usuario = $sql->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        header("Location: ../index.php?existe=1");
        exit;
    }

    $datosIncompletos = empty($usuario["direccion"]) || empty($usuario["localidad"]) || empty($usuario["provincia"]);

    // Obtener carrito
    $sql = $con->prepare("SELECT c.id, c.cantidad, p.nombre, p.precio, p.imagen FROM carrito c JOIN productos p ON c.id_producto = p.id WHERE c.id_usuario = :idUsuario");
    $sql->execute([":idUsuario" => $idUsuario]);
    $carrito = $sql->fetchAll(PDO::FETCH_ASSOC);

    // Total
    $total = 0;
    foreach ($carrito as $item) {
        $total += $item["precio"] * $item["cantidad"];
    }

    // Fecha estimada
    $inicio = date("d/m/Y", strtotime("+6 days"));
    $fin = date("d/m/Y", strtotime("+11 days"));

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar compra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="jtech-card-wide p-4 mb-4">

                    <h3 class="fw-bold mb-3">Dirección de entrega</h3>
                        <div class="mb-3">
                            <?php

                                if (isset($_GET["nombreE"]) && isset($_GET["emailE"])) {
                                    $nombreE = htmlspecialchars($_GET["nombreE"]);
                                    $emailE = htmlspecialchars($_GET["emailE"]);
                                    echo "<p class='alert alert-success'>El usuario <b>$nombreE</b> con email <b>$emailE</b> ha sido modificado correctamente.</p>";
                                }

                            ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div class="me-3">
                                <?php if ($datosIncompletos): ?>
                                    <div class="alert alert-warning mb-3">
                                        Faltan datos de envío. Por favor complétalos antes de continuar.
                                    </div>
                                <?php else: ?>
                                    <p class="mb-1">
                                        <strong><?= htmlspecialchars($usuario["nombre"]) ?></strong> - 
                                        <strong><?= htmlspecialchars($usuario["telefono"]) ?></strong>
                                    </p>
                                    <p class="mb-1"><?= htmlspecialchars($usuario["direccion"]) ?></p>
                                    <p class="mb-3">
                                        <?= htmlspecialchars($usuario["localidad"]) ?>, 
                                        <?= htmlspecialchars($usuario["provincia"]) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="mt-2 mt-lg-0">
                                <a href="../usuarios/usuarioEditar.php?id=<?= $idUsuario ?>&from=finalizar" class="btn btn-outline-jtech fw-semibold">Cambiar</a>
                            </div>
                        </div>

                    <hr class="jtech-divider my-4">

                    <h3 class="fw-bold mb-3">Método de pago</h3>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" checked disabled>
                        <label class="form-check-label fw-semibold">Stripe</label>
                    </div>
                </div>

                <div class="jtech-card-wide p-4 mb-4">
                    <h3 class="fw-bold mb-3">Detalles de los artículos</h3>

                    <?php foreach ($carrito as $item): ?>
                        <div class="d-flex align-items-center mb-3 border-bottom pb-2">
                            <img src="../<?= $item['imagen'] ?>" class="jtech-cart-img me-3">

                            <div class="flex-grow-1">
                                <p class="fw-semibold mb-1"><?= htmlspecialchars($item["nombre"]) ?></p>
                                <p class="mb-0">Cantidad: <?= $item["cantidad"] ?></p>
                            </div>

                            <div class="text-end">
                                <p class="fw-bold text-success mb-0">
                                    <?= number_format($item["precio"] * $item["cantidad"], 2) ?> €
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>

            <div class="col-lg-4">
                <div class="jtech-card p-4 mb-4">

                    <h3 class="fw-bold mb-3">Método de envío</h3>

                    <p class="mb-1"><strong>Envío gratis</strong></p>
                    <p class="mb-1">Entrega estimada: <?= $inicio ?> - <?= $fin ?></p>
                    <p class="mb-0">Empresa de transporte: <strong>Joserreos</strong></p>
                </div>

                <div class="jtech-card p-4">
                    <h3 class="fw-bold mb-3">Resumen</h3>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Total productos:</span>
                        <span><?= number_format($total, 2) ?> €</span>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Gastos de envío:</span>
                        <span class="text-success fw-bold">Gratis</span>
                    </div>

                    <?php if ($datosIncompletos): ?>
                        <a href="../usuarios/usuarioEditar.php?id=<?= $idUsuario ?>&from=finalizar" class="btn btn-jtech w-100 fw-semibold mb-2">Completar datos de envío</a>
                    <?php else: ?>
                        <form action="../stripe/crear_sesion.php" method="POST">
                            <button type="submit" class="btn btn-jtech w-100 fw-semibold mb-2">Completar el pago</button>
                        </form>
                    <?php endif; ?>

                    <a href="../carrito/carrito.php" class="btn btn-outline-jtech w-100 fw-semibold">Volver al carrito</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
