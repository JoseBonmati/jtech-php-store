<?php

    require_once "../utilidades/conectar_db.php";
    session_start();

    $con = conectar();

    if (isset($_POST["iniciarS"])) {
        $email = trim($_POST["email"] ?? "");
        $contrasenya = trim($_POST["contrasenya"] ?? "");

        if ($email === "" || $contrasenya === "") {
            header("Location: ../index.php?error=Los campos no pueden estar vacíos");
            exit;
        }

        $sql = $con->prepare("SELECT id, nombre, email, contrasenya, rol, estado FROM usuarios WHERE email = :email");
        $sql->execute([":email" => $email]);

        if ($usuario = $sql->fetch()) {
            if ($usuario["estado"] !== "activo") {
                header("Location: ../index.php?error=Tu cuenta está inactiva, contacte con contacto@jtech.com.");
                exit;
            }

            if (password_verify($contrasenya, $usuario["contrasenya"])) {
                session_regenerate_id(true);

                $_SESSION["id"] = $usuario["id"];
                $_SESSION["email"] = $usuario["email"];
                $_SESSION["rol"] = $usuario["rol"];
                $_SESSION["nombre"] = $usuario["nombre"];

                // Migrar carrito del token al usuario
                $token = $_SESSION["carrito_token"];
                $idUsuario = $usuario["id"];

                $sql = $con->prepare("UPDATE carrito SET id_usuario = :idUsuario WHERE token = :token AND id_usuario IS NULL");
                $sql->execute([
                    ":idUsuario" => $idUsuario,
                    ":token" => $token
                ]);


                header("Location: ../index.php");
                exit;
            }
        }

        header("Location: ../index.php?error=Credenciales incorrectas");
        exit;
    }

?>
