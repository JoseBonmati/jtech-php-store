<?php

    require_once "../utilidades/conectar_db.php";
    $con = conectar();
    session_start();

    // Solo usuarios logueados
    if (!isset($_SESSION["id"])) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    $mensajesError = [];

    $volverA = $_GET["from"] ?? $_POST["from"] ?? (isset($_GET["soloMios"]) ? "soloMios" : null);

    $rol = $_SESSION["rol"];

    // Obtener ID del usuario a editar
    if ($rol === "administrador") {
        if (isset($_POST["enviar"]) || isset($_POST["cambiarRol"])) {
            $id = (int) ($_POST["id"] ?? 0);
        } else {
            $id = (int) ($_GET["id"] ?? 0);
        }
    } else {
        $id = $_SESSION["id"];
    }

    // Solo admin o el propio usuario
    if ($rol !== "administrador" && $_SESSION["id"] !== $id) {
        header("Location: ../index.php?acceso=denegado");
        exit;
    }

    // Procesar formulario
    if (isset($_POST["enviar"]) || isset($_POST["cambiarRol"])) {
        $nombre = trim($_POST["nombre"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $contrasenya = $_POST["contrasenya"] ?? "";
        $direccion = trim($_POST["direccion"] ?? "");
        $localidad = trim($_POST["localidad"] ?? "");
        $provincia = trim($_POST["provincia"] ?? "");

        // Validaciones
        if ($nombre === "") {
            $mensajesError[] = "El campo Nombre no puede estar vacío.";
        } 

        if ($telefono === "" || !preg_match("/^[0-9]{9}$/", $telefono)) {
            $mensajesError[] = "El teléfono debe tener exactamente 9 dígitos numéricos.";
        }

        if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensajesError[] = "Formato de email no válido.";
        }

        // Comprobar email duplicado
        if (empty($mensajesError)) {
            $check = $con->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email AND id != :id");
            $check->execute([":email" => $email, ":id" => $id]);

            if ($check->fetchColumn() > 0) {
                $mensajesError[] = "El email ya está registrado por otro usuario.";
            }
        }

        // Construir SQL dinámico
        $sql = "UPDATE usuarios SET nombre = :nombre, telefono = :telefono, email = :email, direccion = :direccion, localidad = :localidad, provincia = :provincia";

        $params = [
            ":nombre" => $nombre,
            ":telefono" => $telefono,
            ":email" => $email,
            ":direccion" => $direccion,
            ":localidad" => $localidad,
            ":provincia" => $provincia,
            ":id" => $id
        ];

        // Actualizar contraseña si se ha introducido
        if ($contrasenya !== "") {
            if (strlen($contrasenya) < 8) {
                $mensajesError[] = "La contraseña debe tener al menos 8 caracteres.";
            } else {
                $sql .= ", contrasenya = :contrasenya";
                $params[":contrasenya"] = password_hash($contrasenya, PASSWORD_DEFAULT);
            }
        }

        // Cambiar rol (solo admin)
        if ($rol === "administrador") {
            if (isset($_POST["cambiarRol"])) {
                $rol = $_POST["cambiarRol"];
            } else {
                $rolQuery = $con->prepare("SELECT rol FROM usuarios WHERE id = :id");
                $rolQuery->execute([":id" => $id]);
                $rol = $rolQuery->fetchColumn();
            }
            $sql .= ", rol = :rol";
            $params[":rol"] = $rol;
        }

        $sql .= " WHERE id = :id";

        // Ejecutar actualización
        if (empty($mensajesError)) {
            $updateQuery = $con->prepare($sql);
            $updateQuery->execute($params);

            if ($_SESSION["id"] == $id) {
                $_SESSION["nombre"] = $nombre;
                $_SESSION["email"] = $email;
            }

            if ($updateQuery->rowCount() >= 0) {

                // Si viene desde finalizar compra volver a finalizar compra
                if ($volverA === "finalizar") {
                    header("Location: ../compra/compraFinalizar.php?nombreE=" . urlencode($nombre) . "&emailE=" . urlencode($email));
                    exit;

                // Si es admin editando sus propios datos volver al index
                } elseif ($_SESSION["rol"] === "administrador" && $volverA === "soloMios") {
                    header("Location: ../index.php?nombreE=" . urlencode($nombre) . "&emailE=" . urlencode($email));
                    exit;
    
                // Si es admin editando a otro usuario volver a la consulta
                } elseif ($_SESSION["rol"] === "administrador") {
                    header("Location: usuarioConsulta.php?nombreE=" . urlencode($nombre) . "&emailE=" . urlencode($email));
                    exit;

                } else {
                    // Usuarios normales vuelven al index
                    header("Location: ../index.php?nombreE=" . urlencode($nombre) . "&emailE=" . urlencode($email));
                    exit;
                }

            } else {
                $mensajesError[] = "No se ha podido actualizar el usuario.";
            }
        }
    }

    // Obtener datos del usuario
    $query = $con->prepare("SELECT id, nombre, email, telefono, direccion, localidad, provincia, rol, estado FROM usuarios WHERE id = :id");
    $query->execute([":id" => $id]);

    if ($row = $query->fetch()) {
        $nombreM = $row["nombre"];
        $emailM = $row["email"];
        $telefonoM = $row["telefono"];
        $direccionM = $row["direccion"];
        $localidadM = $row["localidad"];
        $provinciaM = $row["provincia"];
        $rolM = $row["rol"];
        $estadoM = $row["estado"];
    } else {
        $mensajesError[] = "No se ha encontrado el usuario.";
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
    <link rel="stylesheet" href="../estilos.css">
    <link rel="icon" type="image/x-icon" href="../assets/logos/jtech-favicon.ico"/>
</head>
<body>
    <div class="d-flex justify-content-center align-items-start jtech-bg">
        <div class="p-4 jtech-card" style="max-width: 700px;">
            <h2 class="text-center mb-4 fw-bold">Editar usuario</h2>

            <div class="mb-3">
                <?php
                    if (isset($_GET["estadoCambiado"])) {
                        echo "<p class='alert alert-success mb-0'>Estado del usuario cambiado.</p>";
                    }
                    if (!empty($mensajesError)) {
                        echo "<p class='alert alert-danger mb-0'>" . implode("<br>", $mensajesError) . "</p>";
                    }
                ?>
            </div>

            <form method="post" action="usuarioEditar.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <input type="hidden" name="from" value="<?= htmlspecialchars($volverA) ?>">

                <p class="mb-4 text-center fw-semibold">Modifica los datos del usuario y guarda los cambios.</p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Correo electrónico</label>
                    <input type="text" name="email" class="form-control jtech-input" maxlength="50" value="<?= htmlspecialchars($emailM) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Contraseña</label>
                    <input type="password" name="contrasenya" class="form-control jtech-input" maxlength="30">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre</label>
                    <input type="text" name="nombre" class="form-control jtech-input" maxlength="50" value="<?= htmlspecialchars($nombreM) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Teléfono</label>
                    <input type="text" name="telefono" class="form-control jtech-input" maxlength="9" value="<?= htmlspecialchars($telefonoM) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Dirección</label>
                    <input type="text" name="direccion" class="form-control jtech-input" maxlength="100" value="<?= htmlspecialchars($direccionM) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Localidad</label>
                    <input type="text" name="localidad" class="form-control jtech-input" maxlength="50" value="<?= htmlspecialchars($localidadM) ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Provincia</label>
                    <input type="text" name="provincia" class="form-control jtech-input" maxlength="50" value="<?= htmlspecialchars($provinciaM) ?>">
                </div>

                <?php if ($_SESSION["rol"] === "administrador" && $_SESSION["id"] != $id): ?>
                    <h5 class="fw-bold mt-4 mb-3">Gestión administrativa</h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rol</label><br>

                        <?php if ($rolM === "usuario"): ?>
                            <button type="submit" name="cambiarRol" value="empleado" class="btn btn-outline-primary fw-semibold"
                                onclick="return confirm('¿Convertir este usuario en empleado?');">
                                Convertir en Empleado
                            </button>

                            <button type="submit" name="cambiarRol" value="administrador" class="btn btn-outline-danger fw-semibold"
                                onclick="return confirm('¿Convertir este usuario en administrador?');">
                                Convertir en Administrador
                            </button>

                        <?php elseif ($rolM === "empleado"): ?>
                            <button type="submit" name="cambiarRol" value="usuario" class="btn btn-outline-secondary fw-semibold"
                                onclick="return confirm('¿Convertir este empleado en usuario?');">
                                Convertir en Usuario
                            </button>

                            <button type="submit" name="cambiarRol" value="administrador" class="btn btn-outline-danger fw-semibold"
                                onclick="return confirm('¿Convertir este empleado en administrador?');">
                                Convertir en Administrador
                            </button>

                        <?php elseif ($rolM === "administrador"): ?>
                            <button type="submit" name="cambiarRol" value="usuario" class="btn btn-outline-secondary fw-semibold"
                                onclick="return confirm('¿Quitar permisos de administrador y convertir en usuario?');">
                                Convertir en Usuario
                            </button>

                            <button type="submit" name="cambiarRol" value="empleado" class="btn btn-outline-primary fw-semibold"
                                onclick="return confirm('¿Quitar permisos de administrador y convertir en empleado?');">
                                Convertir en Empleado
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <?php if ($estadoM === "activo"): ?>
                            <a href="usuarioDesactivar.php?id=<?= $id ?>&accion=desactivar" class="btn btn-outline-warning fw-semibold"
                            onclick="return confirm('¿Seguro que quieres desactivar este usuario?');">Desactivar usuario</a>
                        <?php else: ?>
                            <a href="usuarioDesactivar.php?id=<?= $id ?>&accion=activar" class="btn btn-outline-success fw-semibold"
                            onclick="return confirm('¿Seguro que quieres activar este usuario?');">Activar usuario</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="d-grid gap-3 mt-4">
                    <button type="submit" name="enviar" class="btn btn-jtech fw-semibold">Guardar cambios</button>
                    <hr class="jtech-divider">

                    <?php

                        // Si viene desde finalizar compra, vuelve a ella
                        if ($volverA === "finalizar") {
                            $volver = "../compra/compraFinalizar.php";

                        // Si es usuario normal siempre vuelve al index
                        } elseif ($rol === "usuario" || $rol === "empleado") {
                            $volver = "../index.php";

                        // Si es admin y está editando su usuario, volver al index
                        } elseif (isset($_GET["soloMios"]) && $_GET["soloMios"] == 1) {
                            $volver = "../index.php";

                        // Si es admin editando otro usuario volver a la consulta
                        } else {
                            $volver = "usuarioConsulta.php";
                        }
                    ?>
                    <a href="<?= $volver ?>" class="btn btn-outline-secondary">Volver</a>
                    
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
