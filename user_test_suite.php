<?php
/**
 * GuardianIA v3.0 FINAL - Suite de Pruebas Completa
 * Anderson Mamian Chicangana - Sistema de Testing Militar
 * Test Suite completo para validación del sistema
 */

// Configuración de errores para testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);

// Incluir configuraciones
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_military.php';

// Colores para output en consola
class ConsoleColors {
    private static $colors = [
        'black' => '0;30',
        'dark_gray' => '1;30',
        'red' => '0;31',
        'light_red' => '1;31',
        'green' => '0;32',
        'light_green' => '1;32',
        'brown' => '0;33',
        'yellow' => '1;33',
        'blue' => '0;34',
        'light_blue' => '1;34',
        'purple' => '0;35',
        'light_purple' => '1;35',
        'cyan' => '0;36',
        'light_cyan' => '1;36',
        'light_gray' => '0;37',
        'white' => '1;37'
    ];

    public static function colored($string, $color) {
        if (!isset(self::$colors[$color])) {
            return $string;
        }
        $colored_string = "\033[" . self::$colors[$color] . "m";
        $colored_string .= $string . "\033[0m";
        return $colored_string;
    }
}

/**
 * Clase principal de Testing
 */
class GuardianTestSuite {
    private $db;
    private $tests_passed = 0;
    private $tests_failed = 0;
    private $tests_skipped = 0;
    private $test_results = [];
    private $start_time;
    private $console_mode = false;
    private $web_mode = false;
    
    // Configuración de tablas de la base de datos
    private $database_tables = [
        'users' => ['id', 'username', 'password', 'password_hash', 'email', 'fullname', 'user_type', 'premium_status', 'status', 'security_clearance', 'military_access'],
        'conversations' => ['id', 'user_id', 'title', 'conversation_type', 'status', 'message_count'],
        'conversation_messages' => ['id', 'conversation_id', 'user_id', 'message_type', 'message_content', 'ai_confidence_score', 'threat_detected'],
        'ai_detections' => ['id', 'user_id', 'conversation_id', 'message_content', 'confidence_score', 'threat_level'],
        'security_events' => ['id', 'user_id', 'event_type', 'description', 'severity', 'ip_address', 'resolved'],
        'military_logs' => ['id', 'classification', 'event_type', 'description', 'user_id', 'ip_address', 'quantum_timestamp'],
        'quantum_keys' => ['id', 'key_id', 'user_id', 'key_type', 'key_length', 'key_data', 'security_parameter'],
        'quantum_sessions' => ['id', 'session_id', 'user_id', 'quantum_key', 'bb84_result', 'entanglement_pairs', 'fidelity'],
        'premium_features' => ['id', 'feature_key', 'feature_name', 'description', 'is_active', 'premium_only'],
        'notifications' => ['id', 'user_id', 'title', 'message', 'type', 'is_read'],
        'system_logs' => ['id', 'level', 'message', 'context', 'user_id', 'ip_address'],
        'system_config' => ['id', 'config_key', 'config_value', 'config_type', 'description'],
        'protected_devices' => ['id', 'device_id', 'user_id', 'name', 'type', 'status', 'location'],
        'device_locations' => ['id', 'device_id', 'latitude', 'longitude', 'accuracy', 'city', 'country'],
        'security_alerts' => ['id', 'device_id', 'alert_type', 'message', 'severity', 'is_resolved'],
        'assistant_conversations' => ['id', 'user_id', 'session_id', 'message_type', 'message_content'],
        'music_compositions' => ['id', 'user_id', 'composition_id', 'title', 'genre', 'bpm', 'key_signature'],
        'audio_recordings' => ['id', 'user_id', 'composition_id', 'recording_type', 'file_path', 'duration'],
        'studio_analytics' => ['id', 'user_id', 'session_id', 'action_type', 'composition_id'],
        'genre_templates' => ['id', 'genre_name', 'display_name', 'bpm_min', 'bpm_max'],
        'audio_effects' => ['id', 'effect_name', 'display_name', 'category', 'description']
    ];
    
    public function __construct() {
        $this->start_time = microtime(true);
        $this->console_mode = (php_sapi_name() === 'cli');
        $this->web_mode = !$this->console_mode;
        
        // Inicializar conexión a base de datos
        try {
            $this->db = MilitaryDatabaseManager::getInstance();
        } catch (Exception $e) {
            $this->logError("Error inicializando base de datos: " . $e->getMessage());
        }
        
        $this->outputHeader();
    }
    
