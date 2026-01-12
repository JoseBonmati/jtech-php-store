<?php

    require_once "../utilidades/conectar_db.php";
    require_once "Pedido.php";
    $con = conectar();
    session_start();

    if (!isset($_SESSION["id"])) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    $rol = $_SESSION["rol"];
    $idUsuario = $_SESSION["id"];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="jtech-bg d-flex justify-content-center align-items-start py-5">
        <div class="jtech-card-wide p-4 w-100">

            <h2 class="text-center fw-bold mb-4">
                <?= ($rol === "usuario") ? "Mis pedidos" : "Gestión de pedidos" ?>
            </h2>

            <!-- Mensajes -->
            <div class="mb-3">

                <?php

                    if (isset($_GET["estadoCambiado"])) {
                        echo "<p class='alert alert-success'>Estado del pedido actualizado correctamente.</p>";
                    }
                    if (isset($_GET["pedidoCancelado"])) {
                        echo "<p class='alert alert-warning'>El pedido ha sido cancelado.</p>";
                    }

                    if (isset($_GET["error"])) {
                        $error = htmlspecialchars($_GET["error"]);

                        if ($error === "estadoInvalido") {
                            echo "<p class='alert alert-danger text-center mb-4'>Ese estado no es válido.</p>";
                        }
                        if ($error === "pedidoNoExiste") {
                            echo "<p class='alert alert-danger text-center mb-4'>El pedido no existe.</p>";
                        }
                        if ($error === "noAutorizado") {
                            echo "<p class='alert alert-danger text-center mb-4'>Este producto ya no está disponible.</p>";
                        }
                        if ($error === "noCancelable") {
                            echo "<p class='alert alert-danger text-center mb-4'>El pedido ya no es cancelable.</p>";
                        }
                    }

                ?>
            </div>

            <?php

            // Configuración de paginación y ordenación
            $pagina = isset($_GET["pagina"]) ? (int)$_GET["pagina"] : 1;
            $resultadosPP = 5;

            $columnasPermitidas = ["p.id", "p.fecha", "p.total", "p.estado", "u.nombre"];
            $orden = $_GET["orden"] ?? "p.fecha";
            if (!in_array($orden, $columnasPermitidas)) $orden = "p.fecha";

            $tipoOrden = strtoupper($_GET["tipoOrden"] ?? "DESC");
            if (!in_array($tipoOrden, ["ASC", "DESC"])) $tipoOrden = "DESC";

            // Si es usuario normal solo sus pedidos
            // Si es admin/empleado y viene ?soloMios=1 solo sus pedidos
            if ($rol === "usuario") {
                $soloUsuario = $idUsuario;
            } elseif (isset($_GET["soloMios"]) && $_GET["soloMios"] == 1) {
                $soloUsuario = $idUsuario;
            } else {
                $soloUsuario = null;
            }


            // Obtener pedidos
            $pedidos = obtenerPedidos($con, $pagina, $resultadosPP, $orden, $tipoOrden, $soloUsuario);

            // Contar total de pedidos
            if ($soloUsuario !== null) {
                $count = $con->prepare("SELECT COUNT(*) AS total FROM pedidos WHERE id_usuario = :id");
                $count->execute([":id" => $idUsuario]);
            } else {
                $count = $con->prepare("SELECT COUNT(*) AS total FROM pedidos");
                $count->execute();
            }

            $pedidosTotal = $count->fetch()["total"];
            $paginasTotal = ceil($pedidosTotal / $resultadosPP);

            // Iconos de ordenación
            function iconoOrden($col, $orden, $tipoOrden) {
                if ($col !== $orden) return '<i class="bi bi-arrow-down-up"></i>';
                return $tipoOrden === "ASC"
                    ? '<i class="bi bi-arrow-up"></i>'
                    : '<i class="bi bi-arrow-down"></i>';
            }

            function urlOrden($col, $tipoOrden) {
                $nuevoTipo = $tipoOrden === "ASC" ? "DESC" : "ASC";
                return "pedidoConsulta.php?orden=$col&tipoOrden=$nuevoTipo";
            }

            ?>

            <div class="table-responsive">
                <table class="jtech-table align-middle text-center mx-auto">
                    <thead>
                        <tr>
                            <th><a href="<?= urlOrden('p.id', $tipoOrden) ?>">ID <?= iconoOrden('p.id', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('p.fecha', $tipoOrden) ?>">Fecha <?= iconoOrden('p.fecha', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('p.total', $tipoOrden) ?>">Total <?= iconoOrden('p.total', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('p.estado', $tipoOrden) ?>">Estado <?= iconoOrden('p.estado', $orden, $tipoOrden) ?></a></th>

                            <?php if ($rol !== "usuario"): ?>
                                <th><a href="<?= urlOrden('u.nombre', $tipoOrden) ?>">Usuario <?= iconoOrden('u.nombre', $orden, $tipoOrden) ?></a></th>
                            <?php endif; ?>

                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        if (empty($pedidos)) {
                            echo "<tr><td colspan='10' class='text-center py-3 text-muted'>
                                    No se encontraron pedidos
                                </td></tr>";
                        } else {
                            foreach ($pedidos as $p) {
                                echo "<tr>";
                                echo "<td>" . $p->getId() . "</td>";
                                echo "<td>" . date("d/m/Y H:i", strtotime($p->getFecha())) . "</td>";
                                echo "<td>" . number_format($p->getTotal(), 2) . " €</td>";
                                echo "<td>" . htmlspecialchars($p->getEstado()) . "</td>";

                                if ($rol !== "usuario") {
                                    echo "<td>" . htmlspecialchars($p->getUsuarioNombre()) . "</td>";
                                }

                                echo "<td>";

                                // Acciones según rol
                                if ($rol === "usuario" || isset($_GET["soloMios"]) && $_GET["soloMios"] == 1) {

                                    if ($p->getEstado() === "En curso") {
                                        echo "<a href='pedidoCancelar.php?id=" . $p->getId() . "'class='btn btn-outline-danger btn-sm fw-semibold'
                                                onclick='return confirm(\"¿Seguro que quieres cancelar este pedido?\");'>Cancelar
                                              </a>";
                                    } else {
                                        echo "<span class='text-muted'>—</span>";
                                    }

                                } else {

                                    echo "<a class='text-jtech fw-bold' href='pedidoEditar.php?id=" . $p->getId() . "'>
                                              <i class='bi bi-pencil-square'></i>
                                          </a>";

                                }

                                echo "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="text-center mt-4">
                <?php
                for ($i = 1; $i <= $paginasTotal; $i++) {
                    $url = "pedidoConsulta.php?pagina=$i&orden=$orden&tipoOrden=$tipoOrden";

                    if ($i == $pagina) {
                        echo "<button class='btn btn-jtech mx-1'>$i</button>";
                    } else {
                        echo "<a href='$url' class='btn btn-outline-jtech mx-1'>$i</a>";
                    }
                }
                ?>
            </div>

            <div class="text-center text-lg-start mt-3">
                <?php
                    // Si es usuario normal siempre vuelve al index
                    if ($rol === "usuario") {
                        $volver = "../index.php";

                    // Si es admin/empleado y está viendo solo sus pedidos volver al index
                    } elseif (isset($_GET["soloMios"]) && $_GET["soloMios"] == 1) {
                        $volver = "../index.php";

                    // Si es admin/empleado viendo todos los pedidos volver al panel
                    } else {
                        $volver = "../utilidades/panelAdministrador.php";
                    }
                ?>
                <a href="<?= $volver ?>" class="btn btn-outline-secondary">Volver</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
