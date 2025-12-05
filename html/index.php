<?php 
include __DIR__ . '/header.php'; 
?>

<div style="display:flex; flex-direction:column; align-items:center; width: 100%; max-width: 1000px; margin: 0 auto;">

    <div class="welcome-section">
        <h1>Bienvenido al Gestor de Scripts</h1>
        <p>Selecciona tu sistema operativo para generar automatizaciones de infraestructura.</p>
    </div>

    <div class="os-container">
        
        <a href="generador.php?os=windows" class="os-card windows-card">
            <i class="fa-brands fa-windows"></i>
            <h2>Windows</h2>
            <p class="os-desc">Scripts PowerShell para Active Directory, DHCP y GPO.</p>
        </a>

        <div class="divider">
            <span>O</span>
        </div>

        <a href="generador.php?os=linux" class="os-card linux-card">
            <i class="fa-brands fa-linux"></i>
            <h2>Linux</h2>
            <p class="os-desc">Scripts Bash para Samba 4 AD DC y gestión de usuarios.</p>
        </a>

    </div>

    <div style="margin-top: 50px; text-align: center; color: #7f8c8d; max-width: 600px;">
        <h3>¿Cómo funciona?</h3>
        <p style="font-size: 0.95rem;">
            Esta herramienta permite desplegar infraestructuras complejas en segundos.
            Simplemente selecciona tu entorno, sube los archivos CSV requeridos y descarga el script listo para ejecutar en tu servidor.
        </p>
    </div>

</div>

<?php include __DIR__ . '/footer.php'; ?>