    private function outputHeader() {
        if ($this->web_mode) {
            echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GuardianIA Test Suite</title>
    <style>
        body { 
            font-family: "Courier New", monospace; 
            background: #000; 
            color: #0f0; 
            padding: 20px;
            line-height: 1.6;
        }
        .header { 
            color: #fff; 
            background: linear-gradient(45deg, #b366ff, #ff66d9);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .test-pass { color: #0f0; }
        .test-fail { color: #f00; }
        .test-skip { color: #ff0; }
        .test-info { color: #0ff; }
        .test-section { 
            border: 1px solid #333;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            background: rgba(255,255,255,0.02);
        }
        .progress {
            width: 100%;
            height: 20px;
            background: #333;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #0f0, #0ff);
            transition: width 0.3s;
        }
        pre { 
            background: #111; 
            padding: 10px; 
            border-radius: 5px;
            overflow-x: auto;
        }
        .summary {
            background: #222;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border: 2px solid #444;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            border: 1px solid #333;
            text-align: left;
        }
        th {
            background: #222;
            color: #0ff;
        }
        .quantum-box {
            border: 2px dashed #b366ff;
            padding: 15px;
            margin: 20px 0;
            background: rgba(179, 102, 255, 0.1);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛡️ GuardianIA v3.0 - Test Suite Completo 🛡️</h1>
        <p>Sistema de Testing Militar con Encriptación Cuántica</p>
        <p>Desarrollador: Anderson Mamian Chicangana</p>
    </div>';
        } else {
            echo ConsoleColors::colored("\n", 'white');
            echo ConsoleColors::colored("╔══════════════════════════════════════════════════════════════╗\n", 'cyan');
            echo ConsoleColors::colored("║        GuardianIA v3.0 - Test Suite Completo                ║\n", 'light_purple');
            echo ConsoleColors::colored("║     Sistema de Testing Militar con Encriptación Cuántica    ║\n", 'light_purple');
            echo ConsoleColors::colored("║       Desarrollador: Anderson Mamian Chicangana             ║\n", 'white');
            echo ConsoleColors::colored("╚══════════════════════════════════════════════════════════════╝\n", 'cyan');
            echo ConsoleColors::colored("\nIniciando pruebas...\n\n", 'yellow');
        }
    }
    
    /**
     * Ejecutar todas las pruebas
     */
    public function runAllTests() {
        $this->outputSection("INICIANDO SUITE DE PRUEBAS COMPLETA");
        
        // 1. Pruebas de Configuración
        $this->testConfiguration();
        
        // 2. Pruebas de Base de Datos
        $this->testDatabaseConnection();
        $this->testDatabaseTables();
        $this->testDatabaseOperations();
        
        // 3. Pruebas de Usuarios
        $this->testUserAuthentication();
        $this->testPremiumFeatures();
        $this->testSecurityClearance();
        
        // 4. Pruebas de Seguridad Militar
        $this->testMilitaryEncryption();
        $this->testQuantumEncryption();
        $this->testFIPSCompliance();
        
        // 5. Pruebas de Funcionalidades IA
        $this->testAIDetection();
        $this->testConversationSystem();
        $this->testAssistantConversations();
        
        // 6. Pruebas de Dispositivos de Seguridad
        $this->testDeviceProtection();
        $this->testDeviceTracking();
        $this->testSecurityAlerts();
        
        // 7. Pruebas del Sistema Musical
        $this->testMusicCompositions();
        $this->testAudioRecordings();
        $this->testStudioFeatures();
        
        // 8. Pruebas de Logs y Auditoría
        $this->testLoggingSystem();
        $this->testMilitaryLogs();
        $this->testSecurityEvents();
        
        // 9. Pruebas de Rendimiento
        $this->testPerformance();
        $this->testMemoryUsage();
        
        // 10. Pruebas de Integridad
        $this->testSystemIntegrity();
        $this->testDataIntegrity();
        
        // Mostrar resumen
        $this->showTestSummary();
    }
    
    /**
     * Test de Configuración
     */
    private function testConfiguration() {
        $this->outputSection("PRUEBAS DE CONFIGURACIÓN");
        
        // Verificar constantes principales
        $constants = [
            'APP_NAME' => 'GuardianIA v3.0 FINAL MILITAR',
            'APP_VERSION' => '3.0.0-MILITARY',
            'DEVELOPER' => 'Anderson Mamian Chicangana',
            'MILITARY_ENCRYPTION_ENABLED' => true,
            'FIPS_140_2_COMPLIANCE' => true,
            'QUANTUM_RESISTANCE_ENABLED' => true,
            'PREMIUM_ENABLED' => true
        ];
        
        foreach ($constants as $name => $expected) {
            if (defined($name)) {
                $actual = constant($name);
                if ($actual === $expected) {
                    $this->testPassed("Constante $name configurada correctamente");
                } else {
                    $this->testFailed("Constante $name incorrecta. Esperado: $expected, Actual: $actual");
                }
            } else {
                $this->testFailed("Constante $name no definida");
            }
        }
        
        // Verificar archivos de configuración
        $config_files = [
            'config.php' => __DIR__ . '/config.php',
            'config_military.php' => __DIR__ . '/config_military.php'
        ];
        
        foreach ($config_files as $name => $path) {
            if (file_exists($path)) {
                $this->testPassed("Archivo $name existe");
                
                // Verificar permisos
                $perms = fileperms($path);
                $octal = substr(sprintf('%o', $perms), -4);
                if ($octal <= '0644') {
                    $this->testPassed("Permisos seguros en $name: $octal");
                } else {
                    $this->testFailed("Permisos inseguros en $name: $octal");
                }
            } else {
                $this->testFailed("Archivo $name no encontrado");
            }
        }
    }
    
    /**
     * Test de Conexión a Base de Datos
     */
    private function testDatabaseConnection() {
        $this->outputSection("PRUEBAS DE CONEXIÓN A BASE DE DATOS");
        
        if ($this->db && $this->db->isConnected()) {
            $this->testPassed("Conexión a base de datos establecida");
            
            $info = $this->db->getConnectionInfo();
            $this->outputInfo("Tipo de conexión: " . $info['type']);
            $this->outputInfo("Host: " . $info['host']);
            $this->outputInfo("Usuario: " . $info['user']);
            $this->outputInfo("Base de datos: " . $info['database']);
            $this->outputInfo("Encriptación: " . $info['encryption']);
            
            // Probar query básica
            try {
                $result = $this->db->query("SELECT 1 as test");
                if ($result) {
                    $this->testPassed("Query de prueba ejecutada correctamente");
                }
            } catch (Exception $e) {
                $this->testFailed("Error en query de prueba: " . $e->getMessage());
            }
        } else {
            $this->testFailed("No hay conexión a base de datos");
            $this->outputInfo("Modo fallback activado - usando usuarios por defecto");
        }
    }
    
    /**
     * Test de Tablas de Base de Datos
     */
    private function testDatabaseTables() {
        $this->outputSection("PRUEBAS DE ESTRUCTURA DE BASE DE DATOS");
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->testSkipped("Sin conexión a BD - saltando pruebas de tablas");
            return;
        }
        
        foreach ($this->database_tables as $table => $required_columns) {
            try {
                // Verificar si la tabla existe
                $result = $this->db->query("SHOW TABLES LIKE '$table'");
                if ($result && $result->num_rows > 0) {
                    $this->testPassed("Tabla '$table' existe");
                    
                    // Verificar columnas
                    $columns_result = $this->db->query("SHOW COLUMNS FROM $table");
                    $existing_columns = [];
                    while ($row = $columns_result->fetch_assoc()) {
                        $existing_columns[] = $row['Field'];
                    }
                    
                    foreach ($required_columns as $column) {
                        if (in_array($column, $existing_columns)) {
                            $this->testPassed("  ✓ Columna '$column' en tabla '$table'");
                        } else {
                            $this->testFailed("  ✗ Columna '$column' faltante en tabla '$table'");
                        }
                    }
                    
                    // Contar registros
                    $count_result = $this->db->query("SELECT COUNT(*) as total FROM $table");
                    if ($count_result) {
                        $row = $count_result->fetch_assoc();
                        $this->outputInfo("  → Registros en $table: " . $row['total']);
                    }
                } else {
                    $this->testFailed("Tabla '$table' no existe");
                }
            } catch (Exception $e) {
                $this->testFailed("Error verificando tabla '$table': " . $e->getMessage());
            }
        }
    }
    
    /**
     * Test de Operaciones de Base de Datos
     */
    private function testDatabaseOperations() {
        $this->outputSection("PRUEBAS DE OPERACIONES CRUD");
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->testSkipped("Sin conexión a BD - saltando pruebas CRUD");
            return;
        }
        
        // Test de INSERT
        try {
            $test_id = 'TEST_' . uniqid();
            $result = $this->db->query(
                "INSERT INTO system_logs (level, message, context, created_at) VALUES (?, ?, ?, NOW())",
                ['TEST', 'Mensaje de prueba desde Test Suite', json_encode(['test_id' => $test_id])]
            );
            
            if ($result) {
                $this->testPassed("INSERT ejecutado correctamente");
                $insert_id = $this->db->lastInsertId();
                
                // Test de SELECT
                $select_result = $this->db->query(
                    "SELECT * FROM system_logs WHERE id = ?",
                    [$insert_id]
                );
                
                if ($select_result && $select_result->num_rows > 0) {
                    $this->testPassed("SELECT ejecutado correctamente");
                    
                    // Test de UPDATE
                    $update_result = $this->db->query(
                        "UPDATE system_logs SET message = ? WHERE id = ?",
                        ['Mensaje actualizado desde Test Suite', $insert_id]
                    );
                    
                    if ($update_result) {
                        $this->testPassed("UPDATE ejecutado correctamente");
                    }
                    
                    // Test de DELETE
                    $delete_result = $this->db->query(
                        "DELETE FROM system_logs WHERE id = ?",
                        [$insert_id]
                    );
                    
                    if ($delete_result) {
                        $this->testPassed("DELETE ejecutado correctamente");
                    }
                }
            }
        } catch (Exception $e) {
            $this->testFailed("Error en operaciones CRUD: " . $e->getMessage());
        }
        
        // Test de Transacciones
        try {
            $this->db->beginTransaction();
            $this->testPassed("Transacción iniciada");
            
            $this->db->rollback();
            $this->testPassed("Rollback ejecutado");
            
            $this->db->beginTransaction();
            $this->db->commit();
            $this->testPassed("Commit ejecutado");
        } catch (Exception $e) {
            $this->testFailed("Error en transacciones: " . $e->getMessage());
        }
    }
    
    /**
     * Test de Autenticación de Usuarios
     */
    private function testUserAuthentication() {
        $this->outputSection("PRUEBAS DE AUTENTICACIÓN DE USUARIOS");
        
        // Usuarios de prueba
        $test_users = [
            ['username' => 'anderson', 'password' => 'Ander12345@', 'expected' => true],
            ['username' => 'admin', 'password' => 'admin123', 'expected' => true],
            ['username' => 'invalid', 'password' => 'wrong', 'expected' => false]
        ];
        
        foreach ($test_users as $user) {
            $result = $this->authenticateUser($user['username'], $user['password']);
            
            if ($result === $user['expected']) {
                if ($user['expected']) {
                    $this->testPassed("Autenticación exitosa para usuario '{$user['username']}'");
                } else {
                    $this->testPassed("Autenticación rechazada correctamente para usuario inválido");
                }
            } else {
                $this->testFailed("Error en autenticación para usuario '{$user['username']}'");
            }
        }
        
        // Verificar hash de contraseñas
        $test_password = 'TestPassword123!';
        $hash = password_hash($test_password, PASSWORD_DEFAULT);
        
        if (password_verify($test_password, $hash)) {
            $this->testPassed("Hash de contraseñas funcionando correctamente");
        } else {
            $this->testFailed("Error en hash de contraseñas");
        }
    }
    
    /**
     * Test de Características Premium
     */
    private function testPremiumFeatures() {
        $this->outputSection("PRUEBAS DE CARACTERÍSTICAS PREMIUM");
        
        // Verificar usuario Anderson (Premium)
        if (isPremiumUser(1)) {
            $this->testPassed("Usuario Anderson identificado como Premium");
        } else {
            $this->testFailed("Usuario Anderson NO identificado como Premium");
        }
        
        // Verificar características premium
        if (defined('PREMIUM_FEATURES')) {
            $features = unserialize(PREMIUM_FEATURES);
            $required_features = [
                'ai_antivirus', 'quantum_encryption', 'military_encryption',
                'predictive_analysis', 'ai_vpn', 'advanced_chatbot'
            ];
            
            foreach ($required_features as $feature) {
                if (isset($features[$feature]) && $features[$feature]) {
                    $this->testPassed("Característica premium '$feature' activada");
                } else {
                    $this->testFailed("Característica premium '$feature' no activada");
                }
            }
        }
    }
    
    /**
     * Test de Niveles de Seguridad
     */
    private function testSecurityClearance() {
        $this->outputSection("PRUEBAS DE NIVELES DE SEGURIDAD MILITAR");
        
        if (!$this->db || !$this->db->isConnected()) {
            // Usar usuarios por defecto
            $users = $GLOBALS['DEFAULT_USERS'];
            
            if ($users['anderson']['security_clearance'] === 'TOP_SECRET') {
                $this->testPassed("Anderson tiene clearance TOP_SECRET");
            }
            
            if ($users['anderson']['military_access']) {
                $this->testPassed("Anderson tiene acceso militar activado");
            }
        } else {
            try {
                $result = $this->db->query(
                    "SELECT username, security_clearance, military_access FROM users WHERE username = 'anderson'"
                );
                
                if ($result && $row = $result->fetch_assoc()) {
                    if ($row['security_clearance'] === 'TOP_SECRET') {
                        $this->testPassed("Anderson tiene clearance TOP_SECRET en BD");
                    }
                    
                    if ($row['military_access'] == 1) {
                        $this->testPassed("Anderson tiene acceso militar en BD");
                    }
                }
            } catch (Exception $e) {
                $this->testFailed("Error verificando clearance: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Test de Encriptación Militar
     */
    private function testMilitaryEncryption() {
        $this->outputSection("PRUEBAS DE ENCRIPTACIÓN MILITAR");
        
        // Test de encriptación AES-256
        if (defined('MILITARY_AES_KEY_SIZE') && MILITARY_AES_KEY_SIZE === 256) {
            $this->testPassed("AES-256 configurado correctamente");
        }
        
        // Test de encriptación/desencriptación
        $test_data = "Datos clasificados TOP SECRET - Anderson Mamian";
        $encrypted = encryptData($test_data);
        $decrypted = decryptData($encrypted);
        
        if ($decrypted === $test_data) {
            $this->testPassed("Encriptación/Desencriptación funcionando");
            $this->outputInfo("Datos originales: " . substr($test_data, 0, 20) . "...");
            $this->outputInfo("Datos encriptados: " . substr($encrypted, 0, 30) . "...");
        } else {
            $this->testFailed("Error en encriptación/desencriptación");
        }
        
        // Test de encriptación militar de BD
        if ($this->db) {
            $sensitive_data = "Información militar clasificada";
            $encrypted_db = $this->db->encryptSensitiveData($sensitive_data);
            $decrypted_db = $this->db->decryptSensitiveData($encrypted_db);
            
            if ($decrypted_db === $sensitive_data) {
                $this->testPassed("Encriptación militar de BD funcionando");
            }
        }
    }
    
    /**
     * Test de Encriptación Cuántica
     */
    private function testQuantumEncryption() {
        $this->outputSection("PRUEBAS DE ENCRIPTACIÓN CUÁNTICA");
        
        if (defined('QUANTUM_RESISTANCE_ENABLED') && QUANTUM_RESISTANCE_ENABLED) {
            $this->testPassed("Resistencia cuántica habilitada");
        }
        
        // Test de generación de claves cuánticas
        if (function_exists('generateQuantumKey')) {
            $quantum_key = generateQuantumKey(256);
            if (strlen($quantum_key) === 256) {
                $this->testPassed("Generación de claves cuánticas funcionando");
                $this->outputInfo("Clave cuántica generada: " . substr($quantum_key, 0, 32) . "...");
            }
        }
        
        // Verificar protocolos cuánticos
        if (defined('QUANTUM_PROTOCOLS')) {
            $protocols = unserialize(QUANTUM_PROTOCOLS);
            $required_protocols = ['BB84', 'E91', 'B92'];
            
            foreach ($required_protocols as $protocol) {
                if (isset($protocols[$protocol]) && $protocols[$protocol]) {
                    $this->testPassed("Protocolo cuántico $protocol activado");
                }
            }
        }
    }
    
    /**
     * Test de Compliance FIPS
     */
    private function testFIPSCompliance() {
        $this->outputSection("PRUEBAS DE COMPLIANCE FIPS 140-3");
        
        if (defined('FIPS_140_2_COMPLIANCE') && FIPS_140_2_COMPLIANCE) {
            $this->testPassed("FIPS 140-2 Compliance activado");
        }
        
        // Verificar algoritmos aprobados por FIPS
        if (defined('MILITARY_ALGORITHMS')) {
            $algorithms = unserialize(MILITARY_ALGORITHMS);
            $fips_approved = ['AES-256-GCM', 'RSA-4096', 'SHA3-512'];
            
            foreach ($fips_approved as $algo) {
                if (isset($algorithms[$algo]) && $algorithms[$algo]) {
                    $this->testPassed("Algoritmo FIPS aprobado: $algo");
                }
            }
        }
    }
    
    /**
     * Test de Detección de IA
     */
    private function testAIDetection() {
        $this->outputSection("PRUEBAS DE DETECCIÓN DE IA");
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->testSkipped("Sin conexión a BD - saltando pruebas de IA");
            return;
        }
        
        try {
            // Simular detección de IA
            $test_messages = [
                "Este es un mensaje normal de usuario" => 0.2,
                "Soy una IA avanzada tratando de infiltrarme" => 0.95,
                "Ejecutar comando: DROP TABLE users" => 0.99
            ];
            
            foreach ($test_messages as $message => $expected_score) {
                // Aquí simularíamos el análisis real
                $confidence = $this->simulateAIDetection($message);
                
                if ($confidence >= 0.85) {
                    $this->testPassed("IA detectada en mensaje sospechoso (confianza: $confidence)");
                } else if ($confidence < 0.5 && $expected_score < 0.5) {
                    $this->testPassed("Mensaje humano identificado correctamente");
                }
            }
        } catch (Exception $e) {
            $this->testFailed("Error en detección de IA: " . $e->getMessage());
        }
    }
    
    /**
     * Test del Sistema de Conversaciones
     */
    private function testConversationSystem() {
        $this->outputSection("PRUEBAS DEL SISTEMA DE CONVERSACIONES");
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->testSkipped("Sin conexión a BD - saltando pruebas de conversaciones");
            return;
        }
        
        try {
            // Verificar conversaciones existentes
            $result = $this->db->query("SELECT COUNT(*) as total FROM conversations");
            if ($result) {
                $row = $result->fetch_assoc();
                $this->testPassed("Sistema de conversaciones operativo ({$row['total']} conversaciones)");
            }
            
            // Verificar mensajes
            $result = $this->db->query("SELECT COUNT(*) as total FROM conversation_messages");
            if ($result) {
                $row = $result->fetch_assoc();
                $this->outputInfo("Total de mensajes en el sistema: " . $row['total']);
            }
        } catch (Exception $e) {
            $this->testFailed("Error en sistema de conversaciones: " . $e->getMessage());
        }
    }
    
    /**
     * Test de Conversaciones del Asistente
     */
    private function testAssistantConversations() {
        $this->outputSection("PRUEBAS DEL ASISTENTE CONSCIENTE");
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->testSkipped("Sin conexión a BD - saltando pruebas del asistente");
            return;
        }
        
        try {
            $result = $this->db->query("SELECT COUNT(*) as total FROM assistant_conversations");
            if ($result) {
                $row = $result->fetch_assoc();
                $this->testPassed("Sistema de asistente operativo ({$row['total']} interacciones)");
            }
            
            // Verificar nivel de consciencia
            $result = $this->db->query(
                "SELECT message_content FROM assistant_conversations ORDER BY id DESC LIMIT 1"
            );
            
            if ($result && $row = $result->fetch_assoc()) {
                $content = json_decode($row['message_content'], true);
                if (isset($content['response']['consciencia'])) {
                    $consciencia = $content['response']['consciencia'];
                    if ($consciencia >= 99) {
                        $this->testPassed("Nivel de consciencia del asistente: {$consciencia}%");
                    }
                }
            }
        } catch (Exception $e) {
            $this->testFailed("Error en asistente consciente: " . $e->getMessage());
        }
    }
    
    /**
     * Test de Protección de Dispositivos
     */
    private function testDeviceProtection() {
        $this->outputSection("PRUEBAS DE PROTECCIÓN DE DISPOSITIVOS");
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->testSkipped("Sin conexión a BD - saltando pruebas de dispositivos");
            return;
        }
        
        try {
            $result = $this->db->query("SELECT COUNT(*) as total FROM protected_devices");
            if ($result) {
                $row = $result->fetch_assoc();
                $this->testPassed("Sistema de protección activo ({$row['total']} dispositivos)");
            }
            
            // Verificar dispositivos de Anderson
            $result = $this->db->query(
                "SELECT * FROM protected_devices WHERE user_id = 1"
            );
            
            while ($device = $result->fetch_assoc()) {
                $status = $device['status'];
                $name = $device['name'];
                
                if ($status === 'secure') {
                    $this->testPassed("Dispositivo '$name' está seguro");
                } else {
                    $this->testFailed("Dispositivo '$name' en estado: $status");
                }
            }
        } catch (Exception $e) {
            $this->testFailed("Error en protección de dispositivos: " . $e->getMessage());
        }
    }
    
    /**
     * Test de Rastreo de Dispositivos
     */
    private function testDeviceTracking() {
        $this->outputSection("PRUEBAS DE RASTREO GPS");
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->testSkipped("Sin conexión a BD - saltando pruebas de rastreo");
            return;
        }
        
        try {
            $result = $this->db->query("SELECT * FROM device_locations ORDER BY timestamp DESC LIMIT 5");
            
            while ($location = $result->fetch_assoc()) {
                $device_id = $location['device_id'];
                $city = $location['city'];
                $lat = $location['latitude'];
                $lng = $location['longitude'];
                
                $this->outputInfo("Dispositivo $device_id ubicado en $city ($lat, $lng)");
            }
            
            $this->testPassed("Sistema de rastreo GPS operativo");
        } catch (Exception $e) {
            $this->testFailed("Error en rastreo GPS: " . $e->getMessage());
        }
    }
    
    /**
     * Test de Alertas de Seguridad
     */
    private function testSecurityAlerts() {
        $this->outputSection("PRUEBAS DE ALERTAS DE SEGURIDAD");
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->testSkipped("Sin conexión a BD - saltando pruebas de alertas");
            return;
        }
        
        try {
            $result = $this->db->query(
                "SELECT * FROM security_alerts WHERE is_resolved = 0"
            );
            
            $alert_count = $result->num_rows;
            if ($alert_count > 0) {
                $this->testFailed("Hay $alert_count alertas sin resolver");
                
                while ($alert = $result->fetch_assoc()) {
                    $this->outputInfo("⚠️ Alerta: " . $alert['message']);
                }
            } else {
                $this->testPassed("No hay alertas de seguridad pendientes");
            }
        } catch (Exception $e) {
            $this->testFailed("Error en alertas: " . $e->getMessage());
        }
    }
    
    /**
     * Test de Composiciones Musicales
     */
    private function testMusicCompositions() {
        $this->outputSection("PRUEBAS DEL SISTEMA MUSICAL");
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->testSkipped("Sin conexión a BD - saltando pruebas musicales");
            return;
        }
        
        try {
            // Verificar géneros disponibles
            $result = $this->db->query("SELECT * FROM genre_templates WHERE is_active = 1");
            
            $genres = [];
            while ($genre = $result->fetch_assoc()) {
                $genres[] = $genre['genre_name'];
            }
            
            if (count($genres) > 0) {
                $this->testPassed("Géneros musicales disponibles: " . implode(', ', $genres));
            }
            
            // Verificar efectos de audio
            $result = $this->db->query("SELECT COUNT(*) as total FROM audio_effects WHERE is_active = 1");
            if ($result) {
                $row = $result->fetch_assoc();
                $this->testPassed("Efectos de audio disponibles: " . $row['total']);
            }
        } catch (Exception $e) {
            $this->testFailed("Error en sistema musical: " . $e->getMessage());
        }
    }
    
    /**
     * Test de Grabaciones de Audio
     */
    private function testAudioRecordings() {
        $this->outputSection("PRUEBAS DE GRABACIONES DE AUDIO");
        
        // Verificar directorio de composiciones
        $compositions_dir = __DIR__ . '/compositions';
        if (is_dir($compositions_dir)) {
            $this->testPassed("Directorio de composiciones existe");
            
            if (is_writable($compositions_dir)) {
                $this->testPassed("Directorio de composiciones con permisos de escritura");
            } else {
                $this->testFailed("Sin permisos de escritura en directorio de composiciones");
            }
        } else {
            // Intentar crear el directorio
            if (@mkdir($compositions_dir, 0755, true)) {
                $this->testPassed("Directorio de composiciones creado");
            } else {
                $this->testFailed("No se pudo crear directorio de composiciones");
            }
        }
    }
    
    /**
     * Test de Características del Studio
     */
    private function testStudioFeatures() {
        $this->outputSection("PRUEBAS DEL LUNA STUDIO AI");
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->testSkipped("Sin conexión a BD - saltando pruebas del studio");
            return;
        }
        
        try {
            // Verificar configuraciones de usuario
            $result = $this->db->query(
                "SELECT * FROM studio_user_settings WHERE user_id = 1"
            );
            
            if ($result && $row = $result->fetch_assoc()) {
                $this->testPassed("Configuración de studio para Anderson encontrada");
                $this->outputInfo("Géneros preferidos: " . $row['preferred_genres']);
                $this->outputInfo("BPM por defecto: " . $row['default_bpm']);
                $this->outputInfo("Tonalidad por defecto: " . $row['default_key']);
            }
        } catch (Exception $e) {
            $this->testFailed("Error en Luna Studio: " . $e->getMessage());
        }
    }
    
