<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . "/../utils/Database.php";
    require_once __DIR__ . "/Order.php";
    $db = Database::getConnection();

    if (!isset($_SESSION["id"])) {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    $role = $_SESSION["rol"];
    $userId = $_SESSION["id"];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="jtech-bg d-flex justify-content-center align-items-start py-5">
        <div class="jtech-card-wide p-4 w-100">

            <h2 class="text-center fw-bold mb-4">
                <?= ($role === "usuario") ? "Mis pedidos" : "Gestión de pedidos" ?>
            </h2>

            <!-- Messages -->
            <div class="mb-3">
                <?php
                    // Feedback parameters
                    if (isset($_GET["status_updated"])) {
                        echo "<p class='alert alert-success'>Estado del pedido actualizado correctamente.</p>";
                    }
                    if (isset($_GET["order_canceled"])) {
                        echo "<p class='alert alert-warning'>El pedido ha sido cancelado.</p>";
                    }

                    // Standardized error dictionary mapping
                    if (isset($_GET["error"])) {
                        $error = htmlspecialchars($_GET["error"]);

                        if ($error === "invalidStatus") {
                            echo "<p class='alert alert-danger text-center mb-4'>Ese estado no es válido.</p>";
                        }
                        if ($error === "orderNotFound") {
                            echo "<p class='alert alert-danger text-center mb-4'>El pedido no existe.</p>";
                        }
                        if ($error === "unauthorized") {
                            echo "<p class='alert alert-danger text-center mb-4'>Este producto ya no está disponible.</p>";
                        }
                        if ($error === "notCancelable") {
                            echo "<p class='alert alert-danger text-center mb-4'>El pedido ya no es cancelable.</p>";
                        }
                    }
                ?>
            </div>

            <?php

            // Pagination and sorting configuration
            $page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
            $perPage = 5;

            // White-list mapping for sorting
            $allowedColumns = [
                "id" => "p.id",
                "date" => "p.fecha",
                "total" => "p.total",
                "status" => "p.estado",
                "user_name" => "u.nombre"
            ];
            
            $sortBy = $_GET["sort_by"] ?? "date";
            $orderField = $allowedColumns[$sortBy] ?? "p.fecha";

            $sortDir = strtoupper($_GET["sort_dir"] ?? "DESC");
            if (!in_array($sortDir, ["ASC", "DESC"])) $sortDir = "DESC";

            // Determine if query should filter by the logged-in user only
            if ($role === "usuario") {
                $userIdOnly = $userId;
            } elseif (isset($_GET["only_mine"]) && $_GET["only_mine"] == 1) {
                $userIdOnly = $userId;
            } else {
                $userIdOnly = null;
            }

            // Fetch orders utilizing the translated Order class methodology
            $orders = getOrders($db, $page, $perPage, $sortBy, $sortDir, $userIdOnly);

            // Count total orders for pagination math
            if ($userIdOnly !== null) {
                $countQuery = $db->prepare("SELECT COUNT(*) AS total FROM pedidos WHERE id_usuario = :id");
                $countQuery->execute([":id" => $userId]);
            } else {
                $countQuery = $db->prepare("SELECT COUNT(*) AS total FROM pedidos");
                $countQuery->execute();
            }

            $totalOrders = $countQuery->fetch(PDO::FETCH_ASSOC)["total"];
            $totalPages = ceil($totalOrders / $perPage);

            // Sorting UI helpers
            function sortIcon(string $col, string $currentSortBy, string $currentSortDir): string {
                if ($col !== $currentSortBy) return '<i class="bi bi-arrow-down-up"></i>';
                return $currentSortDir === "ASC"
                    ? '<i class="bi bi-arrow-up"></i>'
                    : '<i class="bi bi-arrow-down"></i>';
            }

            function sortUrl(string $col, string $currentSortDir, ?int $userIdOnly): string {
                $newDir = $currentSortDir === "ASC" ? "DESC" : "ASC";
                $url = "/orders/order_list.php?sort_by=$col&sort_dir=$newDir";
                
                // Preserve 'only_mine' filter in URL if active for admins/employees
                if ($userIdOnly !== null && isset($_GET["only_mine"])) {
                    $url .= "&only_mine=1";
                }
                return $url;
            }

            ?>

            <div class="table-responsive">
                <table class="jtech-table align-middle text-center mx-auto">
                    <thead>
                        <tr>
                            <th><a href="<?= sortUrl('id', $sortDir, $userIdOnly) ?>">ID <?= sortIcon('id', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('date', $sortDir, $userIdOnly) ?>">Fecha <?= sortIcon('date', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('total', $sortDir, $userIdOnly) ?>">Total <?= sortIcon('total', $sortBy, $sortDir) ?></a></th>
                            <th><a href="<?= sortUrl('status', $sortDir, $userIdOnly) ?>">Estado <?= sortIcon('status', $sortBy, $sortDir) ?></a></th>

                            <?php if ($role !== "usuario"): ?>
                                <th><a href="<?= sortUrl('user_name', $sortDir, $userIdOnly) ?>">Usuario <?= sortIcon('user_name', $sortBy, $sortDir) ?></a></th>
                            <?php endif; ?>

                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        if (empty($orders)) {
                            echo "<tr><td colspan='10' class='text-center py-3 text-muted'>
                                      No se encontraron pedidos
                                  </td></tr>";
                        } else {
                            foreach ($orders as $o) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars((string)$o->getId()) . "</td>";
                                echo "<td>" . date("d/m/Y H:i", strtotime($o->getDate())) . "</td>";
                                echo "<td>" . number_format($o->getTotal(), 2) . " €</td>";
                                echo "<td>" . htmlspecialchars((string)$o->getStatus()) . "</td>";

                                if ($role !== "usuario") {
                                    echo "<td>" . htmlspecialchars((string)$o->getUserName()) . "</td>";
                                }

                                echo "<td>";

                                // Contextual action buttons based on user role and view state
                                if ($role === "usuario" || (isset($_GET["only_mine"]) && $_GET["only_mine"] == 1)) {
                                    if ($o->getStatus() === "En curso") {
                                        // Absolute path for cancelation action
                                        echo "<a href='/orders/order_cancel.php?id=" . $o->getId() . "' class='btn btn-outline-danger btn-sm fw-semibold'
                                                onclick='return confirm(\"¿Seguro que quieres cancelar este pedido?\");'>Cancelar
                                              </a>";
                                    } else {
                                        echo "<span class='text-muted'>—</span>";
                                    }
                                } else {
                                    // Absolute path for editing action
                                    echo "<a class='text-jtech fw-bold' href='/orders/order_edit.php?id=" . $o->getId() . "'>
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

            <!-- Pagination -->
            <div class="text-center mt-4">
                <?php
                for ($i = 1; $i <= $totalPages; $i++) {
                    $url = "/orders/order_list.php?page=$i&sort_by=$sortBy&sort_dir=$sortDir";
                    
                    if ($userIdOnly !== null && isset($_GET["only_mine"])) {
                        $url .= "&only_mine=1";
                    }

                    if ($i == $page) {
                        echo "<button class='btn btn-jtech mx-1'>$i</button>";
                    } else {
                        echo "<a href='$url' class='btn btn-outline-jtech mx-1'>$i</a>";
                    }
                }
                ?>
            </div>

            <div class="text-center text-lg-start mt-3">
                <?php
                    // Dynamic return path based on user context
                    if ($role === "usuario") {
                        $returnPath = "/index.php";
                    } elseif (isset($_GET["only_mine"]) && $_GET["only_mine"] == 1) {
                        $returnPath = "/index.php";
                    } else {
                        $returnPath = "/utils/admin_panel.php";
                    }
                ?>
                <a href="<?= $returnPath ?>" class="btn btn-outline-secondary">Volver</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>