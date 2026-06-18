<?php
    include("plantillas/header.php");
?>

<div class="jtech-layout">

    <?php include("plantillas/asideIzq.php"); ?>

    <main class="main">
        <?php

            function iconoOrdenPrecio($ordenActual) {
                if ($ordenActual === "ASC") {
                    return '<i class="bi bi-arrow-up"></i>';
                }
                if ($ordenActual === "DESC") {
                    return '<i class="bi bi-arrow-down"></i>';
                }
                return '<i class="bi bi-arrow-down-up"></i>';
            }

            function urlOrdenPrecio($ordenActual) {
                $nuevoOrden = ($ordenActual === "ASC") ? "DESC" : "ASC";

                $url = "index.php?orden_precio=$nuevoOrden";

                if (isset($_GET["buscar"])) {
                    $url .= "&buscar=" . urlencode($_GET["buscar"]);
                }
                if (isset($_GET["precio_min"])) {
                    $url .= "&precio_min=" . urlencode($_GET["precio_min"]);
                }
                if (isset($_GET["precio_max"])) {
                    $url .= "&precio_max=" . urlencode($_GET["precio_max"]);
                }
                if (isset($_GET["categoria"])) {
                    $url .= "&categoria=" . (int)$_GET["categoria"];
                }
                if (isset($_GET["subcategoria"])) {
                    $url .= "&subcategoria=" . (int)$_GET["subcategoria"];
                }

                return $url;
            }

            $where = "WHERE estado = 'activo'";

            // Filtro por categoría
            if (isset($_GET["categoria"])) {
                $cat = (int)$_GET["categoria"];
                $where .= " AND id_categoria = $cat";
            }

            // Filtro por subcategoría
            if (isset($_GET["subcategoria"])) {
                $sub = (int)$_GET["subcategoria"];
                $where .= " AND id_subcategoria = $sub";
            }

            // Búsqueda por nombre
            if (isset($_GET["buscar"]) && trim($_GET["buscar"]) !== "") {
                $buscar = "%" . trim($_GET["buscar"]) . "%";
                $where .= " AND nombre LIKE " . $con->quote($buscar);
            }

            // Filtro por precio mínimo
            if (isset($_GET["precio_min"]) && is_numeric($_GET["precio_min"])) {
                $precioMin = (float)$_GET["precio_min"];
                $where .= " AND precio >= $precioMin";
            }

            // Filtro por precio máximo
            if (isset($_GET["precio_max"]) && is_numeric($_GET["precio_max"])) {
                $precioMax = (float)$_GET["precio_max"];
                $where .= " AND precio <= $precioMax";
            }

            // Ordenación por precio
            $ordenPrecio = "";
            if (isset($_GET["orden_precio"]) && in_array($_GET["orden_precio"], ["ASC", "DESC"])) {
                $ordenPrecio = " ORDER BY precio " . $_GET["orden_precio"];
            } else {
                $ordenPrecio = " ORDER BY nombre ASC";
            }

            $pagina = isset($_GET["pagina"]) ? (int)$_GET["pagina"] : 1;
            if ($pagina < 1) $pagina = 1;

            $resultadosPP = 8;
            $inicio = ($pagina - 1) * $resultadosPP;


            // Obtener productos
            $sql = "SELECT id, nombre, precio, imagen, descripcion FROM productos $where $ordenPrecio LIMIT :inicio, :resultados";

            $query = $con->prepare($sql);
            $query->bindValue(":inicio", $inicio, PDO::PARAM_INT);
            $query->bindValue(":resultados", $resultadosPP, PDO::PARAM_INT);
            $query->execute();

            $productos = $query->fetchAll(PDO::FETCH_ASSOC);

            $totalQuery = $con->query("SELECT COUNT(*) FROM productos $where");
            $totalProductos = $totalQuery->fetchColumn();

            $totalPaginas = ceil($totalProductos / $resultadosPP);

            // Construir parámetros extra para mantener filtros 
            $extraParams = ""; 

            if (isset($_GET["categoria"])) { 
                $extraParams .= "&categoria=" . (int)$_GET["categoria"]; 
            } 
            if (isset($_GET["subcategoria"])) { 
                $extraParams .= "&subcategoria=" . (int)$_GET["subcategoria"]; 
            }
            if (isset($_GET["buscar"])) {
                $extraParams .= "&buscar=" . urlencode($_GET["buscar"]);
            }
            if (isset($_GET["precio_min"])) {
                $extraParams .= "&precio_min=" . urlencode($_GET["precio_min"]);
            }

            if (isset($_GET["precio_max"])) {
                $extraParams .= "&precio_max=" . urlencode($_GET["precio_max"]);
            }

            if (isset($_GET["orden_precio"])) {
                $extraParams .= "&orden_precio=" . urlencode($_GET["orden_precio"]);
            }

        ?>

        <?php

            if (isset($_GET["agregado"])) {
                echo "<p class='alert alert-success text-center mb-4'>Producto añadido al carrito correctamente.</p>";
            }
            if (isset($_GET["existe"])) {
                echo "<p class='alert alert-danger text-center mb-4'>El usuario no existe.</p>";
            }
            if (isset($_GET["pedidoError"])) {
                echo "<p class='alert alert-danger text-center mb-4'>Ha ocurrido un error con el pedido.</p>";
            }
            if (isset($_GET["pagoError"])) {
                echo "<p class='alert alert-danger text-center mb-4'>Ha ocurrido un error con el pago.</p>";
            }

            if (isset($_GET["errorC"])) {
                $errorC = htmlspecialchars($_GET["errorC"]);

                if ($errorC === "sinStock") {
                    echo "<p class='alert alert-danger text-center mb-4'>Este producto está agotado.</p>";
                }
                if ($errorC === "stockInsuficiente") {
                    echo "<p class='alert alert-danger text-center mb-4'>No hay suficiente stock para añadir más unidades.</p>";
                }
                if ($errorC === "productoNoDisponible") {
                    echo "<p class='alert alert-danger text-center mb-4'>Este pedido no te pertenece.</p>";
                }
                if ($errorC === "productoInvalido") {
                    echo "<p class='alert alert-danger text-center mb-4'>El producto seleccionado no es válido.</p>";
                }
            }

        ?>

        <!-- Buscador y filtros -->
        <form method="get" action="index.php" class="mb-4 jtech-search mx-auto" style="max-width: 600px;">
            <?php if (isset($_GET["categoria"])): ?>
                <input type="hidden" name="categoria" value="<?= (int)$_GET["categoria"] ?>">
            <?php endif; ?>

            <?php if (isset($_GET["subcategoria"])): ?>
                <input type="hidden" name="subcategoria" value="<?= (int)$_GET["subcategoria"] ?>">
            <?php endif; ?>

            <div class="input-group mb-2">
                <a href="index.php" class="btn btn-outline-jtech fw-semibold">Limpiar filtros</a>
                <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre" value="<?= isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : '' ?>">
                <button class="btn btn-jtech fw-semibold" type="submit">Buscar</button>
            </div>

            <div class="row g-2">
                <div class="col">
                    <input type="number" name="precio_min" class="form-control" placeholder="Precio mínimo" value="<?= isset($_GET['precio_min']) ? htmlspecialchars($_GET['precio_min']) : '' ?>">
                </div>
                <div class="col">
                    <input type="number" name="precio_max" class="form-control" placeholder="Precio máximo" value="<?= isset($_GET['precio_max']) ? htmlspecialchars($_GET['precio_max']) : '' ?>">
                </div>
                <div class="col">
                    <a href="<?= urlOrdenPrecio($_GET['orden_precio'] ?? '') ?>" class="btn btn-outline-jtech w-100 fw-semibold">
                        Precio <?= iconoOrdenPrecio($_GET['orden_precio'] ?? '') ?>
                    </a>
                </div>
            </div>
        </form>

        <div class="row g-4">

        <?php if (count($productos) === 0): ?>
            <p class="text-center text-muted">No hay productos para mostrar.</p>
        <?php endif; ?>

        <?php foreach ($productos as $p): ?>
            <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                <div class="card h-100 shadow-sm jtech-card-producto">
                    <div class="position-absolute top-0 end-0 m-2" data-bs-toggle="tooltip" data-bs-placement="left" title="<?= htmlspecialchars($p['descripcion']) ?>">
                        <i class="bi bi-info-circle text-muted"></i>
                    </div>


                    <img src="<?= $p['imagen'] ?>" class="card-img-top mt-3" style="height:180px; object-fit:contain; background-color:#fff;">
                    <div class="card-body text-center d-flex flex-column justify-content-between">
                        <h5 class="card-title fw-semibold jtech-nombre-producto"><?= htmlspecialchars($p['nombre']) ?></h5>
                        <p class="card-text text-primary fw-bold fs-4"><?= number_format($p['precio'], 2) ?> €</p>

                        <form action="../carrito/carritoAgregar.php" method="post">
                            <input type="hidden" name="redireccionar" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                            <input type="hidden" name="id_producto" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-jtech w-100 mt-auto fw-semibold">
                                Añadir al carrito
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php if ($totalPaginas > 1): ?>
            <div>
                <ul class="pagination justify-content-center mt-4">
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $i . $extraParams ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
        <?php endif; ?>

    </main>

    <?php include("plantillas/asideDer.php"); ?>

</div>

<?php
    include("plantillas/footer.php");
?>
