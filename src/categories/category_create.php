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

    // Array to store error messages
    $errorMessages = [];

    // Check if form was submitted
    if (isset($_POST["create_submit"])) {
        $name = trim($_POST["name"] ?? "");

        // Validate name
        if ($name === "") {
            $errorMessages[] = "El campo Nombre no puede estar vacío.";
        }

        // Check if the category already exists
        if (empty($errorMessages)) {
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM categorias WHERE nombre = :name");
            $checkStmt->execute([":name" => $name]);

            if ($checkStmt->fetchColumn() > 0) {
                $errorMessages[] = "La categoría ya está registrada, use otro nombre.";
            }
        }

        // Insert new category if no errors
        if (empty($errorMessages)) {
            $insertStmt = $db->prepare("INSERT INTO categorias (nombre, estado) VALUES (:name, 'activo')");
            $insertStmt->execute([":name" => $name]);

            if ($insertStmt->rowCount() > 0) {
                // Redirect with success parameter
                header("Location: /categories/category_list.php?created_category=" . urlencode($name));
                exit;
            } else {
                $errorMessages[] = "Ha ocurrido un error con la base de datos.";
            }
        }
    }

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva categoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 500px;">
            
            <h2 class="text-center mb-4 fw-bold">Crear categoría</h2>

            <!-- Server errors -->
            <div class="mb-3">
                <?php
                    if (!empty($errorMessages)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $errorMessages) . "</p>";
                    }
                ?>
            </div>

            <form method="post" action="/categories/category_create.php">
                <p class="mb-3 text-center fw-semibold">Rellena los siguientes datos para crear una nueva categoría.</p>

                <div class="mb-3">
                    <label for="name" class="form-label">Nombre de la categoría</label>
                    <input type="text" class="form-control jtech-input" name="name" id="name" maxlength="100"
                           value="<?php if(isset($_POST['name'])) echo htmlspecialchars($_POST['name']); ?>">
                </div>

                <div class="d-grid gap-3">
                    <button type="submit" class="btn fw-semibold btn-jtech" name="create_submit">Crear categoría</button>
                    <hr class="jtech-divider">
                    <a href="/categories/category_list.php" class="btn btn-outline-secondary">Volver</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>