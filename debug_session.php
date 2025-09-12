<?php
/**
 * GuardianIA v3.0 FINAL - Sistema de Diagnostico Completo
 * Anderson Mamian Chicangana - Debug y Testing
 * Sincronizado con config.php y config_military.php
 */

// Inicializar sesion si no esta activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuracion si existe
$config_loaded = false;
$config_error = '';
if (file_exists('config.php')) {
    try {
        require_once 'config.php';
        $config_loaded = true;
    } catch (Exception $e) {
        $config_error = $e->getMessage();
    }
}

// Cargar configuracion militar si existe
$military_loaded = false;
if ($config_loaded && file_exists('config_military.php')) {
    try {
        require_once 'config_military.php';
        $military_loaded = true;
    } catch (Exception $e) {
        $config_error .= ' | Military: ' . $e->getMessage();
    }
}

// Funcion de autenticacion para testing
function testAuthenticateUser($username, $password) {
    global $db, $DEFAULT_USERS;
    
    // Intentar autenticacion con base de datos
    if ($db && $db->isConnected()) {
        try {
            $result = $db->query("SELECT * FROM users WHERE username = ? AND status = 'active'", [$username]);
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Verificar contraseña
                $password_valid = false;
                if (!empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                    $password_valid = true;
                } elseif (!empty($user['password']) && $password === $user['password']) {
                    $password_valid = true;
                }
                
                if ($password_valid) {
                    return [
                        'success' => true,
                        'user' => $user,
                        'source' => 'database'
                    ];
                }
            }
        } catch (Exception $e) {
            // Log error
        }
    }
    
    // Fallback a usuarios por defecto
    if (isset($DEFAULT_USERS[$username])) {
        $user = $DEFAULT_USERS[$username];
        if ($password === $user['password'] || 
            (isset($user['password_hash']) && password_verify($password, $user['password_hash']))) {
            return [
                'success' => true,
                'user' => $user,
                'source' => 'default'
            ];
        }
    }
    
    return ['success' => false, 'message' => 'Credenciales incorrectas'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Completo - GuardianIA v3.0 MILITARY</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            padding: 20px; 
            min-height: 100vh;
        }
        
        .container { 
            background: rgba(255, 255, 255, 0.95); 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            max-width: 1200px; 
            margin: 0 auto;
            backdrop-filter: blur(10px);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 2.5em;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .success { 
            color: #155724; 
            background: #d4edda; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 10px 0; 
            border-left: 4px solid #28a745;
        }
        
        .error { 
            color: #721c24; 
            background: #f8d7da; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 10px 0; 
            border-left: 4px solid #dc3545;
        }
        
        .warning { 
            color: #856404; 
            background: #fff3cd; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 10px 0; 
            border-left: 4px solid #ffc107;
        }
        
        .info {
            color: #0c5460;
            background: #d1ecf1;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        
        .military {
            color: #fff;
            background: linear-gradient(135deg, #28a745, #20c997);
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        
        pre { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 8px; 
            overflow-x: auto; 
            font-size: 0.9em;
            border: 1px solid #e9ecef;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .btn { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
            padding: 12px 24px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            margin: 5px; 
            text-decoration: none; 
            display: inline-block; 
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn.logout { background: linear-gradient(135deg, #dc3545, #c82333); }
        .btn.warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
        .btn.success { background: linear-gradient(135deg, #28a745, #20c997); }
        
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        
        .section h2 {
            color: #495057;
            margin-top: 0;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
        }
        
        table th, table td {
            border: 1px solid #dee2e6;
            padding: 12px;
            text-align: left;
        }
        
        table th {
            background: #e9ecef;
            font-weight: 600;
        }
        
        .test-form {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #bbdefb;
            margin: 20px 0;
        }
        
        .flex-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .flex-item {
            flex: 1;
            min-width: 300px;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-online { background: #28a745; }
        .status-offline { background: #dc3545; }
        .status-warning { background: #ffc107; }
        
        .input-field {
            padding: 10px;
            margin: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .grid-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .grid-item strong {
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ Debug GuardianIA v3.0 MILITARY</h1>
            <p>Sistema de Diagnostico Completo - Version Militar</p>
            <p style="color: #666; font-size: 0.9em;">
                <?php echo date('Y-m-d H:i:s'); ?> | 
                PHP <?php echo PHP_VERSION; ?> | 
                Session: <?php echo session_id() ? substr(session_id(), 0, 16) . '...' : 'No iniciada'; ?>
            </p>
        </div>

        <!-- Estado de Configuracion -->
        <div class="section">
            <h2><span class="status-indicator <?php echo $config_loaded ? 'status-online' : 'status-offline'; ?>"></span>1. Estado de Configuracion</h2>
            
            <div class="grid">
                <div class="grid-item">
                    <strong>config.php:</strong><br>
                    <?php if ($config_loaded): ?>
                        <span style="color: green;">✅ Cargado correctamente</span>
                    <?php else: ?>
                        <span style="color: red;">❌ Error: <?php echo htmlspecialchars($config_error ?: 'Archivo no encontrado'); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="grid-item">
                    <strong>config_military.php:</strong><br>
                    <?php if ($military_loaded): ?>
                        <span style="color: green;">✅ Cargado correctamente</span>
                    <?php else: ?>
                        <span style="color: orange;">⚠️ No cargado</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($config_loaded): ?>
                <div class="grid-item">
                    <strong>Version:</strong><br>
                    <?php echo defined('APP_VERSION') ? APP_VERSION : 'No definida'; ?>
                </div>
                
                <div class="grid-item">
                    <strong>Developer:</strong><br>
                    <?php echo defined('DEVELOPER') ? DEVELOPER : 'No definido'; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($config_loaded && defined('MILITARY_ENCRYPTION_ENABLED') && MILITARY_ENCRYPTION_ENABLED): ?>
                <div class="military">
                    🔐 ENCRIPTACION MILITAR ACTIVA<br>
                    FIPS 140-2: <?php echo FIPS_140_2_COMPLIANCE ? 'COMPLIANT' : 'NO COMPLIANT'; ?><br>
                    Quantum Resistance: <?php echo QUANTUM_RESISTANCE_ENABLED ? 'ENABLED' : 'DISABLED'; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Estado de Base de Datos -->
        <div class="section">
            <h2>2. Estado de la Base de Datos</h2>
            <?php
            global $db, $conn;
            $db_status = false;
            $db_info = [];
            
            if ($config_loaded && isset($db) && $db && method_exists($db, 'isConnected') && $db->isConnected()) {
                $db_status = true;
                $db_info = $db->getConnectionInfo();
                ?>
                <div class="success">✅ Base de datos conectada correctamente</div>
                
                <div class="grid">
                    <div class="grid-item">
                        <strong>Tipo:</strong> <?php echo htmlspecialchars($db_info['type'] ?? 'unknown'); ?>
                    </div>
                    <div class="grid-item">
                        <strong>Usuario:</strong> <?php echo htmlspecialchars($db_info['user'] ?? 'unknown'); ?>
                    </div>
                    <div class="grid-item">
                        <strong>Host:</strong> <?php echo htmlspecialchars($db_info['host'] ?? 'unknown'); ?>
                    </div>
                    <div class="grid-item">
                        <strong>Database:</strong> <?php echo htmlspecialchars($db_info['database'] ?? 'unknown'); ?>
                    </div>
                </div>
                
                <?php
                // Test database query
                try {
                    $test_query = $db->query("SELECT VERSION() as version, DATABASE() as db_name, USER() as user");
                    if ($test_query && $row = $test_query->fetch_assoc()) {
                        echo '<div class="info">';
                        echo '🔍 MySQL Version: ' . $row['version'] . '<br>';
                        echo '🗄️ Database: ' . $row['db_name'] . '<br>';
                        echo '👤 User: ' . $row['user'];
                        echo '</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="warning">⚠️ Error en test query: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            } else {
                ?>
                <div class="error">❌ Base de datos NO conectada</div>
                <div class="warning">⚠️ Modo fallback activado - Usando usuarios por defecto</div>
                
                <?php if (isset($GLOBALS['DEFAULT_USERS'])): ?>
                <div class="info">
                    <strong>Usuarios disponibles en modo fallback:</strong><br>
                    <?php foreach ($GLOBALS['DEFAULT_USERS'] as $username => $user): ?>
                        • <strong><?php echo $username; ?></strong> 
                        (<?php echo $user['user_type']; ?> - <?php echo $user['premium_status']; ?>)<br>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php } ?>
        </div>

        <!-- Verificacion de Tabla de Usuarios -->
        <div class="section">
            <h2>3. Tabla de Usuarios</h2>
            <?php if ($db_status): ?>
                <?php
                try {
                    // Verificar si la tabla existe
                    $result = $db->query("SHOW TABLES LIKE 'users'");
                    if ($result && $result->num_rows > 0) {
                        echo '<div class="success">✅ Tabla "users" existe</div>';
                        
                        // Contar usuarios
                        $count_result = $db->query("SELECT COUNT(*) as total FROM users");
                        $total_users = $count_result ? $count_result->fetch_assoc()['total'] : 0;
                        echo '<div class="info">Total de usuarios en DB: <strong>' . $total_users . '</strong></div>';
                        
                        // Mostrar usuarios existentes
                        $users_result = $db->query("SELECT id, username, email, user_type, premium_status, security_clearance, military_access, status, last_login, created_at FROM users ORDER BY id ASC LIMIT 10");
                        if ($users_result && $users_result->num_rows > 0) {
                            echo '<h3>Usuarios en la base de datos:</h3>';
                            echo '<div style="overflow-x: auto;">';
                            echo '<table>';
                            echo '<tr>';
                            echo '<th>ID</th>';
                            echo '<th>Usuario</th>';
                            echo '<th>Email</th>';
                            echo '<th>Tipo</th>';
                            echo '<th>Premium</th>';
                            echo '<th>Clearance</th>';
                            echo '<th>Militar</th>';
                            echo '<th>Estado</th>';
                            echo '<th>Ultimo Login</th>';
                            echo '</tr>';
                            while ($row = $users_result->fetch_assoc()) {
                                $row_class = $row['user_type'] === 'admin' ? 'style="background: #e3f2fd;"' : '';
                                echo '<tr ' . $row_class . '>';
                                echo '<td>' . $row['id'] . '</td>';
                                echo '<td><strong>' . htmlspecialchars($row['username']) . '</strong></td>';
                                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                                echo '<td><span class="badge">' . $row['user_type'] . '</span></td>';
                                echo '<td>' . ($row['premium_status'] === 'premium' ? '⭐ Premium' : 'Basic') . '</td>';
                                echo '<td>' . ($row['security_clearance'] ?? 'N/A') . '</td>';
                                echo '<td>' . ($row['military_access'] ? '✅ Si' : '❌ No') . '</td>';
                                echo '<td>' . ($row['status'] === 'active' ? '🟢' : '🔴') . ' ' . $row['status'] . '</td>';
                                echo '<td>' . ($row['last_login'] ?? 'Nunca') . '</td>';
                                echo '</tr>';
                            }
                            echo '</table>';
                            echo '</div>';
                        } else {
                            echo '<div class="warning">⚠️ La tabla usuarios existe pero esta vacia</div>';
                        }
                        
                        // Verificar estructura de la tabla
                        $structure_result = $db->query("DESCRIBE users");
                        if ($structure_result) {
                            echo '<details style="margin-top: 15px;">';
                            echo '<summary style="cursor: pointer; font-weight: bold;">📋 Estructura de la tabla (click para expandir)</summary>';
                            echo '<table style="margin-top: 10px; font-size: 0.9em;">';
                            echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>';
                            while ($col = $structure_result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . $col['Field'] . '</td>';
                                echo '<td>' . $col['Type'] . '</td>';
                                echo '<td>' . $col['Null'] . '</td>';
                                echo '<td>' . $col['Key'] . '</td>';
                                echo '<td>' . ($col['Default'] ?? 'NULL') . '</td>';
                                echo '</tr>';
                            }
                            echo '</table>';
                            echo '</details>';
                        }
                    } else {
                        echo '<div class="error">❌ Tabla "users" NO existe</div>';
                        echo '<div class="warning">⚠️ La tabla se creara automaticamente al registrar el primer usuario</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="error">❌ Error verificando tabla: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            <?php else: ?>
                <div class="warning">⚠️ No se puede verificar - Base de datos no conectada</div>
                <div class="info">
                    <h3>Usuarios por defecto disponibles (modo fallback):</h3>
                    <table>
                        <tr><th>Usuario</th><th>Contraseña</th><th>Tipo</th><th>Premium</th><th>Clearance</th></tr>
                        <tr>
                            <td><strong>anderson</strong></td>
                            <td>Ander12345@</td>
                            <td>admin</td>
                            <td>✅ Premium</td>
                            <td>TOP_SECRET</td>
                        </tr>
                        <tr>
                            <td><strong>admin</strong></td>
                            <td>admin123</td>
                            <td>admin</td>
                            <td>❌ Basic</td>
                            <td>SECRET</td>
                        </tr>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Estado de la Sesion -->
        <div class="section">
            <h2>4. Estado de la Sesion</h2>
            
            <div class="flex-container">
                <div class="flex-item">
                    <?php if (empty($_SESSION)): ?>
                        <div class="error">❌ No hay datos de sesion activa</div>
                        <p>La sesion esta vacia. Necesitas hacer login primero.</p>
                    <?php else: ?>
                        <div class="success">✅ Sesion activa detectada</div>
                        <h3>Variables de sesion principales:</h3>
                        <table>
                            <tr><th>Variable</th><th>Valor</th></tr>
                            <?php
                            $important_vars = ['logged_in', 'user_id', 'username', 'user_type', 'premium_status', 'security_clearance', 'military_access'];
                            foreach ($important_vars as $var) {
                                echo '<tr>';
                                echo '<td><strong>$_SESSION[\'' . $var . '\']</strong></td>';
                                echo '<td>';
                                if (isset($_SESSION[$var])) {
                                    $value = $_SESSION[$var];
                                    if (is_bool($value)) {
                                        echo $value ? '✅ true' : '❌ false';
                                    } else {
                                        echo htmlspecialchars($value);
                                    }
                                } else {
                                    echo '<span style="color: red;">No definido</span>';
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </table>
                        
                        <details style="margin-top: 15px;">
                            <summary style="cursor: pointer; font-weight: bold;">📋 Todas las variables de sesion</summary>
                            <pre style="margin-top: 10px;"><?php echo htmlspecialchars(print_r($_SESSION, true)); ?></pre>
                        </details>
                    <?php endif; ?>
                </div>
                
                <div class="flex-item">
                    <h3>Verificaciones de Acceso:</h3>
                    
                    <?php
                    // Verificaciones de acceso
                    $checks = [
                        'login.php' => 'Siempre accesible',
                        'user_dashboard.php' => isset($_SESSION['logged_in']) && $_SESSION['logged_in'],
                        'admin_dashboard.php' => isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin',
                        'security_audit.php' => isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin',
                        'user_assistant.php' => isset($_SESSION['logged_in']) && $_SESSION['logged_in'],
                        'quantum_encryption.php' => isset($_SESSION['military_access']) && $_SESSION['military_access']
                    ];
                    
                    echo '<table>';
                    foreach ($checks as $page => $access) {
                        echo '<tr>';
                        echo '<td><strong>' . $page . '</strong></td>';
                        echo '<td>';
                        if ($page === 'login.php' || $access === true) {
                            echo '<span style="color: green;">✅ Acceso permitido</span>';
                        } else {
                            echo '<span style="color: red;">❌ Acceso denegado</span>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    ?>
                    
                    <h3>Informacion del Usuario:</h3>
                    <?php
                    $username = 'No identificado';
                    $user_type = 'No definido';
                    $user_id = 'No definido';

                    if (isset($_SESSION['user'])) {
                        $username = $_SESSION['user']['username'] ?? 'Usuario sin nombre';
                        $user_type = $_SESSION['user']['user_type'] ?? 'No definido';
                        $user_id = $_SESSION['user']['id'] ?? 'No definido';
                    } elseif (isset($_SESSION['username'])) {
                        $username = $_SESSION['username'];
                        $user_type = $_SESSION['user_type'] ?? 'No definido';
                        $user_id = $_SESSION['user_id'] ?? 'No definido';
                    }
                    ?>
                    
                    <table>
                        <tr><td><strong>Usuario:</strong></td><td><?php echo htmlspecialchars($username); ?></td></tr>
                        <tr><td><strong>Tipo:</strong></td><td><?php echo htmlspecialchars($user_type); ?></td></tr>
                        <tr><td><strong>ID:</strong></td><td><?php echo htmlspecialchars($user_id); ?></td></tr>
                        <tr><td><strong>Session ID:</strong></td><td><?php echo session_id() ? substr(session_id(), 0, 20) . '...' : 'No hay sesion'; ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Test de Autenticacion -->
        <div class="section">
            <h2>5. Test de Autenticacion</h2>
            
            <div class="test-form">
                <h3>Probar funcion de autenticacion:</h3>
                <form method="POST">
                    <input type="hidden" name="test_auth" value="1">
                    <input type="text" name="test_username" placeholder="Usuario" value="anderson" class="input-field">
                    <input type="password" name="test_password" placeholder="Contraseña" value="Ander12345@" class="input-field">
                    <button type="submit" class="btn">🔑 Probar Autenticacion</button>
                </form>
                
                <?php
                if (isset($_POST['test_auth'])) {
                    $test_username = $_POST['test_username'] ?? '';
                    $test_password = $_POST['test_password'] ?? '';
                    
                    if (!empty($test_username) && !empty($test_password)) {
                        try {
                            $auth_result = testAuthenticateUser($test_username, $test_password);
                            
                            echo '<h4>Resultado de autenticacion:</h4>';
                            
                            if ($auth_result['success']) {
                                echo '<div class="success">✅ Autenticacion exitosa</div>';
                                echo '<div class="info">';
                                echo '<strong>Origen:</strong> ' . htmlspecialchars($auth_result['source']) . '<br>';
                                echo '<strong>Usuario:</strong> ' . htmlspecialchars($auth_result['user']['username']) . '<br>';
                                echo '<strong>Tipo:</strong> ' . htmlspecialchars($auth_result['user']['user_type']) . '<br>';
                                echo '<strong>Premium:</strong> ' . htmlspecialchars($auth_result['user']['premium_status']) . '<br>';
                                if (isset($auth_result['user']['security_clearance'])) {
                                    echo '<strong>Clearance:</strong> ' . htmlspecialchars($auth_result['user']['security_clearance']) . '<br>';
                                }
                                echo '</div>';
                            } else {
                                echo '<div class="error">❌ Autenticacion fallida: ' . htmlspecialchars($auth_result['message'] ?? 'Error desconocido') . '</div>';
                            }
                            
                            echo '<details style="margin-top: 10px;">';
                            echo '<summary style="cursor: pointer;">Ver datos completos</summary>';
                            echo '<pre>' . htmlspecialchars(print_r($auth_result, true)) . '</pre>';
                            echo '</details>';
                        } catch (Exception $e) {
                            echo '<div class="error">❌ Error en authenticateUser(): ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                    }
                }
                ?>
            </div>
        </div>

        <!-- Login Manual Simulado -->
        <div class="section">
            <h2>6. Login Manual (Establecer Sesion)</h2>
            <div class="test-form">
                <h3>Establecer sesion manualmente para testing:</h3>
                <form method="POST">
                    <input type="hidden" name="manual_login" value="1">
                    <button type="submit" name="login_user" value="anderson" class="btn success">👨‍💼 Login como Anderson (Admin Premium)</button>
                    <button type="submit" name="login_user" value="admin" class="btn">🔧 Login como Admin (Basic)</button>
                    <button type="submit" name="login_user" value="user" class="btn warning">👤 Login como Usuario Regular</button>
                </form>
                
                <?php
                if (isset($_POST['manual_login'])) {
                    $login_user = $_POST['login_user'] ?? '';
                    
                    $test_users = [
                        'anderson' => [
                            'id' => 1,
                            'username' => 'anderson',
                            'email' => 'anderson@guardianai.com',
                            'fullname' => 'Anderson Mamian Chicangana',
                            'user_type' => 'admin',
                            'premium_status' => 'premium',
                            'security_clearance' => 'TOP_SECRET',
                            'military_access' => true,
                            'status' => 'active'
                        ],
                        'admin' => [
                            'id' => 2,
                            'username' => 'admin',
                            'email' => 'admin@guardianai.com',
                            'fullname' => 'Administrador GuardianIA',
                            'user_type' => 'admin',
                            'premium_status' => 'basic',
                            'security_clearance' => 'SECRET',
                            'military_access' => false,
                            'status' => 'active'
                        ],
                        'user' => [
                            'id' => 3,
                            'username' => 'usuario_prueba',
                            'email' => 'user@test.com',
                            'fullname' => 'Usuario de Prueba',
                            'user_type' => 'user',
                            'premium_status' => 'basic',
                            'security_clearance' => 'UNCLASSIFIED',
                            'military_access' => false,
                            'status' => 'active'
                        ]
                    ];

                    if (isset($test_users[$login_user])) {
                        // Establecer TODAS las variables de sesion necesarias
                        $_SESSION['user'] = $test_users[$login_user];
                        $_SESSION['logged_in'] = true;
                        $_SESSION['login_time'] = time();
                        $_SESSION['user_type'] = $test_users[$login_user]['user_type'];
                        $_SESSION['username'] = $test_users[$login_user]['username'];
                        $_SESSION['user_id'] = $test_users[$login_user]['id'];
                        $_SESSION['email'] = $test_users[$login_user]['email'];
                        $_SESSION['fullname'] = $test_users[$login_user]['fullname'];
                        $_SESSION['premium_status'] = $test_users[$login_user]['premium_status'];
                        $_SESSION['security_clearance'] = $test_users[$login_user]['security_clearance'];
                        $_SESSION['military_access'] = $test_users[$login_user]['military_access'];
                        
                        echo '<div class="success">✅ Login simulado exitoso para: ' . htmlspecialchars($test_users[$login_user]['fullname']) . '</div>';
                        echo '<div class="info">🔄 Recargando pagina en 2 segundos...</div>';
                        echo '<script>setTimeout(function(){ location.reload(); }, 2000);</script>';
                    }
                }
                ?>
            </div>
        </div>

        <!-- Acciones Disponibles -->
        <div class="section">
            <h2>7. Acciones Disponibles</h2>
            
            <div style="margin-bottom: 15px;">
                <a href="login.php" class="btn">🔑 Ir al Login</a>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn warning">🔄 Recargar Debug</a>
                
                <?php if (!empty($_SESSION) && isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                    <a href="logout.php" class="btn logout">🚪 Cerrar Sesion</a>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($_SESSION) && isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                <h3>Paginas disponibles segun tu sesion:</h3>
                <div>
                    <a href="user_dashboard.php" class="btn">📊 User Dashboard</a>
                    <a href="user_assistant.php" class="btn">🤖 Asistente IA</a>
                    
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                        <a href="admin_dashboard.php" class="btn success">⚙️ Admin Dashboard</a>
                        <a href="security_audit.php" class="btn success">🔒 Security Audit</a>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['military_access']) && $_SESSION['military_access']): ?>
                        <a href="firewall_quantum.php" class="btn success">⚛️ Quantum Firewall</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="warning">⚠️ Debes iniciar sesion para acceder a las paginas del sistema</div>
            <?php endif; ?>
        </div>

        <!-- Informacion del Sistema -->
        <div class="section">
            <h2>8. Informacion del Sistema</h2>
            <div class="grid">
                <div class="grid-item">
                    <strong>PHP Version:</strong><br>
                    <?php echo PHP_VERSION; ?>
                </div>
                <div class="grid-item">
                    <strong>Server Software:</strong><br>
                    <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
                </div>
                <div class="grid-item">
                    <strong>Session Status:</strong><br>
                    <?php 
                    $status = session_status();
                    if ($status === PHP_SESSION_ACTIVE) echo '🟢 Activa';
                    elseif ($status === PHP_SESSION_NONE) echo '🔴 No iniciada';
                    else echo '🟡 Deshabilitada';
                    ?>
                </div>
                <div class="grid-item">
                    <strong>Memory Usage:</strong><br>
                    <?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB
                </div>
                <div class="grid-item">
                    <strong>Max Execution Time:</strong><br>
                    <?php echo ini_get('max_execution_time'); ?> segundos
                </div>
                <div class="grid-item">
                    <strong>Upload Max Size:</strong><br>
                    <?php echo ini_get('upload_max_filesize'); ?>
                </div>
            </div>
        </div>

        <!-- Variables Globales del Sistema -->
        <div class="section">
            <h2>9. Constantes del Sistema</h2>
            <?php if ($config_loaded): ?>
                <div class="grid">
                    <div class="grid-item">
                        <strong>APP_NAME:</strong><br>
                        <?php echo defined('APP_NAME') ? APP_NAME : 'No definido'; ?>
                    </div>
                    <div class="grid-item">
                        <strong>APP_VERSION:</strong><br>
                        <?php echo defined('APP_VERSION') ? APP_VERSION : 'No definido'; ?>
                    </div>
                    <div class="grid-item">
                        <strong>DEVELOPER:</strong><br>
                        <?php echo defined('DEVELOPER') ? DEVELOPER : 'No definido'; ?>
                    </div>
                    <div class="grid-item">
                        <strong>PREMIUM_ENABLED:</strong><br>
                        <?php echo defined('PREMIUM_ENABLED') ? (PREMIUM_ENABLED ? '✅ Si' : '❌ No') : 'No definido'; ?>
                    </div>
                    <div class="grid-item">
                        <strong>MILITARY_ENCRYPTION:</strong><br>
                        <?php echo defined('MILITARY_ENCRYPTION_ENABLED') ? (MILITARY_ENCRYPTION_ENABLED ? '✅ Activo' : '❌ Inactivo') : 'No definido'; ?>
                    </div>
                    <div class="grid-item">
                        <strong>QUANTUM_RESISTANCE:</strong><br>
                        <?php echo defined('QUANTUM_RESISTANCE_ENABLED') ? (QUANTUM_RESISTANCE_ENABLED ? '✅ Activo' : '❌ Inactivo') : 'No definido'; ?>
                    </div>
                    <div class="grid-item">
                        <strong>AI_LEARNING:</strong><br>
                        <?php echo defined('AI_LEARNING_ENABLED') ? (AI_LEARNING_ENABLED ? '✅ Activo' : '❌ Inactivo') : 'No definido'; ?>
                    </div>
                    <div class="grid-item">
                        <strong>VPN_ENABLED:</strong><br>
                        <?php echo defined('VPN_ENABLED') ? (VPN_ENABLED ? '✅ Activo' : '❌ Inactivo') : 'No definido'; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="error">Configuracion no cargada - No se pueden mostrar las constantes</div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #666;">
            <p>GuardianIA v3.0 MILITARY - Sistema de Diagnostico</p>
            <p style="font-size: 0.9em;">Desarrollado por Anderson Mamian Chicangana</p>
        </div>
    </div>
</body>
</html>