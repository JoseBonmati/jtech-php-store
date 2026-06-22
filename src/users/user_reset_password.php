<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();
    
    $errorMessages = [];

    if (isset($_POST["reset_submit"])) {
        $email = trim($_POST["email"] ?? "");
        $password = trim($_POST["password"] ?? "");

        if ($email === "") {
            $errorMessages[] = "El campo Email no puede estar vacío.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessages[] = "Formato de email no válido.";
        }

        if ($password === "") {
            $errorMessages[] = "El campo Contraseña no puede estar vacío.";
        } elseif (strlen($password) < 8) {
            $errorMessages[] = "La contraseña debe tener al menos 8 caracteres.";
        }

        if (empty($errorMessages)) {
            $stmt = $db->prepare("SELECT nombre, estado FROM usuarios WHERE email = :email");
            $stmt->execute([":email" => $email]);

            if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($user["estado"] !== "activo") {
                    $errorMessages[] = "El usuario está inactivo. Contacte con el administrador.";
                } else {
                    $name = $user["nombre"];
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    $updateStmt = $db->prepare("UPDATE usuarios SET contrasenya = :password WHERE email = :email");
                    $updateStmt->execute([
                        ":password" => $passwordHash,
                        ":email" => $email
                    ]);

                    if ($updateStmt->rowCount() >= 0) {
                        header("Location: /index.php?reset_name=" . urlencode($name));
                        exit;
                    } else {
                        $errorMessages[] = "No se ha podido actualizar la contraseña. Inténtelo de nuevo.";
                    }
                }
            } else {
                $errorMessages[] = "El email introducido no es correcto, o no existe un usuario con dichos datos.";
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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link class="jtech-favicon" rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center jtech-bg">
        <div class="p-4 jtech-card">
            <h2 class="text-center mb-4 fw-bold">Restablecer contraseña</h2>
            <div class="mb-3">
                <?php
                    if (!empty($errorMessages)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $errorMessages) . "</p>";
                    }
                ?>
            </div>

            <form method="post" action="/users/user_reset_password.php">
                <p class="mb-3 text-center fw-semibold">Introduce tu correo electrónico y la nueva contraseña que deseas establecer.</p>

                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="text" class="form-control jtech-input" name="email" id="email" maxlength="50" value="<?php if(isset($_POST['email'])) echo htmlspecialchars($_POST['email']); ?>">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control jtech-input" name="password" id="password" maxlength="30">
                </div>

                <div class="d-grid gap-3">
                    <button type="submit" class="btn fw-semibold btn-jtech" name="reset_submit">Restablecer contraseña</button>
                    <hr class="jtech-divider">
                    <a href="/index.php" class="btn btn-outline-secondary">Volver</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>