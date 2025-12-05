<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include __DIR__ . '/header.php'; 
include __DIR__ . '/db.php';

$os = isset($_GET['os']) ? $_GET['os'] : 'windows'; 

if ($os === 'linux') {
    $osName = 'Linux (Samba AD)';
    $osIcon = 'fa-brands fa-linux';
    $osColor = '#e95420'; 
    $defaultTab = 'samba_infra';
    $jsMapping = [
        'samba_infra' => 0,
        'samba_users' => 1,
        'samba_resources' => 2,
        'linux_client' => 3
    ];
} else {
    $osName = 'Windows Server';
    $osIcon = 'fa-brands fa-windows';
    $osColor = '#007bff'; 
    $defaultTab = 'infra';
    $jsMapping = [
        'infra' => 0,
        'config' => 1,
        'users' => 2,
        'resources' => 3,
        'maintenance' => 4
    ];
}

$generatedCode = "";
$scriptName = "script.txt";
$activeTab = isset($_POST['active_tab']) ? $_POST['active_tab'] : $defaultTab;

if (isset($_POST['generate_ad_install'])) {
    $activeTab = "infra"; 
    $scriptName = "1_Install_Domain.ps1";
    $domain = $_POST['domain_name']; 
    $netbios = $_POST['netbios_name']; 
    $adminPass = $_POST['safe_password'];
    
    $generatedCode .= "# FASE 1: INSTALAR DOMINIO WINDOWS\n";
    $generatedCode .= "Install-WindowsFeature AD-Domain-Services -IncludeManagementTools\n";
    $generatedCode .= "\$pass = ConvertTo-SecureString '$adminPass' -AsPlainText -Force\n";
    $generatedCode .= "Install-ADDSForest -DomainName '$domain' -DomainNetbiosName '$netbios' -SafeModeAdministratorPassword \$pass -InstallDns:\$true -Force:\$true\n";
    
    $conn->query("INSERT INTO historial (usuario_id, sistema) VALUES ({$_SESSION['user_id']}, 'Win Srv: Crear Dominio')");
}

if (isset($_POST['process_global_config'])) {
    $activeTab = "config"; 
    $scriptName = "2_Config_Network.ps1";
    $orgName = $_POST['org_name']; 
    $dhcpStart = $_POST['dhcp_start']; 
    $dhcpEnd = $_POST['dhcp_end']; 
    
    $gateway = "192.168.1.1";
    if (strpos($dhcpStart, '.') !== false) {
        $parts = explode('.', $dhcpStart);
        $gateway = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.1';
    }
    
    $generatedCode .= "# FASE 2: RED Y CONFIGURACIÓN\n";
    $generatedCode .= "Install-WindowsFeature DHCP -IncludeManagementTools\n";
    $generatedCode .= "Add-DhcpServerv4Scope -Name 'LanScope' -StartRange $dhcpStart -EndRange $dhcpEnd -SubnetMask 255.255.255.0 -State Active\n";
    $generatedCode .= "Set-DhcpServerv4OptionValue -OptionId 3 -Value $gateway\n";
    $generatedCode .= "Set-DhcpServerv4OptionValue -OptionId 6 -Value '127.0.0.1'\n";
    $generatedCode .= "Add-DhcpServerInDC -DnsName 'miempresa.local' -IPAddress '127.0.0.1'\n\n";
    $generatedCode .= "try { New-ADOrganizationalUnit -Name '$orgName' -Path 'DC=miempresa,DC=local' -ErrorAction Stop } catch {}\n";
    $generatedCode .= "try { New-ADOrganizationalUnit -Name 'Bajas' -Path 'OU=$orgName,DC=miempresa,DC=local' } catch {}\n";
    $generatedCode .= "Set-ADDefaultDomainPasswordPolicy -MaxPasswordAge \$null\n";
    $generatedCode .= "\$ScriptPath = 'C:\\Windows\\SYSVOL\\sysvol\\miempresa.local\\scripts\\login.bat'\n";
    $generatedCode .= "Set-Content -Path \$ScriptPath -Value \"@echo off`nif exist Z: net use Z: /delete`nnet use Z: \\\\MIEMPRESA\\Datos /persistent:yes\"\n";
    
    $conn->query("INSERT INTO historial (usuario_id, sistema) VALUES ({$_SESSION['user_id']}, 'Win Srv: Config Global')");
}

