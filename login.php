<?php
/**
 * GuardianIA v3.0 FINAL - Sistema de Login Militar
 * Anderson Mamian Chicangana - Sistema de Autenticacion
 * Compatible con config.php y config_military.php
 */

// Cargar configuracion PRIMERO (incluye inicializacion de sesion)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_military.php';

/**
 * Funcion mejorada para autenticar usuario
 */
function authenticateUser($username, $password) {
    global $db, $DEFAULT_USERS;
    
    // Intentar autenticacion con base de datos
    if ($db && $db->isConnected()) {
        try {
            $result = $db->query("SELECT * FROM users WHERE username = ? AND status = 'active'", [$username]);
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Verificar contraseña (hash o texto plano para compatibilidad)
                $password_valid = false;
                if (!empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                    $password_valid = true;
                } elseif (!empty($user['password']) && $password === $user['password']) {
                    $password_valid = true;
                }
                
                if ($password_valid) {
                    // Actualizar ultimo login
                    $db->query("UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?", [$user['id']]);
                    
                    // Log de acceso militar
                    logMilitaryEvent('USER_LOGIN_SUCCESS', "Usuario autenticado: {$username}", $user['security_clearance'] ?? 'UNCLASSIFIED');
                    
                    return [
                        'success' => true,
                        'user' => $user,
                        'source' => 'database',
                        'connection' => $db->getConnectionInfo()
                    ];
                } else {
                    // Incrementar intentos fallidos
                    $db->query("UPDATE users SET login_attempts = login_attempts + 1 WHERE username = ?", [$username]);
                    logMilitaryEvent('USER_LOGIN_FAILED', "Intento de acceso fallido: {$username}", 'SECURITY_ALERT');
                }
            }
        } catch (Exception $e) {
            error_log("Error en autenticacion de base de datos militar: " . $e->getMessage());
            logMilitaryEvent('AUTH_ERROR', "Error de autenticacion: " . $e->getMessage(), 'CRITICAL');
        }
    }
    
    // Fallback a usuarios por defecto
    if (isset($DEFAULT_USERS[$username])) {
        $user = $DEFAULT_USERS[$username];
        
        if ($password === $user['password'] || password_verify($password, $user['password_hash'])) {
            logMilitaryEvent('USER_LOGIN_SUCCESS', "Usuario por defecto autenticado: {$username}", $user['security_clearance']);
            
            return [
                'success' => true,
                'user' => $user,
                'source' => 'default',
                'connection' => ['type' => 'fallback', 'status' => 'no_database']
            ];
        }
    }
    
    logMilitaryEvent('USER_LOGIN_FAILED', "Credenciales incorrectas para: {$username}", 'SECURITY_ALERT');
    
    return [
        'success' => false,
        'message' => 'Credenciales incorrectas'
    ];
}

