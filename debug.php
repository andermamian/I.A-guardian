<?php
require_once 'config.php';
// Esto ya inicializa la sesión de forma segura
/**
 * Archivo de debugging para GuardianIA v3.0
 * Este archivo nos ayuda a ver qué usuarios están registrados
 */

// Configuración de base de datos
$config = [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '0987654321',
    'db_name' => 'guardianai_db',
    'use_database' => true
];

function connectDB($config) {
    try {
        $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
        if ($conn->connect_error) {
            return false;
        }
        return $conn;
    } catch (Exception $e) {
        return false;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GuardianIA Debug</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 20px;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .user-card {
            background: #f9f9f9;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #4CAF50;
        }
        .error {
            color: red;
            background: #ffebee;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .success {
            color: green;
            background: #e8f5e8;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 GuardianIA Debug Panel</h1>
        
        <div class="section">
            <h2>Estado de la Base de Datos</h2>
            <?php
            $conn = connectDB($config);
            if ($conn) {
                echo '<div class="success">✅ Conexión a MySQL exitosa</div>';
                
                // Verificar si existe la tabla users
                $result = $conn->query("SHOW TABLES LIKE 'users'");
                if ($result && $result->num_rows > 0) {
                    echo '<div class="success">✅ Tabla "users" existe</div>';
                    
                    // Mostrar usuarios registrados
                    echo '<h3>Usuarios registrados en la base de datos:</h3>';
                    $users = $conn->query("SELECT id, username, email, fullname, user_type, premium_status, status, created_at FROM users");
                    
                    if ($users && $users->num_rows > 0) {
                        echo '<table>';
                        echo '<tr><th>ID</th><th>Usuario</th><th>Email</th><th>Nombre</th><th>Tipo</th><th>Estado</th><th>Creado</th></tr>';
                        
                        while ($user = $users->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $user['id'] . '</td>';
                            echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                            echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                            echo '<td>' . htmlspecialchars($user['fullname']) . '</td>';
                            echo '<td>' . $user['user_type'] . '</td>';
                            echo '<td>' . $user['status'] . '</td>';
                            echo '<td>' . $user['created_at'] . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    } else {
                        echo '<div class="error">⚠️ No hay usuarios registrados en la base de datos</div>';
                    }
                } else {
                    echo '<div class="error">⚠️ La tabla "users" no existe</div>';
                    echo '<p>Ejecutando creación de tabla...</p>';
                    
                    $createTable = "CREATE TABLE IF NOT EXISTS users (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(50) UNIQUE NOT NULL,
                        email VARCHAR(100) UNIQUE NOT NULL,
                        password VARCHAR(255) NOT NULL,
                        fullname VARCHAR(100),
                        user_type ENUM('admin', 'user') DEFAULT 'user',
                        premium_status ENUM('basic', 'premium') DEFAULT 'basic',
                        status ENUM('active', 'inactive') DEFAULT 'active',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    
                    if ($conn->query($createTable)) {
                        echo '<div class="success">✅ Tabla "users" creada correctamente</div>';
                    } else {
                        echo '<div class="error">❌ Error creando tabla: ' . $conn->error . '</div>';
                    }
                }
                
                $conn->close();
            } else {
                echo '<div class="error">❌ No se pudo conectar a MySQL</div>';
                echo '<p>Verificar:</p>';
                echo '<ul>';
                echo '<li>Host: ' . $config['db_host'] . '</li>';
                echo '<li>Usuario: ' . $config['db_user'] . '</li>';
                echo '<li>Base de datos: ' . $config['db_name'] . '</li>';
                echo '</ul>';
            }
            ?>
        </div>

        <div class="section">
            <h2>Usuarios por Defecto</h2>
            <p>Estos usuarios funcionan sin base de datos:</p>
            
            <div class="user-card">
                <strong>anderson</strong> / Ander12345@ (Admin Premium)
            </div>
            <div class="user-card">
                <strong>admin</strong> / admin123 (Admin Básico)
            </div>
            <div class="user-card">
                <strong>A_mc</strong> / 123456 (Usuario Regular)
            </div>
            <div class="user-card">
                <strong>isabella</strong> / 123456 (Usuario Regular)
            </div>
            <div class="user-card">
                <strong>demo</strong> / demo123 (Usuario Regular)
            </div>
        </div>

        <div class="section">
            <h2>Test de Login</h2>
            <p>Prueba las credenciales aquí:</p>
            
            <form method="POST" style="margin-top: 20px;">
                <div style="margin-bottom: 15px;">
                    <label>Usuario:</label><br>
                    <input type="text" name="test_username" style="padding: 8px; width: 200px;" placeholder="Ingresa usuario">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Contraseña:</label><br>
                    <input type="password" name="test_password" style="padding: 8px; width: 200px;" placeholder="Ingresa contraseña">
                </div>
                <button type="submit" name="test_login" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 5px;">
                    Probar Login
                </button>
            </form>

            <?php
            if (isset($_POST['test_login'])) {
                $test_username = $_POST['test_username'] ?? '';
                $test_password = $_POST['test_password'] ?? '';
                
                echo '<h3>Resultado del Test:</h3>';
                
                if (empty($test_username) || empty($test_password)) {
                    echo '<div class="error">❌ Usuario y contraseña son requeridos</div>';
                } else {
                    // Buscar en base de datos
                    $found_in_db = false;
                    $conn = connectDB($config);
                    
                    if ($conn) {
                        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
                        if ($stmt) {
                            $stmt->bind_param('s', $test_username);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result && $result->num_rows > 0) {
                                $user = $result->fetch_assoc();
                                $found_in_db = true;
                                
                                echo '<div class="success">✅ Usuario encontrado en base de datos</div>';
                                echo '<p><strong>Datos almacenados:</strong></p>';
                                echo '<ul>';
                                echo '<li>Usuario: ' . htmlspecialchars($user['username']) . '</li>';
                                echo '<li>Email: ' . htmlspecialchars($user['email']) . '</li>';
                                echo '<li>Nombre: ' . htmlspecialchars($user['fullname']) . '</li>';
                                echo '<li>Tipo: ' . $user['user_type'] . '</li>';
                                echo '<li>Hash de contraseña: ' . substr($user['password'], 0, 20) . '...</li>';
                                echo '</ul>';
                                
                                // Verificar contraseña
                                if (password_verify($test_password, $user['password'])) {
                                    echo '<div class="success">✅ Contraseña correcta (hash verificado)</div>';
                                } elseif ($test_password === $user['password']) {
                                    echo '<div class="success">✅ Contraseña correcta (texto plano)</div>';
                                } else {
                                    echo '<div class="error">❌ Contraseña incorrecta</div>';
                                    echo '<p>Intentaste: "' . htmlspecialchars($test_password) . '"</p>';
                                }
                            }
                            $stmt->close();
                        }
                        $conn->close();
                    }
                    
                    // Buscar en usuarios por defecto
                    if (!$found_in_db) {
                        $default_users = [
                            'anderson' => 'Ander12345@',
                            'admin' => 'admin123',
                            'A_mc' => '123456',
                            'isabella' => '123456',
                            'demo' => 'demo123'
                        ];
                        
                        if (isset($default_users[$test_username])) {
                            echo '<div class="success">✅ Usuario encontrado en usuarios por defecto</div>';
                            if ($test_password === $default_users[$test_username]) {
                                echo '<div class="success">✅ Contraseña correcta</div>';
                            } else {
                                echo '<div class="error">❌ Contraseña incorrecta</div>';
                                echo '<p>Contraseña esperada: "' . $default_users[$test_username] . '"</p>';
                                echo '<p>Contraseña ingresada: "' . htmlspecialchars($test_password) . '"</p>';
                            }
                        } else {
                            echo '<div class="error">❌ Usuario no encontrado en ningún lugar</div>';
                        }
                    }
                }
            }
            ?>
        </div>

        <div class="section">
            <p><a href="login.php" style="color: #4CAF50;">⬅️ Volver al Login</a></p>
        </div>
    </div>
</body>
</html>