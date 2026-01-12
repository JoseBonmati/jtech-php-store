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

    if (isset($_POST["enviar"])) {
        $nombre = trim($_POST["nombre"] ?? "");

        // Validar nombre
        if ($nombre === "") {
            $mensajesError[] = "El campo Nombre no puede estar vacío.";
        }

        // Comprobar si la categoría ya existe
        if (empty($mensajesError)) {
            $consulta = $con->prepare("SELECT COUNT(*) FROM categorias WHERE nombre = :nombre");
            $consulta->execute([":nombre" => $nombre]);

            if ($consulta->fetchColumn() > 0) {
                $mensajesError[] = "La categoría ya está registrada, use otro nombre.";
            }
        }

        // Insertar nueva categoría si no hay errores
        if (empty($mensajesError)) {
            $insert = $con->prepare("INSERT INTO categorias (nombre, estado) VALUES (:nombre, 'activo')");
            $insert->execute([":nombre" => $nombre]);

            if ($insert->rowCount() > 0) {
                header("Location: categoriaConsulta.php?nombreN=" . urlencode($nombre));
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
    <title>Nueva categoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 500px;">
            
            <h2 class="text-center mb-4 fw-bold">Crear categoría</h2>

            <!-- Errores del servidor -->
            <div class="mb-3">
                <?php
                    if (!empty($mensajesError)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $mensajesError) . "</p>";
                    }
                ?>
            </div>

            <form method="post" action="categoriaCrear.php">
                <p class="mb-3 text-center fw-semibold">Rellena los siguientes datos para crear una nueva categoría.</p>

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre de la categoría</label>
                    <input type="text" class="form-control jtech-input" name="nombre" id="nombre" maxlength="100"
                           value="<?php if(isset($_POST['nombre'])) echo htmlspecialchars($_POST['nombre']); ?>">
                </div>

                <div class="d-grid gap-3">
                    <button type="submit" class="btn fw-semibold btn-jtech" name="enviar">Crear categoría</button>
                    <hr class="jtech-divider">
                    <a href="categoriaConsulta.php" class="btn btn-outline-secondary">Volver</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