/**
 * Procesar formularios
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    // Validar token CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        echo json_encode([
            'success' => false,
            'message' => 'Token de seguridad invalido. Recarga la pagina e intenta nuevamente.'
        ]);
        exit;
    }
    
    if ($action === 'login') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            echo json_encode([
                'success' => false,
                'message' => 'Usuario y contraseña son obligatorios'
            ]);
            exit;
        }
        
        // Usar la funcion de autenticacion
        try {
            $result = authenticateUser($username, $password);
            
            if ($result['success']) {
                // IMPORTANTE: Establecer TODAS las variables de sesion correctamente
                $_SESSION['user'] = $result['user'];
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                $_SESSION['username'] = $result['user']['username'];
                $_SESSION['email'] = $result['user']['email'] ?? '';
                $_SESSION['fullname'] = $result['user']['fullname'] ?? $result['user']['username'];
                
                // CRITICO: Establecer user_type correctamente
                $_SESSION['user_type'] = $result['user']['user_type'] ?? 'user';
                
                // Establecer permisos premium y militares
                $_SESSION['premium_status'] = $result['user']['premium_status'] ?? 'basic';
                $_SESSION['security_clearance'] = $result['user']['security_clearance'] ?? 'UNCLASSIFIED';
                $_SESSION['military_access'] = $result['user']['military_access'] ?? false;
                
                // Regenerar ID de sesion por seguridad
                session_regenerate_id(true);
                
                // Log del login exitoso
                logSecurityEvent('LOGIN_SUCCESS', "Usuario {$username} inicio sesion exitosamente", 'info', $result['user']['id']);
                
                // Determinar la redireccion correcta
                $redirect = ($_SESSION['user_type'] === 'admin') ? 'admin_dashboard.php' : 'user_dashboard.php';
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Login exitoso',
                    'redirect' => $redirect,
                    'user' => [
                        'username' => $result['user']['username'],
                        'user_type' => $_SESSION['user_type'],
                        'premium_status' => $_SESSION['premium_status'],
                        'fullname' => $_SESSION['fullname'],
                        'security_clearance' => $_SESSION['security_clearance']
                    ],
                    'source' => $result['source'] ?? 'unknown',
                    'connection' => $result['connection'] ?? null
                ]);
            } else {
                // Log del intento fallido
                logSecurityEvent('LOGIN_FAILED', "Intento fallido para usuario: {$username} desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 'warning');
                
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? 'Credenciales incorrectas'
                ]);
            }
        } catch (Exception $e) {
            error_log("Error en proceso de login: " . $e->getMessage());
            logSecurityEvent('LOGIN_ERROR', "Error en autenticacion para {$username}: " . $e->getMessage(), 'error');
            
            echo json_encode([
                'success' => false,
                'message' => 'Error interno del servidor. Intente nuevamente.'
            ]);
        }
        
        exit;
    }
    
    if ($action === 'register') {
        $data = [
            'username' => sanitizeInput($_POST['username'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'fullname' => sanitizeInput($_POST['fullname'] ?? '')
        ];
        
        // Validar datos
        if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Todos los campos son obligatorios'
            ]);
            exit;
        }
        
        // Validar formato de email
        if (!validateEmail($data['email'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Formato de email invalido'
            ]);
            exit;
        }
        
        // Validar longitud de contraseña
        if (strlen($data['password']) < 6) {
            echo json_encode([
                'success' => false,
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ]);
            exit;
        }
        
        // Validar formato de username
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $data['username'])) {
            echo json_encode([
                'success' => false,
                'message' => 'El usuario debe tener 3-20 caracteres (letras, numeros y guiones bajos)'
            ]);
            exit;
        }
        
        // Intentar registrar en base de datos
        global $db;
        
        if ($db && $db->isConnected()) {
            try {
                // Verificar si el usuario ya existe
                $result = $db->query(
                    "SELECT id FROM users WHERE username = ? OR email = ?",
                    [$data['username'], $data['email']]
                );
                
                if ($result && $result->num_rows > 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Usuario o email ya existe'
                    ]);
                    exit;
                }
                
                // Crear tabla users si no existe (estructura completa)
                $createTable = "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    password VARCHAR(255),
                    password_hash VARCHAR(255),
                    fullname VARCHAR(100),
                    user_type ENUM('admin', 'user') DEFAULT 'user',
                    premium_status ENUM('basic', 'premium') DEFAULT 'basic',
                    security_clearance ENUM('UNCLASSIFIED', 'CONFIDENTIAL', 'SECRET', 'TOP_SECRET') DEFAULT 'UNCLASSIFIED',
                    military_access BOOLEAN DEFAULT FALSE,
                    premium_expires_at DATETIME NULL,
                    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                    login_attempts INT DEFAULT 0,
                    locked_until DATETIME NULL,
                    failed_login_attempts INT DEFAULT 0,
                    last_login DATETIME NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_username (username),
                    INDEX idx_email (email),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $db->getConnection()->query($createTable);
                
                // Insertar nuevo usuario
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                
                // Determinar si es el primer usuario (sera admin)
                $countResult = $db->query("SELECT COUNT(*) as total FROM users");
                $count = $countResult ? $countResult->fetch_assoc()['total'] : 0;
                $userType = ($count == 0) ? 'admin' : 'user';
                
                $insertResult = $db->query(
                    "INSERT INTO users (username, email, password_hash, password, fullname, user_type, premium_status, security_clearance, military_access, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, 'basic', 'UNCLASSIFIED', FALSE, 'active', NOW())",
                    [$data['username'], $data['email'], $hashedPassword, $data['password'], $data['fullname'], $userType]
                );
                
                if ($insertResult) {
                    $userId = $db->lastInsertId();
                    
                    // Log del registro exitoso
                    logSecurityEvent('USER_REGISTERED', "Nuevo usuario registrado: {$data['username']} como {$userType}", 'info', $userId);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Usuario registrado exitosamente. Ya puedes iniciar sesion.',
                        'user_id' => $userId,
                        'user_type' => $userType
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error al registrar usuario en la base de datos'
                    ]);
                }
                
            } catch (Exception $e) {
                error_log("Error registrando usuario: " . $e->getMessage());
                logSecurityEvent('REGISTER_ERROR', "Error al registrar usuario {$data['username']}: " . $e->getMessage(), 'error');
                
                echo json_encode([
                    'success' => false,
                    'message' => 'Error en el servidor. Intente nuevamente.'
                ]);
            }
        } else {
            // Modo sin base de datos - registro simulado
            logSecurityEvent('REGISTER_SIMULATED', "Registro simulado para usuario: {$data['username']}", 'info');
            
            echo json_encode([
                'success' => true,
                'message' => 'Usuario registrado exitosamente (modo demo - sin base de datos)',
                'user_id' => rand(1000, 9999),
                'note' => 'Registro simulado - Base de datos no disponible'
            ]);
        }
        
        exit;
    }
    
    // Accion no valida
    echo json_encode([
        'success' => false,
        'message' => 'Accion no valida'
    ]);
    exit;
}

/**
 * Verificar si ya esta logueado
 */
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $user_type = $_SESSION['user_type'] ?? 'user';
    $redirect = ($user_type === 'admin') ? 'admin_dashboard.php' : 'user_dashboard.php';
    header("Location: $redirect");
    exit;
}

