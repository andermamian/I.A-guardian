<?php
/**
 * Test Completo de Sincronización GuardianIA v3.0
 * Verifica consistencia entre BD, config y scripts
 * Anderson Mamian Chicangana
 */

// Incluir configuraciones
require_once 'config.php';
require_once 'config_military.php';

// Inicializar sesión para tests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class GuardianSyncTest {
    private $db;
    private $errors = [];
    private $warnings = [];
    private $successes = [];
    private $testResults = [];
    
    public function __construct() {
        $this->db = MilitaryDatabaseManager::getInstance();
        echo "🔍 INICIANDO TEST DE SINCRONIZACIÓN GUARDIA AI v3.0\n";
        echo str_repeat("=", 80) . "\n";
    }
    
    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        $this->testDatabaseConnection();
        $this->testTableStructure();
        $this->testConfigurationConstants();
        $this->testFunctionDefinitions();
        $this->testSessionVariables();
        $this->testFileReferences();
        $this->testDatabaseQueries();
        $this->testUserAuthentication();
        $this->testPermissionSystems();
        $this->testModuleIntegration();
        
        $this->displayResults();
        return $this->generateReport();
    }
    
    /**
     * Test 1: Conexión a Base de Datos
     */
    private function testDatabaseConnection() {
        echo "\n📊 TEST 1: CONEXIÓN A BASE DE DATOS\n";
        echo str_repeat("-", 50) . "\n";
        
        try {
            if ($this->db && $this->db->isConnected()) {
                $conn_info = $this->db->getConnectionInfo();
                $this->addSuccess("Conexión a BD establecida: " . $conn_info['type']);
                $this->addSuccess("Encryption: " . $conn_info['encryption']);
                $this->addSuccess("FIPS Compliance: " . ($conn_info['fips_compliance'] ? 'YES' : 'NO'));
            } else {
                $this->addError("No se pudo conectar a la base de datos");
            }
        } catch (Exception $e) {
            $this->addError("Error de conexión: " . $e->getMessage());
        }
    }
    
    /**
     * Test 2: Estructura de Tablas
     */
    private function testTableStructure() {
        echo "\n🗄️ TEST 2: ESTRUCTURA DE TABLAS\n";
        echo str_repeat("-", 50) . "\n";
        
        $required_tables = [
            'users' => ['id', 'username', 'password_hash', 'email', 'user_type', 'premium_status'],
            'conversations' => ['id', 'user_id', 'title', 'status'],
            'conversation_messages' => ['id', 'conversation_id', 'user_id', 'message_content'],
            'security_events' => ['id', 'user_id', 'event_type', 'description', 'severity'],
            'system_logs' => ['id', 'level', 'message', 'user_id'],
            'premium_features' => ['id', 'feature_key', 'feature_name'],
            'music_compositions' => ['id', 'user_id', 'title', 'genre'],
            'audio_recordings' => ['id', 'user_id', 'file_path']
        ];
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->addWarning("Sin conexión BD - usando usuarios por defecto");
            return;
        }
        
        try {
            foreach ($required_tables as $table => $required_fields) {
                // Verificar si existe la tabla
                $result = $this->db->query("SHOW TABLES LIKE '$table'");
                if ($result && $result->num_rows > 0) {
                    $this->addSuccess("Tabla '$table' existe");
                    
                    // Verificar campos requeridos
                    $desc_result = $this->db->query("DESCRIBE $table");
                    $existing_fields = [];
                    while ($row = $desc_result->fetch_assoc()) {
                        $existing_fields[] = $row['Field'];
                    }
                    
                    foreach ($required_fields as $field) {
                        if (in_array($field, $existing_fields)) {
                            $this->addSuccess("  ✓ Campo '$field' existe en '$table'");
                        } else {
                            $this->addError("  ✗ Campo '$field' falta en '$table'");
                        }
                    }
                } else {
                    $this->addError("Tabla '$table' no existe");
                }
            }
        } catch (Exception $e) {
            $this->addError("Error verificando tablas: " . $e->getMessage());
        }
    }
    
    /**
     * Test 3: Constantes de Configuración
     */
    private function testConfigurationConstants() {
        echo "\n⚙️ TEST 3: CONSTANTES DE CONFIGURACIÓN\n";
        echo str_repeat("-", 50) . "\n";
        
        $required_constants = [
            'APP_NAME', 'APP_VERSION', 'DEVELOPER',
            'MILITARY_ENCRYPTION_ENABLED', 'FIPS_140_2_COMPLIANCE',
            'DB_PRIMARY_HOST', 'DB_PRIMARY_USER', 'DB_PRIMARY_NAME',
            'ENCRYPTION_KEY', 'SESSION_LIFETIME', 'PREMIUM_ENABLED'
        ];
        
        foreach ($required_constants as $constant) {
            if (defined($constant)) {
                $value = constant($constant);
                $this->addSuccess("Constante '$constant' = " . 
                    (is_bool($value) ? ($value ? 'TRUE' : 'FALSE') : 
                    (strlen($value) > 50 ? substr($value, 0, 50) . "..." : $value)));
            } else {
                $this->addError("Constante '$constant' no está definida");
            }
        }
    }
    
    /**
     * Test 4: Definiciones de Funciones
     */
    private function testFunctionDefinitions() {
        echo "\n🔧 TEST 4: FUNCIONES DEFINIDAS\n";
        echo str_repeat("-", 50) . "\n";
        
        $required_functions = [
            'logEvent' => 'config.php',
            'logMilitaryEvent' => 'config_military.php',
            'logSecurityEvent' => 'config.php',
            'encryptData' => 'config.php',
            'decryptData' => 'config.php',
            'isPremiumUser' => 'config.php',
            'getSystemStats' => 'config.php',
            'verifySystemIntegrity' => 'config.php',
            'generateToken' => 'config.php',
            'sanitizeInput' => 'config.php'
        ];
        
        foreach ($required_functions as $function => $file) {
            if (function_exists($function)) {
                $this->addSuccess("Función '$function' está definida ($file)");
            } else {
                $this->addError("Función '$function' no está definida (esperada en $file)");
            }
        }
        
        // Verificar funciones que se usan pero pueden no estar definidas
        $possibly_missing = [
            'checkMembershipAccess' => 'user_dashboard.php',
            'checkModuleAccess' => 'user_dashboard.php',
            'getUserStats' => 'user_dashboard.php (local)',
            'getUserRecentActivity' => 'user_dashboard.php (local)'
        ];
        
        echo "\n🔍 Verificando funciones posiblemente faltantes:\n";
        foreach ($possibly_missing as $function => $location) {
            if (function_exists($function)) {
                $this->addSuccess("Función '$function' está definida");
            } else {
                $this->addWarning("Función '$function' puede estar definida localmente en $location");
            }
        }
    }
    
    /**
     * Test 5: Variables de Sesión
     */
    private function testSessionVariables() {
        echo "\n🔐 TEST 5: VARIABLES DE SESIÓN\n";
        echo str_repeat("-", 50) . "\n";
        
        $expected_session_vars = [
            'logged_in', 'user_id', 'username', 'user_type',
            'premium_status', 'security_clearance', 'military_access'
        ];
        
        echo "Estado de sesión: " . (session_status() === PHP_SESSION_ACTIVE ? "ACTIVA" : "INACTIVA") . "\n";
        
        foreach ($expected_session_vars as $var) {
            if (isset($_SESSION[$var])) {
                $value = $_SESSION[$var];
                $display_value = is_bool($value) ? ($value ? 'TRUE' : 'FALSE') : $value;
                $this->addSuccess("Variable de sesión '$var' = $display_value");
            } else {
                $this->addWarning("Variable de sesión '$var' no está definida");
            }
        }
        
        // Verificar usuario por defecto
        if (isset($GLOBALS['DEFAULT_USERS'])) {
            $default_users = $GLOBALS['DEFAULT_USERS'];
            $this->addSuccess("Usuarios por defecto definidos: " . count($default_users));
            foreach ($default_users as $username => $data) {
                $this->addSuccess("  ✓ Usuario por defecto: $username ({$data['user_type']})");
            }
        } else {
            $this->addError("Array DEFAULT_USERS no está definido");
        }
    }
    
    /**
     * Test 6: Referencias a Archivos
     */
    private function testFileReferences() {
        echo "\n📁 TEST 6: REFERENCIAS A ARCHIVOS\n";
        echo str_repeat("-", 50) . "\n";
        
        $referenced_files = [
            'login.php', 'logout.php', 'membership_system.php',
            'user_security.php', 'user_performance.php', 'user_settings.php',
            'user_permissions.php', 'user_assistant.php',
            'ai_consciousness.php', 'neural_visualizer.php', 'real_time_monitor.php'
        ];
        
        foreach ($referenced_files as $file) {
            if (file_exists(__DIR__ . '/' . $file)) {
                $this->addSuccess("Archivo '$file' existe");
            } else {
                $this->addWarning("Archivo '$file' no encontrado - puede afectar navegación");
            }
        }
        
        // Verificar directorios
        $required_dirs = ['logs', 'uploads', 'cache', 'military', 'keys', 'compositions'];
        foreach ($required_dirs as $dir) {
            $dir_path = __DIR__ . '/' . $dir;
            if (is_dir($dir_path)) {
                $this->addSuccess("Directorio '$dir' existe");
            } else {
                $this->addWarning("Directorio '$dir' no existe - se creará automáticamente");
                // Intentar crear el directorio
                if (@mkdir($dir_path, 0755, true)) {
                    $this->addSuccess("  ✓ Directorio '$dir' creado automáticamente");
                } else {
                    $this->addError("  ✗ No se pudo crear directorio '$dir'");
                }
            }
        }
    }
    
    /**
     * Test 7: Consultas de Base de Datos
     */
    private function testDatabaseQueries() {
        echo "\n💾 TEST 7: CONSULTAS DE BASE DE DATOS\n";
        echo str_repeat("-", 50) . "\n";
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->addWarning("Sin conexión BD - saltando tests de consultas");
            return;
        }
        
        try {
            // Test consulta básica usuarios
            $result = $this->db->query("SELECT COUNT(*) as count FROM users");
            if ($result) {
                $row = $result->fetch_assoc();
                $this->addSuccess("Consulta usuarios: " . $row['count'] . " usuarios encontrados");
            }
            
            // Test consulta con parámetros
            $result = $this->db->query("SELECT username FROM users WHERE status = ? LIMIT 1", ['active']);
            if ($result) {
                $this->addSuccess("Consulta preparada con parámetros funciona correctamente");
            }
            
            // Test procedimientos almacenados
            $result = $this->db->query("CALL GetSystemStats()");
            if ($result) {
                $this->addSuccess("Procedimiento almacenado GetSystemStats() funciona");
            }
            
        } catch (Exception $e) {
            $this->addError("Error en consultas BD: " . $e->getMessage());
        }
    }
    
    /**
     * Test 8: Sistema de Autenticación
     */
    private function testUserAuthentication() {
        echo "\n👤 TEST 8: SISTEMA DE AUTENTICACIÓN\n";
        echo str_repeat("-", 50) . "\n";
        
        // Verificar función isPremiumUser
        if (function_exists('isPremiumUser')) {
            $test_user_id = 1; // Anderson
            $is_premium = isPremiumUser($test_user_id);
            $this->addSuccess("isPremiumUser($test_user_id) = " . ($is_premium ? 'TRUE' : 'FALSE'));
        }
        
        // Verificar hash de passwords en usuarios por defecto
        if (isset($GLOBALS['DEFAULT_USERS'])) {
            foreach ($GLOBALS['DEFAULT_USERS'] as $username => $data) {
                if (isset($data['password_hash'])) {
                    $this->addSuccess("Usuario '$username' tiene password_hash definido");
                } else {
                    $this->addError("Usuario '$username' sin password_hash");
                }
            }
        }
        
        // Verificar configuración de sesión segura
        $session_config = [
            'session.cookie_httponly' => ini_get('session.cookie_httponly'),
            'session.use_strict_mode' => ini_get('session.use_strict_mode')
        ];
        
        foreach ($session_config as $setting => $value) {
            if ($value) {
                $this->addSuccess("Configuración segura: $setting = $value");
            } else {
                $this->addWarning("Configuración de seguridad: $setting no está habilitado");
            }
        }
    }
    
    /**
     * Test 9: Sistema de Permisos
     */
    private function testPermissionSystems() {
        echo "\n🔒 TEST 9: SISTEMA DE PERMISOS\n";
        echo str_repeat("-", 50) . "\n";
        
        // Verificar niveles de clasificación militar
        if (defined('CLASSIFICATION_LEVELS')) {
            $levels = unserialize(CLASSIFICATION_LEVELS);
            $this->addSuccess("Niveles de clasificación definidos: " . count($levels));
            foreach ($levels as $level => $value) {
                $this->addSuccess("  ✓ $level = $value");
            }
        } else {
            $this->addError("CLASSIFICATION_LEVELS no está definido");
        }
        
        // Verificar algoritmos militares
        if (defined('MILITARY_ALGORITHMS')) {
            $algorithms = unserialize(MILITARY_ALGORITHMS);
            $this->addSuccess("Algoritmos militares definidos: " . count($algorithms));
        } else {
            $this->addError("MILITARY_ALGORITHMS no está definido");
        }
        
        // Verificar cumplimiento FIPS
        if (defined('FIPS_140_2_COMPLIANCE') && FIPS_140_2_COMPLIANCE) {
            $this->addSuccess("Cumplimiento FIPS 140-2 habilitado");
        } else {
            $this->addWarning("Cumplimiento FIPS 140-2 no habilitado");
        }
    }
    
    /**
     * Test 10: Integración de Módulos
     */
    private function testModuleIntegration() {
        echo "\n🔗 TEST 10: INTEGRACIÓN DE MÓDULOS\n";
        echo str_repeat("-", 50) . "\n";
        
        // Verificar que las clases principales estén disponibles
        if (class_exists('MilitaryDatabaseManager')) {
            $this->addSuccess("Clase MilitaryDatabaseManager disponible");
            
            // Verificar métodos de la clase
            $methods = ['getInstance', 'getConnection', 'isConnected', 'query', 'encryptSensitiveData'];
            foreach ($methods as $method) {
                if (method_exists('MilitaryDatabaseManager', $method)) {
                    $this->addSuccess("  ✓ Método '$method' disponible");
                } else {
                    $this->addError("  ✗ Método '$method' no disponible");
                }
            }
        } else {
            $this->addError("Clase MilitaryDatabaseManager no está disponible");
        }
        
        // Verificar integración con funciones globales
        $global_functions = ['getSystemStats', 'verifySystemIntegrity', 'generateToken'];
        foreach ($global_functions as $func) {
            if (function_exists($func)) {
                try {
                    // Intentar ejecutar función (algunas pueden requerir parámetros)
                    if ($func === 'getSystemStats') {
                        $stats = getSystemStats();
                        $this->addSuccess("Función '$func' ejecutada correctamente");
                    } elseif ($func === 'verifySystemIntegrity') {
                        $integrity = verifySystemIntegrity();
                        $this->addSuccess("Función '$func' retorna score: " . $integrity['score'] . "%");
                    } elseif ($func === 'generateToken') {
                        $token = generateToken(16);
                        $this->addSuccess("Función '$func' genera token de " . strlen($token) . " caracteres");
                    }
                } catch (Exception $e) {
                    $this->addWarning("Función '$func' definida pero error al ejecutar: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Agregar resultado exitoso
     */
    private function addSuccess($message) {
        $this->successes[] = $message;
        echo "✅ $message\n";
    }
    
    /**
     * Agregar advertencia
     */
    private function addWarning($message) {
        $this->warnings[] = $message;
        echo "⚠️  $message\n";
    }
    
    /**
     * Agregar error
     */
    private function addError($message) {
        $this->errors[] = $message;
        echo "❌ $message\n";
    }
    
    /**
     * Mostrar resultados finales
     */
    private function displayResults() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "📋 RESUMEN DE RESULTADOS\n";
        echo str_repeat("=", 80) . "\n";
        
        echo "✅ ÉXITOS: " . count($this->successes) . "\n";
        echo "⚠️  ADVERTENCIAS: " . count($this->warnings) . "\n";
        echo "❌ ERRORES: " . count($this->errors) . "\n";
        
        $total_tests = count($this->successes) + count($this->warnings) + count($this->errors);
        $success_rate = $total_tests > 0 ? (count($this->successes) / $total_tests) * 100 : 0;
        
        echo "\n📊 TASA DE ÉXITO: " . round($success_rate, 2) . "%\n";
        
        if ($success_rate >= 90) {
            echo "🎉 EXCELENTE - Sistema bien sincronizado\n";
        } elseif ($success_rate >= 75) {
            echo "👍 BUENO - Algunos ajustes menores necesarios\n";
        } elseif ($success_rate >= 60) {
            echo "⚠️  REGULAR - Se requieren correcciones\n";
        } else {
            echo "❌ CRÍTICO - Problemas serios de sincronización\n";
        }
        
        if (count($this->errors) > 0) {
            echo "\n🚨 ERRORES CRÍTICOS QUE REQUIEREN ATENCIÓN:\n";
            foreach (array_slice($this->errors, 0, 5) as $error) {
                echo "  • $error\n";
            }
            if (count($this->errors) > 5) {
                echo "  ... y " . (count($this->errors) - 5) . " errores más\n";
            }
        }
    }
    
    /**
     * Generar reporte detallado
     */
    private function generateReport() {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_tests' => count($this->successes) + count($this->warnings) + count($this->errors),
            'successes' => count($this->successes),
            'warnings' => count($this->warnings),
            'errors' => count($this->errors),
            'success_rate' => count($this->successes) + count($this->warnings) + count($this->errors) > 0 
                ? (count($this->successes) / (count($this->successes) + count($this->warnings) + count($this->errors))) * 100 
                : 0,
            'details' => [
                'successes' => $this->successes,
                'warnings' => $this->warnings,
                'errors' => $this->errors
            ]
        ];
        
        // Guardar reporte en archivo
        $report_file = __DIR__ . '/logs/sync_test_' . date('Y-m-d_H-i-s') . '.json';
        if (!file_exists(dirname($report_file))) {
            @mkdir(dirname($report_file), 0755, true);
        }
        file_put_contents($report_file, json_encode($report, JSON_PRETTY_PRINT));
        
        echo "\n💾 Reporte detallado guardado en: $report_file\n";
        
        return $report;
    }
}

// Ejecutar el test si el archivo se ejecuta directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $test = new GuardianSyncTest();
    $results = $test->runAllTests();
    
    echo "\n🔧 RECOMENDACIONES PARA MEJORAR LA SINCRONIZACIÓN:\n";
    echo str_repeat("-", 60) . "\n";
    echo "1. Agregar función checkMembershipAccess() en config.php\n";
    echo "2. Verificar que todos los archivos referenciados existan\n";
    echo "3. Asegurar consistencia en nombres de campos BD vs sesiones\n";
    echo "4. Implementar validación de integridad en consultas\n";
    echo "5. Crear tests automatizados para ejecución regular\n";
    
    exit(0);
}
?> 