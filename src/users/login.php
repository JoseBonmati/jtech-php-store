<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    if (isset($_POST["login_submit"])) {
        $email = trim($_POST["email"] ?? "");
        $password = trim($_POST["password"] ?? "");

        if ($email === "" || $password === "") {
            header("Location: /index.php?error=" . urlencode("Los campos no pueden estar vacíos"));
            exit;
        }

        // Fetch user from DB
        $stmt = $db->prepare("SELECT id, nombre, email, contrasenya, rol, estado FROM usuarios WHERE email = :email");
        $stmt->execute([":email" => $email]);

        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($user["estado"] !== "activo") {
                header("Location: /index.php?error=" . urlencode("Tu cuenta está inactiva, contacte con contacto@jtech.com."));
                exit;
            }

            if (password_verify($password, $user["contrasenya"])) {
                session_regenerate_id(true);

                $_SESSION["id"] = $user["id"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["rol"] = $user["rol"];
                $_SESSION["nombre"] = $user["nombre"];

                $cartToken = $_SESSION["cart_token"] ?? null;
                $userId = $user["id"];

                if ($cartToken) {
                    $updateCartStmt = $db->prepare("UPDATE carrito SET id_usuario = :idUsuario WHERE token = :token AND id_usuario IS NULL");
                    $updateCartStmt->execute([
                        ":idUsuario" => $userId,
                        ":token" => $cartToken
                    ]);
                }

                header("Location: /index.php");
                exit;
            }
        }

        header("Location: /index.php?error=" . urlencode("Credenciales incorrectas"));
        exit;
    }

    // Fallback redirect if accessed directly without POST data
    header("Location: /index.php");
    exit;

?>