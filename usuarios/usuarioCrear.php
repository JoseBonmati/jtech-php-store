<?php

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    $mensajesError = [];

    if (isset($_POST["enviar"])) {
        $email = trim($_POST["email"] ?? "");
        $contrasenya = trim($_POST["contrasenya"] ?? "");
        $nombre = trim($_POST["nombre"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");

        // Validación de email
        if ($email === "") {
            $mensajesError[] = "El campo Email no puede estar vacío.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensajesError[] = "Formato de email no válido.";
        }

        // Validación de contraseña
        if ($contrasenya === "") {
            $mensajesError[] = "El campo Contraseña no puede estar vacío.";
        } elseif (strlen($contrasenya) < 8) {
            $mensajesError[] = "La contraseña debe tener al menos 8 caracteres.";
        }

        // Validación de nombre
        if ($nombre === "") {
            $mensajesError[] = "El campo Nombre no puede estar vacío.";
        }

        // Validación de teléfono
        if ($telefono === "") {
            $mensajesError[] = "El campo Teléfono no puede estar vacío.";
        } elseif (!preg_match("/^[0-9]{9}$/", $telefono)) {
            $mensajesError[] = "El teléfono debe tener exactamente 9 dígitos numéricos.";
        }

        // Comprobar email duplicado
        if (empty($mensajesError)) {
            $check = $con->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $check->execute([":email" => $email]);

            if ($check->fetchColumn() > 0) {
                $mensajesError[] = "El email ya está registrado, use otro.";
            }
        }

        // Insertar usuario
        if (empty($mensajesError)) {
            $stmt = $con->prepare("INSERT INTO usuarios (nombre, email, contrasenya, telefono, rol, estado)
                                   VALUES (:nombre, :email, :contrasenya, :telefono, 'usuario', 'activo')
            ");

            $stmt->execute([
                ":nombre" => $nombre,
                ":email" => $email,
                ":contrasenya" => password_hash($contrasenya, PASSWORD_DEFAULT),
                ":telefono" => $telefono
            ]);

            if ($stmt->rowCount() > 0) {
                if (isset($_SESSION["rol"]) && $_SESSION["rol"] === "administrador") {
                    header("Location: usuarioConsulta.php?nombreN=" . urlencode($nombre) . "&emailN=" . urlencode($email));
                    exit;
                }

                // Si es un usuario normal que se está registrando se inicia sesión automáticamente
                $idNuevoUsuario = $con->lastInsertId();

                $_SESSION["id"] = $idNuevoUsuario;
                $_SESSION["email"] = $email;
                $_SESSION["rol"] = "usuario";
                $_SESSION["nombre"] = $nombre;

                // Migrar carrito del invitado al nuevo usuario
                $token = $_SESSION["carrito_token"];

                $sql = $con->prepare("UPDATE carrito SET id_usuario = :idUsuario WHERE token = :token AND id_usuario IS NULL");
                $sql->execute([
                    ":idUsuario" => $idNuevoUsuario,
                    ":token" => $token
                ]);

                header("Location: ../index.php?nombreN=" . urlencode($nombre) . "&emailN=" . urlencode($email));
                exit;
            }
        }
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center jtech-bg">
        <div class="p-4 jtech-card">
            <h2 class="text-center mb-4 fw-bold">Crear usuario</h2>

            <!-- Errores del servidor -->
            <div class="mb-3">
                <?php
                    if (!empty($mensajesError)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $mensajesError) . "</p>";
                    }
                ?>
            </div>

            <form method="post" action="usuarioCrear.php">
                <p class="mb-3 text-center fw-semibold">Rellena los siguientes datos para crear tu cuenta.</p>

                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input type="text" name="email" class="form-control jtech-input" maxlength="50" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="contrasenya" class="form-control jtech-input" maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control jtech-input" maxlength="50" value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control jtech-input" maxlength="9" value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>">
                </div>

                <div class="d-grid gap-3 mt-4">
                    <button type="submit" name="enviar" class="btn btn-jtech fw-semibold">Crear usuario</button>
                    <hr class="jtech-divider">
                    <a href="<?= (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador') ? 'usuarioConsulta.php' : '../index.php' ?>" 
                    class="btn btn-outline-secondary">Volver</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