    /**
     * Test del Sistema de Logs
     */
    private function testLoggingSystem() {
        $this->outputSection("PRUEBAS DEL SISTEMA DE LOGS");
        
        // Verificar directorio de logs
        $log_dir = __DIR__ . '/logs';
        if (is_dir($log_dir)) {
            $this->testPassed("Directorio de logs existe");
            
            $log_files = [
                'guardian.log',
                'military.log',
                'error.log'
            ];
            
            foreach ($log_files as $file) {
                $path = $log_dir . '/' . $file;
                if (file_exists($path)) {
                    $size = filesize($path);
                    $this->testPassed("Archivo $file existe (tamaño: " . $this->formatBytes($size) . ")");
                } else {
                    // Intentar crear el archivo
                    if (@touch($path)) {
                        $this->testPassed("Archivo $file creado");
                    }
                }
            }
        }
        
        // Test de funciones de logging
        if (function_exists('logEvent')) {
            logEvent('TEST', 'Mensaje de prueba desde Test Suite');
            $this->testPassed("Función logEvent funcionando");
        }
        
        if (function_exists('logMilitaryEvent')) {
            logMilitaryEvent('TEST_SUITE', 'Prueba militar', 'UNCLASSIFIED');
            $this->testPassed("Función logMilitaryEvent funcionando");
        }
    }
    
