<?php 

    // Detecta si esta en localhost o en hosting
    $esLocal = in_array($_SERVER["SERVER_NAME"], ["localhost", "127.0.0.1"]);

    // Configuración LOCAL
    if ($esLocal) {
        define("HOST", "localhost");
        define("DB", "jtech_db");
        define("USER", "root");
        define("PASS", "");
    }
    // Configuración HOSTING
    else {
        define("HOST", "sql200.infinityfree.com");
        define("DB", "if0_40695982_jtech_db");
        define("USER", "if0_40695982");
        define("PASS", "DIL4yuviHmA8");
    }

    define("CHARSET", "utf8mb4");

    // Establecer conexión con la base de datos usando PDO
    function conectar() { 
        $dsn = "mysql:host=" . HOST . ";dbname=" . DB . ";charset=" . CHARSET; 
        
        try { 
            $con = new PDO($dsn, USER, PASS); 
            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);  
            return $con; 
        } catch (PDOException $e) {
            if ($esLocal) {
                die("Error en la conexión: " . $e->getMessage());
            } else {
                error_log($e->getMessage());
                die("No se pudo conectar a la base de datos.");
            }
        }
    }

?>
