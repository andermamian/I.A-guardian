<?php
// Activar TODOS los errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Debug del Sistema Cuántico</h1>";

// Test 1: PHP funciona
echo "<h2>Test 1: PHP Básico</h2>";
echo "✅ PHP está funcionando<br>";
echo "Versión PHP: " . PHP_VERSION . "<br><br>";

// Test 2: Config existe
echo "<h2>Test 2: Archivos de Configuración</h2>";
if (file_exists('config.php')) {
    echo "✅ config.php existe<br>";
    try {
        require_once 'config.php';
        echo "✅ config.php cargado correctamente<br>";
    } catch (Exception $e) {
        echo "❌ Error en config.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ config.php NO existe<br>";
}

// Test 3: Config militar
echo "<br>";
if (file_exists('config_military.php')) {
    echo "✅ config_military.php existe<br>";
    try {
        require_once 'config_military.php';
        echo "✅ config_military.php cargado correctamente<br>";
    } catch (Exception $e) {
        echo "❌ Error en config_military.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ config_military.php NO existe - DEBES CREARLO<br>";
    echo "Contenido sugerido:<br>";
    echo "<pre>";
    echo htmlspecialchars('<?php
if (!defined("QUANTUM_KEY_LENGTH")) {
    define("QUANTUM_KEY_LENGTH", 2048);
    define("QUANTUM_ENTANGLEMENT_PAIRS", 1024);
    define("QUANTUM_ERROR_THRESHOLD", 0.11);
    define("QUANTUM_CHANNEL_FIDELITY", 0.95);
}

if (!function_exists("logMilitaryEvent")) {
    function logMilitaryEvent($event_type, $description, $classification = "UNCLASSIFIED") {
        $log_dir = __DIR__ . "/logs";
        if (!file_exists($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        $timestamp = date("Y-m-d H:i:s");
        $log_entry = "[{$timestamp}] {$classification} - {$event_type}: {$description}" . PHP_EOL;
        @file_put_contents($log_dir . "/military.log", $log_entry, FILE_APPEND | LOCK_EX);
    }
}
?>');
    echo "</pre>";
}

// Test 4: Quantum encryption
echo "<h2>Test 3: Módulo Cuántico</h2>";
if (file_exists('quantum_encryption.php')) {
    echo "✅ quantum_encryption.php existe<br>";
    echo "Tamaño del archivo: " . filesize('quantum_encryption.php') . " bytes<br>";
    
    // Verificar sintaxis PHP
    $code = file_get_contents('quantum_encryption.php');
    $tokens = token_get_all($code);
    $error = false;
    
    // Buscar errores obvios
    if (substr($code, 0, 5) !== '<?php') {
        echo "⚠️ El archivo no empieza con <?php<br>";
    }
    
    // Verificar caracteres especiales
    if (strpos($code, 'Ã') !== false) {
        echo "⚠️ ADVERTENCIA: El archivo contiene caracteres con problemas de codificación (Ã)<br>";
        echo "Esto puede causar errores. El archivo necesita ser guardado en UTF-8<br>";
    }
    
    echo "<br>Intentando cargar el módulo...<br>";
    
    // Intentar incluir con captura de errores
    ob_start();
    $error_reporting = error_reporting(E_ALL);
    
    try {
        include_once 'quantum_encryption.php';
        echo "✅ Archivo incluido<br>";
        
        if (class_exists('AdvancedQuantumEncryption')) {
            echo "✅ Clase AdvancedQuantumEncryption existe<br>";
        } else {
            echo "❌ Clase AdvancedQuantumEncryption NO existe<br>";
        }
    } catch (ParseError $e) {
        echo "❌ Error de sintaxis: " . $e->getMessage() . " en línea " . $e->getLine() . "<br>";
    } catch (Error $e) {
        echo "❌ Error fatal: " . $e->getMessage() . " en línea " . $e->getLine() . "<br>";
    } catch (Exception $e) {
        echo "❌ Excepción: " . $e->getMessage() . "<br>";
    }
    
    $output = ob_get_clean();
    if ($output) {
        echo "Salida del archivo:<br><pre>" . htmlspecialchars($output) . "</pre>";
    }
    
    error_reporting($error_reporting);
} else {
    echo "❌ quantum_encryption.php NO existe<br>";
}

// Test 5: Verificar sesión
echo "<h2>Test 4: Sesión</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Estado de sesión: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ ACTIVA" : "❌ INACTIVA") . "<br>";

// Test 6: Base de datos
echo "<h2>Test 5: Base de Datos</h2>";
if (class_exists('MilitaryDatabaseManager')) {
    $db = MilitaryDatabaseManager::getInstance();
    if ($db && $db->isConnected()) {
        echo "✅ Conectado a la base de datos<br>";
    } else {
        echo "❌ No conectado a la base de datos<br>";
    }
} else {
    echo "⚠️ Clase MilitaryDatabaseManager no disponible<br>";
}

echo "<h2>Resumen</h2>";
echo "<p>Revisa los errores arriba. El problema principal parece estar en los caracteres de codificación.</p>";
echo "<p><strong>SOLUCIÓN SUGERIDA:</strong></p>";
echo "<ol>";
echo "<li>El archivo quantum_encryption.php tiene problemas de codificación (caracteres Ã)</li>";
echo "<li>Necesitas volver a guardar el archivo en codificación UTF-8</li>";
echo "<li>O reemplazar todos los caracteres problemáticos</li>";
echo "</ol>";

phpinfo();
?>