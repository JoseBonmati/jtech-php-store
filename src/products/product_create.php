<?php 

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    // Restrict access: only administrators or employees can create products
    if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    // Array to store server validation error messages
    $errorMessages = [];

    // Process form submission
    if (isset($_POST["create_submit"])) {
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

        // Image upload validation
        if (empty($_FILES["image"]["tmp_name"])) {
            $errorMessages[] = "Debe subir una imagen.";
        } else {
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
                
                $checkImgStmt = $db->prepare("SELECT id FROM productos WHERE imagen = :image");
                $checkImgStmt->execute([":image" => $dbImagePath]);
                if ($checkImgStmt->fetch()) {
                    $errorMessages[] = "Ya existe una imagen con ese nombre en la base de datos.";
                }
            }
        }

        // Insert new product if there are no validation errors
        if (empty($errorMessages)) {
            $tmpImage = $_FILES["image"]["tmp_name"];
            $imgName = basename($_FILES["image"]["name"]);
            $dbImagePath = "assets/imagenes/" . $imgName;
            
            // Calculate absolute server path to move the uploaded file securely
            $serverPath = __DIR__ . "/../" . $dbImagePath;

            if (move_uploaded_file($tmpImage, $serverPath)) {

                $insertStmt = $db->prepare("INSERT INTO productos (nombre, id_categoria, id_subcategoria, descripcion, precio, stock, imagen, estado)
                                            VALUES (:name, :category, :subcategory, :description, :price, :stock, :image, 'activo')");

                $insertStmt->execute([
                    ":name" => $name,
                    ":category" => $categoryId,
                    ":subcategory" => $subcategoryId,
                    ":description" => $description,
                    ":price" => $price,
                    ":stock" => $stock,
                    ":image" => $dbImagePath
                ]);

                if ($insertStmt->rowCount() > 0) {
                    // Redirect to product list with translated success parameter
                    header("Location: /products/product_list.php?created_product=" . urlencode($name));
                    exit;
                } else {
                    $errorMessages[] = "Ha ocurrido un error con la base de datos.";
                }
            } else {
                $errorMessages[] = "Error al guardar la imagen en el servidor.";
            }
        }
    }

    // Fetch categories with aliases
    $catQuery = $db->query("SELECT id, nombre AS name FROM categorias ORDER BY nombre ASC");
    $categories = $catQuery->fetchAll(PDO::FETCH_ASSOC);

    // Fetch subcategories with aliases
    $subQuery = $db->query("SELECT id, id_categoria AS category_id, nombre AS name FROM subcategorias ORDER BY nombre ASC");
    $subcategories = $subQuery->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center jtech-bg">
        <div class="p-4 jtech-card">
            <h2 class="text-center mb-4 fw-bold">Crear producto</h2>

            <?php if (!empty($errorMessages)): ?>
                <p class="alert alert-danger"><?= implode("<br>", $errorMessages) ?></p>
            <?php endif; ?>

            <form method="post" action="/products/product_create.php" enctype="multipart/form-data">

                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" class="form-control jtech-input" name="name" maxlength="50" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control jtech-input" name="description" value="<?= htmlspecialchars($_POST['description'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Precio (€)</label>
                    <input type="text" class="form-control jtech-input" name="price" maxlength="10" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Stock</label>
                    <input type="number" class="form-control jtech-input" name="stock" value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Categoría</label>
                    <select name="category_id" id="category_id" class="form-select jtech-input">
                        <option value="">-- Seleccione una categoría --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= htmlspecialchars((string)$c['id']) ?>" <?= (($_POST['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
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
                            <option value="<?= htmlspecialchars((string)$s['id']) ?>" data-cat="<?= htmlspecialchars((string)$s['category_id']) ?>">
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Imagen</label>
                    <input type="file" class="form-control jtech-input" name="image" accept=".jpg,.jpeg,.png">
                </div>

                <div class="d-grid gap-3">
                    <button type="submit" name="create_submit" class="btn btn-jtech fw-semibold">Crear producto</button>
                    <hr class="jtech-divider">
                    <a href="/products/product_list.php" class="btn btn-outline-secondary">Volver</a>
                </div>

            </form>
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