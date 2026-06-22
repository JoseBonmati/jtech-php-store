<?php

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database Connection
    require_once __DIR__ . "/../utils/Database.php";
    $db = Database::getConnection();

    // Only logged-in users allowed
    if (!isset($_SESSION["id"])) {
        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    $isAdmin = ($_SESSION["rol"] === "administrador");

    if ($isAdmin) {
        $id = $_GET["id"] ?? null;
    } else {
        $id = $_SESSION["id"];
    }
    
    $action = $_GET["action"] ?? null;

    if (!$id || !$action) {
        header("Location: /index.php");
        exit;
    }

    // Access restrictions:
    // 1. A normal user can only deactivate themselves.
    // 2. An admin CANNOT deactivate themselves.
    if ((!$isAdmin && $_SESSION["id"] != $id) ||
        ($isAdmin && $_SESSION["id"] == $id && $action === "deactivate")) {

        header("Location: /index.php?unauthorized_access=1");
        exit;
    }

    // Determine new status (DB values remain in Spanish)
    if ($action === "deactivate") {
        $newStatus = "inactivo";
    } elseif ($action === "activate" && $isAdmin) {
        $newStatus = "activo";
    } else {
        header("Location: /index.php");
        exit;
    }

    // Update status
    $stmt = $db->prepare("UPDATE usuarios SET estado = :status WHERE id = :id");
    $stmt->execute([
        ":status" => $newStatus,
        ":id" => $id
    ]);

    // If a normal user deactivates themselves -> log out
    if (!$isAdmin && $action === "deactivate") {
        session_unset();
        session_destroy();
        header("Location: /index.php?account_deactivated=1");
        exit;
    }

    // Return to user edit page
    header("Location: /users/user_edit.php?id=" . urlencode((string)$id) . "&status_changed=1");
    exit;

?>