<?php 

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    // Administradores y empleados pueden crear productos
    if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    // Array de errores
    $mensajesError = [];

    if (isset($_POST["enviar"])) {
        $nombre = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $precio = trim($_POST["precio"] ?? "");
        $stock = trim($_POST["stock"] ?? "");
        $categoriaId = trim($_POST["categoriaId"] ?? "");
        $subcategoriaId = trim($_POST["subcategoriaId"] ?? "");

        // Validaciones
        if ($nombre === "") $mensajesError[] = "El campo Nombre no puede estar vacío.";
        if ($descripcion === "") $mensajesError[] = "El campo Descripción no puede estar vacío.";

        if ($precio === "") {
            $mensajesError[] = "El campo Precio no puede estar vacío.";
        } elseif (!preg_match("/^[0-9]*\.?[0-9]+$/", $precio)) {
            $mensajesError[] = "El formato del precio no es válido.";
        }

        if ($stock === "") {
            $mensajesError[] = "El campo Stock no puede estar vacío.";
        } elseif (!ctype_digit($stock)) {
            $mensajesError[] = "El stock debe ser un número entero.";
        }

        if ($categoriaId === "") $mensajesError[] = "Debe seleccionar una categoría.";
        if ($subcategoriaId === "") $mensajesError[] = "Debe seleccionar una subcategoría.";

        // Validar que la subcategoría pertenece a la categoría seleccionada
        if ($categoriaId !== "" && $subcategoriaId !== "") {
            $checkSub = $con->prepare("SELECT COUNT(*) FROM subcategorias WHERE id = :sub AND id_categoria = :cat");
            $checkSub->execute([
                ":sub" => $subcategoriaId,
                ":cat" => $categoriaId
            ]);

            if ($checkSub->fetchColumn() == 0) {
                $mensajesError[] = "La subcategoría seleccionada no pertenece a la categoría elegida.";
            }
        }

        // Validación de imagen
        if (empty($_FILES["imagen"]["tmp_name"])) {
            $mensajesError[] = "Debe subir una imagen.";
        } else {
            $file = $_FILES["imagen"];
            $temp_name = $file["tmp_name"];
            $real_name = $file["name"];
            $size = $file["size"];

            $max_size = 2 * 1024 * 1024;
            if ($size > $max_size) $mensajesError[] = "La imagen es demasiado grande (máx 2MB).";

            $img_info = getimagesize($temp_name);
            if (!$img_info) {
                $mensajesError[] = "El archivo no es una imagen válida.";
            } else {
                $width = $img_info[0];
                $height = $img_info[1];
                $ext = strtolower(pathinfo($real_name, PATHINFO_EXTENSION));

                if (!in_array($ext, ["jpg","jpeg","png"])) {
                    $mensajesError[] = "Formato no permitido (solo jpg, jpeg, png).";
                }

                if ($width > 600 || $height > 700) {
                    $mensajesError[] = "La imagen no puede superar 600x700 píxeles.";
                }

                $destinationC = "../assets/imagenes/" . basename($real_name);
                $query = $con->prepare("SELECT id FROM productos WHERE imagen = :imagen");
                $query->execute([":imagen" => $destinationC]);
                if ($query->fetch()) {
                    $mensajesError[] = "Ya existe una imagen con ese nombre en la base de datos.";
                }
            }
        }

        // Insertar si no hay errores
        if (empty($mensajesError)) {
            $tmpImage = $_FILES["imagen"]["tmp_name"];
            $imgName = $_FILES["imagen"]["name"];
            $destination = "../assets/imagenes/" . $imgName;

            if (move_uploaded_file($tmpImage, $destination)) {

                $stmt = $con->prepare("INSERT INTO productos (nombre, id_categoria, id_subcategoria, descripcion, precio, stock, imagen, estado)
                                       VALUES (:nombre, :categoria, :subcategoria, :descripcion, :precio, :stock, :imagen, 'activo')");

                $stmt->execute([
                    ":nombre" => $nombre,
                    ":categoria" => $categoriaId,
                    ":subcategoria" => $subcategoriaId,
                    ":descripcion" => $descripcion,
                    ":precio" => $precio,
                    ":stock" => $stock,
                    ":imagen" => $destination
                ]);

                if ($stmt->rowCount() > 0) {
                    header("Location: productoConsulta.php?nombreN=" . urlencode($nombre));
                    exit;
                } else {
                    $mensajesError[] = "Ha ocurrido un error con la base de datos.";
                }
            } else {
                $mensajesError[] = "Error al subir la imagen.";
            }
        }
    }

    // Obtener categorías
    $catQuery = $con->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = $catQuery->fetchAll(PDO::FETCH_ASSOC);

    // Obtener subcategorías
    $subQuery = $con->query("SELECT id, id_categoria, nombre FROM subcategorias ORDER BY nombre ASC");
    $subcategorias = $subQuery->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center jtech-bg">
        <div class="p-4 jtech-card">
            <h2 class="text-center mb-4 fw-bold">Crear producto</h2>

            <?php if (!empty($mensajesError)): ?>
                <p class="alert alert-danger"><?= implode("<br>", $mensajesError) ?></p>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">

                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" class="form-control jtech-input" name="nombre" maxlength="50" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control jtech-input" name="descripcion" value="<?= htmlspecialchars($_POST['descripcion'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Precio (€)</label>
                    <input type="text" class="form-control jtech-input" name="precio" maxlength="10" value="<?= htmlspecialchars($_POST['precio'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Stock</label>
                    <input type="number" class="form-control jtech-input" name="stock" value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Categoría</label>
                    <select name="categoriaId" id="categoriaId" class="form-select jtech-input">
                        <option value="">-- Seleccione una categoría --</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (($_POST['categoriaId'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subcategoría</label>
                    <select name="subcategoriaId" id="subcategoriaId" class="form-select jtech-input">
                        <option value="">-- Seleccione una subcategoría --</option>

                        <?php foreach ($subcategorias as $s): ?>
                            <option value="<?= $s['id'] ?>" data-cat="<?= $s['id_categoria'] ?>">
                                <?= htmlspecialchars($s['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Imagen</label>
                    <input type="file" class="form-control jtech-input" name="imagen" accept=".jpg,.jpeg,.png">
                </div>

                <div class="d-grid gap-3">
                    <button type="submit" name="enviar" class="btn btn-jtech fw-semibold">Crear producto</button>
                    <hr class="jtech-divider">
                    <a href="productoConsulta.php" class="btn btn-outline-secondary">Volver</a>
                </div>

            </form>
        </div>
    </div>

    <!-- Filtrar subcategorías -->
    <script>
        const categoria = document.getElementById("categoriaId");
        const subcategoria = document.getElementById("subcategoriaId");

        function filtrarSubcategorias() {
            const cat = categoria.value;

            for (let opt of subcategoria.options) {
                if (!opt.dataset.cat) continue;
                opt.hidden = opt.dataset.cat !== cat;
            }
        }

        categoria.addEventListener("change", () => {
            filtrarSubcategorias();
            subcategoria.value = "";
        });

        filtrarSubcategorias();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
