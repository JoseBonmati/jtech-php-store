<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    // Restrict access: only administrators can delete products
    if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "administrador") {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    // Array to store server validation error messages
    $errorMessages = [];

    $name = "";
    $image = "";
    $id = null;

    // Fetch product data for display
    if (isset($_GET["id"])) {
        $id = (int) $_GET["id"];

        // Use English aliases to map properties consistently
        $query = $db->prepare("SELECT nombre AS name, imagen AS image FROM productos WHERE id = :id");
        $query->execute([":id" => $id]);

        if ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $name = $data["name"];
            $image = $data["image"];
        } else {
            $errorMessages[] = "No se ha encontrado el producto con el ID proporcionado.";
        }
    }

    // Process actual deletion operation
    if (isset($_POST["delete_submit"])) {
        $id = (int) $_POST["id"];

        $checkStmt = $db->prepare("SELECT nombre AS name, imagen AS image FROM productos WHERE id = :id");
        $checkStmt->execute([":id" => $id]);

        if ($data = $checkStmt->fetch(PDO::FETCH_ASSOC)) {
            $name = $data["name"];
            $image = $data["image"];

            // Calculate absolute server path to safely remove the file from the filesystem
            if (!empty($image)) {
                $serverPath = __DIR__ . "/../" . $image;
                if (file_exists($serverPath) && is_file($serverPath)) {
                    unlink($serverPath);
                }
            }

            // Remove product record from database
            $deleteStmt = $db->prepare("DELETE FROM productos WHERE id = :id");
            $deleteStmt->execute([":id" => $id]);

            if ($deleteStmt->rowCount() > 0) {
                // Redirect to product list with query param
                header("Location: /products/product_list.php?deleted_product=" . urlencode($name));
                exit;
            } else {
                $errorMessages[] = "No se ha podido eliminar el producto.";
            }

        } else {
            $errorMessages[] = "El producto indicado no existe.";
        }
    }

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-start jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 600px;">
            <h2 class="text-center mb-4 fw-bold">Eliminar producto</h2>

            <!-- Errors -->
            <div class="mb-3">
                <?php 
                    if (!empty($errorMessages)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $errorMessages) . "</p>";
                    } 
                ?>
            </div>

            <?php if (empty($errorMessages) && $id !== null): ?>
                <p class="text-center fw-semibold mb-4">
                    ¿Desea eliminar el producto<br>
                    <span class="text-jtech fw-bold"><?= htmlspecialchars($name) ?></span>?
                </p>

                <?php if (!empty($image)): ?>
                    <div class="text-center mb-3">
                        <img src="/<?= htmlspecialchars($image) ?>" class="img-thumbnail" style="max-height: 150px;" alt="Product preview">
                    </div>
                <?php endif; ?>

                <form method="post" action="/products/product_delete.php">
                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>">

                    <div class="d-grid gap-3">
                        <button type="submit" name="delete_submit" class="btn btn-danger fw-semibold" onclick="return confirm('¿Seguro que deseas eliminar este producto?');">
                            Eliminar producto
                        </button>
                        <hr class="jtech-divider">
                        <a href="/products/product_list.php" class="btn btn-outline-secondary fw-semibold">Volver</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>