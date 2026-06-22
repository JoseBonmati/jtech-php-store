<?php

    // Ensure Database connection ($db) is properly initialized in header.php
    include_once "templates/header.php";

    // Returns the Bootstrap icon HTML based on the current price sorting
    function getPriceSortIcon(string $currentSort): string {
        if ($currentSort === "ASC") {
            return '<i class="bi bi-arrow-up"></i>';
        }
        if ($currentSort === "DESC") {
            return '<i class="bi bi-arrow-down"></i>';
        }
        return '<i class="bi bi-arrow-down-up"></i>';
    }

    //Builds the URL for the price sorting button, maintaining existing search filters
    function getPriceSortUrl(string $currentSort): string {
        $newSort = ($currentSort === "ASC") ? "DESC" : "ASC";
        $url = "index.php?sort_price=$newSort";

        if (isset($_GET["search"])) {
            $url .= "&search=" . urlencode($_GET["search"]);
        }
        if (isset($_GET["min_price"])) {
            $url .= "&min_price=" . urlencode($_GET["min_price"]);
        }
        if (isset($_GET["max_price"])) {
            $url .= "&max_price=" . urlencode($_GET["max_price"]);
        }
        if (isset($_GET["category"])) {
            $url .= "&category=" . (int)$_GET["category"];
        }
        if (isset($_GET["subcategory"])) {
            $url .= "&subcategory=" . (int)$_GET["subcategory"];
        }

        return $url;
    }

    // Main logic & Query building
    $whereClause = "WHERE estado = 'activo'";

    // Category Filter
    if (isset($_GET["category"])) {
        $categoryId = (int)$_GET["category"];
        $whereClause .= " AND id_categoria = $categoryId";
    }

    // Subcategory Filter
    if (isset($_GET["subcategory"])) {
        $subcategoryId = (int)$_GET["subcategory"];
        $whereClause .= " AND id_subcategoria = $subcategoryId";
    }

    // Search Filter
    if (isset($_GET["search"]) && trim($_GET["search"]) !== "") {
        $searchTerm = "%" . trim($_GET["search"]) . "%";
        $whereClause .= " AND nombre LIKE " . $db->quote($searchTerm);
    }

    // Price Filters
    if (isset($_GET["min_price"]) && is_numeric($_GET["min_price"])) {
        $minPrice = (float)$_GET["min_price"];
        $whereClause .= " AND precio >= $minPrice";
    }
    if (isset($_GET["max_price"]) && is_numeric($_GET["max_price"])) {
        $maxPrice = (float)$_GET["max_price"];
        $whereClause .= " AND precio <= $maxPrice";
    }

    // Order Logic
    $orderClause = " ORDER BY nombre ASC";
    if (isset($_GET["sort_price"]) && in_array($_GET["sort_price"], ["ASC", "DESC"])) {
        $orderClause = " ORDER BY precio " . $_GET["sort_price"];
    }

    // Pagination Logic
    $currentPage = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
    if ($currentPage < 1) $currentPage = 1;

    $resultsPerPage = 8;
    $offset = ($currentPage - 1) * $resultsPerPage;

    // Fetch Products
    $sql = "SELECT id, nombre, precio, imagen, descripcion FROM productos $whereClause $orderClause LIMIT :offset, :limit";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->bindValue(":limit", $resultsPerPage, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Total Products for Pagination
    $totalStmt = $db->query("SELECT COUNT(*) FROM productos $whereClause");
    $totalProducts = $totalStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $resultsPerPage);

    // Build Extra Params for Pagination Links
    $extraParams = ""; 
    if (isset($_GET["category"])) $extraParams .= "&category=" . (int)$_GET["category"]; 
    if (isset($_GET["subcategory"])) $extraParams .= "&subcategory=" . (int)$_GET["subcategory"]; 
    if (isset($_GET["search"])) $extraParams .= "&search=" . urlencode($_GET["search"]);
    if (isset($_GET["min_price"])) $extraParams .= "&min_price=" . urlencode($_GET["min_price"]);
    if (isset($_GET["max_price"])) $extraParams .= "&max_price=" . urlencode($_GET["max_price"]);
    if (isset($_GET["sort_price"])) $extraParams .= "&sort_price=" . urlencode($_GET["sort_price"]);

