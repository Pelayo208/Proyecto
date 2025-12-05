<?php 
// Usamos __DIR__ para evitar problemas de rutas en Docker
include __DIR__ . '/header.php'; 
?>

<div style="display:flex; flex-direction:column; align-items:center; width: 100%; max-width: 1000px; margin: 0 auto;">

    <div class="welcome-section">
        <h1>Bienvenido al Gestor de Scripts</h1>
        <p>Selecciona tu sistema operativo para generar estructuras de carpetas automáticamente.</p>
    </div>

    <div class="os-container">
        
        <a href="generador.php?os=windows" class="os-card windows-card">
            <i class="fa-brands fa-windows"></i>
            <h2>Windows</h2>
            <p class="os-desc">Generar archivo <b>.BAT</b> compatible con CMD y PowerShell.</p>
        </a>

        <div class="divider">
            <span>O</span>
        </div>

        <a href="generador.php?os=linux" class="os-card linux-card">
            <i class="fa-brands fa-linux"></i>
            <h2>Linux</h2>
            <p class="os-desc">Generar script <b>.SH</b> con permisos de ejecución (chmod).</p>
        </a>

    </div>

    <div style="margin-top: 50px; text-align: center; color: #7f8c8d; max-width: 600px;">
        <h3>¿Cómo funciona?</h3>
        <p style="font-size: 0.95rem;">
            Simplemente sube un archivo CSV con las rutas que necesitas. 
            Nuestra herramienta detectará tu selección y creará el código necesario 
            para crear miles de carpetas en segundos.
        </p>
    </div>

</div>

<?php include __DIR__ . '/footer.php'; ?>