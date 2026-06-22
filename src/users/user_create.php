<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    $errorMessages = [];

    if (isset($_POST["register_submit"])) {
        $email = trim($_POST["email"] ?? "");
        $password = trim($_POST["password"] ?? "");
        $name = trim($_POST["name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");

        // Email validation
        if ($email === "") {
            $errorMessages[] = "El campo Email no puede estar vacío.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessages[] = "Formato de email no válido.";
        }

        // Password validation
        if ($password === "") {
            $errorMessages[] = "El campo Contraseña no puede estar vacío.";
        } elseif (strlen($password) < 8) {
            $errorMessages[] = "La contraseña debe tener al menos 8 caracteres.";
        }

        // Name validation
        if ($name === "") {
            $errorMessages[] = "El campo Nombre no puede estar vacío.";
        }

        // Phone validation
        if ($phone === "") {
            $errorMessages[] = "El campo Teléfono no puede estar vacío.";
        } elseif (!preg_match("/^[0-9]{9}$/", $phone)) {
            $errorMessages[] = "El teléfono debe tener exactamente 9 dígitos numéricos.";
        }

        // Check for duplicate email
        if (empty($errorMessages)) {
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $checkStmt->execute([":email" => $email]);

            if ($checkStmt->fetchColumn() > 0) {
                $errorMessages[] = "El email ya está registrado, use otro.";
            }
        }

        // Insert new user
        if (empty($errorMessages)) {
            $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, contrasenya, telefono, rol, estado)
                                   VALUES (:nombre, :email, :contrasenya, :telefono, 'usuario', 'activo')");

            $stmt->execute([
                ":nombre" => $name,
                ":email" => $email,
                ":contrasenya" => password_hash($password, PASSWORD_DEFAULT),
                ":telefono" => $phone
            ]);

            if ($stmt->rowCount() > 0) {
                if (isset($_SESSION["rol"]) && $_SESSION["rol"] === "administrador") {
                    header("Location: /users/user_list.php?created_name=" . urlencode($name) . "&created_email=" . urlencode($email));
                    exit;
                }

                // Auto login for regular user registration
                $newUserId = $db->lastInsertId();

                $_SESSION["id"] = $newUserId;
                $_SESSION["email"] = $email;
                $_SESSION["rol"] = "usuario";
                $_SESSION["nombre"] = $name;

                $cartToken = $_SESSION["cart_token"] ?? null;

                if ($cartToken) {
                    $updateCartStmt = $db->prepare("UPDATE carrito SET id_usuario = :idUsuario WHERE token = :token AND id_usuario IS NULL");
                    $updateCartStmt->execute([
                        ":idUsuario" => $newUserId,
                        ":token" => $cartToken
                    ]);
                }

                header("Location: /index.php?created_name=" . urlencode($name) . "&created_email=" . urlencode($email));
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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center jtech-bg">
        <div class="p-4 jtech-card">
            <h2 class="text-center mb-4 fw-bold">Crear usuario</h2>

            <!-- Server errors -->
            <div class="mb-3">
                <?php
                    if (!empty($errorMessages)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $errorMessages) . "</p>";
                    }
                ?>
            </div>

            <form method="post" action="/users/user_create.php">
                <p class="mb-3 text-center fw-semibold">Rellena los siguientes datos para crear tu cuenta.</p>

                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input type="text" name="email" class="form-control jtech-input" maxlength="50" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control jtech-input" maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control jtech-input" maxlength="50" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="phone" class="form-control jtech-input" maxlength="9" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                </div>

                <div class="d-grid gap-3 mt-4">
                    <button type="submit" name="register_submit" class="btn btn-jtech fw-semibold">Crear usuario</button>
                    <hr class="jtech-divider">
                    <a href="<?= (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador') ? '/users/user_list.php' : '/index.php' ?>" 
                    class="btn btn-outline-secondary">Volver</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>