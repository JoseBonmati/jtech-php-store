<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    // Restrict access: only administrators or employees can view this page
    if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    // Array to store server validation error messages
    $errorMessages = [];

    // Retrieve Subcategory ID from POST or GET requests safely
    if (isset($_POST["edit_submit"])) {
        $id = (int) ($_POST["id"] ?? 0);
    } else {
        $id = (int) ($_GET["id"] ?? 0);
    }

    // Process form submission (update subcategory data)
    if (isset($_POST["edit_submit"])) {
        $name = trim($_POST["name"] ?? "");
        $categoryId = (int) ($_POST["category_id"] ?? 0);

        // Input validation
        if ($name === "") {
            $errorMessages[] = "El campo Nombre no puede estar vacío.";
        }

        if ($categoryId <= 0) {
            $errorMessages[] = "Debe seleccionar una categoría válida.";
        }

        // Check for duplicate names within the same parent category context
        if (empty($errorMessages)) {
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM subcategorias WHERE nombre = :name AND id_categoria = :category_id AND id != :id");
            $checkStmt->execute([
                ":name" => $name,
                ":category_id" => $categoryId,
                ":id" => $id
            ]);

            if ($checkStmt->fetchColumn() > 0) {
                $errorMessages[] = "Ya existe otra subcategoría con ese nombre en esta categoría.";
            }
        }

        // Persist update data if no errors are found
        if (empty($errorMessages)) {
            $updateStmt = $db->prepare("UPDATE subcategorias SET nombre = :name, id_categoria = :category_id WHERE id = :id");
            $updateStmt->execute([
                ":name" => $name,
                ":category_id" => $categoryId,
                ":id" => $id
            ]);

            // Redirect back to parent category edit view with standardized success parameter
            header("Location: /categories/category_edit.php?id=$categoryId&updated_subcategory=" . urlencode($name));
            exit;
        }
    }

    // Fetch current state data for subcategory formulation
    $query = $db->prepare("SELECT id, nombre, id_categoria FROM subcategorias WHERE id = :id");
    $query->execute([":id" => $id]);

    if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $fetchedName = $row["nombre"];
        $fetchedCategoryId = $row["id_categoria"];
    } else {
        $errorMessages[] = "No se ha encontrado la subcategoría con el ID proporcionado.";
    }

    // Fetch all parent categories for the selection dropdown list
    $catQuery = $db->prepare("SELECT id, nombre AS name FROM categorias ORDER BY nombre ASC");
    $catQuery->execute();
    $categories = $catQuery->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar subcategoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-start jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 600px;">
            <h2 class="text-center mb-4 fw-bold">Editar subcategoría</h2>

            <!-- Server errors -->
            <div class="mb-3">
                <?php
                    if (!empty($errorMessages)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $errorMessages) . "</p>";
                    }
                ?>
            </div>

            <?php if (empty($errorMessages) || isset($_POST["edit_submit"])): ?>

            <form method="post" action="/subcategories/subcategory_edit.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>">

                <p class="mb-4 text-center fw-semibold">
                    Modifica los datos de la subcategoría y guarda los cambios.
                </p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre de la subcategoría</label>
                    <input type="text" name="name" id="name" class="form-control jtech-input" maxlength="100" value="<?= htmlspecialchars($fetchedName ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Categoría padre</label>
                    <select name="category_id" class="form-select jtech-input">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars((string)$cat['id']) ?>" 
                                <?= $cat['id'] == ($fetchedCategoryId ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-grid gap-3 mt-4">
                    <button type="submit" name="edit_submit" class="btn btn-jtech fw-semibold">Guardar cambios</button>
                    <hr class="jtech-divider">
                    <a href="/categories/category_edit.php?id=<?= htmlspecialchars((string)($fetchedCategoryId ?? 0)) ?>" 
                       class="btn btn-outline-secondary fw-semibold">Volver</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>