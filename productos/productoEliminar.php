<?php

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    // Solo administrador puede eliminar productos
    if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "administrador") {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    // Array de errores
    $mensajesError = [];

    $nombre = "";
    $imagen = "";
    $id = null;

    // Obtener datos del producto
    if (isset($_GET["id"])) {
        $id = (int) $_GET["id"];

        $query = $con->prepare("SELECT nombre, imagen FROM productos WHERE id = :id");
        $query->execute([":id" => $id]);

        if ($data = $query->fetch()) {
            $nombre = $data["nombre"];
            $imagen = $data["imagen"];
        } else {
            $mensajesError[] = "No se ha encontrado el producto con el ID proporcionado.";
        }
    }

    // Procesar eliminación real
    if (isset($_POST["eliminar"])) {
        $id = (int) $_POST["id"];

        $check = $con->prepare("SELECT nombre, imagen FROM productos WHERE id = :id");
        $check->execute([":id" => $id]);

        if ($data = $check->fetch()) {
            $nombre = $data["nombre"];
            $imagen = $data["imagen"];

            // Eliminar imagen si existe

            if (!empty($imagen) && file_exists($imagen)) {
                unlink($imagen);
            }

            // Eliminar producto de la base de datos
            $delete = $con->prepare("DELETE FROM productos WHERE id = :id");
            $delete->execute([":id" => $id]);

            if ($delete->rowCount() > 0) {
                header("Location: productoConsulta.php?nombreD=" . urlencode($nombre));
                exit;
            } else {
                $mensajesError[] = "No se ha podido eliminar el producto.";
            }

        } else {
            $mensajesError[] = "El producto indicado no existe.";
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
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-start jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 600px;">
            <h2 class="text-center mb-4 fw-bold">Eliminar producto</h2>

            <!-- Errores -->
            <div class="mb-3">
                <?php 
                    if (!empty($mensajesError)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $mensajesError) . "</p>";
                    } 
                ?>
            </div>

            <?php if (empty($mensajesError) && $id !== null): ?>
                <p class="text-center fw-semibold mb-4">
                    ¿Desea eliminar el producto<br>
                    <span class="text-jtech fw-bold"><?= htmlspecialchars($nombre) ?></span>?
                </p>

                <?php if (!empty($image)): ?>
                    <div class="text-center mb-3">
                        <img src="<?= htmlspecialchars($image) ?>" class="img-thumbnail" style="max-height: 150px;">
                    </div>
                <?php endif; ?>

                <form method="post" action="productoEliminar.php">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

                    <div class="d-grid gap-3">
                        <button type="submit" name="eliminar" class="btn btn-danger fw-semibold" onclick="return confirm('¿Seguro que deseas eliminar este producto?');">
                            Eliminar producto
                        </button>
                        <hr class="jtech-divider">
                        <a href="productoConsulta.php" class="btn btn-outline-secondary fw-semibold">Volver</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