if (isset($_POST['process_csv_users'])) {
    $activeTab = "users"; 
    $scriptName = "3_Deploy_Users.ps1";
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = fopen($_FILES['csv_file']['tmp_name'], "r");
        
        $generatedCode .= "# FASE 3: ALTA DE USUARIOS\n";
        $generatedCode .= "\$RootPath = 'C:\\UsuariosCorporativos'\n";
        
        $generatedCode .= "if (!(Test-Path \$RootPath)) { New-Item -ItemType Directory -Path \$RootPath | Out-Null }\n";
        $generatedCode .= "icacls \$RootPath /inheritance:d /grant 'Administrators:(OI)(CI)F' 'SYSTEM:(OI)(CI)F' 'Domain Users:(OI)(CI)RX' /quiet\n";
        $generatedCode .= "if (!(Get-SmbShare -Name 'Usuarios' -ErrorAction SilentlyContinue)) { New-SmbShare -Name 'Usuarios' -Path \$RootPath -FullAccess 'Everyone' }\n\n";
        
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $nombre = trim($data[0] ?? ''); 
            $apellido = trim($data[1] ?? ''); 
            $dept = trim($data[2] ?? 'General'); 
            $pass = trim($data[3] ?? 'P@ssw0rd2024');
            
            if (empty($nombre)) continue;
            
            $sam = strtolower(substr($nombre, 0, 1) . $apellido); 
            $grpName = "GRP_$dept";
            
            $generatedCode .= "if (!(Get-ADGroup -Filter {Name -eq '$grpName'})) { New-ADGroup -Name '$grpName' -GroupScope Global -Path 'CN=Users,DC=miempresa,DC=local' }\n";
            $generatedCode .= "\$p = ConvertTo-SecureString '$pass' -AsPlainText -Force\n";
            
            $generatedCode .= "try { New-ADUser -Name '$sam' -GivenName '$nombre' -AccountPassword \$p -Enabled \$true -Path 'CN=Users,DC=miempresa,DC=local' -HomeDrive 'H:' -HomeDirectory \"\\\\MIEMPRESA\\Usuarios\\$sam\" -ErrorAction Stop } catch {}\n";
            
            $generatedCode .= "Add-ADGroupMember -Identity '$grpName' -Members '$sam' -ErrorAction SilentlyContinue\n";
            
            $generatedCode .= "\$UF = \"\$RootPath\\$sam\"\n";
            $generatedCode .= "if (!(Test-Path \$UF)) { New-Item -ItemType Directory -Path \$UF | Out-Null }\n";
            $generatedCode .= "icacls \$UF /inheritance:d /grant 'Administrators:(OI)(CI)F' \"MIEMPRESA\\$sam:(OI)(CI)F\" /quiet\n";
        }
        fclose($file);
        $conn->query("INSERT INTO historial (usuario_id, sistema) VALUES ({$_SESSION['user_id']}, 'Win Srv: Alta Usuarios')");
    }
}