?>

<div class="jtech-layout">

    <?php include_once "templates/aside_left.php"; ?>

    <main class="main">
        
        <?php
            // Success messages
            if (isset($_GET["added"])) {
                echo "<p class='alert alert-success text-center mb-4'>Producto añadido al carrito correctamente.</p>";
            }
            // Error messages
            if (isset($_GET["error"])) {
                $errorCode = htmlspecialchars($_GET["error"]);

                $errorMessages = [
                    "userNotFound" => "El usuario no existe.",
                    "orderError" => "Ha ocurrido un error con el pedido.",
                    "paymentError" => "Ha ocurrido un error con el pago.",
                    "outOfStock" => "Este producto está agotado.",
                    "insufficientStock" => "No hay suficiente stock para añadir más unidades.",
                    "invalidOrder" => "Este pedido no te pertenece.",
                    "invalidProduct" => "El producto seleccionado no es válido."
                ];

                if (array_key_exists($errorCode, $errorMessages)) {
                    echo "<p class='alert alert-danger text-center mb-4'>{$errorMessages[$errorCode]}</p>";
                }
            }
        ?>

        <form method="get" action="index.php" class="mb-4 jtech-search mx-auto" style="max-width: 600px;">
            <?php if (isset($_GET["category"])): ?>
                <input type="hidden" name="category" value="<?= (int)$_GET["category"] ?>">
            <?php endif; ?>

            <?php if (isset($_GET["subcategory"])): ?>
                <input type="hidden" name="subcategory" value="<?= (int)$_GET["subcategory"] ?>">
            <?php endif; ?>

            <div class="input-group mb-2">
                <a href="index.php" class="btn btn-outline-jtech fw-semibold">Limpiar filtros</a>
                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <button class="btn btn-jtech fw-semibold" type="submit">Buscar</button>
            </div>

            <div class="row g-2">
                <div class="col">
                    <input type="number" name="min_price" class="form-control" placeholder="Precio mínimo" value="<?= isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : '' ?>">
                </div>
                <div class="col">
                    <input type="number" name="max_price" class="form-control" placeholder="Precio máximo" value="<?= isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : '' ?>">
                </div>
                <div class="col">
                    <a href="<?= getPriceSortUrl($_GET['sort_price'] ?? '') ?>" class="btn btn-outline-jtech w-100 fw-semibold">
                        Precio <?= getPriceSortIcon($_GET['sort_price'] ?? '') ?>
                    </a>
                </div>
            </div>
        </form>

        <div class="row g-4">
            <?php if (count($products) === 0): ?>
                <p class="text-center text-muted">No hay productos para mostrar.</p>
            <?php endif; ?>

            <?php foreach ($products as $product): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                    <div class="card h-100 shadow-sm jtech-product-card">
                        <div class="position-absolute top-0 end-0 m-2" data-bs-toggle="tooltip" data-bs-placement="left" title="<?= htmlspecialchars($product['descripcion']) ?>">
                            <i class="bi bi-info-circle text-muted"></i>
                        </div>

                        <img src="<?= htmlspecialchars($product['imagen']) ?>" class="card-img-top mt-3" style="height:180px; object-fit:contain; background-color:#fff;" alt="<?= htmlspecialchars($product['nombre']) ?>">
                        
                        <div class="card-body text-center d-flex flex-column justify-content-between">
                            <h5 class="card-title fw-semibold jtech-product-name"><?= htmlspecialchars($product['nombre']) ?></h5>
                            <p class="card-text text-primary fw-bold fs-4"><?= number_format($product['precio'], 2) ?> €</p>

                            <form action="/cart/cart_add.php" method="post">
                                <input type="hidden" name="redirect_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" class="btn btn-jtech w-100 mt-auto fw-semibold">
                                    Añadir al carrito
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div>
                <ul class="pagination justify-content-center mt-4">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i . $extraParams ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
        <?php endif; ?>

    </main>

    <?php include_once "templates/aside_right.php"; ?>

</div>

<?php include_once "templates/footer.php"; ?>