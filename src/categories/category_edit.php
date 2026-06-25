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

    // Retrieve Category ID from POST or GET requests safely
    if (isset($_POST["edit_submit"]) || isset($_POST["toggle_status"]) || isset($_POST["sub_action"])) {
        $id = (int) ($_POST["id"] ?? 0);
    } else {
        $id = (int) ($_GET["id"] ?? 0);
    }

    // Process category activation/deactivation toggle
    if (isset($_POST["toggle_status"])) {
        $currentStatus = $_POST["current_status"] ?? "activo";
        $newStatus = ($currentStatus === "activo") ? "inactivo" : "activo";

        $statusStmt = $db->prepare("UPDATE categorias SET estado = :status WHERE id = :id");
        $statusStmt->execute([
            ":status" => $newStatus,
            ":id" => $id
        ]);

        header("Location: /categories/category_edit.php?id=$id");
        exit;
    }

    // Process operational actions on associated subcategories
    if (isset($_POST["sub_action"])) {
        $subId = (int) $_POST["sub_category_id"];
        $action = $_POST["sub_action"];
        $subStatus = $_POST["sub_status"] ?? "activo";

        if ($action === "editar") {
            header("Location: /subcategories/subcategory_edit.php?id=$subId");
            exit;
        }

        if ($action === "eliminar") {
            header("Location: /subcategories/subcategory_delete.php?id=$subId");
            exit;
        }

        if ($action === "toggle") {
            $newStatus = ($subStatus === "activo") ? "inactivo" : "activo";

            $updateSubStmt = $db->prepare("UPDATE subcategorias SET estado = :status WHERE id = :id");
            $updateSubStmt->execute([
                ":status" => $newStatus,
                ":id" => $subId
            ]);

            header("Location: /categories/category_edit.php?id=$id");
            exit;
        }
    }

    // Process category basic data update form
    if (isset($_POST["edit_submit"])) {
        $name = trim($_POST["name"] ?? "");

        // Input data validation
        if ($name === "") {
            $errorMessages[] = "El campo Nombre no puede estar vacío.";
        }

        // Check for unique name constraints excluding the current entry
        if (empty($errorMessages)) {
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM categorias WHERE nombre = :name AND id != :id");
            $checkStmt->execute([
                ":name" => $name,
                ":id" => $id
            ]);

            if ($checkStmt->fetchColumn() > 0) {
                $errorMessages[] = "Ya existe otra categoría con ese nombre.";
            }
        }

        // Persist update if no errors are present
        if (empty($errorMessages)) {
            $updateStmt = $db->prepare("UPDATE categorias SET nombre = :name WHERE id = :id");
            $updateStmt->execute([
                ":name" => $name,
                ":id" => $id
            ]);

            header("Location: /categories/category_list.php?updated_category=" . urlencode($name));
            exit;
        }
    }

    // Fetch current data state for category formulation
    $query = $db->prepare("SELECT id, nombre, estado FROM categorias WHERE id = :id");
    $query->execute([":id" => $id]);

    if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $fetchedName = $row["nombre"];
        $fetchedStatus = $row["estado"];
    } else {
        $errorMessages[] = "No se ha encontrado la categoría con el ID proporcionado.";
    }

    // Fetch all child subcategories belonging to this category context
    $subQuery = $db->prepare("SELECT id, nombre, estado FROM subcategorias WHERE id_categoria = :id ORDER BY nombre ASC");
    $subQuery->execute([":id" => $id]);
    $subcategories = $subQuery->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar categoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-start jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 700px;">
            <h2 class="text-center mb-4 fw-bold">Editar categoría</h2>

            <div class="mb-3">
                <?php
                    if (!empty($errorMessages)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $errorMessages) . "</p>";
                    }
                ?>
            </div>

            <?php if (empty($errorMessages) || isset($_POST["edit_submit"])): ?>

            <form method="post" action="/categories/category_edit.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>">

                <p class="mb-4 text-center fw-semibold">Modifica los datos de la categoría y guarda los cambios.</p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre de la categoría</label>
                    <input type="text" name="name" id="name" class="form-control jtech-input" maxlength="100" value="<?= htmlspecialchars($fetchedName ?? '') ?>">
                </div>

                <div class="d-grid gap-3 mt-4">
                    <button type="submit" name="edit_submit" class="btn btn-jtech fw-semibold">Guardar cambios</button>
                </div>
            </form>

            <form method="post" action="/categories/category_edit.php" class="mt-3">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>">
                <input type="hidden" name="current_status" value="<?= htmlspecialchars($fetchedStatus ?? '') ?>">

                <button type="submit" name="toggle_status"
                        class="btn <?= (isset($fetchedStatus) && $fetchedStatus === 'activo') ? 'btn-warning' : 'btn-success' ?> fw-semibold w-100">
                    <?= (isset($fetchedStatus) && $fetchedStatus === 'activo') ? 'Desactivar categoría' : 'Activar categoría' ?>
                </button>
            </form>

            <h4 class="fw-bold mt-4">Subcategorías de esta categoría</h4>

            <form method="post" action="/categories/category_edit.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Selecciona una subcategoría</label>
                    <select name="sub_category_id" class="form-select jtech-input" required>
                        <option value="">Seleccione una subcategoría</option>
                        <?php foreach ($subcategories as $sub): ?>
                            <option value="<?= htmlspecialchars((string)$sub['id']) ?>">
                                <?= htmlspecialchars($sub['nombre']) ?> (<?= htmlspecialchars($sub['estado']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="hidden" name="sub_status" id="sub_status">

                <div class="d-grid gap-2">
                    <button type="submit" name="sub_action" value="editar" class="btn btn-jtech fw-semibold">
                        Editar subcategoría
                    </button>

                    <?php if ($_SESSION["rol"] === "administrador"): ?>
                        <button type="submit" name="sub_action" value="eliminar" class="btn btn-danger fw-semibold" onclick="return confirm('¿Seguro que deseas eliminar esta subcategoría?');">
                            Eliminar subcategoría
                        </button>
                    <?php endif; ?>

                    <button type="submit" name="sub_action" value="toggle" class="btn btn-warning fw-semibold">
                        Activar / Desactivar subcategoría
                    </button>
                </div>

                <hr class="jtech-divider my-3">
                <a href="/categories/category_list.php" class="btn btn-outline-secondary fw-semibold w-100">Volver</a>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Dynamically track and update the state hidden field for the selected subcategory context
        document.querySelector("select[name='sub_category_id']").addEventListener("change", function() {
            const selected = this.options[this.selectedIndex].text;
            const status = selected.includes("(activo)") ? "activo" : "inactivo";
            document.getElementById("sub_status").value = status;
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>