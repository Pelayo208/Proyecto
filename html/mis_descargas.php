<?php
session_start();

// 1. Seguridad
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include __DIR__ . '/header.php';
include __DIR__ . '/db.php';

$usuario_id = $_SESSION['user_id'];

// 2. Consulta
$sql = "SELECT * FROM historial WHERE usuario_id = $usuario_id ORDER BY fecha DESC";
$result = $conn->query($sql);
?>

<div style="max-width: 900px; margin: 40px auto; width: 100%;">

    <div class="welcome-section" style="text-align: left; margin-bottom: 20px;">
        <h1><i class="fa-solid fa-folder-open"></i> Tu Actividad</h1>
        <p>Aquí puedes ver los scripts que has generado recientemente.</p>
    </div>

    <div class="table-container">
        
        <?php if ($result->num_rows > 0): ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sistema Operativo</th>
                        <th>Fecha y Hora</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php 
                            // --- CORRECCIÓN AQUÍ ---
                            // Analizamos el texto para ver si contiene palabras clave de Linux
                            $sys = $row['sistema'];
                            
                            // Si contiene "Linux" O "Samba", es Linux
                            $isLinux = (stripos($sys, 'Linux') !== false || stripos($sys, 'Samba') !== false);
                            
                            $badgeClass = $isLinux ? 'badge-linux' : 'badge-win';
                            // Usamos fa-linux para Linux/Samba, fa-windows para el resto
                            $icon = $isLinux ? 'fa-brands fa-linux' : 'fa-brands fa-windows';
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <i class="<?php echo $icon; ?>"></i> <?php echo $sys; ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date("d/m/Y - H:i", strtotime($row['fecha'])); ?>
                            </td>
                            <td style="color: #28a745; font-weight: bold;">
                                <i class="fa-solid fa-check"></i> Generado
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            
            <div style="text-align: center; padding: 40px; color: #999;">
                <i class="fa-solid fa-ghost" style="font-size: 3rem; margin-bottom: 10px;"></i>
                <p>Aún no has generado ningún script.</p>
                <a href="index.php" style="color: #007bff;">¡Crea el primero ahora!</a>
            </div>

        <?php endif; ?>
        
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>