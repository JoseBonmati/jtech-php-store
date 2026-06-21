<?php 

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    // Administradores y empleados pueden editar productos
    if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    // Array de errores
    $mensajesError = [];

    // Obtener ID del producto
    if (isset($_POST["enviar"])) {
        $id = (int) ($_POST["id"] ?? 0);
    } else {
        $id = (int) ($_GET["id"] ?? 0);
    }

    // Procesar formulario
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
        $newImageUploaded = !empty($_FILES["imagen"]["tmp_name"]);
        if ($newImageUploaded) {
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
                    $mensajesError[] = "Ya existe una imagen con ese nombre.";
                }
            }
        }

        // Actualizar si no hay errores
        if (empty($mensajesError)) {

            $sql = "UPDATE productos SET nombre = :nombre, descripcion = :descripcion, precio = :precio, stock = :stock, id_categoria = :categoria, id_subcategoria = :subcategoria";

            $params = [
                ":nombre" => $nombre,
                ":descripcion" => $descripcion,
                ":precio" => $precio,
                ":stock" => $stock,
                ":categoria" => $categoriaId,
                ":subcategoria" => $subcategoriaId,
                ":id" => $id
            ];

            if ($newImageUploaded) {
                $tmpImage = $_FILES["imagen"]["tmp_name"];
                $imgName = $_FILES["imagen"]["name"];
                $destination = "../assets/imagenes/" . $imgName;

                // Delete old image
                $oldQuery = $con->prepare("SELECT imagen FROM productos WHERE id = :id");
                $oldQuery->execute([":id" => $id]);
                if ($oldRow = $oldQuery->fetch()) {
                    if (!empty($oldRow["imagen"]) && file_exists($oldRow["imagen"])) {
                        unlink($oldRow["imagen"]);
                    }
                }

                if (move_uploaded_file($tmpImage, $destination)) {
                    $sql .= ", imagen = :imagen";
                    $params[":imagen"] = $destination;
                } else {
                    $mensajesError[] = "Error inesperado al subir la imagen.";
                }
            }

            $sql .= " WHERE id = :id";

            if (empty($mensajesError)) {
                $update = $con->prepare($sql);
                $update->execute($params);

                if ($update->rowCount() > 0) {
                    header("Location: productoConsulta.php?nombreE=" . urlencode($nombre));
                    exit;
                } else {
                    $mensajesError[] = "No se ha podido actualizar el producto.";
                }
            }
        }
    }

    // Activar / desactivar producto
    if (isset($_POST["cambiarEstado"])) {
        $id = (int)$_POST["id"];
        $nuevoEstado = $_POST["estado"] === "activo" ? "inactivo" : "activo";

        $updateEstado = $con->prepare("UPDATE productos SET estado = :estado WHERE id = :id");
        $updateEstado->execute([
            ":estado" => $nuevoEstado,
            ":id" => $id
        ]);

        header("Location: productoEditar.php?id=" . urlencode($id) . "&estadoCambiado=" . urlencode($nuevoEstado));
        exit;
    }

    // Obtener datos del producto
    $prod = $con->prepare("SELECT * FROM productos WHERE id = :id");
    $prod->execute([":id" => $id]);
    $producto = $prod->fetch();

    if (!$producto) {
        $mensajesError[] = "No se ha encontrado el producto.";
    }

    // Obtener categorías y subcategorías
    $categorias = $con->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    $subcategorias = $con->query("SELECT id, id_categoria, nombre FROM subcategorias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-start jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 700px;">
            <h2 class="text-center mb-4 fw-bold">Editar producto</h2>

            <div class="mb-3">
                <?php
                    if (isset($_GET["estadoCambiado"])) {
                        echo "<p class='alert alert-success mb-0'>Estado del producto cambiado.</p>";
                    }
                    if (!empty($mensajesError)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $mensajesError) . "</p>";
                    }
                ?>
            </div>

            <?php if ($producto): ?>

            <form method="post" enctype="multipart/form-data">

                <input type="hidden" name="id" value="<?= $producto['id'] ?>">

                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control jtech-input" maxlength="50" value="<?= htmlspecialchars($producto['nombre']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="descripcion" class="form-control jtech-input" value="<?= htmlspecialchars($producto['descripcion']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Precio (€)</label>
                    <input type="text" name="precio" class="form-control jtech-input" maxlength="10" value="<?= htmlspecialchars($producto['precio']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" class="form-control jtech-input" value="<?= htmlspecialchars($producto['stock']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Categoría</label>
                    <select name="categoriaId" id="categoriaId" class="form-select jtech-input">
                        <option value="">-- Seleccione una categoría --</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($c['id'] == $producto['id_categoria']) ? 'selected' : '' ?>>
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
                            <option value="<?= $s['id'] ?>" data-cat="<?= $s['id_categoria'] ?>"
                                <?= ($s['id'] == $producto['id_subcategoria']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Imagen actual</label><br>
                    <img src="<?= $producto['imagen'] ?>" class="img-thumbnail" style="max-height:150px;">
                </div>

                <div class="mb-3">
                    <label class="form-label">Nueva imagen</label>
                    <input type="file" name="imagen" class="form-control jtech-input" accept=".jpg,.jpeg,.png">
                </div>

                <div class="d-grid gap-3">

                    <!-- Botón activar/desactivar -->
                    <form method="post">
                        <input type="hidden" name="id" value="<?= $producto['id'] ?>">
                        <input type="hidden" name="estado" value="<?= $producto['estado'] ?>">

                        <?php if ($producto['estado'] === "activo"): ?>
                            <button type="submit" name="cambiarEstado" class="btn btn-danger fw-semibold">
                                Desactivar producto
                            </button>
                        <?php else: ?>
                            <button type="submit" name="cambiarEstado" class="btn btn-success fw-semibold">
                                Activar producto
                            </button>
                        <?php endif; ?>
                    </form>

                    <button type="submit" name="enviar" class="btn btn-jtech fw-semibold">Guardar cambios</button>
                    <hr class="jtech-divider">
                    <a href="productoConsulta.php" class="btn btn-outline-secondary">Volver</a>
                </div>

            </form>

            <?php endif; ?>
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