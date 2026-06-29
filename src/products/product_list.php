<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . "/../utils/Database.php";
    require_once __DIR__ . "/Product.php";
    $db = Database::getConnection();

    // Restrict access: only administrators or employees can view this page
    if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: /index.php?unauthorized_access=1");
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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="jtech-bg d-flex justify-content-center align-items-start py-5">
        <div class="jtech-card-wide p-4 w-100">
            <h2 class="text-center fw-bold mb-4">Gestión de Productos</h2>

            <!-- Messages -->
            <div class="mb-3">
                <?php
                    // Translated GET parameters
                    if (isset($_GET["updated_product"])) {
                        $updatedProduct = htmlspecialchars($_GET["updated_product"]);
                        echo "<p class='alert alert-success'>El producto <b>$updatedProduct</b> ha sido modificado correctamente.</p>";
                    }
                    if (isset($_GET["created_product"])) {
                        $createdProduct = htmlspecialchars($_GET["created_product"]);
                        echo "<p class='alert alert-success'>El producto <b>$createdProduct</b> se ha creado correctamente.</p>";
                    }
                    if (isset($_GET["deleted_product"])) {
                        $deletedProduct = htmlspecialchars($_GET["deleted_product"]);
                        echo "<p class='alert alert-success'>El producto <b>$deletedProduct</b> se ha desactivado correctamente.</p>";
                    }
                ?>
            </div>

            <form method="get" action="/products/product_list.php" class="mb-4 jtech-search mx-auto">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o categoria" autocomplete="off"
                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button class="btn btn-jtech fw-semibold" type="submit">Buscar</button>
                </div>
            </form>

            <?php

                // Pagination and sorting configuration
                $page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
                $perPage = 5;

                // White-list mapping for sorting
                $allowedColumns = [
                    "id" => "p.id",
                    "name" => "p.nombre",
                    "price" => "p.precio",
                    "stock" => "p.stock",
                    "category_name" => "c.nombre",
                    "subcategory_name" => "s.nombre",
                    "status" => "p.estado"
                ];

                $sortBy = $_GET["sort_by"] ?? "name";
                $orderField = $allowedColumns[$sortBy] ?? "p.nombre";

                $sortDir = strtoupper($_GET["sort_dir"] ?? "ASC");
                if (!in_array($sortDir, ["ASC", "DESC"])) $sortDir = "ASC";

                // If search is active, apply filter + sort + pagination
                if (isset($_GET["search"]) && trim($_GET["search"]) !== "") {

                    $searchTerm = "%" . trim($_GET["search"]) . "%";

                    // Count filtered results
                    $countQuery = $db->prepare("SELECT COUNT(*) AS total FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id
                                                LEFT JOIN subcategorias s ON p.id_subcategoria = s.id WHERE (p.nombre LIKE :search OR c.nombre LIKE :search)");
                    $countQuery->execute([":search" => $searchTerm]);
                    $totalProducts = $countQuery->fetch(PDO::FETCH_ASSOC)["total"];

                    $totalPages = ceil($totalProducts / $perPage);
                    $offset = ($page - 1) * $perPage;

                    // Fetch filtered results with exact aliases for the Product class
                    $query = $db->prepare("SELECT p.id, p.id_categoria AS category_id, p.id_subcategoria AS subcategory_id, p.nombre AS name, p.descripcion AS description, 
                                           p.precio AS price, p.stock AS stock, p.imagen AS image, p.estado AS status, c.nombre AS category_name, s.nombre AS subcategory_name
                                           FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id LEFT JOIN subcategorias s ON p.id_subcategoria = s.id 
                                           WHERE (p.nombre LIKE :search OR c.nombre LIKE :search) ORDER BY $orderField $sortDir LIMIT :offset, :limit");

                    $query->bindValue(":search", $searchTerm, PDO::PARAM_STR);
                    $query->bindValue(":offset", $offset, PDO::PARAM_INT);
                    $query->bindValue(":limit", $perPage, PDO::PARAM_INT);
                    $query->execute();

                    $query->setFetchMode(PDO::FETCH_CLASS, "Product");
                    $products = $query->fetchAll();

                } else {

                    // Normal fetch using standard function
                    $products = getProducts($db, $page, $perPage, $sortBy, $sortDir);

                    $countQuery = $db->query("SELECT COUNT(*) AS total FROM productos");
                    $totalProducts = $countQuery->fetch(PDO::FETCH_ASSOC)["total"];

                    $totalPages = ceil($totalProducts / $perPage);
                }

                // Sorting icons
                function sortIcon(string $col, string $currentSortBy, string $currentSortDir): string {
                    if ($col !== $currentSortBy) return '<i class="bi bi-arrow-down-up"></i>';
                    return $currentSortDir === "ASC"
                        ? '<i class="bi bi-arrow-up"></i>'
                        : '<i class="bi bi-arrow-down"></i>';
                }

                // Sorting URL builder
                function sortUrl(string $col, string $currentSortDir): string {
                    $newDir = $currentSortDir === "ASC" ? "DESC" : "ASC";
                    $url = "/products/product_list.php?sort_by=$col&sort_dir=$newDir";
                    if (isset($_GET["search"])) {
                        $url .= "&search=" . urlencode($_GET["search"]);
                    }
                    return $url;
                }

            ?>

            <div class="table-responsive">
                <table class="jtech-table align-middle text-center mx-auto">
                    <thead>
                        <tr>
                            <th><a href="<?= sortUrl('id', $sortDir) ?>">ID <?= sortIcon('id', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('name', $sortDir) ?>">Nombre <?= sortIcon('name', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('category_name', $sortDir) ?>">Categoría <?= sortIcon('category_name', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('subcategory_name', $sortDir) ?>">Subcategoría <?= sortIcon('subcategory_name', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('price', $sortDir) ?>">Precio <?= sortIcon('price', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('stock', $sortDir) ?>">Stock <?= sortIcon('stock', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('status', $sortDir) ?>">Estado <?= sortIcon('status', $sortBy, $sortDir) ?></a></th>
                            <th>Imagen</th>
                            <th>Editar</th>
                            <th>Borrar</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php

                            if (empty($products)) {
                                echo "<tr><td colspan='10' class='text-center py-3 text-muted'>
                                          No se encontraron productos
                                      </td></tr>";
                            } else {
                                foreach ($products as $p) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars((string)$p->getId()) . "</td>";
                                    echo "<td>" . htmlspecialchars((string)$p->getName()) . "</td>";
                                    echo "<td>" . htmlspecialchars((string)$p->getCategoryName()) . "</td>";
                                    echo "<td>" . htmlspecialchars((string)$p->getSubcategoryName()) . "</td>";
                                    echo "<td>" . htmlspecialchars((string)$p->getPrice()) . " €</td>";
                                    echo "<td>" . htmlspecialchars((string)$p->getStock()) . "</td>";
                                    echo "<td>" . htmlspecialchars((string)$p->getStatus()) . "</td>";
                                    
                                    // Ensure absolute path rendering for the image
                                    echo "<td><img src='/" . htmlspecialchars((string)$p->getImage()) . "' class='img-thumbnail' style='max-height: 120px;'></td>";

                                    // Edit route absolute
                                    echo "<td><a class='text-jtech fw-bold' href='/products/product_edit.php?id=" . $p->getId() . "'>
                                              <i class='bi bi-pencil-square'></i>
                                          </a></td>";

                                    if ($_SESSION["rol"] === "administrador") {
                                        // Delete route absolute
                                        echo "<td><a class='text-danger fw-bold' href='/products/product_delete.php?id=" . $p->getId() . "'>
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

            <!-- Pagination -->
            <div class="text-center mt-4">
                <?php
                    for ($i = 1; $i <= $totalPages; $i++) {
                        $url = "/products/product_list.php?page=$i&sort_by=$sortBy&sort_dir=$sortDir";
                        if (isset($_GET["search"])) {
                            $url .= "&search=" . urlencode($_GET["search"]);
                        }

                        if ($i == $page) {
                            echo "<button class='btn btn-jtech mx-1'>$i</button>";
                        } else {
                            echo "<a href='$url' class='btn btn-outline-jtech mx-1'>$i</a>";
                        }
                    }
                ?>
            </div>

            <div class="text-center mt-4 d-flex justify-content-center">
                <a href="/products/product_create.php" class="btn btn-jtech">Nuevo producto</a>
            </div>

            <div class="text-center text-lg-start mt-3">
                <a href="/utils/admin_panel.php" class="btn btn-outline-secondary">Volver</a>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>