// Obtener usuarios disponibles para mostrar en las credenciales
global $DEFAULT_USERS;
$demo_users = $DEFAULT_USERS ?? [];

// Agregar usuarios adicionales de demo si no existen en DEFAULT_USERS
$additional_users = [
    'A_mc' => [
        'password' => '123456',
        'type' => 'Usuario Regular'
    ],
    'isabella' => [
        'password' => '123456',
        'type' => 'Usuario Regular'
    ],
    'demo' => [
        'password' => 'demo123',
        'type' => 'Usuario Demo'
    ]
];

// Obtener estado de la base de datos
global $db;
$dbConnected = $db && $db->isConnected();
$dbInfo = $dbConnected ? $db->getConnectionInfo() : null;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('APP_NAME') ? APP_NAME : 'GuardianIA v3.0'; ?> - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Particulas de fondo */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 10;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #333;
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo p {
            color: #666;
            font-size: 1.1em;
            font-weight: 500;
        }

        .form-container {
            position: relative;
        }

        .form-tabs {
            display: flex;
            margin-bottom: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 5px;
        }

        .tab-button {
            flex: 1;
            padding: 12px 20px;
            border: none;
            background: transparent;
            color: #666;
            font-size: 1em;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 0.95em;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .credentials-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9em;
        }

        .credentials-info h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }

        .credentials-info p {
            color: #424242;
            margin: 5px 0;
        }

        .db-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .db-status.connected {
            background: #d4edda;
            color: #155724;
        }

        .db-status.disconnected {
            background: #fff3cd;
            color: #856404;
        }

        .military-status {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
            background: #dc3545;
            color: white;
        }

        .military-status.enabled {
            background: #28a745;
        }

        @media (max-width: 480px) {
            .container {
                margin: 20px;
                padding: 30px 25px;
            }
            
            .logo h1 {
                font-size: 2em;
            }
            
            .db-status, .military-status {
                position: static;
                display: inline-block;
                margin: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Particulas de fondo -->
    <div class="particles" id="particles"></div>

    <div class="container">
        <!-- Estado del sistema -->
        <div class="db-status <?php echo $dbConnected ? 'connected' : 'disconnected'; ?>" title="<?php echo $dbConnected ? 'Conexion: ' . ($dbInfo['type'] ?? 'unknown') : 'Modo Fallback'; ?>">
            <?php echo $dbConnected ? '🟢 DB Conectada' : '🟡 Modo Fallback'; ?>
        </div>
        
        <?php if (defined('MILITARY_ENCRYPTION_ENABLED')): ?>
        <div class="military-status <?php echo MILITARY_ENCRYPTION_ENABLED ? 'enabled' : ''; ?>" title="Encriptacion Militar">
            <?php echo MILITARY_ENCRYPTION_ENABLED ? '🛡️ MILITAR' : '🔒 BASICO'; ?>
        </div>
        <?php endif; ?>

        <div class="logo">
            <h1>🛡️ GuardianIA</h1>
            <p><?php echo defined('APP_VERSION') ? 'Version ' . APP_VERSION : 'Sistema de Ciberseguridad v3.0'; ?></p>
        </div>

        <div id="message-container"></div>
        
        <div class="form-container">
            <div class="form-tabs">
                <button class="tab-button active" onclick="switchTab('login')">Iniciar Sesion</button>
                <button class="tab-button" onclick="switchTab('register')">Registrarse</button>
            </div>

            <!-- Formulario de Login -->
            <div id="login-section" class="form-section active">
                <form id="login-form">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="login-username">Usuario</label>
                        <input type="text" id="login-username" name="username" required autocomplete="username" maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Contraseña</label>
                        <input type="password" id="login-password" name="password" required autocomplete="current-password">
                    </div>
                    
                    <button type="submit" class="submit-btn">Iniciar Sesion</button>
                </form>

                <?php if (!empty($demo_users)): ?>
                <div class="credentials-info">
                    <h4>👑 Credenciales de Administrador</h4>
                    <?php 
                    $admin_shown = false;
                    foreach ($demo_users as $username => $user): 
                        if ($user['user_type'] === 'admin'):
                            if ($admin_shown) echo '<hr style="margin: 10px 0; border: none; border-top: 1px solid #ccc;">';
                    ?>
                    <p><strong>Usuario:</strong> <?php echo htmlspecialchars($username); ?></p>
                    <p><strong>Contraseña:</strong> <?php echo htmlspecialchars($user['password']); ?></p>
                    <p><strong>Tipo:</strong> Admin <?php echo ($user['premium_status'] ?? 'basic') === 'premium' ? 'Premium' : 'Basico'; ?></p>
                    <?php 
                            $admin_shown = true;
                        endif; 
                    endforeach; 
                    ?>
                    
                    <h4 style="margin-top: 15px; color: #1976d2;">👤 Credenciales Adicionales</h4>
                    <?php foreach ($additional_users as $username => $user): ?>
                    <p><strong>Usuario:</strong> <?php echo htmlspecialchars($username); ?> / <strong>Pass:</strong> <?php echo htmlspecialchars($user['password']); ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Formulario de Registro -->
            <div id="register-section" class="form-section">
                <form id="register-form">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="register-fullname">Nombre Completo</label>
                        <input type="text" id="register-fullname" name="fullname" required maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="register-username">Usuario</label>
                        <input type="text" id="register-username" name="username" required pattern="[a-zA-Z0-9_]{3,20}" title="3-20 caracteres: letras, numeros y guiones bajos" maxlength="20">
                    </div>
                    
                    <div class="form-group">
                        <label for="register-email">Email</label>
                        <input type="email" id="register-email" name="email" required maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password">Contraseña</label>
                        <input type="password" id="register-password" name="password" required minlength="6" maxlength="255">
                        <small style="color: #666; font-size: 0.8em;">Minimo 6 caracteres</small>
                    </div>
                    
                    <button type="submit" class="submit-btn">Registrarse</button>
                </form>
            </div>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Procesando...</p>
            </div>
        </div>
    </div>

    <script>
        // Crear particulas de fondo
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Cambiar entre tabs
        function switchTab(tab) {
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            const buttons = document.querySelectorAll('.tab-button');
            if (tab === 'login') {
                buttons[0].classList.add('active');
            } else {
                buttons[1].classList.add('active');
            }

            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(tab + '-section').classList.add('active');

            document.getElementById('message-container').innerHTML = '';
        }

        // Mostrar mensaje
        function showMessage(message, type) {
            const container = document.getElementById('message-container');
            container.innerHTML = `<div class="message ${type}">${message}</div>`;
            
            setTimeout(() => {
                if (container.innerHTML.includes(message)) {
                    container.innerHTML = '';
                }
            }, 5000);
        }

        // Mostrar loading
        function showLoading(show) {
            const loading = document.getElementById('loading');
            const forms = document.querySelectorAll('.form-section');
            
            if (show) {
                loading.style.display = 'block';
                forms.forEach(form => form.style.display = 'none');
            } else {
                loading.style.display = 'none';
                document.querySelector('.form-section.active').style.display = 'block';
            }
        }

        // Manejar formulario de login
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('login-username').value.trim();
            const password = document.getElementById('login-password').value;
            
            if (!username || !password) {
                showMessage('Por favor, completa todos los campos', 'error');
                return;
            }
            
            showLoading(true);
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                showLoading(false);
                
                if (result.success) {
                    showMessage('¡Login exitoso! Redirigiendo...', 'success');
                    
                    // Mostrar informacion del usuario
                    if (result.user) {
                        console.log('Usuario autenticado:', result.user);
                        console.log('Tipo de usuario:', result.user.user_type);
                        console.log('Clearance:', result.user.security_clearance);
                    }
                    
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1500);
                } else {
                    showMessage(result.message || 'Error en el login', 'error');
                }
            } catch (error) {
                showLoading(false);
                showMessage('Error de conexion. Intente nuevamente.', 'error');
                console.error('Error:', error);
            }
        });

        // Manejar formulario de registro
        document.getElementById('register-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const password = document.getElementById('register-password').value;
            const username = document.getElementById('register-username').value.trim();
            const email = document.getElementById('register-email').value.trim();
            
            if (password.length < 6) {
                showMessage('La contraseña debe tener al menos 6 caracteres', 'error');
                return;
            }
            
            if (!/^[a-zA-Z0-9_]{3,20}$/.test(username)) {
                showMessage('Usuario invalido (3-20 caracteres: letras, numeros y guiones bajos)', 'error');
                return;
            }
            
            if (!/\S+@\S+\.\S+/.test(email)) {
                showMessage('Formato de email invalido', 'error');
                return;
            }
            
            showLoading(true);
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                showLoading(false);
                
                if (result.success) {
                    showMessage(result.message, 'success');
                    this.reset();
                    
                    // Si fue el primer usuario, mostrar que es admin
                    if (result.user_type === 'admin') {
                        showMessage('Eres el primer usuario registrado. Se te han otorgado permisos de administrador!', 'success');
                    }
                    
                    setTimeout(() => {
                        switchTab('login');
                    }, 2000);
                } else {
                    showMessage(result.message || 'Error en el registro', 'error');
                }
            } catch (error) {
                showLoading(false);
                showMessage('Error de conexion. Intente nuevamente.', 'error');
                console.error('Error:', error);
            }
        });

        // Verificar mensaje de logout
        function checkLogoutMessage() {
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            
            if (message === 'logout_success') {
                showMessage('Sesion cerrada correctamente. ¡Hasta pronto!', 'success');
                
                const url = new URL(window.location);
                url.searchParams.delete('message');
                window.history.replaceState({}, document.title, url.pathname);
            } else if (message === 'access_denied') {
                showMessage('Acceso denegado. Por favor, inicia sesion.', 'error');
                
                const url = new URL(window.location);
                url.searchParams.delete('message');
                window.history.replaceState({}, document.title, url.pathname);
            }
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            checkLogoutMessage();
            
            // Auto-completar credenciales de demo (solo para desarrollo)
            <?php if (isset($demo_users['anderson'])): ?>
            const loginUsername = document.getElementById('login-username');
            const loginPassword = document.getElementById('login-password');
            
            if (loginUsername && loginPassword) {
                loginUsername.addEventListener('dblclick', function() {
                    if (confirm('¿Autocompletar con credenciales de Anderson?')) {
                        this.value = 'anderson';
                        loginPassword.value = '<?php echo $demo_users['anderson']['password']; ?>';
                    }
                });
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>