if (isset($_POST['process_resources'])) {
    $activeTab = "resources"; 
    $scriptName = "4_Deploy_Resources.ps1";
    if (isset($_FILES['csv_resources']) && $_FILES['csv_resources']['error'] == 0) {
        $file = fopen($_FILES['csv_resources']['tmp_name'], "r");
        
        $generatedCode .= "# FASE 4: RECURSOS\n";
        $generatedCode .= "\$DeptRoot = 'C:\\DatosDepartamentos'\n";
        $generatedCode .= "if (!(Test-Path \$DeptRoot)) { New-Item -ItemType Directory -Path \$DeptRoot }\n";
        $generatedCode .= "if (!(Get-SmbShare -Name 'Datos' -ErrorAction SilentlyContinue)) { New-SmbShare -Name 'Datos' -Path \$DeptRoot -FullAccess 'Everyone' }\n\n";
        
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $tipo = strtolower(trim($data[0] ?? '')); $nombre = trim($data[1] ?? ''); $deptString = trim($data[2] ?? ''); $detalle = trim($data[3] ?? '');
            
            if ($tipo == 'carpeta') {
                $generatedCode .= "\$Path = \"\$DeptRoot\\$nombre\"\n";
                $generatedCode .= "New-Item -ItemType Directory -Path \$Path -Force | Out-Null\n";
                $generatedCode .= "icacls \$Path /inheritance:d /grant 'Administrators:(OI)(CI)F' /quiet\n";
                $deptList = explode(';', $deptString);
                foreach ($deptList as $d) { 
                    $d = trim($d); 
                    if(!empty($d)) $generatedCode .= "icacls \$Path /grant \"MIEMPRESA\\GRP_$d:(OI)(CI)M\" /quiet\n"; 
                }
            } elseif ($tipo == 'impresora') {
                $generatedCode .= "if (!(Get-PrinterPort -Name '$detalle' -ErrorAction SilentlyContinue)) { Add-PrinterPort -Name '$detalle' -PrinterHostAddress '$detalle' }\n";
                $generatedCode .= "if (!(Get-Printer -Name '$nombre' -ErrorAction SilentlyContinue)) { Add-Printer -Name '$nombre' -DriverName 'Microsoft IPP Class Driver' -PortName '$detalle' -Shared \$true -ShareName '$nombre' }\n";
            }
        }
        fclose($file);
        $conn->query("INSERT INTO historial (usuario_id, sistema) VALUES ({$_SESSION['user_id']}, 'Win Srv: Recursos')");
    }
}

if (isset($_POST['process_maintenance'])) {
    $activeTab = "maintenance"; 
    $scriptName = "5_Offboarding.ps1";
    if (isset($_FILES['csv_bajas']) && $_FILES['csv_bajas']['error'] == 0) {
        $file = fopen($_FILES['csv_bajas']['tmp_name'], "r");
        
        $generatedCode .= "# FASE 5: BAJAS\n\$BajaDate = Get-Date -Format 'yyyy-MM-dd'\n";
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $userTarget = trim($data[0] ?? '');
            if (empty($userTarget)) continue;
            $generatedCode .= "\$u = Get-ADUser -Filter {SamAccountName -eq '$userTarget'} -Properties MemberOf\n";
            $generatedCode .= "if (\$u) { Disable-ADAccount -Identity \$u; Set-ADUser -Identity \$u -Description \"BAJA - \$BajaDate\"; \$u.MemberOf | ForEach-Object { Remove-ADGroupMember -Identity \$_ -Members \$u -Confirm:\$false -ErrorAction SilentlyContinue }; \$BajaOU = Get-ADOrganizationalUnit -Filter 'Name -eq \"Bajas\"'; if (\$BajaOU) { Move-ADObject -Identity \$u -TargetPath \$BajaOU.DistinguishedName } }\n";
        }
        fclose($file);
        $conn->query("INSERT INTO historial (usuario_id, sistema) VALUES ({$_SESSION['user_id']}, 'Win Srv: Bajas')");
    }
}

if (isset($_POST['generate_samba_infra'])) {
    $activeTab = "samba_infra"; 
    $scriptName = "1_install_samba.sh";
    $domain = $_POST['domain_name'];
    $realm = strtoupper($domain);
    $pass = $_POST['admin_pass'];
    $shortDomain = explode('.', $domain)[0];

    $generatedCode .= "#!/bin/bash\n# Instalación SAMBA 4 DC\n";
    $generatedCode .= "apt update && apt install -y samba smbclient winbind libpam-winbind libnss-winbind krb5-user krb5-config\n";
    $generatedCode .= "systemctl stop smbd nmbd winbind\n";
    $generatedCode .= "mv /etc/samba/smb.conf /etc/samba/smb.conf.bak\n";
    $generatedCode .= "samba-tool domain provision --use-rfc2307 --realm=$realm --domain=$shortDomain --admin-password='$pass' --server-role=dc --dns-backend=SAMBA_INTERNAL\n";
    $generatedCode .= "cp /var/lib/samba/private/krb5.conf /etc/krb5.conf\n";
    $generatedCode .= "systemctl unmask samba-ad-dc\n";
    $generatedCode .= "systemctl enable samba-ad-dc\n";
    $generatedCode .= "systemctl start samba-ad-dc\n";
    
    $conn->query("INSERT INTO historial (usuario_id, sistema) VALUES ({$_SESSION['user_id']}, 'Samba: Provisioning')");
}

