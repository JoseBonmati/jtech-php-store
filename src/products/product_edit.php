<?php 

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    // Restrict access: only administrators or employees can edit products
    if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    // Array to store server validation error messages
    $errorMessages = [];

    // Retrieve Product ID securely
    if (isset($_POST["edit_submit"]) || isset($_POST["toggle_status"])) {
        $id = (int) ($_POST["id"] ?? 0);
    } else {
        $id = (int) ($_GET["id"] ?? 0);
    }

    // Process product activation/deactivation toggle
    if (isset($_POST["toggle_status"])) {
        $currentStatus = $_POST["current_status"] ?? "activo";
        $newStatus = ($currentStatus === "activo") ? "inactivo" : "activo";

        $updateStatusStmt = $db->prepare("UPDATE productos SET estado = :status WHERE id = :id");
        $updateStatusStmt->execute([
            ":status" => $newStatus,
            ":id" => $id
        ]);

        header("Location: /products/product_edit.php?id=" . urlencode((string)$id) . "&status_toggled=1");
        exit;
    }

    // Process form submission for editing product data
    if (isset($_POST["edit_submit"])) {
        $name = trim($_POST["name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $price = trim($_POST["price"] ?? "");
        $stock = trim($_POST["stock"] ?? "");
        $categoryId = trim($_POST["category_id"] ?? "");
        $subcategoryId = trim($_POST["subcategory_id"] ?? "");

        // Input Validations
        if ($name === "") $errorMessages[] = "El campo Nombre no puede estar vacío.";
        if ($description === "") $errorMessages[] = "El campo Descripción no puede estar vacío.";

        if ($price === "") {
            $errorMessages[] = "El campo Precio no puede estar vacío.";
        } elseif (!preg_match("/^[0-9]*\.?[0-9]+$/", $price)) {
            $errorMessages[] = "El formato del precio no es válido.";
        }

        if ($stock === "") {
            $errorMessages[] = "El campo Stock no puede estar vacío.";
        } elseif (!ctype_digit($stock)) {
            $errorMessages[] = "El stock debe ser un número entero.";
        }

        if ($categoryId === "") $errorMessages[] = "Debe seleccionar una categoría.";
        if ($subcategoryId === "") $errorMessages[] = "Debe seleccionar una subcategoría.";

        // Validate that the selected subcategory belongs to the chosen parent category
        if ($categoryId !== "" && $subcategoryId !== "") {
            $checkSubStmt = $db->prepare("SELECT COUNT(*) FROM subcategorias WHERE id = :sub AND id_categoria = :cat");
            $checkSubStmt->execute([
                ":sub" => $subcategoryId,
                ":cat" => $categoryId
            ]);

            if ($checkSubStmt->fetchColumn() == 0) {
                $errorMessages[] = "La subcategoría seleccionada no pertenece a la categoría elegida.";
            }
        }

        // Image validation (Only if a new image is uploaded)
        $newImageUploaded = !empty($_FILES["image"]["tmp_name"]);
        if ($newImageUploaded) {
            $file = $_FILES["image"];
            $tempName = $file["tmp_name"];
            $realName = $file["name"];
            $size = $file["size"];

            $maxSize = 2 * 1024 * 1024;
            if ($size > $maxSize) $errorMessages[] = "La imagen es demasiado grande (máx 2MB).";

            $imgInfo = getimagesize($tempName);
            if (!$imgInfo) {
                $errorMessages[] = "El archivo no es una imagen válida.";
            } else {
                $width = $imgInfo[0];
                $height = $imgInfo[1];
                $ext = strtolower(pathinfo($realName, PATHINFO_EXTENSION));

                if (!in_array($ext, ["jpg","jpeg","png"])) {
                    $errorMessages[] = "Formato no permitido (solo jpg, jpeg, png).";
                }

                if ($width > 600 || $height > 700) {
                    $errorMessages[] = "La imagen no puede superar 600x700 píxeles.";
                }

                // Standardized DB image path for absolute rendering
                $dbImagePath = "assets/imagenes/" . basename($realName);
                
                $checkImgStmt = $db->prepare("SELECT id FROM productos WHERE imagen = :image AND id != :id");
                $checkImgStmt->execute([
                    ":image" => $dbImagePath,
                    ":id" => $id
                ]);
                if ($checkImgStmt->fetch()) {
                    $errorMessages[] = "Ya existe una imagen con ese nombre.";
                }
            }
        }

        // Proceed with update if there are no validation errors
        if (empty($errorMessages)) {

            $sql = "UPDATE productos SET nombre = :name, descripcion = :description, precio = :price, stock = :stock, id_categoria = :category, id_subcategoria = :subcategory";

            $params = [
                ":name" => $name,
                ":description" => $description,
                ":price" => $price,
                ":stock" => $stock,
                ":category" => $categoryId,
                ":subcategory" => $subcategoryId,
                ":id" => $id
            ];

            if ($newImageUploaded) {
                $tmpImage = $_FILES["image"]["tmp_name"];
                $imgName = basename($_FILES["image"]["name"]);
                
                $dbImagePath = "assets/imagenes/" . $imgName;
                $serverPath = __DIR__ . "/../" . $dbImagePath;

                // Delete old image dynamically from the filesystem
                $oldQuery = $db->prepare("SELECT imagen AS image FROM productos WHERE id = :id");
                $oldQuery->execute([":id" => $id]);
                if ($oldRow = $oldQuery->fetch(PDO::FETCH_ASSOC)) {
                    if (!empty($oldRow["image"])) {
                        $oldServerPath = __DIR__ . "/../" . $oldRow["image"];
                        if (file_exists($oldServerPath) && is_file($oldServerPath)) {
                            unlink($oldServerPath);
                        }
                    }
                }

                // Move new image to the unified directory
                if (move_uploaded_file($tmpImage, $serverPath)) {
                    $sql .= ", imagen = :image";
                    $params[":image"] = $dbImagePath;
                } else {
                    $errorMessages[] = "Error inesperado al guardar la imagen en el servidor.";
                }
            }

            $sql .= " WHERE id = :id";

            if (empty($errorMessages)) {
                $updateStmt = $db->prepare($sql);
                $updateStmt->execute($params);

                if ($updateStmt->rowCount() > 0 || $newImageUploaded) {
                    header("Location: /products/product_list.php?updated_product=" . urlencode($name));
                    exit;
                } else {
                    $errorMessages[] = "No se ha podido actualizar el producto o no hubo cambios reales.";
                }
            }
        }
    }

    // Fetch product data with aliases for straightforward rendering
    $prodQuery = $db->prepare("SELECT id, nombre AS name, descripcion AS description, precio AS price, stock, id_categoria AS category_id, id_subcategoria AS subcategory_id, 
                               imagen AS image, estado AS status FROM productos WHERE id = :id");
    $prodQuery->execute([":id" => $id]);
    $product = $prodQuery->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $errorMessages[] = "No se ha encontrado el producto.";
    }

    // Fetch categories and subcategories with aliases
    $categories = $db->query("SELECT id, nombre AS name FROM categorias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    $subcategories = $db->query("SELECT id, id_categoria AS category_id, nombre AS name FROM subcategorias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-start jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 700px;">
            <h2 class="text-center mb-4 fw-bold">Editar producto</h2>

            <div class="mb-3">
                <?php
                    if (isset($_GET["status_toggled"])) {
                        echo "<p class='alert alert-success mb-0'>Estado del producto cambiado.</p>";
                    }
                    if (!empty($errorMessages)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $errorMessages) . "</p>";
                    }
                ?>
            </div>

            <?php if ($product): ?>

            <form method="post" action="/products/product_edit.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$product['id']) ?>">

                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control jtech-input" maxlength="50" value="<?= htmlspecialchars($product['name']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="description" class="form-control jtech-input" value="<?= htmlspecialchars($product['description']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Precio (€)</label>
                    <input type="text" name="price" class="form-control jtech-input" maxlength="10" value="<?= htmlspecialchars((string)$product['price']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" class="form-control jtech-input" value="<?= htmlspecialchars((string)$product['stock']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Categoría</label>
                    <select name="category_id" id="category_id" class="form-select jtech-input">
                        <option value="">-- Seleccione una categoría --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= htmlspecialchars((string)$c['id']) ?>" <?= ($c['id'] == $product['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subcategoría</label>
                    <select name="subcategory_id" id="subcategory_id" class="form-select jtech-input">
                        <option value="">-- Seleccione una subcategoría --</option>
                        <?php foreach ($subcategories as $s): ?>
                            <option value="<?= htmlspecialchars((string)$s['id']) ?>" data-cat="<?= htmlspecialchars((string)$s['category_id']) ?>"
                                <?= ($s['id'] == $product['subcategory_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Imagen actual</label><br>
                    <img src="/<?= htmlspecialchars($product['image']) ?>" class="img-thumbnail" style="max-height:150px;" alt="Current Product Image">
                </div>

                <div class="mb-3">
                    <label class="form-label">Nueva imagen (Opcional)</label>
                    <input type="file" name="image" class="form-control jtech-input" accept=".jpg,.jpeg,.png">
                </div>

                <div class="d-grid gap-3">
                    <button type="submit" name="edit_submit" class="btn btn-jtech fw-semibold">Guardar cambios</button>
                    <hr class="jtech-divider">
                    <a href="/products/product_list.php" class="btn btn-outline-secondary">Volver</a>
                </div>
            </form>

            <form method="post" action="/products/product_edit.php" class="mt-3">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$product['id']) ?>">
                <input type="hidden" name="current_status" value="<?= htmlspecialchars($product['status']) ?>">

                <div class="d-grid gap-3">
                    <?php if ($product['status'] === "activo"): ?>
                        <button type="submit" name="toggle_status" class="btn btn-danger fw-semibold">
                            Desactivar producto
                        </button>
                    <?php else: ?>
                        <button type="submit" name="toggle_status" class="btn btn-success fw-semibold">
                            Activar producto
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <?php endif; ?>
        </div>
    </div>

    <!-- Filter subcategories -->
    <script>
        const categorySelect = document.getElementById("category_id");
        const subcategorySelect = document.getElementById("subcategory_id");

        function filterSubcategories() {
            const catId = categorySelect.value;

            for (let opt of subcategorySelect.options) {
                if (!opt.dataset.cat) continue; // Skip default option
                opt.hidden = opt.dataset.cat !== catId;
            }
        }

        categorySelect.addEventListener("change", () => {
            filterSubcategories();
            subcategorySelect.value = "";
        });

        // Initialize state on load
        filterSubcategories();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>