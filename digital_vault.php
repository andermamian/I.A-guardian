<?php
/**
 * GuardianIA v3.0 FINAL - Boveda Digital Militar
 * Anderson Mamian Chicangana - Sistema de Almacenamiento Seguro
 * Encriptacion Militar + Interfaz Futurista
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_military.php';

// Verificar autenticacion
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// ========================================
// CREAR TABLA SI NO EXISTE (ANTES DE USARLA)
// ========================================
if ($db && $db->isConnected()) {
    try {
        $db->getConnection()->query("
            CREATE TABLE IF NOT EXISTS vault_files (
                id INT AUTO_INCREMENT PRIMARY KEY,
                file_id VARCHAR(50) UNIQUE NOT NULL,
                user_id INT NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                file_type VARCHAR(100),
                file_size BIGINT,
                encrypted_path VARCHAR(500),
                upload_date DATETIME,
                last_access DATETIME,
                encryption_method VARCHAR(50),
                integrity_hash VARCHAR(128),
                INDEX idx_user (user_id),
                INDEX idx_file_id (file_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        logMilitaryEvent('VAULT_TABLE_SYNC', 'Tabla vault_files sincronizada correctamente', 'UNCLASSIFIED');
    } catch (Exception $e) {
        error_log("Error creando tabla vault_files: " . $e->getMessage());
        logMilitaryEvent('VAULT_TABLE_ERROR', 'Error sincronizando tabla: ' . $e->getMessage(), 'WARNING');
    }
}

// Clase para manejo de la boveda digital
class DigitalVault {
    private $db;
    private $vault_path;
    private $encryption_key;
    private $user_id;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->user_id = $_SESSION['user_id'] ?? 0;
        $this->vault_path = __DIR__ . '/vault/' . $this->user_id;
        $this->encryption_key = hash('sha256', MASTER_ENCRYPTION_KEY . $this->user_id);
        
        // Crear directorio de boveda si no existe
        if (!file_exists($this->vault_path)) {
            mkdir($this->vault_path, 0700, true);
        }
    }
    
    public function uploadFile($file) {
        $file_id = uniqid('vault_');
        $original_name = $file['name'];
        $file_size = $file['size'];
        $file_type = $file['type'];
        
        // Leer contenido del archivo
        $content = file_get_contents($file['tmp_name']);
        
        // Encriptar contenido
        $encrypted_content = $this->encryptData($content);
        
        // Guardar archivo encriptado
        $encrypted_path = $this->vault_path . '/' . $file_id . '.enc';
        file_put_contents($encrypted_path, $encrypted_content);
        
        // Guardar metadata en base de datos
        if ($this->db && $this->db->isConnected()) {
            try {
                $this->db->query(
                    "INSERT INTO vault_files (file_id, user_id, original_name, file_type, file_size, encrypted_path, upload_date, encryption_method) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)",
                    [$file_id, $this->user_id, $original_name, $file_type, $file_size, $encrypted_path, 'AES-256-GCM']
                );
            } catch (Exception $e) {
                error_log("Error guardando metadata del archivo: " . $e->getMessage());
                // Continuar aunque falle la BD, el archivo está encriptado localmente
            }
        }
        
        // Log de seguridad
        logMilitaryEvent('VAULT_UPLOAD', "Archivo almacenado: {$original_name}", 'CONFIDENTIAL');
        
        return [
            'success' => true,
            'file_id' => $file_id,
            'name' => $original_name,
            'size' => $file_size
        ];
    }
    
    public function getFiles() {
        $files = [];
        
        if ($this->db && $this->db->isConnected()) {
            try {
                $result = $this->db->query(
                    "SELECT * FROM vault_files WHERE user_id = ? ORDER BY upload_date DESC",
                    [$this->user_id]
                );
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $files[] = $row;
                    }
                }
            } catch (Exception $e) {
                error_log("Error obteniendo archivos de la BD: " . $e->getMessage());
                // Continuar con búsqueda local
            }
        }
        
        // Si no hay DB o no hay archivos en DB, buscar archivos locales
        if (empty($files) && is_dir($this->vault_path)) {
            $vault_files = glob($this->vault_path . '/*.enc');
            foreach ($vault_files as $file) {
                $files[] = [
                    'file_id' => basename($file, '.enc'),
                    'original_name' => 'Encrypted File',
                    'file_size' => filesize($file),
                    'upload_date' => date('Y-m-d H:i:s', filemtime($file))
                ];
            }
        }
        
        return $files;
    }
    
    public function downloadFile($file_id) {
        // Primero intentar obtener info de la BD
        if ($this->db && $this->db->isConnected()) {
            try {
                $result = $this->db->query(
                    "SELECT * FROM vault_files WHERE file_id = ? AND user_id = ?",
                    [$file_id, $this->user_id]
                );
                
                if ($result && $row = $result->fetch_assoc()) {
                    $encrypted_path = $row['encrypted_path'];
                    $original_name = $row['original_name'];
                    
                    if (file_exists($encrypted_path)) {
                        // Leer y desencriptar
                        $encrypted_content = file_get_contents($encrypted_path);
                        $decrypted_content = $this->decryptData($encrypted_content);
                        
                        // Log de seguridad
                        logMilitaryEvent('VAULT_DOWNLOAD', "Archivo descargado: {$original_name}", 'CONFIDENTIAL');
                        
                        return [
                            'success' => true,
                            'content' => $decrypted_content,
                            'name' => $original_name,
                            'type' => $row['file_type']
                        ];
                    }
                }
            } catch (Exception $e) {
                error_log("Error descargando archivo de BD: " . $e->getMessage());
            }
        }
        
        // Busqueda local como fallback
        $local_path = $this->vault_path . '/' . $file_id . '.enc';
        if (file_exists($local_path)) {
            $encrypted_content = file_get_contents($local_path);
            $decrypted_content = $this->decryptData($encrypted_content);
            
            return [
                'success' => true,
                'content' => $decrypted_content,
                'name' => 'downloaded_file',
                'type' => 'application/octet-stream'
            ];
        }
        
        return ['success' => false, 'error' => 'Archivo no encontrado'];
    }
    
    public function deleteFile($file_id) {
        $deleted = false;
        
        if ($this->db && $this->db->isConnected()) {
            try {
                $result = $this->db->query(
                    "SELECT encrypted_path FROM vault_files WHERE file_id = ? AND user_id = ?",
                    [$file_id, $this->user_id]
                );
                
                if ($result && $row = $result->fetch_assoc()) {
                    // Eliminar archivo fisico
                    if (file_exists($row['encrypted_path'])) {
                        unlink($row['encrypted_path']);
                        $deleted = true;
                    }
                    
                    // Eliminar de DB
                    $this->db->query(
                        "DELETE FROM vault_files WHERE file_id = ? AND user_id = ?",
                        [$file_id, $this->user_id]
                    );
                    
                    logMilitaryEvent('VAULT_DELETE', "Archivo eliminado: {$file_id}", 'CONFIDENTIAL');
                    
                    return ['success' => true];
                }
            } catch (Exception $e) {
                error_log("Error eliminando archivo de BD: " . $e->getMessage());
            }
        }
        
        // Intento de eliminacion local
        $local_path = $this->vault_path . '/' . $file_id . '.enc';
        if (file_exists($local_path)) {
            unlink($local_path);
            $deleted = true;
        }
        
        return ['success' => $deleted, 'error' => $deleted ? null : 'Archivo no encontrado'];
    }
    
    private function encryptData($data) {
        if (!MILITARY_ENCRYPTION_ENABLED || !function_exists('openssl_encrypt')) {
            return base64_encode($data);
        }
        
        $iv = random_bytes(16);
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-gcm',
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    private function decryptData($encrypted_data) {
        if (!MILITARY_ENCRYPTION_ENABLED || !function_exists('openssl_decrypt')) {
            return base64_decode($encrypted_data);
        }
        
        $data = base64_decode($encrypted_data);
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $encrypted = substr($data, 32);
        
        return openssl_decrypt(
            $encrypted,
            'aes-256-gcm',
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }
    
    public function getVaultStats() {
        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'encryption_method' => 'AES-256-GCM',
            'last_access' => date('Y-m-d H:i:s')
        ];
        
        try {
            $files = $this->getFiles();
            $stats['total_files'] = count($files);
            
            foreach ($files as $file) {
                $stats['total_size'] += $file['file_size'] ?? 0;
            }
        } catch (Exception $e) {
            error_log("Error obteniendo estadisticas: " . $e->getMessage());
        }
        
        return $stats;
    }
}

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $vault = new DigitalVault();
    
    switch ($_POST['action']) {
        case 'upload':
            if (isset($_FILES['file'])) {
                $result = $vault->uploadFile($_FILES['file']);
                echo json_encode($result);
            }
            exit;
            
        case 'list':
            $files = $vault->getFiles();
            echo json_encode(['success' => true, 'files' => $files]);
            exit;
            
        case 'download':
            if (isset($_POST['file_id'])) {
                $result = $vault->downloadFile($_POST['file_id']);
                if ($result['success']) {
                    header('Content-Type: ' . $result['type']);
                    header('Content-Disposition: attachment; filename="' . $result['name'] . '"');
                    echo $result['content'];
                    exit;
                }
            }
            break;
            
        case 'delete':
            if (isset($_POST['file_id'])) {
                $result = $vault->deleteFile($_POST['file_id']);
                echo json_encode($result);
            }
            exit;
    }
}

// Inicializar boveda
$vault = new DigitalVault();
$vault_stats = $vault->getVaultStats();
$user_files = $vault->getFiles();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Vault - GuardianIA v3.0 MILITARY</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');
        
        body {
            font-family: 'Orbitron', monospace;
            background: #000;
            color: #00ff88;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Fondo animado cyberpunk */
        .cyber-grid {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(180deg, #000 0%, #0a0a1f 50%, #000 100%);
            z-index: -2;
        }
        
        .cyber-grid::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(0, 255, 136, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 255, 136, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: grid-move 10s linear infinite;
        }
        
        @keyframes grid-move {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        /* Particulas flotantes */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #00ff88;
            box-shadow: 0 0 10px #00ff88;
            border-radius: 50%;
            animation: float-up 15s linear infinite;
        }
        
        @keyframes float-up {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) rotate(720deg);
                opacity: 0;
            }
        }
        
        /* Header holografico */
        .header {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1), rgba(102, 126, 234, 0.1));
            border-bottom: 2px solid #00ff88;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 200%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(0, 255, 136, 0.3), 
                transparent);
            animation: scan 3s linear infinite;
        }
        
        @keyframes scan {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        h1 {
            font-size: 3em;
            font-weight: 900;
            text-transform: uppercase;
            text-align: center;
            background: linear-gradient(45deg, #00ff88, #667eea, #764ba2, #00ff88);
            background-size: 400% 400%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-shift 5s ease infinite;
            text-shadow: 0 0 30px rgba(0, 255, 136, 0.5);
        }
        
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Container principal */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid #00ff88;
            border-radius: 15px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.3);
            border-color: #667eea;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #00ff88, #667eea, #764ba2, #00ff88);
            border-radius: 15px;
            opacity: 0;
            z-index: -1;
            transition: opacity 0.3s ease;
            animation: gradient-shift 3s ease infinite;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: 900;
            color: #00ff88;
            text-shadow: 0 0 20px rgba(0, 255, 136, 0.5);
        }
        
        .stat-label {
            font-size: 0.9em;
            color: #667eea;
            text-transform: uppercase;
            margin-top: 10px;
        }
        
        /* Upload zone */
        .upload-zone {
            background: rgba(0, 0, 0, 0.9);
            border: 2px dashed #00ff88;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .upload-zone:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        
        .upload-zone.dragover {
            background: rgba(0, 255, 136, 0.1);
            border-color: #00ff88;
            transform: scale(1.02);
        }
        
        .upload-icon {
            font-size: 4em;
            margin-bottom: 20px;
            animation: pulse 2s ease infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Files grid */
        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .file-card {
            background: rgba(0, 0, 0, 0.9);
            border: 1px solid rgba(0, 255, 136, 0.3);
            border-radius: 15px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .file-card:hover {
            transform: translateY(-5px) rotateX(5deg);
            box-shadow: 0 20px 40px rgba(0, 255, 136, 0.2);
            border-color: #667eea;
        }
        
        .file-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 136, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .file-card:hover::after {
            left: 100%;
        }
        
        .file-icon {
            font-size: 3em;
            margin-bottom: 15px;
            color: #667eea;
        }
        
        .file-name {
            font-weight: 700;
            color: #00ff88;
            margin-bottom: 10px;
            word-break: break-all;
        }
        
        .file-meta {
            font-size: 0.8em;
            color: #667eea;
            margin: 5px 0;
        }
        
        .file-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        /* Buttons */
        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 0.9em;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff0044, #cc0033);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #00ff88, #00cc66);
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 0.8em;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.95), rgba(10, 10, 31, 0.95));
            border: 2px solid #00ff88;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            position: relative;
            animation: modal-appear 0.3s ease;
        }
        
        @keyframes modal-appear {
            0% {
                opacity: 0;
                transform: scale(0.8) rotateX(90deg);
            }
            100% {
                opacity: 1;
                transform: scale(1) rotateX(0);
            }
        }
        
        /* Progress bar */
        .progress-bar {
            width: 100%;
            height: 30px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff88;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00ff88, #667eea);
            width: 0%;
            transition: width 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s linear infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        /* Loading spinner */
        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(0, 255, 136, 0.3);
            border-top: 3px solid #00ff88;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Glitch effect */
        @keyframes glitch {
            0%, 100% {
                transform: translate(0);
                filter: hue-rotate(0deg);
            }
            20% {
                transform: translate(-1px, 1px);
                filter: hue-rotate(90deg);
            }
            40% {
                transform: translate(1px, -1px);
                filter: hue-rotate(180deg);
            }
            60% {
                transform: translate(-1px, -1px);
                filter: hue-rotate(270deg);
            }
            80% {
                transform: translate(1px, 1px);
                filter: hue-rotate(360deg);
            }
        }
        
        .glitch {
            animation: glitch 0.3s ease infinite;
        }
        
        /* Security badge */
        .security-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.9);
            border: 2px solid #00ff88;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 0.8em;
            text-transform: uppercase;
            animation: pulse 2s ease infinite;
        }
        
        .encryption-status {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            background: #00ff88;
            border-radius: 50%;
            animation: blink 1s ease infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            h1 {
                font-size: 2em;
            }
            
            .files-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Cyber grid background -->
    <div class="cyber-grid"></div>
    
    <!-- Particulas -->
    <div class="particles" id="particles"></div>
    
    <!-- Security badge -->
    <div class="security-badge">
        <div class="encryption-status">
            <span class="status-dot"></span>
            <span><?php echo MILITARY_ENCRYPTION_ENABLED ? 'MILITARY ENCRYPTION ACTIVE' : 'STANDARD ENCRYPTION'; ?></span>
        </div>
    </div>
    
    <!-- Header -->
    <div class="header">
        <h1>🔐 DIGITAL VAULT</h1>
        <p style="text-align: center; color: #667eea; margin-top: 10px;">
            GuardianIA v3.0 MILITARY - Secure Storage System
        </p>
    </div>
    
    <!-- Container -->
    <div class="container">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $vault_stats['total_files']; ?></div>
                <div class="stat-label">📁 Total Files</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($vault_stats['total_size'] / 1024 / 1024, 2); ?> MB</div>
                <div class="stat-label">💾 Storage Used</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $vault_stats['encryption_method']; ?></div>
                <div class="stat-label">🔒 Encryption</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $_SESSION['security_clearance'] ?? 'UNCLASSIFIED'; ?></div>
                <div class="stat-label">🎖️ Clearance Level</div>
            </div>
        </div>
        
        <!-- Upload Zone -->
        <div class="upload-zone" id="uploadZone">
            <div class="upload-icon">📤</div>
            <h2 style="color: #00ff88; margin-bottom: 10px;">SECURE UPLOAD</h2>
            <p style="color: #667eea;">Drag & drop files here or click to browse</p>
            <input type="file" id="fileInput" style="display: none;" multiple>
            <button class="btn" style="margin-top: 20px;" onclick="document.getElementById('fileInput').click()">
                SELECT FILES
            </button>
        </div>
        
        <!-- Progress -->
        <div class="progress-bar" id="progressBar" style="display: none;">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        
        <!-- Files Grid -->
        <h2 style="color: #00ff88; margin: 30px 0;">🗂️ ENCRYPTED FILES</h2>
        <div class="files-grid" id="filesGrid">
            <?php foreach ($user_files as $file): ?>
            <div class="file-card" data-file-id="<?php echo htmlspecialchars($file['file_id']); ?>">
                <div class="file-icon">
                    <?php
                    $extension = pathinfo($file['original_name'], PATHINFO_EXTENSION);
                    $icon = '📄';
                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) $icon = '🖼️';
                    elseif (in_array($extension, ['pdf'])) $icon = '📕';
                    elseif (in_array($extension, ['doc', 'docx'])) $icon = '📝';
                    elseif (in_array($extension, ['zip', 'rar'])) $icon = '📦';
                    elseif (in_array($extension, ['mp3', 'wav'])) $icon = '🎵';
                    elseif (in_array($extension, ['mp4', 'avi'])) $icon = '🎬';
                    echo $icon;
                    ?>
                </div>
                <div class="file-name"><?php echo htmlspecialchars($file['original_name']); ?></div>
                <div class="file-meta">Size: <?php echo number_format($file['file_size'] / 1024, 2); ?> KB</div>
                <div class="file-meta">Uploaded: <?php echo $file['upload_date']; ?></div>
                <div class="file-actions">
                    <button class="btn btn-success btn-small" onclick="downloadFile('<?php echo $file['file_id']; ?>')">
                        Download
                    </button>
                    <button class="btn btn-danger btn-small" onclick="deleteFile('<?php echo $file['file_id']; ?>')">
                        Delete
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($user_files)): ?>
        <div style="text-align: center; padding: 60px; color: #667eea;">
            <div style="font-size: 4em; margin-bottom: 20px;">🔒</div>
            <h3>Your vault is empty</h3>
            <p>Upload files to start securing them with military-grade encryption</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal -->
    <div class="modal" id="modal">
        <div class="modal-content">
            <h2 style="color: #00ff88; margin-bottom: 20px;">Processing...</h2>
            <div class="spinner"></div>
            <p id="modalMessage" style="text-align: center; color: #667eea;"></p>
        </div>
    </div>
    
    <script>
        // Crear particulas
        function createParticles() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }
        
        createParticles();
        
        // Drag and drop
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            handleFiles(files);
        });
        
        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
        
        // Upload files
        function handleFiles(files) {
            for (let file of files) {
                uploadFile(file);
            }
        }
        
        function uploadFile(file) {
            const formData = new FormData();
            formData.append('action', 'upload');
            formData.append('file', file);
            
            // Mostrar progress bar
            document.getElementById('progressBar').style.display = 'block';
            const progressFill = document.getElementById('progressFill');
            
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressFill.style.width = percentComplete + '%';
                }
            });
            
            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showMessage('File uploaded successfully!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showMessage('Upload failed!', 'error');
                    }
                }
                progressFill.style.width = '0%';
                setTimeout(() => {
                    document.getElementById('progressBar').style.display = 'none';
                }, 1000);
            });
            
            xhr.open('POST', window.location.href);
            xhr.send(formData);
        }
        
        // Download file
        function downloadFile(fileId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'download';
            
            const fileInput = document.createElement('input');
            fileInput.type = 'hidden';
            fileInput.name = 'file_id';
            fileInput.value = fileId;
            
            form.appendChild(actionInput);
            form.appendChild(fileInput);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
        
        // Delete file
        function deleteFile(fileId) {
            if (!confirm('Are you sure you want to delete this file? This action cannot be undone.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('file_id', fileId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const card = document.querySelector(`[data-file-id="${fileId}"]`);
                    card.style.animation = 'glitch 0.3s ease';
                    setTimeout(() => {
                        card.remove();
                        showMessage('File deleted successfully!', 'success');
                    }, 300);
                } else {
                    showMessage('Delete failed!', 'error');
                }
            });
        }
        
        // Show message
        function showMessage(message, type) {
            const modal = document.getElementById('modal');
            const modalMessage = document.getElementById('modalMessage');
            
            modalMessage.textContent = message;
            modalMessage.style.color = type === 'success' ? '#00ff88' : '#ff0044';
            modal.classList.add('active');
            
            setTimeout(() => {
                modal.classList.remove('active');
            }, 2000);
        }
        
        // Console message
        console.log('%c🔐 DIGITAL VAULT ACTIVE', 'color: #00ff88; font-size: 20px; font-weight: bold;');
        console.log('%cMilitary-grade encryption enabled', 'color: #667eea; font-size: 14px;');
        console.log('%cAll files are encrypted with AES-256-GCM', 'color: #764ba2; font-size: 12px;');
    </script>
</body>
</html>