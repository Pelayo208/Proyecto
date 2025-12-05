<?php
// 1. Iniciamos la sesión para saber cuál hay que cerrar
session_start();

// 2. Borramos todas las variables de sesión (Tu nombre, tu ID, etc.)
$_SESSION = array();

// 3. (Opcional pero recomendado) Borramos la cookie de la sesión del navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruimos la sesión en el servidor
session_destroy();

// 5. Redirigimos al usuario a la página de inicio (o al login)
header("Location: index.php");
exit();
?>