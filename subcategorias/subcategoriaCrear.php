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

    // Obtener categorías para el select
    $consultaCategorias = $con->prepare("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
    $consultaCategorias->execute();
    $categorias = $consultaCategorias->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_POST["enviar"])) {
        $nombre = trim($_POST["nombre"] ?? "");
        $categoriaId = $_POST["id_categoria"] ?? "";

        // Validar nombre
        if ($nombre === "") {
            $mensajesError[] = "El campo Nombre no puede estar vacío.";
        }

        // Validar categoría seleccionada
        if ($categoriaId === "" || !ctype_digit($categoriaId)) {
            $mensajesError[] = "Debe seleccionar una categoría válida.";
        }

        // Comprobar si la subcategoría ya existe dentro de la misma categoría
        if (empty($mensajesError)) {
            $consulta = $con->prepare("SELECT COUNT(*) FROM subcategorias WHERE nombre = :nombre AND id_categoria = :id_categoria");
            $consulta->execute([
                ":nombre" => $nombre,
                ":id_categoria" => $categoriaId
            ]);

            if ($consulta->fetchColumn() > 0) {
                $mensajesError[] = "Esta subcategoría ya existe dentro de la categoría seleccionada.";
            }
        }

        // Insertar nueva subcategoría si no hay errores
        if (empty($mensajesError)) {
            $insert = $con->prepare("INSERT INTO subcategorias (nombre, id_categoria, estado) VALUES (:nombre, :id_categoria, 'activo')");
            $insert->execute([
                ":nombre" => $nombre,
                ":id_categoria" => $categoriaId
            ]);

            if ($insert->rowCount() > 0) {
                header("Location: ../categorias/categoriaConsulta.php?nombreS=" . urlencode($nombre));
                exit;
            } else {
                $mensajesError[] = "Ha ocurrido un error con la base de datos.";
            }
        }
    }

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva subcategoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 500px;">
            
            <h2 class="text-center mb-4 fw-bold">Crear subcategoría</h2>

            <!-- Errores del servidor -->
            <div class="mb-3">
                <?php
                    if (!empty($mensajesError)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $mensajesError) . "</p>";
                    }
                ?>
            </div>

            <form method="post" action="subcategoriaCrear.php">
                <p class="mb-3 text-center fw-semibold">Rellena los siguientes datos para crear una nueva subcategoría.</p>

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre de la subcategoría</label>
                    <input type="text" class="form-control jtech-input" name="nombre" id="nombre" maxlength="100"
                           value="<?php if(isset($_POST['nombre'])) echo htmlspecialchars($_POST['nombre']); ?>">
                </div>

                <div class="mb-3">
                    <label for="id_categoria" class="form-label">Categoría padre</label>
                    <select class="form-select jtech-input" name="id_categoria" id="id_categoria">
                        <option value="">Seleccione una categoría</option>
                        <?php
                            foreach ($categorias as $cat) {
                                $selected = (isset($_POST["id_categoria"]) && $_POST["id_categoria"] == $cat["id"]) ? "selected" : "";
                                echo "<option value='" . htmlspecialchars($cat["id"]) . "' $selected>" . htmlspecialchars($cat["nombre"]) . "</option>";
                            }
                        ?>
                    </select>
                </div>

                <div class="d-grid gap-3">
                    <button type="submit" class="btn fw-semibold btn-jtech" name="enviar">Crear subcategoría</button>
                    <hr class="jtech-divider">
                    <a href="../categorias/categoriaConsulta.php" class="btn btn-outline-secondary">Volver</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