if (isset($_POST['process_samba_users'])) {
    $activeTab = "samba_users"; 
    $scriptName = "2_samba_users.sh";
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = fopen($_FILES['csv_file']['tmp_name'], "r");
        $generatedCode .= "#!/bin/bash\n# Usuarios Samba\n\n";
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $nombre = trim($data[0] ?? ''); $apellido = trim($data[1] ?? ''); $dept = trim($data[2] ?? 'General'); $pass = trim($data[3] ?? 'Pass123!');
            if (empty($nombre)) continue;
            $user = strtolower(substr($nombre, 0, 1) . $apellido); $group = "GRP_$dept";
            $generatedCode .= "samba-tool group add $group 2>/dev/null\n";
            $generatedCode .= "samba-tool user create $user '$pass' --given-name='$nombre' --surname='$apellido'\n";
            $generatedCode .= "samba-tool group addmembers $group $user\n";
        }
        fclose($file);
        $conn->query("INSERT INTO historial (usuario_id, sistema) VALUES ({$_SESSION['user_id']}, 'Samba: Usuarios')");
    }
}

if (isset($_POST['process_samba_resources'])) {
    $activeTab = "samba_resources"; 
    $scriptName = "3_samba_shares.sh";
    
    if (isset($_FILES['csv_resources']) && $_FILES['csv_resources']['error'] == 0) {
        $file = fopen($_FILES['csv_resources']['tmp_name'], "r");
        
        $generatedCode .= "#!/bin/bash\n# Recursos Samba (Padre 'Datos')\n";
        $generatedCode .= "mkdir -p /srv/samba/departamentos\n";
        $generatedCode .= "chmod 755 /srv/samba/departamentos\n\n";
        
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $tipo = strtolower(trim($data[0] ?? '')); 
            $nombre = trim($data[1] ?? ''); 
            $grupo = trim($data[2] ?? '');
            
            if ($tipo == 'carpeta') {
                $path = "/srv/samba/departamentos/$nombre";
                $groupName = "GRP_$grupo"; 
                $generatedCode .= "mkdir -p $path\n";
                $generatedCode .= "chown root:\"$groupName\" $path\n";
                $generatedCode .= "chmod 0770 $path\n";
            }
        }
        fclose($file);
        
        $confBlock = "\n### PEGAR EN /etc/samba/smb.conf ###\n";
        $confBlock .= "[Datos]\n"; 
        $confBlock .= "   path = /srv/samba/departamentos\n";
        $confBlock .= "   read only = no\n";
        $confBlock .= "   browseable = yes\n";
        $confBlock .= "   force create mode = 0660\n";
        $confBlock .= "   force directory mode = 0770\n";
        
        $generatedCode .= "echo \"$confBlock\"\n";
        $generatedCode .= "echo \"\n# Reiniciar: systemctl restart samba-ad-dc\"\n";
        
        $conn->query("INSERT INTO historial (usuario_id, sistema) VALUES ({$_SESSION['user_id']}, 'Samba: Recursos')");
    }
}

if (isset($_POST['generate_linux_join'])) {
    $activeTab = "linux_client"; 
    $scriptName = "4_join_domain.sh";
    $domain = $_POST['join_domain']; 
    $adminUser = $_POST['join_user']; 
    $adminPass = $_POST['join_pass'];
    
    $generatedCode .= "#!/bin/bash\n# Unir Cliente Linux\n";
    $generatedCode .= "apt update && apt install -y realmd sssd sssd-tools libnss-sss libpam-sss adcli packagekit policykit-1\n";
    $generatedCode .= "realm discover $domain\n";
    $generatedCode .= "echo '$adminPass' | realm join -U $adminUser $domain --verbose\n";
    $generatedCode .= "echo 'session optional pam_mkhomedir.so skel=/etc/skel umask=0077' >> /etc/pam.d/common-session\n";
    
    $conn->query("INSERT INTO historial (usuario_id, sistema) VALUES ({$_SESSION['user_id']}, 'Linux: Client Join')");
}
?>

