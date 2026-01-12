<?php

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    // Restringir acceso: solo administradores o empleados pueden ver esta página
    if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "administrador" && $_SESSION["rol"] !== "empleado")) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    // Array para almacenar mensajes de error
    $mensajesError = [];

    // Obtener ID de la categoría desde POST o GET
    if (isset($_POST["enviar"]) || isset($_POST["toggle_estado"]) || isset($_POST["accion_sub"])) {
        $id = (int) ($_POST["id"] ?? 0);
    } else {
        $id = (int) ($_GET["id"] ?? 0);
    }

    // Procesar activación/desactivación de categoría
    if (isset($_POST["toggle_estado"])) {
        $estadoActual = $_POST["estado_actual"] ?? "activo";
        $nuevoEstado = ($estadoActual === "activo") ? "inactivo" : "activo";

        $updateEstado = $con->prepare("UPDATE categorias SET estado = :estado WHERE id = :id");
        $updateEstado->execute([
            ":estado" => $nuevoEstado,
            ":id" => $id
        ]);

        header("Location: categoriaEditar.php?id=$id");
        exit;
    }

    // Procesar acciones sobre subcategorías
    if (isset($_POST["accion_sub"])) {
        $idSub = (int) $_POST["id_subcategoria"];
        $accion = $_POST["accion_sub"];
        $estadoSub = $_POST["estado_sub"] ?? "activo";

        if ($accion === "editar") {
            header("Location: ../subcategorias/subcategoriaEditar.php?id=$idSub");
            exit;
        }

        if ($accion === "eliminar") {
            header("Location: ../subcategorias/subcategoriaEliminar.php?id=$idSub");
            exit;
        }

        if ($accion === "toggle") {
            $nuevoEstado = ($estadoSub === "activo") ? "inactivo" : "activo";

            $update = $con->prepare("UPDATE subcategorias SET estado = :estado WHERE id = :id");
            $update->execute([
                ":estado" => $nuevoEstado,
                ":id" => $idSub
            ]);

            header("Location: categoriaEditar.php?id=$id");
            exit;
        }
    }

    // Procesar formulario (actualizar categoría)
    if (isset($_POST["enviar"])) {
        $nombre = trim($_POST["nombre"] ?? "");

        // Validación
        if ($nombre === "") {
            $mensajesError[] = "El campo Nombre no puede estar vacío.";
        }

        // Comprobar duplicados
        if (empty($mensajesError)) {
            $check = $con->prepare("SELECT COUNT(*) FROM categorias WHERE nombre = :nombre AND id != :id");
            $check->execute([
                ":nombre" => $nombre,
                ":id" => $id
            ]);

            if ($check->fetchColumn() > 0) {
                $mensajesError[] = "Ya existe otra categoría con ese nombre.";
            }
        }

        // Actualizar si no hay errores
        if (empty($mensajesError)) {
            $update = $con->prepare("UPDATE categorias SET nombre = :nombre WHERE id = :id");
            $update->execute([
                ":nombre" => $nombre,
                ":id" => $id
            ]);

            header("Location: categoriaConsulta.php?nombreE=" . urlencode($nombre));
            exit;
        }
    }

    // Obtener datos actuales de la categoría
    $query = $con->prepare("SELECT id, nombre, estado FROM categorias WHERE id = :id");
    $query->execute([":id" => $id]);

    if ($row = $query->fetch()) {
        $nombreM = $row["nombre"];
        $estadoM = $row["estado"];
    } else {
        $mensajesError[] = "No se ha encontrado la categoría con el ID proporcionado.";
    }

    // Obtener subcategorías de esta categoría
    $subQuery = $con->prepare("SELECT id, nombre, estado FROM subcategorias WHERE id_categoria = :id ORDER BY nombre ASC");
    $subQuery->execute([":id" => $id]);
    $subcategorias = $subQuery->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar categoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-start jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 700px;">
            <h2 class="text-center mb-4 fw-bold">Editar categoría</h2>

            <!-- Errores del servidor -->
            <div class="mb-3">
                <?php
                    if (!empty($mensajesError)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $mensajesError) . "</p>";
                    }
                ?>
            </div>

            <?php if (empty($mensajesError) || isset($_POST["enviar"])): ?>

            <form method="post" action="categoriaEditar.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

                <p class="mb-4 text-center fw-semibold">Modifica los datos de la categoría y guarda los cambios.</p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre de la categoría</label>
                    <input type="text" name="nombre" id="nombre" class="form-control jtech-input" maxlength="100" value="<?= htmlspecialchars($nombreM) ?>">
                </div>

                <div class="d-grid gap-3 mt-4">
                    <button type="submit" name="enviar" class="btn btn-jtech fw-semibold">Guardar cambios</button>
                </div>
            </form>

            <form method="post" action="categoriaEditar.php" class="mt-3">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <input type="hidden" name="estado_actual" value="<?= htmlspecialchars($estadoM) ?>">

                <button type="submit" name="toggle_estado"
                        class="btn <?= $estadoM === 'activo' ? 'btn-warning' : 'btn-success' ?> fw-semibold w-100">
                    <?= $estadoM === 'activo' ? 'Desactivar categoría' : 'Activar categoría' ?>
                </button>
            </form>

            <h4 class="fw-bold mt-4">Subcategorías de esta categoría</h4>

            <form method="post" action="categoriaEditar.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Selecciona una subcategoría</label>
                    <select name="id_subcategoria" class="form-select jtech-input" required>
                        <option value="">Seleccione una subcategoría</option>
                        <?php foreach ($subcategorias as $sub): ?>
                            <option value="<?= $sub['id'] ?>">
                                <?= htmlspecialchars($sub['nombre']) ?> (<?= $sub['estado'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="hidden" name="estado_sub" id="estado_sub">

                <div class="d-grid gap-2">
                    <button type="submit" name="accion_sub" value="editar" class="btn btn-jtech fw-semibold">
                        Editar subcategoría
                    </button>

                    <?php if ($_SESSION["rol"] === "administrador"): ?>
                        <button type="submit" name="accion_sub" value="eliminar" class="btn btn-danger fw-semibold" onclick="return confirm('¿Seguro que deseas eliminar esta subcategoría?');">
                            Eliminar subcategoría
                        </button>
                    <?php endif; ?>

                    <button type="submit" name="accion_sub" value="toggle" class="btn btn-warning fw-semibold">
                        Activar / Desactivar subcategoría
                    </button>
                </div>

                <hr class="jtech-divider my-3">
                <a href="categoriaConsulta.php" class="btn btn-outline-secondary fw-semibold w-100">Volver</a>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Actualiza el estado de la subcategoría seleccionada
        document.querySelector("select[name='id_subcategoria']").addEventListener("change", function() {
            const selected = this.options[this.selectedIndex].text;
            const estado = selected.includes("(activo)") ? "activo" : "inactivo";
            document.getElementById("estado_sub").value = estado;
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
