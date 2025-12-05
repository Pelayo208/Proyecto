<?php
// Verificamos si la sesión ya está iniciada, si no, la iniciamos.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Directorios</title>
    
    <link rel="stylesheet" href="style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Estilos específicos para este Header */
        header {
            background-color: #ffffff;
            padding: 1rem 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between; /* Separa los elementos a los extremos */
            align-items: center;
        }

        .user-welcome {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px; /* Espacio entre los enlaces */
        }

        .nav-link {
            text-decoration: none;
            color: #666;
            font-weight: 500;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .nav-link:hover {
            color: #007bff;
        }
    </style>
</head>
<body>

    <header>
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="user-welcome">
                <i class="fa-solid fa-circle-user" style="color: #007bff; font-size: 1.5rem;"></i>
                <span>Hola, <b><?php echo htmlspecialchars($_SESSION['user_name']); ?></b></span>
            </div>

            <div class="header-actions">
                
                <a href="index.php" class="nav-link">
                    <i class="fa-solid fa-house"></i> Inicio
                </a>

                <a href="mis_descargas.php" class="nav-link">
                    <i class="fa-solid fa-clock-rotate-left"></i> Historial
                </a>

                <a href="logout.php" class="btn-login" style="background-color: #dc3545;">
                    <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
                </a>
            </div>

        <?php else: ?>
            <div>
                 <a href="index.php" class="nav-link" style="font-size: 1.1rem; font-weight: bold; color: #333;">
                    <i class="fa-solid fa-layer-group" style="color:#007bff;"></i> AdminSuite
                </a>
            </div> 

            <div class="header-actions">
                <a href="index.php" class="nav-link">
                    <i class="fa-solid fa-house"></i> Inicio
                </a>

                <a href="login.php" class="btn-login">
                    <i class="fa-solid fa-user"></i> Iniciar Sesión
                </a>
            </div>
        <?php endif; ?>
    </header>

    <main>