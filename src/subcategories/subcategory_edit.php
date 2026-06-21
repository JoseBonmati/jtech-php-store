<?php

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    // Restringir acceso: administradores o empleados
    if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    // Array para almacenar errores
    $mensajesError = [];

    // Obtener ID de la subcategoría desde POST o GET
    if (isset($_POST["enviar"])) {
        $id = (int) ($_POST["id"] ?? 0);
    } else {
        $id = (int) ($_GET["id"] ?? 0);
    }

    // Procesar formulario (actualizar subcategoría)
    if (isset($_POST["enviar"])) {
        $nombre = trim($_POST["nombre"] ?? "");
        $idCategoria = (int) ($_POST["id_categoria"] ?? 0);

        // Validación
        if ($nombre === "") {
            $mensajesError[] = "El campo Nombre no puede estar vacío.";
        }

        if ($idCategoria <= 0) {
            $mensajesError[] = "Debe seleccionar una categoría válida.";
        }

        // Comprobar duplicados dentro de la misma categoría
        if (empty($mensajesError)) {
            $check = $con->prepare("SELECT COUNT(*) FROM subcategorias 
                                    WHERE nombre = :nombre AND id_categoria = :id_categoria AND id != :id");
            $check->execute([
                ":nombre" => $nombre,
                ":id_categoria" => $idCategoria,
                ":id" => $id
            ]);

            if ($check->fetchColumn() > 0) {
                $mensajesError[] = "Ya existe otra subcategoría con ese nombre en esta categoría.";
            }
        }

        // Actualizar si no hay errores
        if (empty($mensajesError)) {
            $update = $con->prepare("UPDATE subcategorias 
                                     SET nombre = :nombre, id_categoria = :id_categoria 
                                     WHERE id = :id");
            $update->execute([
                ":nombre" => $nombre,
                ":id_categoria" => $idCategoria,
                ":id" => $id
            ]);

            header("Location: ../categorias/categoriaEditar.php?id=$idCategoria&subE=" . urlencode($nombre));
            exit;
        }
    }

    // Obtener datos actuales de la subcategoría
    $query = $con->prepare("SELECT id, nombre, id_categoria FROM subcategorias WHERE id = :id");
    $query->execute([":id" => $id]);

    if ($row = $query->fetch()) {
        $nombreM = $row["nombre"];
        $idCategoriaM = $row["id_categoria"];
    } else {
        $mensajesError[] = "No se ha encontrado la subcategoría con el ID proporcionado.";
    }

    // Obtener todas las categorías para el selector
    $catQuery = $con->prepare("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
    $catQuery->execute();
    $categorias = $catQuery->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar subcategoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-start jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 600px;">
            <h2 class="text-center mb-4 fw-bold">Editar subcategoría</h2>

            <!-- Errores del servidor -->
            <div class="mb-3">
                <?php
                    if (!empty($mensajesError)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $mensajesError) . "</p>";
                    }
                ?>
            </div>

            <?php if (empty($mensajesError) || isset($_POST["enviar"])): ?>

            <form method="post" action="subcategoriaEditar.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

                <p class="mb-4 text-center fw-semibold">
                    Modifica los datos de la subcategoría y guarda los cambios.
                </p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre de la subcategoría</label>
                    <input type="text" name="nombre" id="nombre" class="form-control jtech-input" maxlength="100" value="<?= htmlspecialchars($nombreM) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Categoría padre</label>
                    <select name="id_categoria" class="form-select jtech-input">
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" 
                                <?= $cat['id'] == $idCategoriaM ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-grid gap-3 mt-4">
                    <button type="submit" name="enviar" class="btn btn-jtech fw-semibold">Guardar cambios</button>
                    <hr class="jtech-divider">
                    <a href="../categorias/categoriaEditar.php?id=<?= $idCategoriaM ?>" 
                       class="btn btn-outline-secondary fw-semibold">Volver</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
