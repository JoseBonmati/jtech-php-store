<?php

    require_once "../utilidades/conectar_db.php";
    require_once "Producto.php";
    $con = conectar();
    session_start();

    // Acceso permitido: administrador y empleado
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
    <title>Consulta de productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="jtech-bg d-flex justify-content-center align-items-start py-5">
        <div class="jtech-card-wide p-4 w-100">
            <h2 class="text-center fw-bold mb-4">Gestión de Productos</h2>

            <!-- Mensajes -->
            <div class="mb-3">
                <?php
                    if (isset($_GET["nombreE"])) {
                        $nombreE = htmlspecialchars($_GET["nombreE"]);
                        echo "<p class='alert alert-success'>El producto <b>$nombreE</b> ha sido modificado correctamente.</p>";
                    }
                    if (isset($_GET["nombreN"])) {
                        $nombreN = htmlspecialchars($_GET["nombreN"]);
                        echo "<p class='alert alert-success'>El producto <b>$nombreN</b> se ha creado correctamente.</p>";
                    }
                    if (isset($_GET["nombreD"])) {
                        $nombreD = htmlspecialchars($_GET["nombreD"]);
                        echo "<p class='alert alert-success'>El producto <b>$nombreD</b> se ha desactivado correctamente.</p>";
                    }
                ?>
            </div>

            <!-- Buscador -->
            <form method="get" action="productoConsulta.php" class="mb-4 jtech-search mx-auto">
                <div class="input-group">
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre o categoria" autocomplete="off"
                           value="<?= isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : '' ?>">
                    <button class="btn btn-jtech fw-semibold" type="submit">Buscar</button>
                </div>
            </form>

            <?php

                // Configuración de paginación y ordenación
                $pagina = isset($_GET["pagina"]) ? (int)$_GET["pagina"] : 1;
                $resultadosPP = 5;

                $columnasPermitidas = ["p.id", "p.nombre", "p.precio", "p.stock", "c.nombre", "s.nombre", "p.estado"];
                $orden = $_GET["orden"] ?? "p.nombre";
                if (!in_array($orden, $columnasPermitidas)) $orden = "p.nombre";

                $tipoOrden = strtoupper($_GET["tipoOrden"] ?? "ASC");
                if (!in_array($tipoOrden, ["ASC", "DESC"])) $tipoOrden = "ASC";

                // Si hay búsqueda
                if (isset($_GET["buscar"]) && trim($_GET["buscar"]) !== "") {

                    $busqueda = "%" . trim($_GET["buscar"]) . "%";

                    // Contar resultados filtrados
                    $countQuery = $con->prepare("SELECT COUNT(*) AS total FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id
                                                 LEFT JOIN subcategorias s ON p.id_subcategoria = s.id WHERE (p.nombre LIKE :busqueda OR c.nombre LIKE :busqueda)");
                    $countQuery->execute([":busqueda" => $busqueda]);
                    $productosTotal = $countQuery->fetch()["total"];

                    $paginasTotal = ceil($productosTotal / $resultadosPP);
                    $inicio = ($pagina - 1) * $resultadosPP;

                    // Obtener resultados filtrados
                    $query = $con->prepare("SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock, p.imagen, p.estado, c.nombre AS categoriaNombre, s.nombre AS subcategoriaNombre
                                            FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id LEFT JOIN subcategorias s ON p.id_subcategoria = s.id 
                                            WHERE (p.nombre LIKE :busqueda OR c.nombre LIKE :busqueda) ORDER BY $orden $tipoOrden LIMIT :inicio, :resultados");

                    $query->bindValue(":busqueda", $busqueda, PDO::PARAM_STR);
                    $query->bindValue(":inicio", $inicio, PDO::PARAM_INT);
                    $query->bindValue(":resultados", $resultadosPP, PDO::PARAM_INT);
                    $query->execute();

                    $query->setFetchMode(PDO::FETCH_CLASS, "Producto");
                    $productos = $query->fetchAll();

                } else {

                    // Consulta normal
                    $productos = obtenerProductos($con, $pagina, $resultadosPP, $orden, $tipoOrden);

                    $query = $con->prepare("SELECT COUNT(*) AS total FROM productos");
                    $query->execute();
                    $productosTotal = $query->fetch()["total"];

                    $paginasTotal = ceil($productosTotal / $resultadosPP);
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
                    $url = "productoConsulta.php?orden=$col&tipoOrden=$nuevoTipo";
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
                            <th><a href="<?= urlOrden('p.id', $tipoOrden) ?>">ID <?= iconoOrden('p.id', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('p.nombre', $tipoOrden) ?>">Nombre <?= iconoOrden('p.nombre', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('c.nombre', $tipoOrden) ?>">Categoría <?= iconoOrden('c.nombre', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('s.nombre', $tipoOrden) ?>">Subcategoría <?= iconoOrden('s.nombre', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('p.precio', $tipoOrden) ?>">Precio <?= iconoOrden('p.precio', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('p.stock', $tipoOrden) ?>">Stock <?= iconoOrden('p.stock', $orden, $tipoOrden) ?></a></th>
                            <th><a href="<?= urlOrden('p.estado', $tipoOrden) ?>">Estado <?= iconoOrden('p.estado', $orden, $tipoOrden) ?></a></th>
                            <th>Imagen</th>
                            <th>Editar</th>
                            <th>Borrar</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php

                            if (empty($productos)) {
                                echo "<tr><td colspan='10' class='text-center py-3 text-muted'>
                                          No se encontraron productos
                                      </td></tr>";
                            } else {
                                foreach ($productos as $p) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($p->getId()) . "</td>";
                                    echo "<td>" . htmlspecialchars($p->getNombre()) . "</td>";
                                    echo "<td>" . htmlspecialchars($p->getCategoriaNombre()) . "</td>";
                                    echo "<td>" . htmlspecialchars($p->getSubcategoriaNombre()) . "</td>";
                                    echo "<td>" . htmlspecialchars($p->getPrecio()) . " €</td>";
                                    echo "<td>" . htmlspecialchars($p->getStock()) . "</td>";
                                    echo "<td>" . htmlspecialchars($p->getEstado()) . "</td>";
                                    echo "<td><img src='" . htmlspecialchars($p->getImagen()) . "' class='img-thumbnail' style='max-height: 120px;'></td>";

                                    echo "<td><a class='text-jtech fw-bold' href='productoEditar.php?id=" . $p->getId() . "'>
                                              <i class='bi bi-pencil-square'></i>
                                          </a></td>";

                                    if ($_SESSION["rol"] === "administrador") {
                                        echo "<td><a class='text-danger fw-bold' href='productoEliminar.php?id=" . $p->getId() . "'>
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
                        $url = "productoConsulta.php?pagina=$i&orden=$orden&tipoOrden=$tipoOrden";
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
                <a href="productoCrear.php" class="btn btn-jtech">Nuevo producto</a>
            </div>

            <div class="text-center text-lg-start mt-3">
                <a href="../utilidades/panelAdministrador.php" class="btn btn-outline-secondary">Volver</a>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
