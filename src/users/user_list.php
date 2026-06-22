<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . "/../utils/Database.php";
    require_once __DIR__ . "/../users/User.php"; 
    
    $db = Database::getConnection();

    // Restrict access: only administrators can view this page
    if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "administrador") {
        header("Location: /index.php?unauthorized_access=1");
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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="jtech-bg d-flex justify-content-center align-items-start py-5">
        <div class="jtech-card-wide p-4 w-100">
            <h2 class="text-center fw-bold mb-4">Gestión de Usuarios</h2>

            <!-- Success messages -->
            <div class="mb-3">
                <?php
                    if (isset($_GET["updated_name"]) && isset($_GET["updated_email"])) {
                        $updatedName = htmlspecialchars($_GET["updated_name"]);
                        $updatedEmail = htmlspecialchars($_GET["updated_email"]);
                        echo "<p class='alert alert-success'>El usuario <b>$updatedName</b> con email <b>$updatedEmail</b> ha sido modificado correctamente.</p>";
                    }
                    if (isset($_GET["created_name"]) && isset($_GET["created_email"])) {
                        $createdName = htmlspecialchars($_GET["created_name"]);
                        $createdEmail = htmlspecialchars($_GET["created_email"]);
                        echo "<p class='alert alert-success'>El usuario <b>$createdName</b> con email <b>$createdEmail</b> se ha creado correctamente.</p>";
                    }
                    if (isset($_GET["deleted_name"])) {
                        $deletedName = htmlspecialchars($_GET["deleted_name"]);
                        echo "<p class='alert alert-success'>El usuario <b>$deletedName</b> se ha eliminado correctamente.</p>";
                    }
                ?>
            </div>

            <form method="get" action="/users/user_list.php" class="mb-4 jtech-search mx-auto">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o email..." autocomplete="off"
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
                    "email" => "email",
                    "phone" => "telefono",
                    "role" => "rol",
                    "status" => "estado"
                ];

                $sortBy = isset($_GET["sort_by"]) ? $_GET["sort_by"] : "name";
                $orderField = $allowedColumns[$sortBy] ?? "nombre";

                $sortDir = isset($_GET["sort_dir"]) ? strtoupper($_GET["sort_dir"]) : "ASC";
                if (!in_array($sortDir, ["ASC", "DESC"])) {
                    $sortDir = "ASC";
                }

                // If search is active, apply filter + sort + pagination
                if (isset($_GET["search"]) && trim($_GET["search"]) !== "") {

                    $searchTerm = "%" . trim($_GET["search"]) . "%";

                    // Count filtered results
                    $countQuery = $db->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE nombre LIKE :search OR email LIKE :search");
                    $countQuery->execute([":search" => $searchTerm]);
                    $totalUsers = $countQuery->fetch(PDO::FETCH_ASSOC)["total"];

                    $totalPages = ceil($totalUsers / $perPage);
                    $offset = ($page - 1) * $perPage;

                    // SQL query updated with Aliases to properly hydrate the User class
                    $query = $db->prepare("SELECT id, nombre AS name, email, contrasenya AS password, telefono AS phone, rol AS role, estado AS status 
                                           FROM usuarios WHERE nombre LIKE :search OR email LIKE :search ORDER BY $orderField $sortDir LIMIT :offset, :limit");

                    $query->bindValue(":search", $searchTerm, PDO::PARAM_STR);
                    $query->bindValue(":offset", $offset, PDO::PARAM_INT);
                    $query->bindValue(":limit", $perPage, PDO::PARAM_INT);
                    $query->execute();

                    $query->setFetchMode(PDO::FETCH_CLASS, "User");
                    $users = $query->fetchAll();

                } else {

                    // Fetch users normally
                    $users = getUsers($db, $page, $perPage, $sortBy, $sortDir);

                    $countQuery = $db->query("SELECT count(*) AS total FROM usuarios");
                    $totalUsers = $countQuery->fetch(PDO::FETCH_ASSOC)["total"];
                    $totalPages = ceil($totalUsers / $perPage);
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
                    $url = "/users/user_list.php?sort_by=$col&sort_dir=$newDir";
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
                            <th><a href="<?= sortUrl('email', $sortDir) ?>">Email <?= sortIcon('email', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('phone', $sortDir) ?>">Teléfono <?= sortIcon('phone', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('role', $sortDir) ?>">Rol <?= sortIcon('role', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('status', $sortDir) ?>">Estado <?= sortIcon('status', $sortBy, $sortDir) ?></a></th>
                            <th>Editar</th>
                            <th>Borrar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                            if (empty($users)) {
                                echo "<tr><td colspan='8' class='text-center py-3 text-muted'>
                                          No se encontraron usuarios
                                      </td></tr>";
                            } else {
                                foreach ($users as $user) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars((string)$user->getId()) . "</td>";
                                    echo "<td>" . htmlspecialchars((string)$user->getName()) . "</td>";
                                    echo "<td>" . htmlspecialchars((string)$user->getEmail()) . "</td>";
                                    echo "<td>" . htmlspecialchars((string)$user->getPhone()) . "</td>";
                                    echo "<td>" . htmlspecialchars((string)$user->getRole()) . "</td>";
                                    echo "<td>" . htmlspecialchars((string)$user->getStatus()) . "</td>";

                                    if ($_SESSION["id"] == $user->getId()) {
                                        echo "<td><i class='bi bi-ban text-muted'></i></td>"; 
                                        echo "<td><i class='bi bi-ban text-muted'></i></td>";
                                    } else {
                                        // Absolute routes for edit and delete actions
                                        echo "<td><a class='text-jtech fw-bold' href='/users/user_edit.php?id=" . $user->getId() . "'>
                                                <i class='bi bi-pencil-square'></i>
                                            </a></td>";

                                        echo "<td><a class='text-danger fw-bold' href='/users/user_delete.php?id=" . $user->getId() . "'>
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

            <!-- Pagination -->
            <div class="text-center mt-4">
                <?php

                    for ($i = 1; $i <= $totalPages; $i++) {
                        $url = "/users/user_list.php?page=$i&sort_by=$sortBy&sort_dir=$sortDir";
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
                <a href="/users/user_create.php" class="btn btn-jtech">Nuevo usuario</a>
            </div>

            <div class="text-center text-lg-start mt-3">
                <a href="/utils/admin_panel.php" class="btn btn-outline-secondary">Volver</a>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>