<style>
    .tabs-wrapper { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-bottom: 25px; }
    .tab-btn { padding: 12px 15px; background: #f1f3f5; border: 1px solid #dee2e6; border-radius: 8px; cursor: pointer; color: #495057; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease; }
    .tab-btn:hover { background: #e9ecef; transform: translateY(-1px); }
    .tab-btn.active { background: #fff; border-color: <?php echo $osColor; ?>; color: <?php echo $osColor; ?>; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-bottom: 2px solid <?php echo $osColor; ?>; }
    .tab-content { display: none; animation: fadeIn 0.4s; }
    .tab-content.active { display: block; }
    .config-panel { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto; text-align: left; }
    .input-group { margin-bottom: 15px; }
    .input-group label { display: block; font-weight: 600; margin-bottom: 5px; color: #555; }
    .btn-submit { width: 100%; padding: 12px; border:none; border-radius:8px; color:white; font-weight:bold; cursor:pointer; background: <?php echo $osColor; ?>; }
    .drag-area { border: 2px dashed #ccc; background: white; padding: 40px; border-radius: 12px; cursor: pointer; transition: 0.3s; }
    .drag-area:hover { border-color: <?php echo $osColor; ?>; background: #f8f9fa; }
</style>

<div style="max-width: 950px; margin: 40px auto; width: 100%; text-align: center;">

    <h1 style="margin-bottom: 30px; color: #2c3e50;">
        <i class="<?php echo $osIcon; ?>" style="color: <?php echo $osColor; ?>;"></i> 
        Gestor de Dominios <?php echo $osName; ?>
    </h1>

    <?php if ($os === 'windows'): ?>
        <div class="tabs-wrapper">
            <div class="tab-btn <?php echo ($activeTab == 'infra') ? 'active' : ''; ?>" onclick="openTab('infra')"><i class="fa-solid fa-server"></i> 1. Dominio</div>
            <div class="tab-btn <?php echo ($activeTab == 'config') ? 'active' : ''; ?>" onclick="openTab('config')"><i class="fa-solid fa-network-wired"></i> 2. Red</div>
            <div class="tab-btn <?php echo ($activeTab == 'users') ? 'active' : ''; ?>" onclick="openTab('users')"><i class="fa-solid fa-user-plus"></i> 3. Altas</div>
            <div class="tab-btn <?php echo ($activeTab == 'resources') ? 'active' : ''; ?>" onclick="openTab('resources')"><i class="fa-solid fa-folder-tree"></i> 4. Recursos</div>
            <div class="tab-btn <?php echo ($activeTab == 'maintenance') ? 'active' : ''; ?>" onclick="openTab('maintenance')"><i class="fa-solid fa-user-slash"></i> 5. Bajas</div>
        </div>

        <div id="infra" class="tab-content <?php echo ($activeTab == 'infra') ? 'active' : ''; ?>">
            <div class="config-panel">
                <h3>Nuevo Dominio</h3>
                <form action="" method="POST"><input type="hidden" name="active_tab" value="infra">
                <div class="input-group"><label>Dominio (FQDN)</label><input type="text" name="domain_name" class="form-control" placeholder="ej: corporacion.local" required></div>
                <div class="input-group"><label>NetBIOS</label><input type="text" name="netbios_name" class="form-control" placeholder="ej: CORPORACION" required></div>
                <div class="input-group"><label>Pass Admin</label><input type="password" name="safe_password" class="form-control" required></div>
                <button type="submit" name="generate_ad_install" class="btn-submit">Generar Script</button></form>
            </div>
        </div>
        <div id="config" class="tab-content <?php echo ($activeTab == 'config') ? 'active' : ''; ?>">
            <div class="config-panel">
                <h3>Red y GPO</h3>
                <form action="" method="POST"><input type="hidden" name="active_tab" value="config">
                <div class="input-group"><label>Org (OU Raíz)</label><input type="text" name="org_name" class="form-control" placeholder="MiEmpresa" required></div>
                <div class="input-group"><label>Inicio DHCP</label><input type="text" name="dhcp_start" class="form-control" placeholder="192.168.1.100" required></div>
                <div class="input-group"><label>Fin DHCP</label><input type="text" name="dhcp_end" class="form-control" placeholder="192.168.1.200" required></div>
                <button type="submit" name="process_global_config" class="btn-submit" style="background:#6f42c1;">Generar</button></form>
            </div>
        </div>
        <div id="users" class="tab-content <?php echo ($activeTab == 'users') ? 'active' : ''; ?>">
            <form action="" method="POST" enctype="multipart/form-data"><input type="hidden" name="active_tab" value="users">
                <div class="drag-area" onclick="document.getElementById('file-users').click()">
                    <i class="fa-solid fa-file-csv" style="font-size:3rem; color:#ccc;"></i><h3>Altas Masivas</h3><p>CSV: Nombre, Apellido, Dept, Pass</p>
                    <input type="file" name="csv_file" id="file-users" hidden onchange="this.form.submit()"><input type="hidden" name="process_csv_users" value="1">
                </div>
            </form>
        </div>
        <div id="resources" class="tab-content <?php echo ($activeTab == 'resources') ? 'active' : ''; ?>">
            <form action="" method="POST" enctype="multipart/form-data"><input type="hidden" name="active_tab" value="resources">
                <div class="drag-area" style="border-color:#28a745;" onclick="document.getElementById('file-res').click()">
                    <i class="fa-solid fa-network-wired" style="font-size:3rem; color:#28a745;"></i><h3>Recursos</h3><p>CSV: Tipo, Nombre, Depts, Detalle</p>
                    <input type="file" name="csv_resources" id="file-res" hidden onchange="this.form.submit()"><input type="hidden" name="process_resources" value="1">
                </div>
            </form>
        </div>
        <div id="maintenance" class="tab-content <?php echo ($activeTab == 'maintenance') ? 'active' : ''; ?>">
            <form action="" method="POST" enctype="multipart/form-data"><input type="hidden" name="active_tab" value="maintenance">
                <div class="drag-area" style="border-color:#dc3545;" onclick="document.getElementById('file-bajas').click()">
                    <i class="fa-solid fa-user-xmark" style="font-size:3rem; color:#dc3545;"></i><h3 style="color:#dc3545;">Bajas</h3><p>CSV: Usuario</p>
                    <input type="file" name="csv_bajas" id="file-bajas" hidden onchange="this.form.submit()"><input type="hidden" name="process_maintenance" value="1">
                </div>
            </form>
        </div>

    <?php else: ?>

        <div class="tabs-wrapper">
            <div class="tab-btn <?php echo ($activeTab == 'samba_infra') ? 'active' : ''; ?>" onclick="openTab('samba_infra')"><i class="fa-solid fa-server"></i> 1. Instalar Samba</div>
            <div class="tab-btn <?php echo ($activeTab == 'samba_users') ? 'active' : ''; ?>" onclick="openTab('samba_users')"><i class="fa-solid fa-users-gear"></i> 2. Usuarios</div>
            <div class="tab-btn <?php echo ($activeTab == 'samba_resources') ? 'active' : ''; ?>" onclick="openTab('samba_resources')"><i class="fa-solid fa-folder-tree"></i> 3. Recursos</div>
            <div class="tab-btn <?php echo ($activeTab == 'linux_client') ? 'active' : ''; ?>" onclick="openTab('linux_client')"><i class="fa-solid fa-desktop"></i> 4. Unir Cliente</div>
        </div>

        <div id="samba_infra" class="tab-content <?php echo ($activeTab == 'samba_infra') ? 'active' : ''; ?>">
            <div class="config-panel">
                <h3>Provisioning Samba 4</h3>
                <form action="" method="POST"><input type="hidden" name="active_tab" value="samba_infra">
                <div class="input-group"><label>Dominio</label><input type="text" name="domain_name" class="form-control" placeholder="empresa.local" required></div>
                <div class="input-group"><label>Pass Admin</label><input type="password" name="admin_pass" class="form-control" required></div>
                <button type="submit" name="generate_samba_infra" class="btn-submit" style="background:#e95420;">Generar Script Instalación</button>
                </form>
            </div>
        </div>
        <div id="samba_users" class="tab-content <?php echo ($activeTab == 'samba_users') ? 'active' : ''; ?>">
            <form action="" method="POST" enctype="multipart/form-data"><input type="hidden" name="active_tab" value="samba_users">
                <div class="drag-area" style="border-color:#e95420;" onclick="document.getElementById('file-smb-u').click()">
                    <i class="fa-solid fa-users" style="font-size:3rem; color:#e95420;"></i><h3>Usuarios Dominio</h3><p>CSV: Nombre, Apellido, Dept, Pass</p>
                    <input type="file" name="csv_file" id="file-smb-u" hidden onchange="this.form.submit()"><input type="hidden" name="process_samba_users" value="1">
                </div>
            </form>
        </div>
        <div id="samba_resources" class="tab-content <?php echo ($activeTab == 'samba_resources') ? 'active' : ''; ?>">
            <form action="" method="POST" enctype="multipart/form-data"><input type="hidden" name="active_tab" value="samba_resources">
                <div class="drag-area" style="border-color:#772953;" onclick="document.getElementById('file-smb-r').click()">
                    <i class="fa-solid fa-file-code" style="font-size:3rem; color:#772953;"></i><h3>Recursos (smb.conf)</h3><p>CSV: carpeta, Nombre, Grupo</p>
                    <input type="file" name="csv_resources" id="file-smb-r" hidden onchange="this.form.submit()"><input type="hidden" name="process_samba_resources" value="1">
                </div>
            </form>
        </div>
        <div id="linux_client" class="tab-content <?php echo ($activeTab == 'linux_client') ? 'active' : ''; ?>">
            <div class="config-panel">
                <h3><i class="fa-brands fa-ubuntu"></i> Unir PC al Dominio</h3>
                <form action="" method="POST">
                    <input type="hidden" name="active_tab" value="linux_client">
                    <div class="input-group"><label>Dominio</label><input type="text" name="join_domain" class="form-control" placeholder="empresa.local" required></div>
                    <div class="input-group"><label>Usuario Admin</label><input type="text" name="join_user" class="form-control" placeholder="administrator" required></div>
                    <div class="input-group"><label>Pass Admin</label><input type="password" name="join_pass" class="form-control" required></div>
                    <button type="submit" name="generate_linux_join" class="btn-submit" style="background:#5E2750;">Generar Script</button></form>
            </div>
        </div>

    <?php endif; ?>

    <?php if ($generatedCode): ?>
    <div style="margin-top: 40px; text-align: left; animation: fadeIn 0.5s;">
        <div style="background: #1e1e1e; color: #fff; padding: 20px; border-radius: 8px;">
            <div style="display:flex; justify-content:space-between; margin-bottom: 10px;">
                <span style="font-family: monospace; font-weight: bold; color: #00ff00;">>_ Output Generado</span>
                <a href="data:text/plain;charset=utf-8,<?php echo rawurlencode($generatedCode); ?>" download="<?php echo $scriptName; ?>" class="btn-login" style="padding: 5px 15px; font-size: 0.8rem; height: auto;"><i class="fa-solid fa-download"></i> Descargar</a>
            </div>
            <textarea style="width: 100%; height: 350px; background: #000; border: 1px solid #333; color: #a5d6ff; font-family: consolas, monospace; resize: vertical; padding: 10px;" readonly><?php echo $generatedCode; ?></textarea>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
    const mapping = <?php echo json_encode($jsMapping); ?>;

    function openTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        
        const btns = document.querySelectorAll('.tab-btn');
        if (mapping[tabName] !== undefined && btns[mapping[tabName]]) {
            btns[mapping[tabName]].classList.add('active');
        }
    }
</script>

<?php include __DIR__ . '/footer.php'; ?>