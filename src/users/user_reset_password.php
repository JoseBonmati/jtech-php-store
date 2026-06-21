<?php
    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    
    $mensajesError = [];

    if (isset($_POST["enviar"])) {
        $email = trim($_POST["email"] ?? "");
        $contrasenya = trim($_POST["contrasenya"] ?? "");

        if ($email === "") {
            $mensajesError[] = "El campo Email no puede estar vacío.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensajesError[] = "Formato de email no válido.";
        }

        if ($contrasenya === "") {
            $mensajesError[] = "El campo Contraseña no puede estar vacío.";
        } elseif (strlen($contrasenya) < 8) {
            $mensajesError[] = "La contraseña debe tener al menos 8 caracteres.";
        }

        if (empty($mensajesError)) {
            $stmt = $con->prepare("SELECT nombre, estado FROM usuarios WHERE email = :email");
            $stmt->execute([":email" => $email]);

            if ($data = $stmt->fetch()) {
                if ($data["estado"] !== "activo") {
                    $mensajesError[] = "El usuario está inactivo. Contacte con el administrador.";
                } else {
                    $nombre = $data["nombre"];
                    $contrasenyaHash = password_hash($contrasenya, PASSWORD_DEFAULT);

                    $query = $con->prepare("UPDATE usuarios SET contrasenya = :contrasenya WHERE email = :email");
                    $query->execute([
                        ":contrasenya" => $contrasenyaHash,
                        ":email" => $email
                    ]);

                    if ($query->rowCount() >= 0) {
                        header("Location: ../index.php?nombreR=" . urlencode($nombre));
                        exit;
                    } else {
                        $mensajesError[] = "No se ha podido actualizar la contraseña. Inténtelo de nuevo.";
                    }
                }
            } else {
                $mensajesError[] = "El email introducido no es correcto, o no existe un usuario con dichos datos.";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center jtech-bg">
        <div class="p-4 jtech-card">
            <h2 class="text-center mb-4 fw-bold">Restablecer contraseña</h2>
            <div class="mb-3">
                <?php
                    if (!empty($mensajesError)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $mensajesError) . "</p>";
                    }
                ?>
            </div>

            <form method="post" action="restablecerContrasenya.php">
                <p class="mb-3 text-center fw-semibold">Introduce tu correo electrónico y la nueva contraseña que deseas establecer.</p>

                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="text" class="form-control jtech-input" name="email" id="email" maxlength="50" value="<?php if(isset($_POST['email'])) echo htmlspecialchars($_POST['email']); ?>">
                </div>

                <div class="mb-3">
                    <label for="contrasenya" class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control jtech-input" name="contrasenya" id="contrasenya" maxlength="30">
                </div>

                <div class="d-grid gap-3">
                    <button type="submit" class="btn fw-semibold btn-jtech" name="enviar">Restablecer contraseña</button>
                    <hr class="jtech-divider">
                    <a href="../index.php" class="btn btn-outline-secondary">Volver</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
