<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    // Restrict access: only administrators can view this page
    if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "administrador") {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    // Array to store server validation error messages
    $errorMessages = [];

    $name = "";
    $id = null;

    // Fetch subcategory data by ID for confirmation details
    if (isset($_GET["id"])) {
        $id = (int) $_GET["id"];

        $query = $db->prepare("SELECT nombre FROM subcategorias WHERE id = :id");
        $query->execute([":id" => $id]);

        if ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $name = $data["nombre"];
        } else {
            $errorMessages[] = "No se ha encontrado la subcategoría con el ID proporcionado.";
        }
    }

    // Process operational deletion form submission
    if (isset($_POST["delete_submit"])) {
        $id = (int) $_POST["id"];

        $checkQuery = $db->prepare("SELECT nombre FROM subcategorias WHERE id = :id");
        $checkQuery->execute([":id" => $id]);

        if ($data = $checkQuery->fetch(PDO::FETCH_ASSOC)) {
            $name = $data["nombre"];

            $deleteQuery = $db->prepare("DELETE FROM subcategorias WHERE id = :id");
            $deleteQuery->execute([":id" => $id]);

            if ($deleteQuery->rowCount() > 0) {
                // Redirect to category listing page with query param
                header("Location: /categories/category_list.php?deleted_subcategory=" . urlencode($name));
                exit;
            } else {
                $errorMessages[] = "No se ha podido eliminar la subcategoría.";
            }
        } else {
            $errorMessages[] = "La subcategoría indicada no existe.";
        }
    }

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar subcategoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-start jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 600px;">
            <h2 class="text-center mb-4 fw-bold">Eliminar subcategoría</h2>

            <!-- Error messages -->
            <div class="mb-3">
                <?php
                    if (!empty($errorMessages)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $errorMessages) . "</p>";
                    }
                ?>
            </div>

            <?php if (empty($errorMessages) && $id !== null): ?>
                <p class="text-center fw-semibold mb-4">
                    ¿Desea eliminar la subcategoría<br>
                    <span class="text-jtech fw-bold"><?= htmlspecialchars($name) ?></span>?
                </p>

                <form method="post" action="/subcategories/subcategory_delete.php">
                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>">

                    <div class="d-grid gap-3">
                        <button type="submit" name="delete_submit" class="btn btn-danger fw-semibold" onclick="return confirm('¿Seguro que deseas eliminar esta subcategoría?');">
                            Eliminar subcategoría
                        </button>
                        <hr class="jtech-divider">
                        <a href="/categories/category_list.php" class="btn btn-outline-secondary fw-semibold">Volver</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>