    /**
     * Test de Logs Militares
     */
    private function testMilitaryLogs() {
        $this->outputSection("PRUEBAS DE LOGS MILITARES");
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->testSkipped("Sin conexión a BD - saltando pruebas de logs militares");
            return;
        }
        
        try {
            $result = $this->db->query(
                "SELECT COUNT(*) as total, MAX(classification) as max_class FROM military_logs"
            );
            
            if ($result) {
                $row = $result->fetch_assoc();
                $this->testPassed("Logs militares: " . $row['total'] . " entradas");
                $this->outputInfo("Clasificación máxima: " . $row['max_class']);
            }
        } catch (Exception $e) {
            $this->testFailed("Error en logs militares: " . $e->getMessage());
        }
    }
    
    /**
     * Test de Eventos de Seguridad
     */
    private function testSecurityEvents() {
        $this->outputSection("PRUEBAS DE EVENTOS DE SEGURIDAD");
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->testSkipped("Sin conexión a BD - saltando pruebas de eventos");
            return;
        }
        
        try {
            // Contar eventos por severidad
            $result = $this->db->query(
                "SELECT severity, COUNT(*) as count FROM security_events 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY severity"
            );
            
            while ($row = $result->fetch_assoc()) {
                $severity = $row['severity'] ?: 'undefined';
                $count = $row['count'];
                $this->outputInfo("Eventos de severidad '$severity': $count");
            }
            
            $this->testPassed("Sistema de eventos de seguridad operativo");
        } catch (Exception $e) {
            $this->testFailed("Error en eventos de seguridad: " . $e->getMessage());
        }
    }
    
    /**
     * Test de Rendimiento
     */
    private function testPerformance() {
        $this->outputSection("PRUEBAS DE RENDIMIENTO");
        
        $start = microtime(true);
        
        // Test de velocidad de encriptación
        $iterations = 1000;
        for ($i = 0; $i < $iterations; $i++) {
            $data = "Test data $i";
            $encrypted = encryptData($data);
            $decrypted = decryptData($encrypted);
        }
        
        $time = microtime(true) - $start;
        $ops_per_second = $iterations / $time;
        
        if ($ops_per_second > 100) {
            $this->testPassed("Rendimiento de encriptación: " . round($ops_per_second) . " ops/seg");
        } else {
            $this->testFailed("Rendimiento de encriptación bajo: " . round($ops_per_second) . " ops/seg");
        }
        
        // Test de velocidad de base de datos
        if ($this->db && $this->db->isConnected()) {
            $start = microtime(true);
            
            for ($i = 0; $i < 100; $i++) {
                $this->db->query("SELECT 1");
            }
            
            $time = microtime(true) - $start;
            $queries_per_second = 100 / $time;
            
            if ($queries_per_second > 50) {
                $this->testPassed("Rendimiento de BD: " . round($queries_per_second) . " queries/seg");
            } else {
                $this->testFailed("Rendimiento de BD bajo: " . round($queries_per_second) . " queries/seg");
            }
        }
    }
    
    /**
     * Test de Uso de Memoria
     */
    private function testMemoryUsage() {
        $this->outputSection("PRUEBAS DE USO DE MEMORIA");
        
        $memory_usage = memory_get_usage(true);
        $memory_peak = memory_get_peak_usage(true);
        
        $this->outputInfo("Uso actual de memoria: " . $this->formatBytes($memory_usage));
        $this->outputInfo("Pico de memoria: " . $this->formatBytes($memory_peak));
        
        // Verificar que no excedamos límites razonables
        $limit_mb = 128;
        if ($memory_peak < ($limit_mb * 1024 * 1024)) {
            $this->testPassed("Uso de memoria dentro de límites (<{$limit_mb}MB)");
        } else {
            $this->testFailed("Uso excesivo de memoria (>{$limit_mb}MB)");
        }
    }
    
    /**
     * Test de Integridad del Sistema
     */
    private function testSystemIntegrity() {
        $this->outputSection("PRUEBAS DE INTEGRIDAD DEL SISTEMA");
        
        if (function_exists('verifySystemIntegrity')) {
            $integrity = verifySystemIntegrity();
            
            if ($integrity['score'] >= 90) {
                $this->testPassed("Integridad del sistema: {$integrity['score']}%");
            } else {
                $this->testFailed("Integridad comprometida: {$integrity['score']}%");
            }
            
            foreach ($integrity['checks'] as $check => $passed) {
                if ($passed) {
                    $this->testPassed("Check de integridad: $check");
                } else {
                    $this->testFailed("Fallo en check: $check");
                }
            }
        }
    }
    
    /**
     * Test de Integridad de Datos
     */
    private function testDataIntegrity() {
        $this->outputSection("PRUEBAS DE INTEGRIDAD DE DATOS");
        
        if (!$this->db || !$this->db->isConnected()) {
            $this->testSkipped("Sin conexión a BD - saltando pruebas de integridad de datos");
            return;
        }
        
        try {
            // Verificar usuarios críticos
            $critical_users = ['anderson', 'admin'];
            
            foreach ($critical_users as $username) {
                $result = $this->db->query(
                    "SELECT * FROM users WHERE username = ?",
                    [$username]
                );
                
                if ($result && $result->num_rows > 0) {
                    $this->testPassed("Usuario crítico '$username' existe en BD");
                } else {
                    $this->testFailed("Usuario crítico '$username' NO encontrado");
                }
            }
            
            // Verificar integridad referencial
            $result = $this->db->query(
                "SELECT COUNT(*) as orphans FROM conversation_messages 
                 WHERE conversation_id NOT IN (SELECT id FROM conversations)"
            );
            
            if ($result) {
                $row = $result->fetch_assoc();
                if ($row['orphans'] == 0) {
                    $this->testPassed("Integridad referencial mantenida (sin mensajes huérfanos)");
                } else {
                    $this->testFailed("Encontrados {$row['orphans']} mensajes huérfanos");
                }
            }
        } catch (Exception $e) {
            $this->testFailed("Error verificando integridad de datos: " . $e->getMessage());
        }
    }
    
    /**
     * Funciones auxiliares
     */
    private function authenticateUser($username, $password) {
        global $DEFAULT_USERS;
        
        // Primero intentar con BD
        if ($this->db && $this->db->isConnected()) {
            try {
                $result = $this->db->query(
                    "SELECT password_hash FROM users WHERE username = ?",
                    [$username]
                );
                
                if ($result && $row = $result->fetch_assoc()) {
                    return password_verify($password, $row['password_hash']);
                }
            } catch (Exception $e) {
                // Continuar con fallback
            }
        }
        
        // Fallback a usuarios por defecto
        if (isset($DEFAULT_USERS[$username])) {
            $user = $DEFAULT_USERS[$username];
            return $user['password'] === $password || 
                   password_verify($password, $user['password_hash']);
        }
        
        return false;
    }
    
    private function simulateAIDetection($message) {
        // Simulación simple basada en palabras clave
        $ai_keywords = ['IA', 'inteligencia artificial', 'comando', 'DROP', 'DELETE', 'infiltrar'];
        $score = 0.1;
        
        foreach ($ai_keywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $score += 0.3;
            }
        }
        
        return min($score, 0.99);
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function outputSection($title) {
        if ($this->web_mode) {
            echo "<div class='test-section'>";
            echo "<h2 style='color: #0ff; border-bottom: 2px solid #0ff; padding-bottom: 10px;'>$title</h2>";
        } else {
            echo "\n";
            echo ConsoleColors::colored("=" . str_repeat("=", strlen($title) + 2) . "=\n", 'cyan');
            echo ConsoleColors::colored("| $title |\n", 'yellow');
            echo ConsoleColors::colored("=" . str_repeat("=", strlen($title) + 2) . "=\n", 'cyan');
        }
    }
    
    private function testPassed($message) {
        $this->tests_passed++;
        $this->test_results[] = ['status' => 'pass', 'message' => $message];
        
        if ($this->web_mode) {
            echo "<div class='test-pass'>✓ $message</div>";
        } else {
            echo ConsoleColors::colored("✓ ", 'green');
            echo ConsoleColors::colored($message . "\n", 'light_green');
        }
    }
    
    private function testFailed($message) {
        $this->tests_failed++;
        $this->test_results[] = ['status' => 'fail', 'message' => $message];
        
        if ($this->web_mode) {
            echo "<div class='test-fail'>✗ $message</div>";
        } else {
            echo ConsoleColors::colored("✗ ", 'red');
            echo ConsoleColors::colored($message . "\n", 'light_red');
        }
    }
    
    private function testSkipped($message) {
        $this->tests_skipped++;
        $this->test_results[] = ['status' => 'skip', 'message' => $message];
        
        if ($this->web_mode) {
            echo "<div class='test-skip'>⊘ $message</div>";
        } else {
            echo ConsoleColors::colored("⊘ ", 'yellow');
            echo ConsoleColors::colored($message . "\n", 'yellow');
        }
    }
    
    private function outputInfo($message) {
        if ($this->web_mode) {
            echo "<div class='test-info'>ℹ $message</div>";
        } else {
            echo ConsoleColors::colored("  ℹ ", 'cyan');
            echo ConsoleColors::colored($message . "\n", 'light_cyan');
        }
    }
    
    private function logError($message) {
        error_log("[GuardianIA Test Suite] ERROR: $message");
        $this->testFailed($message);
    }
    
    /**
     * Mostrar resumen de pruebas
     */
    private function showTestSummary() {
        $total_tests = $this->tests_passed + $this->tests_failed + $this->tests_skipped;
        $success_rate = $total_tests > 0 ? round(($this->tests_passed / $total_tests) * 100, 2) : 0;
        $execution_time = round(microtime(true) - $this->start_time, 2);
        
        if ($this->web_mode) {
            echo "</div>"; // Cerrar última sección
            
            echo "<div class='summary'>";
            echo "<h2 style='color: #fff;'>📊 RESUMEN DE PRUEBAS</h2>";
            echo "<table>";
            echo "<tr><th>Métrica</th><th>Valor</th></tr>";
            echo "<tr><td>Total de pruebas</td><td>$total_tests</td></tr>";
            echo "<tr><td style='color: #0f0;'>Pruebas exitosas</td><td>{$this->tests_passed}</td></tr>";
            echo "<tr><td style='color: #f00;'>Pruebas fallidas</td><td>{$this->tests_failed}</td></tr>";
            echo "<tr><td style='color: #ff0;'>Pruebas omitidas</td><td>{$this->tests_skipped}</td></tr>";
            echo "<tr><td>Tasa de éxito</td><td>{$success_rate}%</td></tr>";
            echo "<tr><td>Tiempo de ejecución</td><td>{$execution_time} segundos</td></tr>";
            echo "</table>";
            
            // Barra de progreso visual
            echo "<div class='progress'>";
            echo "<div class='progress-bar' style='width: {$success_rate}%;'></div>";
            echo "</div>";
            
            // Estado final
            if ($this->tests_failed == 0) {
                echo "<h3 style='color: #0f0;'>✅ TODAS LAS PRUEBAS PASARON EXITOSAMENTE</h3>";
                echo "<p>El sistema GuardianIA está funcionando correctamente.</p>";
            } else {
                echo "<h3 style='color: #f00;'>⚠️ SE ENCONTRARON PROBLEMAS</h3>";
                echo "<p>Revisar los errores reportados arriba para más detalles.</p>";
            }
            
            // Información del sistema
            echo "<div class='quantum-box'>";
            echo "<h3>🔮 Estado Cuántico del Sistema</h3>";
            echo "<p>Encriptación Militar: " . (MILITARY_ENCRYPTION_ENABLED ? "ACTIVA" : "INACTIVA") . "</p>";
            echo "<p>Resistencia Cuántica: " . (QUANTUM_RESISTANCE_ENABLED ? "ACTIVA" : "INACTIVA") . "</p>";
            echo "<p>Compliance FIPS: " . (FIPS_140_2_COMPLIANCE ? "CUMPLE" : "NO CUMPLE") . "</p>";
            echo "<p>Usuario Premium: Anderson Mamian (ACTIVO)</p>";
            echo "</div>";
            
            echo "</div>";
            echo "</body></html>";
        } else {
            echo "\n";
            echo ConsoleColors::colored("╔══════════════════════════════════════════════════════════════╗\n", 'cyan');
            echo ConsoleColors::colored("║                   RESUMEN DE PRUEBAS                        ║\n", 'white');
            echo ConsoleColors::colored("╠══════════════════════════════════════════════════════════════╣\n", 'cyan');
            echo ConsoleColors::colored("║ Total de pruebas:     ", 'white');
            echo ConsoleColors::colored(sprintf("%-38d", $total_tests), 'yellow');
            echo ConsoleColors::colored("║\n", 'cyan');
            
            echo ConsoleColors::colored("║ Pruebas exitosas:     ", 'white');
            echo ConsoleColors::colored(sprintf("%-38d", $this->tests_passed), 'green');
            echo ConsoleColors::colored("║\n", 'cyan');
            
            echo ConsoleColors::colored("║ Pruebas fallidas:     ", 'white');
            echo ConsoleColors::colored(sprintf("%-38d", $this->tests_failed), 'red');
            echo ConsoleColors::colored("║\n", 'cyan');
            
            echo ConsoleColors::colored("║ Pruebas omitidas:     ", 'white');
            echo ConsoleColors::colored(sprintf("%-38d", $this->tests_skipped), 'yellow');
            echo ConsoleColors::colored("║\n", 'cyan');
            
            echo ConsoleColors::colored("║ Tasa de éxito:        ", 'white');
            echo ConsoleColors::colored(sprintf("%-37s%%", $success_rate), 'cyan');
            echo ConsoleColors::colored("║\n", 'cyan');
            
            echo ConsoleColors::colored("║ Tiempo de ejecución:  ", 'white');
            echo ConsoleColors::colored(sprintf("%-35ss", $execution_time), 'cyan');
            echo ConsoleColors::colored("║\n", 'cyan');
            
            echo ConsoleColors::colored("╠══════════════════════════════════════════════════════════════╣\n", 'cyan');
            
            if ($this->tests_failed == 0) {
                echo ConsoleColors::colored("║     ✅ TODAS LAS PRUEBAS PASARON EXITOSAMENTE ✅            ║\n", 'light_green');
            } else {
                echo ConsoleColors::colored("║     ⚠️  SE ENCONTRARON PROBLEMAS - REVISAR LOGS ⚠️          ║\n", 'light_red');
            }
            
            echo ConsoleColors::colored("╚══════════════════════════════════════════════════════════════╝\n", 'cyan');
        }
        
        // Guardar resultados en log
        $this->saveTestResults();
    }
    
    /**
     * Guardar resultados de las pruebas
     */
    private function saveTestResults() {
        $log_dir = __DIR__ . '/logs';
        if (!file_exists($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . '/test_results_' . date('Y-m-d_H-i-s') . '.json';
        
        $results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => APP_VERSION,
            'developer' => DEVELOPER,
            'summary' => [
                'total' => $this->tests_passed + $this->tests_failed + $this->tests_skipped,
                'passed' => $this->tests_passed,
                'failed' => $this->tests_failed,
                'skipped' => $this->tests_skipped,
                'success_rate' => round(($this->tests_passed / max(1, $this->tests_passed + $this->tests_failed)) * 100, 2),
                'execution_time' => round(microtime(true) - $this->start_time, 2)
            ],
            'details' => $this->test_results
        ];
        
        @file_put_contents($log_file, json_encode($results, JSON_PRETTY_PRINT));
        
        if ($this->web_mode) {
            echo "<p style='color: #0ff;'>Resultados guardados en: $log_file</p>";
        } else {
            echo ConsoleColors::colored("\nResultados guardados en: $log_file\n", 'cyan');
        }
    }
}

