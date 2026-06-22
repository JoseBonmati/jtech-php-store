<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    // Restrict access: only logged-in users allowed
    if (!isset($_SESSION["id"])) {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    $errorMessages = [];

    // Determine where to return after editing
    $returnTo = $_GET["from"] ?? $_POST["from"] ?? (isset($_GET["only_mine"]) ? "only_mine" : null);

    $role = $_SESSION["rol"];

    // Get ID of the user to edit
    if ($role === "administrador") {
        if (isset($_POST["edit_submit"]) || isset($_POST["change_role"])) {
            $id = (int) ($_POST["id"] ?? 0);
        } else {
            $id = (int) ($_GET["id"] ?? 0);
        }
    } else {
        $id = $_SESSION["id"];
    }

    // Restrict access to admin or the user themselves
    if ($role !== "administrador" && $_SESSION["id"] !== $id) {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    // Process form submission
    if (isset($_POST["edit_submit"]) || isset($_POST["change_role"])) {
        $name = trim($_POST["name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $address = trim($_POST["address"] ?? "");
        $city = trim($_POST["city"] ?? "");
        $province = trim($_POST["province"] ?? "");

        // Validations
        if ($name === "") {
            $errorMessages[] = "El campo Nombre no puede estar vacío.";
        } 

        if ($phone === "" || !preg_match("/^[0-9]{9}$/", $phone)) {
            $errorMessages[] = "El teléfono debe tener exactamente 9 dígitos numéricos.";
        }

        if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessages[] = "Formato de email no válido.";
        }

        // Check for duplicate email
        if (empty($errorMessages)) {
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email AND id != :id");
            $checkStmt->execute([":email" => $email, ":id" => $id]);

            if ($checkStmt->fetchColumn() > 0) {
                $errorMessages[] = "El email ya está registrado por otro usuario.";
            }
        }

        // Build dynamic SQL
        $sql = "UPDATE usuarios SET nombre = :nombre, telefono = :telefono, email = :email, direccion = :direccion, localidad = :localidad, provincia = :provincia";

        $params = [
            ":nombre" => $name,
            ":telefono" => $phone,
            ":email" => $email,
            ":direccion" => $address,
            ":localidad" => $city,
            ":provincia" => $province,
            ":id" => $id
        ];

        // Update password if provided
        if ($password !== "") {
            if (strlen($password) < 8) {
                $errorMessages[] = "La contraseña debe tener al menos 8 caracteres.";
            } else {
                $sql .= ", contrasenya = :contrasenya";
                $params[":contrasenya"] = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        // Change role (admin only)
        if ($role === "administrador") {
            if (isset($_POST["change_role"])) {
                $selectedRole = $_POST["change_role"];
            } else {
                $roleQuery = $db->prepare("SELECT rol FROM usuarios WHERE id = :id");
                $roleQuery->execute([":id" => $id]);
                $selectedRole = $roleQuery->fetchColumn();
            }
            $sql .= ", rol = :rol";
            $params[":rol"] = $selectedRole;
        }

        $sql .= " WHERE id = :id";

        // Execute update
        if (empty($errorMessages)) {
            $updateQuery = $db->prepare($sql);
            $updateQuery->execute($params);

            // Update session data if user is editing their own profile
            if ($_SESSION["id"] == $id) {
                $_SESSION["nombre"] = $name;
                $_SESSION["email"] = $email;
            }

            if ($updateQuery->rowCount() >= 0) {

                // If coming from checkout, return to checkout
                if ($returnTo === "finalizar") {
                    header("Location: /checkout/checkout.php?updated_name=" . urlencode($name) . "&updated_email=" . urlencode($email));
                    exit;

                // If admin editing their own data, return to index
                } elseif ($_SESSION["rol"] === "administrador" && $returnTo === "only_mine") {
                    header("Location: /index.php?updated_name=" . urlencode($name) . "&updated_email=" . urlencode($email));
                    exit;
    
                // If admin editing another user, return to user list
                } elseif ($_SESSION["rol"] === "administrador") {
                    header("Location: /users/user_list.php?updated_name=" . urlencode($name) . "&updated_email=" . urlencode($email));
                    exit;

                } else {
                    // Normal users return to index
                    header("Location: /index.php?updated_name=" . urlencode($name) . "&updated_email=" . urlencode($email));
                    exit;
                }

            } else {
                $errorMessages[] = "No se ha podido actualizar el usuario.";
            }
        }
    }

    // Get user data for the form
    $query = $db->prepare("SELECT id, nombre, email, telefono, direccion, localidad, provincia, rol, estado FROM usuarios WHERE id = :id");
    $query->execute([":id" => $id]);

    if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $fetchedName = $row["nombre"];
        $fetchedEmail = $row["email"];
        $fetchedPhone = $row["telefono"];
        $fetchedAddress = $row["direccion"];
        $fetchedCity = $row["localidad"];
        $fetchedProvince = $row["provincia"];
        $fetchedRole = $row["rol"];
        $fetchedStatus = $row["estado"];
    } else {
        $errorMessages[] = "No se ha encontrado el usuario.";
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edición de usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/brand/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-start jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 700px;">
            <h2 class="text-center mb-4 fw-bold">Editar usuario</h2>

            <div class="mb-3">
                <?php
                    if (isset($_GET["status_changed"])) {
                        echo "<p class='alert alert-success mb-0'>Estado del usuario cambiado.</p>";
                    }
                    if (!empty($errorMessages)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $errorMessages) . "</p>";
                    }
                ?>
            </div>

            <form method="post" action="/users/user_edit.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>">
                <input type="hidden" name="from" value="<?= htmlspecialchars((string)$returnTo) ?>">

                <p class="mb-4 text-center fw-semibold">Modifica los datos del usuario y guarda los cambios.</p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Correo electrónico</label>
                    <input type="text" name="email" class="form-control jtech-input" maxlength="50" value="<?= htmlspecialchars($fetchedEmail ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Contraseña</label>
                    <input type="password" name="password" class="form-control jtech-input" maxlength="30">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre</label>
                    <input type="text" name="name" class="form-control jtech-input" maxlength="50" value="<?= htmlspecialchars($fetchedName ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Teléfono</label>
                    <input type="text" name="phone" class="form-control jtech-input" maxlength="9" value="<?= htmlspecialchars($fetchedPhone ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Dirección</label>
                    <input type="text" name="address" class="form-control jtech-input" maxlength="100" value="<?= htmlspecialchars($fetchedAddress ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Localidad</label>
                    <input type="text" name="city" class="form-control jtech-input" maxlength="50" value="<?= htmlspecialchars($fetchedCity ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Provincia</label>
                    <input type="text" name="province" class="form-control jtech-input" maxlength="50" value="<?= htmlspecialchars($fetchedProvince ?? '') ?>">
                </div>

                <?php if ($_SESSION["rol"] === "administrador" && $_SESSION["id"] != $id): ?>
                    <h5 class="fw-bold mt-4 mb-3">Gestión administrativa</h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rol</label><br>

                        <?php if (isset($fetchedRole) && $fetchedRole === "usuario"): ?>
                            <button type="submit" name="change_role" value="empleado" class="btn btn-outline-primary fw-semibold"
                                onclick="return confirm('¿Convertir este usuario en empleado?');">
                                Convertir en Empleado
                            </button>

                            <button type="submit" name="change_role" value="administrador" class="btn btn-outline-danger fw-semibold"
                                onclick="return confirm('¿Convertir este usuario en administrador?');">
                                Convertir en Administrador
                            </button>

                        <?php elseif (isset($fetchedRole) && $fetchedRole === "empleado"): ?>
                            <button type="submit" name="change_role" value="usuario" class="btn btn-outline-secondary fw-semibold"
                                onclick="return confirm('¿Convertir este empleado en usuario?');">
                                Convertir en Usuario
                            </button>

                            <button type="submit" name="change_role" value="administrador" class="btn btn-outline-danger fw-semibold"
                                onclick="return confirm('¿Convertir este empleado en administrador?');">
                                Convertir en Administrador
                            </button>

                        <?php elseif (isset($fetchedRole) && $fetchedRole === "administrador"): ?>
                            <button type="submit" name="change_role" value="usuario" class="btn btn-outline-secondary fw-semibold"
                                onclick="return confirm('¿Quitar permisos de administrador y convertir en usuario?');">
                                Convertir en Usuario
                            </button>

                            <button type="submit" name="change_role" value="empleado" class="btn btn-outline-primary fw-semibold"
                                onclick="return confirm('¿Quitar permisos de administrador y convertir en empleado?');">
                                Convertir en Empleado
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <?php if (isset($fetchedStatus) && $fetchedStatus === "activo"): ?>
                            <a href="/users/user_deactivate.php?id=<?= $id ?>&action=deactivate" class="btn btn-outline-warning fw-semibold"
                            onclick="return confirm('¿Seguro que quieres desactivar este usuario?');">Desactivar usuario</a>
                        <?php else: ?>
                            <a href="/users/user_deactivate.php?id=<?= $id ?>&action=activate" class="btn btn-outline-success fw-semibold"
                            onclick="return confirm('¿Seguro que quieres activar este usuario?');">Activar usuario</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="d-grid gap-3 mt-4">
                    <button type="submit" name="edit_submit" class="btn btn-jtech fw-semibold">Guardar cambios</button>
                    <hr class="jtech-divider">

                    <?php

                        // Return routing logic
                        if ($returnTo === "finalizar") {
                            $returnUrl = "/checkout/checkout.php";

                        } elseif ($role === "usuario" || $role === "empleado") {
                            $returnUrl = "/index.php";

                        } elseif (isset($_GET["only_mine"]) && $_GET["only_mine"] == 1) {
                            $returnUrl = "/index.php";

                        } else {
                            $returnUrl = "/users/user_list.php";
                        }
                    ?>
                    <a href="<?= $returnUrl ?>" class="btn btn-outline-secondary">Volver</a>
                    
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>