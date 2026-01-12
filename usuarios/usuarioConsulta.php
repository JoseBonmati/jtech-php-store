<?php

    require_once "../utilidades/conectar_db.php";
    require_once "Usuario.php";
    $con = conectar();
    session_start();

    // Restringir acceso: solo administradores pueden ver esta página
    if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "administrador") {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="jtech-bg d-flex justify-content-center align-items-start py-5">
        <div class="jtech-card-wide p-4 w-100">
            <h2 class="text-center fw-bold mb-4">Gestión de Usuarios</h2>

            <!-- Mensajes de éxito -->
            <div class="mb-3">
                <?php

                    if (isset($_GET["nombreE"]) && isset($_GET["emailE"])) {
                        $nombreE = htmlspecialchars($_GET["nombreE"]);
                        $emailE = htmlspecialchars($_GET["emailE"]);
                        echo "<p class='alert alert-success'>El usuario <b>$nombreE</b> con email <b>$emailE</b> ha sido modificado correctamente.</p>";
                    }
                    if (isset($_GET["nombreN"]) && isset($_GET["emailN"])) {
                        $nombreN = htmlspecialchars($_GET["nombreN"]);
                        $emailN = htmlspecialchars($_GET["emailN"]);
                        echo "<p class='alert alert-success'>El usuario <b>$nombreN</b> con email <b>$emailN</b> se ha creado correctamente.</p>";
                    }
                    if (isset($_GET["nombreD"])) {
                        $nombreD = htmlspecialchars($_GET["nombreD"]);
                        echo "<p class='alert alert-success'>El usuario <b>$nombreD</b> se ha eliminado correctamente.</p>";
                    }

                ?>
            </div>

            <!-- Barra de búsqueda -->
            <form method="get" action="usuarioConsulta.php" class="mb-4 jtech-search mx-auto">
                <div class="input-group">
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre o email..." autocomplete="off"
                           value="<?= isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : '' ?>">
                    <button class="btn btn-jtech fw-semibold" type="submit">Buscar</button>
                </div>
            </form>

            <?php

                // Configuración de paginación y ordenación
                $pagina = isset($_GET["pagina"]) ? (int)$_GET["pagina"] : 1;
                $resultadosPP = 5;

                $columnasPermitidas = ["id", "nombre", "email", "telefono", "rol", "estado"];
                $orden = isset($_GET["orden"]) ? $_GET["orden"] : "nombre";
                if (!in_array($orden, $columnasPermitidas)) {
                    $orden = "nombre";
                }

                $tipoOrden = isset($_GET["tipoOrden"]) ? strtoupper($_GET["tipoOrden"]) : "ASC";
                if (!in_array($tipoOrden, ["ASC", "DESC"])) {
                    $tipoOrden = "ASC";
                }

                // Si hay búsqueda, aplicamos filtro + orden + paginación
                if (isset($_GET["buscar"]) && trim($_GET["buscar"]) !== "") {

                    $busqueda = "%" . trim($_GET["buscar"]) . "%";

                    // Contar resultados filtrados
                    $countQuery = $con->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE nombre LIKE :busqueda OR email LIKE :busqueda");
                    $countQuery->execute([":busqueda" => $busqueda]);
                    $usuariosTotal = $countQuery->fetch()["total"];

                    $paginasTotal = ceil($usuariosTotal / $resultadosPP);
                    $inicio = ($pagina - 1) * $resultadosPP;

                    // Obtener resultados filtrados con paginación y ordenación
                    $query = $con->prepare("SELECT id, nombre, email, contrasenya, telefono, rol, estado FROM usuarios
                                            WHERE nombre LIKE :busqueda OR email LIKE :busqueda ORDER BY $orden $tipoOrden LIMIT :inicio, :resultados");

                    $query->bindValue(":busqueda", $busqueda, PDO::PARAM_STR);
                    $query->bindValue(":inicio", $inicio, PDO::PARAM_INT);
                    $query->bindValue(":resultados", $resultadosPP, PDO::PARAM_INT);
                    $query->execute();

                    $query->setFetchMode(PDO::FETCH_CLASS, "Usuario");
                    $usuarios = $query->fetchAll();

                } else {

                    // Consultar categorías normalmente
                    $usuarios = obtenerUsuarios($con, $pagina, $resultadosPP, $orden, $tipoOrden);

                    $query = $con->prepare("SELECT count(*) AS total FROM usuarios");
                    $query->execute();
                    $fila = $query->fetch();
                    $usuariosTotal = $fila["total"];

                    $paginasTotal = ceil($usuariosTotal / $resultadosPP);
                }

                // Iconos de ordenación
                function iconoOrden($col, $orden, $tipoOrden) {
                    if ($col !== $orden) return '<i class="bi bi-arrow-down-up"></i>';
                    return $tipoOrden === "ASC"
                        ? '<i class="bi bi-arrow-up"></i>'
                        : '<i class="bi bi-arrow-down"></i>';
                }

                function urlOrden($col, $tipoOrden) {
                    $nuevoTipo = $tipoOrden === "ASC" ? "DESC" : "ASC";
                    $url = "usuarioConsulta.php?orden=$col&tipoOrden=$nuevoTipo";
                    if (isset($_GET["buscar"])) {
                        $url .= "&buscar=" . urlencode($_GET["buscar"]);
                    }
                    return $url;
                }

            ?>

            <div class="table-responsive">
                <table class="jtech-table align-middle text-center mx-auto">
                    <thead>
                        <tr>
                            <th><a href="<?= urlOrden('id', $tipoOrden) ?>">ID <?= iconoOrden('id', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('nombre', $tipoOrden) ?>">Nombre <?= iconoOrden('nombre', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('email', $tipoOrden) ?>">Email <?= iconoOrden('email', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('telefono', $tipoOrden) ?>">Teléfono <?= iconoOrden('telefono', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('rol', $tipoOrden) ?>">Rol <?= iconoOrden('rol', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('estado', $tipoOrden) ?>">Estado <?= iconoOrden('estado', $orden, $tipoOrden) ?></a></th>
                            <th>Editar</th>
                            <th>Borrar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                            if (empty($usuarios)) {
                                echo "<tr><td colspan='8' class='text-center py-3 text-muted'>
                                          No se encontraron usuarios
                                      </td></tr>";
                            } else {
                                foreach ($usuarios as $usuario) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($usuario->getId()) . "</td>";
                                    echo "<td>" . htmlspecialchars($usuario->getNombre()) . "</td>";
                                    echo "<td>" . htmlspecialchars($usuario->getEmail()) . "</td>";
                                    echo "<td>" . htmlspecialchars($usuario->getTelefono()) . "</td>";
                                    echo "<td>" . htmlspecialchars($usuario->getRol()) . "</td>";
                                    echo "<td>" . htmlspecialchars($usuario->getEstado()) . "</td>";

                                    if ($_SESSION["id"] == $usuario->getId()) {
                                        echo "<td><i class='bi bi-ban text-muted'></i></td>"; 
                                        echo "<td><i class='bi bi-ban text-muted'></i></td>";
                                    } else {
                                        echo "<td><a class='text-jtech fw-bold' href='usuarioEditar.php?id=" . $usuario->getId() . "'>
                                                <i class='bi bi-pencil-square'></i>
                                            </a></td>";

                                        echo "<td><a class='text-danger fw-bold' href='usuarioEliminar.php?id=" . $usuario->getId() . "'>
                                                <i class='bi bi-trash-fill'></i>
                                            </a></td>";
                                    }
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
                        $url = "usuarioConsulta.php?pagina=$i&orden=$orden&tipoOrden=$tipoOrden";
                        if (isset($_GET["buscar"])) {
                            $url .= "&buscar=" . urlencode($_GET["buscar"]);
                        }
                        if ($i == $pagina) {
                            echo "<button class='btn btn-jtech mx-1'>$i</button>";
                        } else {
                            echo "<a href='$url' class='btn btn-outline-jtech mx-1'>$i</a>";
                        }
                    }

                ?>
            </div>

            <div class="text-center mt-4 d-flex justify-content-center">
                <a href="usuarioCrear.php" class="btn btn-jtech">Nuevo usuario</a>
            </div>

            <div class="text-center text-lg-start mt-3">
                <a href="../utilidades/panelAdministrador.php" class="btn btn-outline-secondary">Volver</a>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
