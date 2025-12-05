<?php 
include __DIR__ . '/db.php';
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $pass = $_POST['password'];

    $sql = "SELECT id, nombre, email, password FROM usuarios WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if (password_verify($pass, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_name'] = $row['nombre'];
            
            header("Location: index.php");
            exit();
        } else {
            $error = "La contraseña es incorrecta.";
        }
    } else {
        $error = "No existe ninguna cuenta con este correo.";
    }
}

include __DIR__ . '/header.php'; 
?>

<div class="login-wrapper">
    <div class="login-card">
        
        <div style="font-size: 3rem; color: #007bff; margin-bottom: 1rem;">
            <i class="fa-solid fa-circle-user"></i>
        </div>

        <h2 style="margin-bottom:20px;">Iniciar Sesión</h2>
        <p>Entra para gestionar tus scripts</p>
        
        <?php if($error): ?>
            <div style="background-color: #ffe6e6; color: red; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9rem;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" name="email" class="form-control" placeholder="nombre@ejemplo.com" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Entrar</button>
        </form>

        <div class="login-footer">
            <p>¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a></p>
        </div>

    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>