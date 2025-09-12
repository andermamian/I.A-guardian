<?php
/**
 * Test Guardian AI - Archivo de pruebas para verificar funcionalidades
 * Anderson Mamian Chicangana - Sistema de Testing Completo
 */

// Configurar para mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🤖 Guardian AI - Sistema de Pruebas</h1>";
echo "<hr>";

// Test 1: Verificar archivos de configuración
echo "<h2>📋 Test 1: Verificación de Archivos</h2>";

$required_files = [
    'config.php' => 'Configuración principal',
    'config_military.php' => 'Configuración militar',
    'ai_chat.php' => 'Chat básico',
    'guardian_ai_enhanced.php' => 'Chat mejorado'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ <strong>{$description}</strong>: {$file} - ENCONTRADO<br>";
    } else {
        echo "❌ <strong>{$description}</strong>: {$file} - NO ENCONTRADO<br>";
    }
}

echo "<hr>";

// Test 2: Verificar configuración
echo "<h2>⚙️ Test 2: Verificación de Configuración</h2>";

try {
    require_once 'config.php';
    echo "✅ <strong>Config.php cargado correctamente</strong><br>";
    
    // Verificar constantes importantes
    $constants = [
        'APP_NAME' => 'Nombre de la aplicación',
        'APP_VERSION' => 'Versión de la aplicación',
        'DB_PRIMARY_HOST' => 'Host de base de datos',
        'DB_PRIMARY_USER' => 'Usuario de base de datos',
        'DB_PRIMARY_NAME' => 'Nombre de base de datos',
        'MILITARY_ENCRYPTION_ENABLED' => 'Encriptación militar',
        'PREMIUM_ENABLED' => 'Funciones premium'
    ];
    
    foreach ($constants as $const => $desc) {
        if (defined($const)) {
            $value = constant($const);
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            echo "✅ <strong>{$desc}</strong>: {$const} = {$value}<br>";
        } else {
            echo "❌ <strong>{$desc}</strong>: {$const} - NO DEFINIDA<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Error cargando configuración</strong>: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 3: Verificar conexión a base de datos
echo "<h2>🗄️ Test 3: Verificación de Base de Datos</h2>";

try {
    if (class_exists('MilitaryDatabaseManager')) {
        $db = MilitaryDatabaseManager::getInstance();
        
        if ($db->isConnected()) {
            echo "✅ <strong>Conexión a base de datos</strong>: EXITOSA<br>";
            
            $conn_info = $db->getConnectionInfo();
            echo "📊 <strong>Información de conexión</strong>:<br>";
            echo "&nbsp;&nbsp;• Tipo: " . ($conn_info['type'] ?? 'N/A') . "<br>";
            echo "&nbsp;&nbsp;• Host: " . ($conn_info['host'] ?? 'N/A') . "<br>";
            echo "&nbsp;&nbsp;• Usuario: " . ($conn_info['user'] ?? 'N/A') . "<br>";
            echo "&nbsp;&nbsp;• Base de datos: " . ($conn_info['database'] ?? 'N/A') . "<br>";
            echo "&nbsp;&nbsp;• Estado: " . ($conn_info['status'] ?? 'N/A') . "<br>";
            
            // Test de consulta simple
            try {
                $result = $db->query("SELECT 1 as test");
                if ($result) {
                    echo "✅ <strong>Consulta de prueba</strong>: EXITOSA<br>";
                } else {
                    echo "❌ <strong>Consulta de prueba</strong>: FALLIDA<br>";
                }
            } catch (Exception $e) {
                echo "❌ <strong>Consulta de prueba</strong>: ERROR - " . $e->getMessage() . "<br>";
            }
            
        } else {
            echo "❌ <strong>Conexión a base de datos</strong>: FALLIDA<br>";
            echo "⚠️ <strong>Modo fallback</strong>: El sistema funcionará sin base de datos<br>";
        }
    } else {
        echo "❌ <strong>Clase MilitaryDatabaseManager</strong>: NO ENCONTRADA<br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Error de base de datos</strong>: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 4: Verificar funciones principales
echo "<h2>🔧 Test 4: Verificación de Funciones</h2>";

$functions = [
    'logEvent' => 'Logging de eventos',
    'logMilitaryEvent' => 'Logging militar',
    'encryptData' => 'Encriptación de datos',
    'decryptData' => 'Desencriptación de datos',
    'generateToken' => 'Generación de tokens',
    'sanitizeInput' => 'Sanitización de entrada',
    'validateEmail' => 'Validación de email',
    'isPremiumUser' => 'Verificación de usuario premium',
    'getSystemStats' => 'Estadísticas del sistema'
];

foreach ($functions as $func => $desc) {
    if (function_exists($func)) {
        echo "✅ <strong>{$desc}</strong>: {$func}() - DISPONIBLE<br>";
    } else {
        echo "❌ <strong>{$desc}</strong>: {$func}() - NO DISPONIBLE<br>";
    }
}

echo "<hr>";

// Test 5: Verificar extensiones PHP
echo "<h2>🐘 Test 5: Verificación de Extensiones PHP</h2>";

$extensions = [
    'mysqli' => 'MySQL Improved',
    'openssl' => 'OpenSSL para encriptación',
    'json' => 'JSON para datos',
    'session' => 'Sesiones PHP',
    'curl' => 'cURL para HTTP',
    'mbstring' => 'Multibyte String'
];

foreach ($extensions as $ext => $desc) {
    if (extension_loaded($ext)) {
        echo "✅ <strong>{$desc}</strong>: {$ext} - CARGADA<br>";
    } else {
        echo "❌ <strong>{$desc}</strong>: {$ext} - NO CARGADA<br>";
    }
}

echo "<hr>";

// Test 6: Verificar directorios
echo "<h2>📁 Test 6: Verificación de Directorios</h2>";

$directories = [
    'logs' => 'Directorio de logs',
    'uploads' => 'Directorio de uploads',
    'cache' => 'Directorio de cache',
    'military' => 'Directorio militar',
    'keys' => 'Directorio de claves',
    'compositions' => 'Directorio de composiciones',
    'saved_compositions' => 'Directorio de composiciones guardadas'
];

foreach ($directories as $dir => $desc) {
    if (is_dir($dir)) {
        $writable = is_writable($dir) ? 'ESCRIBIBLE' : 'SOLO LECTURA';
        echo "✅ <strong>{$desc}</strong>: {$dir}/ - EXISTE ({$writable})<br>";
    } else {
        echo "❌ <strong>{$desc}</strong>: {$dir}/ - NO EXISTE<br>";
        // Intentar crear el directorio
        if (@mkdir($dir, 0755, true)) {
            echo "&nbsp;&nbsp;✅ <strong>Directorio creado</strong>: {$dir}/<br>";
        } else {
            echo "&nbsp;&nbsp;❌ <strong>No se pudo crear</strong>: {$dir}/<br>";
        }
    }
}

echo "<hr>";

// Test 7: Verificar estadísticas del sistema
echo "<h2>📊 Test 7: Estadísticas del Sistema</h2>";

try {
    if (function_exists('getSystemStats')) {
        $stats = getSystemStats();
        echo "✅ <strong>Estadísticas obtenidas correctamente</strong><br>";
        echo "📈 <strong>Información del sistema</strong>:<br>";
        echo "&nbsp;&nbsp;• Usuarios activos: " . ($stats['users_active'] ?? 'N/A') . "<br>";
        echo "&nbsp;&nbsp;• Usuarios premium: " . ($stats['premium_users'] ?? 'N/A') . "<br>";
        echo "&nbsp;&nbsp;• Amenazas detectadas hoy: " . ($stats['threats_detected_today'] ?? 'N/A') . "<br>";
        echo "&nbsp;&nbsp;• Detecciones IA hoy: " . ($stats['ai_detections_today'] ?? 'N/A') . "<br>";
        echo "&nbsp;&nbsp;• Estado de BD: " . ($stats['database_status'] ?? 'N/A') . "<br>";
        echo "&nbsp;&nbsp;• Nivel de seguridad: " . ($stats['security_level'] ?? 'N/A') . "%<br>";
        echo "&nbsp;&nbsp;• Encriptación militar: " . ($stats['military_encryption_status'] ?? 'N/A') . "<br>";
        echo "&nbsp;&nbsp;• Cumplimiento FIPS: " . ($stats['fips_compliance'] ?? 'N/A') . "<br>";
        echo "&nbsp;&nbsp;• Resistencia cuántica: " . ($stats['quantum_resistance'] ?? 'N/A') . "<br>";
    } else {
        echo "❌ <strong>Función getSystemStats no disponible</strong><br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>Error obteniendo estadísticas</strong>: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 8: Verificar integridad del sistema
echo "<h2>🔒 Test 8: Verificación de Integridad</h2>";

try {
    if (function_exists('verifySystemIntegrity')) {
        $integrity = verifySystemIntegrity();
        echo "✅ <strong>Verificación de integridad completada</strong><br>";
        echo "🛡️ <strong>Puntuación de integridad</strong>: " . ($integrity['score'] ?? 'N/A') . "%<br>";
        echo "📋 <strong>Estado del sistema</strong>: " . ($integrity['status'] ?? 'N/A') . "<br>";
        
        if (isset($integrity['checks'])) {
            echo "🔍 <strong>Verificaciones individuales</strong>:<br>";
            foreach ($integrity['checks'] as $check => $result) {
                $status = $result ? '✅' : '❌';
                echo "&nbsp;&nbsp;{$status} {$check}<br>";
            }
        }
    } else {
        echo "❌ <strong>Función verifySystemIntegrity no disponible</strong><br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>Error verificando integridad</strong>: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 9: Test de encriptación
echo "<h2>🔐 Test 9: Verificación de Encriptación</h2>";

try {
    if (function_exists('encryptData') && function_exists('decryptData')) {
        $test_data = "Guardian AI Test Data - " . date('Y-m-d H:i:s');
        
        $encrypted = encryptData($test_data);
        echo "✅ <strong>Encriptación</strong>: EXITOSA<br>";
        echo "&nbsp;&nbsp;• Datos originales: {$test_data}<br>";
        echo "&nbsp;&nbsp;• Datos encriptados: " . substr($encrypted, 0, 50) . "...<br>";
        
        $decrypted = decryptData($encrypted);
        if ($decrypted === $test_data) {
            echo "✅ <strong>Desencriptación</strong>: EXITOSA<br>";
            echo "&nbsp;&nbsp;• Datos recuperados: {$decrypted}<br>";
        } else {
            echo "❌ <strong>Desencriptación</strong>: FALLIDA<br>";
            echo "&nbsp;&nbsp;• Esperado: {$test_data}<br>";
            echo "&nbsp;&nbsp;• Obtenido: {$decrypted}<br>";
        }
    } else {
        echo "❌ <strong>Funciones de encriptación no disponibles</strong><br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>Error en test de encriptación</strong>: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 10: Resumen final
echo "<h2>📋 Test 10: Resumen Final</h2>";

$total_tests = 10;
$passed_tests = 0;

// Contar tests pasados (simplificado)
if (file_exists('config.php')) $passed_tests++;
if (class_exists('MilitaryDatabaseManager')) $passed_tests++;
if (function_exists('logEvent')) $passed_tests++;
if (extension_loaded('mysqli')) $passed_tests++;
if (is_dir('logs')) $passed_tests++;
if (function_exists('getSystemStats')) $passed_tests++;
if (function_exists('verifySystemIntegrity')) $passed_tests++;
if (function_exists('encryptData')) $passed_tests++;

$success_rate = ($passed_tests / $total_tests) * 100;

echo "<div style='background: " . ($success_rate >= 80 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🎯 Resultado Final</h3>";
echo "<strong>Tests pasados</strong>: {$passed_tests}/{$total_tests}<br>";
echo "<strong>Tasa de éxito</strong>: " . number_format($success_rate, 1) . "%<br>";

if ($success_rate >= 90) {
    echo "<strong>Estado</strong>: 🟢 EXCELENTE - Sistema completamente funcional<br>";
} elseif ($success_rate >= 80) {
    echo "<strong>Estado</strong>: 🟡 BUENO - Sistema funcional con advertencias menores<br>";
} elseif ($success_rate >= 60) {
    echo "<strong>Estado</strong>: 🟠 REGULAR - Sistema funcional con problemas<br>";
} else {
    echo "<strong>Estado</strong>: 🔴 CRÍTICO - Sistema requiere atención inmediata<br>";
}

echo "</div>";

echo "<hr>";
echo "<p><strong>🤖 Guardian AI Testing System v1.0</strong> - Desarrollado por Anderson Mamian Chicangana</p>";
echo "<p><em>Fecha de prueba: " . date('Y-m-d H:i:s') . "</em></p>";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
    color: #333;
}

h1 {
    color: #2c3e50;
    text-align: center;
    margin-bottom: 30px;
}

h2 {
    color: #34495e;
    border-bottom: 2px solid #3498db;
    padding-bottom: 5px;
    margin-top: 30px;
}

hr {
    border: none;
    height: 1px;
    background: #bdc3c7;
    margin: 20px 0;
}

strong {
    color: #2c3e50;
}

code {
    background: #ecf0f1;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
</style>

