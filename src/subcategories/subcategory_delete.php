<?php

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    // Restringir acceso: solo administradores pueden ver esta página
    if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "administrador") {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    // Array para almacenar mensajes de error
    $mensajesError = [];

    $nombre = "";
    $id = null;

    // Obtener datos de la subcategoría por ID
    if (isset($_GET["id"])) {
        $id = (int) $_GET["id"];

        $query = $con->prepare("SELECT nombre FROM subcategorias WHERE id = :id");
        $query->execute([":id" => $id]);

        if ($data = $query->fetch()) {
            $nombre = $data["nombre"];
        } else {
            $mensajesError[] = "No se ha encontrado la subcategoría con el ID proporcionado.";
        }
    }

    // Procesar eliminación
    if (isset($_POST["eliminar"])) {
        $id = (int) $_POST["id"];

        $checkQuery = $con->prepare("SELECT nombre FROM subcategorias WHERE id = :id");
        $checkQuery->execute([":id" => $id]);

        if ($data = $checkQuery->fetch()) {
            $nombre = $data["nombre"];

            $deleteQuery = $con->prepare("DELETE FROM subcategorias WHERE id = :id");
            $deleteQuery->execute([":id" => $id]);

            if ($deleteQuery->rowCount() > 0) {
                header("Location: ../categorias/categoriaConsulta.php?nombreSD=" . urlencode($nombre));
                exit;
            } else {
                $mensajesError[] = "No se ha podido eliminar la subcategoría.";
            }
        } else {
            $mensajesError[] = "La subcategoría indicada no existe.";
        }
    }

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar subcategoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-start jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 600px;">
            <h2 class="text-center mb-4 fw-bold">Eliminar subcategoría</h2>

            <!-- Mensajes de error -->
            <div class="mb-3">
                <?php
                    if (!empty($mensajesError)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $mensajesError) . "</p>";
                    }
                ?>
            </div>

            <?php if (empty($mensajesError) && $id !== null): ?>
                <p class="text-center fw-semibold mb-4">
                    ¿Desea eliminar la subcategoría<br>
                    <span class="text-jtech fw-bold"><?= htmlspecialchars($nombre) ?></span>?
                </p>

                <form method="post" action="subcategoriaEliminar.php">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

                    <div class="d-grid gap-3">
                        <button type="submit" name="eliminar" class="btn btn-danger fw-semibold" onclick="return confirm('¿Seguro que deseas eliminar esta subcategoría?');">
                            Eliminar subcategoría
                        </button>
                        <hr class="jtech-divider">
                        <a href="../categorias/categoriaConsulta.php" class="btn btn-outline-secondary fw-semibold">Volver</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
