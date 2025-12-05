<?php 
include __DIR__ . '/header.php';
include __DIR__ . '/db.php';

$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo_mensaje = "red";
    } else {
        $checkEmail = "SELECT id FROM usuarios WHERE email = '$email'";
        $result = $conn->query($checkEmail);

        if ($result->num_rows > 0) {
            $mensaje = "Este correo ya está registrado.";
            $tipo_mensaje = "red";
        } else {
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios (nombre, email, password) VALUES ('$nombre', '$email', '$pass_hash')";

            if ($conn->query($sql) === TRUE) {
                $mensaje = "¡Cuenta creada con éxito! <a href='login.php'>Inicia sesión aquí</a>";
                $tipo_mensaje = "green";
            } else {
                $mensaje = "Error: " . $conn->error;
                $tipo_mensaje = "red";
            }
        }
    }
}
?>

<div class="login-wrapper">
    <div class="login-card">
        
        <div style="font-size: 3rem; color: #28a745; margin-bottom: 1rem;">
            <i class="fa-solid fa-user-plus"></i>
        </div>

        <h2>Crear Cuenta</h2>
        <p>Regístrate para gestionar tus directorios</p>

        <?php if($mensaje): ?>
            <div style="background-color: <?php echo ($tipo_mensaje == 'red') ? '#ffe6e6' : '#e6fffa'; ?>; 
                        color: <?php echo ($tipo_mensaje == 'red') ? 'red' : 'green'; ?>; 
                        padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9rem;">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form action="registro.php" method="POST">
            
            <div class="form-group">
                <label for="nombre">Nombre Completo</label>
                <input type="text" name="nombre" class="form-control" placeholder="Ej: Juan Pérez" required>
            </div>

            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" name="email" class="form-control" placeholder="nombre@ejemplo.com" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Repite la contraseña" required>
            </div>

            <button type="submit" class="btn-submit" style="background-color: #28a745;">Registrarse</button>

        </form>

        <div class="login-footer">
            <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión</a></p>
        </div>

    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>