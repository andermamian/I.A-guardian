<?php
/**
 * Script de Diagnóstico MySQL para GuardianIA
 * Ayuda a identificar y solucionar problemas de conexión
 */

echo "<html><head><title>Diagnóstico MySQL - GuardianIA</title>";
echo "<style>
body { font-family: Arial, sans-serif; background: #1a1a2e; color: #00ff88; padding: 20px; }
.container { max-width: 800px; margin: 0 auto; }
.step { background: rgba(0,255,136,0.1); border: 1px solid #00ff88; border-radius: 10px; padding: 20px; margin: 20px 0; }
.success { background: rgba(0,255,0,0.2); border-color: #00ff00; }
.error { background: rgba(255,0,0,0.2); border-color: #ff0000; color: #ff6666; }
.warning { background: rgba(255,255,0,0.2); border-color: #ffff00; color: #ffff66; }
.code { background: #000; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
h1, h2 { text-shadow: 0 0 10px #00ff88; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Diagnóstico MySQL - GuardianIA v3.0</h1>";

// Configuración
$host = 'localhost';
$user = 'root';
$pass = '0987654321';
$dbname = 'guardianai_db';
$port = 3306;

echo "<div class='step'>";
echo "<h2>📋 Configuración Actual</h2>";
echo "<ul>";
echo "<li><strong>Host:</strong> $host</li>";
echo "<li><strong>Usuario:</strong> $user</li>";
echo "<li><strong>Contraseña:</strong> " . str_repeat('*', strlen($pass)) . "</li>";
echo "<li><strong>Base de datos:</strong> $dbname</li>";
echo "<li><strong>Puerto:</strong> $port</li>";
echo "</ul>";
echo "</div>";

// Paso 1: Verificar extensión MySQL
echo "<div class='step'>";
echo "<h2>1️⃣ Verificar Extensión MySQL en PHP</h2>";
if (extension_loaded('mysqli')) {
    echo "<div class='success'>✅ Extensión MySQLi está cargada</div>";
} else {
    echo "<div class='error'>❌ Extensión MySQLi NO está cargada</div>";
    echo "<p><strong>Solución:</strong> Habilitar mysqli en php.ini</p>";
}
echo "</div>";

// Paso 2: Verificar si el puerto está abierto
echo "<div class='step'>";
echo "<h2>2️⃣ Verificar Puerto MySQL</h2>";
$connection = @fsockopen($host, $port, $errno, $errstr, 5);
if ($connection) {
    echo "<div class='success'>✅ Puerto $port está abierto y respondiendo</div>";
    fclose($connection);
} else {
    echo "<div class='error'>❌ Puerto $port NO está respondiendo</div>";
    echo "<p><strong>Error:</strong> $errstr ($errno)</p>";
    echo "<div class='warning'>";
    echo "<h3>🛠️ Soluciones posibles:</h3>";
    echo "<ol>";
    echo "<li><strong>Iniciar MySQL:</strong>";
    echo "<div class='code'>net start mysql</div>";
    echo "O desde Servicios de Windows</li>";
    echo "<li><strong>Verificar AppServ:</strong> Abrir AppServ Control Panel y iniciar MySQL</li>";
    echo "<li><strong>Instalar MySQL:</strong> Si no está instalado, descargar desde mysql.com</li>";
    echo "<li><strong>Verificar puerto:</strong> MySQL podría estar en puerto diferente (3307, 3308)</li>";
    echo "</ol>";
    echo "</div>";
}
echo "</div>";

// Paso 3: Intentar conexión básica
echo "<div class='step'>";
echo "<h2>3️⃣ Intentar Conexión MySQL</h2>";
$conn = @new mysqli($host, $user, $pass, '', $port);
if ($conn->connect_error) {
    echo "<div class='error'>❌ Error de conexión: " . $conn->connect_error . "</div>";
    
    // Analizar tipo de error
    $error_code = $conn->connect_errno;
    echo "<div class='warning'>";
    echo "<h3>🔍 Análisis del Error (Código: $error_code)</h3>";
    
    switch ($error_code) {
        case 2002:
            echo "<p><strong>Error 2002:</strong> MySQL no está ejecutándose o puerto bloqueado</p>";
            echo "<h4>Soluciones:</h4>";
            echo "<ol>";
            echo "<li>Iniciar servicio MySQL en Windows</li>";
            echo "<li>Verificar que AppServ esté ejecutando MySQL</li>";
            echo "<li>Probar puerto alternativo (3307)</li>";
            echo "</ol>";
            break;
        case 1045:
            echo "<p><strong>Error 1045:</strong> Credenciales incorrectas</p>";
            echo "<h4>Soluciones:</h4>";
            echo "<ol>";
            echo "<li>Verificar usuario y contraseña</li>";
            echo "<li>Resetear contraseña de root</li>";
            echo "</ol>";
            break;
        default:
            echo "<p><strong>Error desconocido:</strong> $error_code</p>";
    }
    echo "</div>";
} else {
    echo "<div class='success'>✅ Conexión exitosa a MySQL</div>";
    
    // Verificar base de datos
    echo "<h3>4️⃣ Verificar Base de Datos</h3>";
    $result = $conn->query("SHOW DATABASES LIKE '$dbname'");
    if ($result && $result->num_rows > 0) {
        echo "<div class='success'>✅ Base de datos '$dbname' existe</div>";
        
        // Verificar tablas
        $conn->select_db($dbname);
        $result = $conn->query("SHOW TABLES");
        if ($result && $result->num_rows > 0) {
            echo "<div class='success'>✅ Tablas encontradas: " . $result->num_rows . "</div>";
            echo "<h4>Tablas en la base de datos:</h4><ul>";
            while ($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<div class='warning'>⚠️ Base de datos existe pero no tiene tablas</div>";
            echo "<p>Necesitas importar el archivo database_setup.sql</p>";
        }
    } else {
        echo "<div class='warning'>⚠️ Base de datos '$dbname' no existe</div>";
        echo "<p>Creando base de datos...</p>";
        if ($conn->query("CREATE DATABASE $dbname")) {
            echo "<div class='success'>✅ Base de datos creada exitosamente</div>";
        } else {
            echo "<div class='error'>❌ Error creando base de datos: " . $conn->error . "</div>";
        }
    }
    
    $conn->close();
}
echo "</div>";

// Paso 4: Comandos útiles
echo "<div class='step'>";
echo "<h2>🛠️ Comandos Útiles</h2>";
echo "<h3>Para Windows (CMD como Administrador):</h3>";
echo "<div class='code'>";
echo "# Iniciar MySQL<br>";
echo "net start mysql<br><br>";
echo "# Detener MySQL<br>";
echo "net stop mysql<br><br>";
echo "# Ver servicios MySQL<br>";
echo "sc query mysql<br>";
echo "</div>";

echo "<h3>Para conectar manualmente:</h3>";
echo "<div class='code'>";
echo "mysql -u root -p0987654321 -h localhost -P 3306<br>";
echo "</div>";
echo "</div>";

// Paso 5: Próximos pasos
echo "<div class='step'>";
echo "<h2>🎯 Próximos Pasos</h2>";
echo "<ol>";
echo "<li><strong>Si MySQL no está instalado:</strong> Descargar e instalar MySQL Server</li>";
echo "<li><strong>Si está instalado pero no ejecutándose:</strong> Iniciar el servicio</li>";
echo "<li><strong>Si la conexión funciona:</strong> Importar database_setup.sql</li>";
echo "<li><strong>Si todo está bien:</strong> Probar GuardianIA</li>";
echo "</ol>";
echo "</div>";

echo "<div class='step success'>";
echo "<h2>✅ Verificación Completa</h2>";
echo "<p>Una vez que MySQL esté funcionando, GuardianIA debería conectarse automáticamente.</p>";
echo "<p><strong>Archivo de configuración:</strong> config.php</p>";
echo "<p><strong>Script de base de datos:</strong> database_setup.sql</p>";
echo "</div>";

echo "</div></body></html>";
?>