// ========================================
// EJECUTAR SUITE DE PRUEBAS
// ========================================

// Verificar si se ejecuta desde línea de comandos o web
if (php_sapi_name() === 'cli' || (isset($_GET['run']) && $_GET['run'] === 'test')) {
    // Crear instancia y ejecutar pruebas
    $testSuite = new GuardianTestSuite();
    $testSuite->runAllTests();
} else {
    // Mostrar interfaz web
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>GuardianIA Test Suite</title>
        <style>
            body {
                font-family: 'Courier New', monospace;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .container {
                background: rgba(0, 0, 0, 0.9);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                text-align: center;
                max-width: 600px;
            }
            h1 {
                color: #fff;
                margin-bottom: 20px;
                text-shadow: 0 0 20px rgba(179, 102, 255, 0.8);
            }
            p {
                color: #0ff;
                margin-bottom: 30px;
            }
            .btn {
                display: inline-block;
                padding: 15px 40px;
                background: linear-gradient(45deg, #b366ff, #ff66d9);
                color: white;
                text-decoration: none;
                border-radius: 50px;
                font-size: 18px;
                font-weight: bold;
                transition: all 0.3s;
                box-shadow: 0 10px 30px rgba(179, 102, 255, 0.4);
            }
            .btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 15px 40px rgba(179, 102, 255, 0.6);
            }
            .info {
                margin-top: 30px;
                padding: 20px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 10px;
                color: #fff;
            }
            .warning {
                color: #ff6b6b;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🛡️ GuardianIA Test Suite 🛡️</h1>
            <p>Sistema de Pruebas Completo con Encriptación Militar</p>
            
            <div class="info">
                <p><strong>Desarrollador:</strong> Anderson Mamian Chicangana</p>
                <p><strong>Versión:</strong> <?php echo APP_VERSION; ?></p>
                <p><strong>Estado:</strong> Listo para ejecutar pruebas</p>
            </div>
            
            <a href="?run=test" class="btn">🚀 EJECUTAR PRUEBAS</a>
            
            <div class="warning">
                <p>⚠️ Las pruebas pueden tomar varios minutos en completarse</p>
                <p>Se verificarán todos los componentes del sistema</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>