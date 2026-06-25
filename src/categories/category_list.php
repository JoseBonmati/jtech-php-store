<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . "/../utils/Database.php";
    require_once __DIR__ . "/Category.php";
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
    <title>Gestión de Categorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="jtech-bg d-flex justify-content-center align-items-start py-5">
        <div class="jtech-card-wide p-4 w-100">
            <h2 class="text-center fw-bold mb-4">Gestión de Categorías</h2>

            <!-- Success Messages -->
            <div class="mb-3">
                <?php

                    if (isset($_GET["updated_category"])) {
                        $updatedCategory = htmlspecialchars($_GET["updated_category"]);
                        echo "<p class='alert alert-success'>La categoría <b>$updatedCategory</b> ha sido modificada correctamente.</p>";
                    }
                    if (isset($_GET["created_category"])) {
                        $createdCategory = htmlspecialchars($_GET["created_category"]);
                        echo "<p class='alert alert-success'>La categoría <b>$createdCategory</b> se ha creado correctamente.</p>";
                    }
                    if (isset($_GET["deleted_category"])) {
                        $deletedCategory = htmlspecialchars($_GET["deleted_category"]);
                        echo "<p class='alert alert-success'>La categoría <b>$deletedCategory</b> se ha eliminado correctamente.</p>";
                    }
                    if (isset($_GET["created_subcategory"])) {
                        $createdSubcategory = htmlspecialchars($_GET["created_subcategory"]);
                        echo "<p class='alert alert-success'>La subcategoría <b>$createdSubcategory</b> se ha creado correctamente.</p>";
                    }
                    if (isset($_GET["deleted_subcategory"])) {
                        $deletedSubcategory = htmlspecialchars($_GET["deleted_subcategory"]);
                        echo "<p class='alert alert-success'>La subcategoría <b>$deletedSubcategory</b> se ha eliminado correctamente.</p>";
                    }

                ?>
            </div>

            <form method="get" action="/categories/category_list.php" class="mb-4 jtech-search mx-auto">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por nombre..." autocomplete="off"
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
                    "id" => "id",
                    "name" => "nombre",
                    "status" => "estado"
                ];
                
                $sortBy = isset($_GET["sort_by"]) ? $_GET["sort_by"] : "name";
                $orderField = $allowedColumns[$sortBy] ?? "nombre";

                $sortDir = isset($_GET["sort_dir"]) ? strtoupper($_GET["sort_dir"]) : "ASC";
                if (!in_array($sortDir, ["ASC", "DESC"])) $sortDir = "ASC";

                // If search is active, apply filter + sort + pagination
                if (isset($_GET["search"]) && trim($_GET["search"]) !== "") {

                    $searchTerm = "%" . trim($_GET["search"]) . "%";

                    // Count filtered results
                    $countQuery = $db->prepare("SELECT COUNT(*) AS total FROM categorias WHERE nombre LIKE :search");
                    $countQuery->execute([":search" => $searchTerm]);
                    $totalCategories = $countQuery->fetch(PDO::FETCH_ASSOC)["total"];

                    $totalPages = ceil($totalCategories / $perPage);
                    $offset = ($page - 1) * $perPage;

                    // Fetch filtered results with aliases mapped to the Category class properties
                    $query = $db->prepare("SELECT id, nombre AS name, estado AS status FROM categorias 
                                           WHERE nombre LIKE :search ORDER BY $orderField $sortDir LIMIT :offset, :limit");

                    $query->bindValue(":search", $searchTerm, PDO::PARAM_STR);
                    $query->bindValue(":offset", $offset, PDO::PARAM_INT);
                    $query->bindValue(":limit", $perPage, PDO::PARAM_INT);
                    $query->execute();

                    $query->setFetchMode(PDO::FETCH_CLASS, "Category");
                    $categories = $query->fetchAll();

                } else {

                    // Fetch categories normally
                    $categories = getCategories($db, $page, $perPage, $sortBy, $sortDir);

                    $countQuery = $db->query("SELECT count(*) AS total FROM categorias");
                    $totalCategories = $countQuery->fetch(PDO::FETCH_ASSOC)["total"];

                    $totalPages = ceil($totalCategories / $perPage);
                }

                // Sorting icons
                function sortIcon(string $col, string $currentSortBy, string $currentSortDir): string {
                    if ($col !== $currentSortBy) return '<i class="bi bi-arrow-down-up"></i>';
                    return $currentSortDir === "ASC"
                        ? '<i class="bi bi-arrow-up"></i>'
                        : '<i class="bi bi-arrow-down"></i>';
                }

                // Sorting URL
                function sortUrl(string $col, string $currentSortDir): string {
                    $newDir = $currentSortDir === "ASC" ? "DESC" : "ASC";
                    $url = "/categories/category_list.php?sort_by=$col&sort_dir=$newDir";
                    if (isset($_GET["search"])) {
                        $url .= "&search=" . urlencode($_GET["search"]);
                    }
                    return $url;
                }

                // Fetch subcategories for a specific category
                function getSubcategoriesByCategory(PDO $db, int $categoryId): array {
                    $query = $db->prepare("SELECT id, nombre AS name, estado AS status FROM subcategorias WHERE id_categoria = :id ORDER BY nombre ASC");
                    $query->execute([":id" => $categoryId]);
                    return $query->fetchAll(PDO::FETCH_ASSOC);
                }
            ?>

            <div class="table-responsive">
                <table class="jtech-table align-middle text-center mx-auto">
                    <thead>
                        <tr>
                            <th><a href="<?= sortUrl('id', $sortDir) ?>">ID <?= sortIcon('id', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('name', $sortDir) ?>">Nombre <?= sortIcon('name', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('status', $sortDir) ?>">Estado <?= sortIcon('status', $sortBy, $sortDir) ?></a></th>
                            <th>Subcategorías</th>
                            <th>Editar</th>
                            <th>Borrar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                            if (empty($categories)) {
                                echo "<tr><td colspan='6' class='text-center py-3 text-muted'>
                                          No se encontraron categorías
                                      </td></tr>";
                            } else {
                                foreach ($categories as $category) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars((string)$category->getId()) . "</td>";
                                    echo "<td>" . htmlspecialchars((string)$category->getName()) . "</td>";
                                    echo "<td>" . htmlspecialchars((string)$category->getStatus()) . "</td>";

                                    $subcategories = getSubcategoriesByCategory($db, $category->getId());
                                    echo "<td>";
                                        if (empty($subcategories)) {
                                            echo "<span class='badge bg-secondary'>Sin subcategorías</span>";
                                        } else {
                                            foreach ($subcategories as $sub) {
                                                $color = ($sub['status'] === 'activo') ? 'bg-primary' : 'bg-secondary';
                                                echo "<span class='badge $color me-1'>" . htmlspecialchars($sub['name']) . "</span>";
                                            }
                                        }
                                    echo "</td>";

                                    // Edit button
                                    echo "<td><a class='text-jtech fw-bold' href='/categories/category_edit.php?id=" . $category->getId() . "'>
                                              <i class='bi bi-pencil-square'></i>
                                          </a></td>";

                                    if ($_SESSION["rol"] === "administrador") {
                                        // Delete button
                                        echo "<td><a class='text-danger fw-bold' href='/categories/category_delete.php?id=" . $category->getId() . "'>
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
                        $url = "/categories/category_list.php?page=$i&sort_by=$sortBy&sort_dir=$sortDir";
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

            <div class="text-center mt-4 d-flex justify-content-center gap-3">
                <a href="/categories/category_create.php" class="btn btn-jtech">Nueva categoría</a>
                <a href="/subcategories/subcategory_create.php" class="btn btn-jtech">Nueva Subcategoría</a>
            </div>

            <div class="text-center text-lg-start mt-4">
                <a href="/utils/admin_panel.php" class="btn btn-outline-secondary">Volver</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>