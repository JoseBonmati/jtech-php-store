<?php

    require_once "../utilidades/conectar_db.php";
    require_once "Categoria.php";
    $con = conectar();
    session_start();

    // Restringir acceso: solo administradores o empleados pueden ver esta página
    if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="jtech-bg d-flex justify-content-center align-items-start py-5">
        <div class="jtech-card-wide p-4 w-100">
            <h2 class="text-center fw-bold mb-4">Gestión de Categorías</h2>

            <!-- Mensajes de éxito -->
            <div class="mb-3">
                <?php

                    if (isset($_GET["nombreE"])) {
                        $nombreE = htmlspecialchars($_GET["nombreE"]);
                        echo "<p class='alert alert-success'>La categoría <b>$nombreE</b> ha sido modificada correctamente.</p>";
                    }
                    if (isset($_GET["nombreN"])) {
                        $nombreN = htmlspecialchars($_GET["nombreN"]);
                        echo "<p class='alert alert-success'>La categoría <b>$nombreN</b> se ha creado correctamente.</p>";
                    }
                    if (isset($_GET["nombreD"])) {
                        $nombreD = htmlspecialchars($_GET["nombreD"]);
                        echo "<p class='alert alert-success'>La categoría <b>$nombreD</b> se ha eliminado correctamente.</p>";
                    }
                    if (isset($_GET["nombreS"])) {
                        $nombreS = htmlspecialchars($_GET["nombreS"]);
                        echo "<p class='alert alert-success'>La subcategoría <b>$nombreS</b> se ha creado correctamente.</p>";
                    }
                    if (isset($_GET["nombreSD"])) {
                        $nombreSD = htmlspecialchars($_GET["nombreSD"]);
                        echo "<p class='alert alert-success'>La subcategoría <b>$nombreSD</b> se ha eliminado correctamente.</p>";
                    }

                ?>
            </div>

            <!-- Barra de búsqueda -->
            <form method="get" action="categoriaConsulta.php" class="mb-4 jtech-search mx-auto">
                <div class="input-group">
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre..." autocomplete="off"
                           value="<?= isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : '' ?>">
                    <button class="btn btn-jtech fw-semibold" type="submit">Buscar</button>
                </div>
            </form>

            <?php

                // Configuración de paginación y ordenación
                $pagina = isset($_GET["pagina"]) ? (int)$_GET["pagina"] : 1;
                $resultadosPP = 5;

                $columnasPermitidas = ["id","nombre", "estado"];
                $orden = isset($_GET["orden"]) ? $_GET["orden"] : "nombre";
                if (!in_array($orden, $columnasPermitidas)) $orden = "nombre";

                $tipoOrden = isset($_GET["tipoOrden"]) ? strtoupper($_GET["tipoOrden"]) : "ASC";
                if (!in_array($tipoOrden, ["ASC", "DESC"])) $tipoOrden = "ASC";

                // Si hay búsqueda, aplicamos filtro + orden + paginación
                if (isset($_GET["buscar"]) && trim($_GET["buscar"]) !== "") {

                    $busqueda = "%" . trim($_GET["buscar"]) . "%";

                    // Contar resultados filtrados
                    $countQuery = $con->prepare("SELECT COUNT(*) AS total FROM categorias WHERE nombre LIKE :busqueda");
                    $countQuery->execute([":busqueda" => $busqueda]);
                    $categoriasTotal = $countQuery->fetch()["total"];

                    $paginasTotal = ceil($categoriasTotal / $resultadosPP);
                    $inicio = ($pagina - 1) * $resultadosPP;

                    // Obtener resultados filtrados con paginación y ordenación
                    $query = $con->prepare("SELECT id, nombre, estado FROM categorias WHERE nombre LIKE :busqueda ORDER BY $orden $tipoOrden LIMIT :inicio, :resultados");

                    $query->bindValue(":busqueda", $busqueda, PDO::PARAM_STR);
                    $query->bindValue(":inicio", $inicio, PDO::PARAM_INT);
                    $query->bindValue(":resultados", $resultadosPP, PDO::PARAM_INT);
                    $query->execute();

                    $query->setFetchMode(PDO::FETCH_CLASS, "Categoria");
                    $categorias = $query->fetchAll();

                } else {

                    // Consultar categorías normalmente
                    $categorias = obtenerCategorias($con, $pagina, $resultadosPP, $orden, $tipoOrden);

                    $query = $con->prepare("SELECT count(*) AS total FROM categorias");
                    $query->execute();
                    $fila = $query->fetch();
                    $categoriasTotal = $fila["total"];

                    $paginasTotal = ceil($categoriasTotal / $resultadosPP);
                }

                // Iconos de ordenación
                function iconoOrden($columna, $orden, $tipoOrden) {
                    if ($columna !== $orden) return '<i class="bi bi-arrow-down-up"></i>';
                    return $tipoOrden === "ASC"
                        ? '<i class="bi bi-arrow-up"></i>'
                        : '<i class="bi bi-arrow-down"></i>';
                }

                function urlOrden($columna, $tipoOrden) {
                    $nuevoTipo = $tipoOrden === "ASC" ? "DESC" : "ASC";
                    $url = "categoriaConsulta.php?orden=$columna&tipoOrden=$nuevoTipo";
                    if (isset($_GET["buscar"])) {
                        $url .= "&buscar=" . urlencode($_GET["buscar"]);
                    }
                    return $url;
                }

                // Obtener subcategorías para una categoría
                function obtenerSubcategoriasPorCategoria($con, $categoriaId) {
                    $query = $con->prepare("SELECT id, nombre, estado FROM subcategorias WHERE id_categoria = :id ORDER BY nombre ASC");
                    $query->execute([":id" => $categoriaId]);
                    return $query->fetchAll(PDO::FETCH_ASSOC);
                }
            ?>

            <div class="table-responsive">
                <table class="jtech-table align-middle text-center mx-auto">
                    <thead>
                        <tr>
                            <th><a href="<?= urlOrden('id', $tipoOrden) ?>">ID <?= iconoOrden('id', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('nombre', $tipoOrden) ?>">Nombre <?= iconoOrden('nombre', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('estado', $tipoOrden) ?>">Estado <?= iconoOrden('estado', $orden, $tipoOrden) ?></a></th>
                            <th>Subcategorías</th>
                            <th>Editar</th>
                            <th>Borrar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                            if (empty($categorias)) {
                                echo "<tr><td colspan='6' class='text-center py-3 text-muted'>
                                          No se encontraron categorías
                                      </td></tr>";
                            } else {
                                foreach ($categorias as $categoria) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($categoria->getId()) . "</td>";
                                    echo "<td>" . htmlspecialchars($categoria->getNombre()) . "</td>";
                                    echo "<td>" . htmlspecialchars($categoria->getEstado()) . "</td>";

                                    $subcategorias = obtenerSubcategoriasPorCategoria($con, $categoria->getId());
                                    echo "<td>";
                                        if (empty($subcategorias)) {
                                            echo "<span class='badge bg-secondary'>Sin subcategorías</span>";
                                        } else {
                                            foreach ($subcategorias as $sub) {
                                                $color = ($sub['estado'] === 'activo') ? 'bg-primary' : 'bg-secondary';
                                                echo "<span class='badge $color me-1'>" . htmlspecialchars($sub['nombre']) . "</span>";
                                            }
                                        }
                                    echo "</td>";

                                    echo "<td><a class='text-jtech fw-bold' href='categoriaEditar.php?id=" . $categoria->getId() . "'>
                                              <i class='bi bi-pencil-square'></i>
                                          </a></td>";

                                    if ($_SESSION["rol"] === "administrador") {
                                        echo "<td><a class='text-danger fw-bold' href='categoriaEliminar.php?id=" . $categoria->getId() . "'>
                                                <i class='bi bi-trash-fill'></i>
                                              </a></td>";
                                    } else {
                                        echo "<td><i class='bi bi-ban text-muted'></i></td>";
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
                        $url = "categoriaConsulta.php?pagina=$i&orden=$orden&tipoOrden=$tipoOrden";
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
                <a href="categoriaCrear.php" class="btn btn-jtech">Nueva categoría</a>
            </div>
            
            <div class="text-center mt-4 d-flex justify-content-center">
                <a href="../subcategorias/subcategoriaCrear.php" class="btn btn-jtech">Nueva Subcategoría</a>
            </div>

            <div class="text-center text-lg-start mt-3">
                <a href="../utilidades/panelAdministrador.php" class="btn btn-outline-secondary">Volver</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
