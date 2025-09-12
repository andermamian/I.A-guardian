<?php
/**
 * GuardianIA v3.0 FINAL - Configuracion Militar
 * Anderson Mamian Chicangana - Configuracion de Seguridad Militar
 */

// Incluir configuracion base si no esta incluida
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/config.php';
}

// ========================================
// CONFIGURACION MILITAR EXTENDIDA
// ========================================

// Niveles de clasificacion militar
if (!defined('CLASSIFICATION_LEVELS')) {
    define('CLASSIFICATION_LEVELS', serialize([
        'UNCLASSIFIED' => 0,
        'CONFIDENTIAL' => 1,
        'SECRET' => 2,
        'TOP_SECRET' => 3,
        'TOP_SECRET_SCI' => 4
    ]));
}

// Configuracion de encriptacion cuantica
if (!defined('QUANTUM_KEY_LENGTH')) {
    define('QUANTUM_KEY_LENGTH', 2048);
    define('QUANTUM_ENTANGLEMENT_PAIRS', 1024);
    define('QUANTUM_ERROR_THRESHOLD', 0.11);
    define('QUANTUM_CHANNEL_FIDELITY', 0.95);
}

// Algoritmos de encriptacion militar avanzados
if (!defined('MILITARY_ALGORITHMS')) {
    define('MILITARY_ALGORITHMS', serialize([
        'AES-256-GCM' => true,
        'ChaCha20-Poly1305' => true,
        'RSA-4096' => true,
        'ECDSA-P521' => true,
        'SHA3-512' => true,
        'BLAKE3' => true,
        'Argon2id' => true
    ]));
}

// Configuracion de protocolos cuanticos
if (!defined('QUANTUM_PROTOCOLS')) {
    define('QUANTUM_PROTOCOLS', serialize([
        'BB84' => true,
        'E91' => true,
        'B92' => true,
        'SARG04' => true,
        'MDI-QKD' => true
    ]));
}

// Configuracion de seguridad NSA Suite B
if (!defined('NSA_SUITE_B_COMPLIANCE')) {
    define('NSA_SUITE_B_COMPLIANCE', true);
    define('NSA_TYPE_1_ENCRYPTION', true);
    define('TEMPEST_SHIELDING', true);
}

// Configuracion de red militar
if (!defined('MILITARY_NETWORK')) {
    define('MILITARY_NETWORK', serialize([
        'siprnet_enabled' => false,
        'niprnet_enabled' => true,
        'jwics_enabled' => false,
        'secure_tunneling' => true,
        'quantum_vpn' => true
    ]));
}

// Configuracion de auditoria militar
if (!defined('MILITARY_AUDIT')) {
    define('MILITARY_AUDIT', serialize([
        'full_logging' => true,
        'tamper_detection' => true,
        'integrity_monitoring' => true,
        'forensic_mode' => true,
        'retention_days' => 365 * 7
    ]));
}

// Configuracion de cumplimiento
if (!defined('COMPLIANCE_STANDARDS')) {
    define('COMPLIANCE_STANDARDS', serialize([
        'FIPS_140_3' => true,
        'COMMON_CRITERIA_EAL7' => true,
        'NATO_RESTRICTED' => true,
        'ITAR_COMPLIANT' => true,
        'GDPR_COMPLIANT' => true,
        'HIPAA_COMPLIANT' => true
    ]));
}

// Configuracion de cifrado post-cuantico
if (!defined('POST_QUANTUM_ALGORITHMS')) {
    define('POST_QUANTUM_ALGORITHMS', serialize([
        'CRYSTALS-Kyber' => true,
        'CRYSTALS-Dilithium' => true,
        'FALCON' => true,
        'SPHINCS+' => true
    ]));
}

// Configuracion de red neuronal para deteccion de amenazas
if (!defined('NEURAL_NETWORK_CONFIG')) {
    define('NEURAL_NETWORK_CONFIG', serialize([
        'layers' => 7,
        'neurons_per_layer' => 128,
        'activation_function' => 'relu',
        'output_activation' => 'sigmoid',
        'learning_rate' => 0.001,
        'dropout_rate' => 0.2,
        'batch_size' => 32
    ]));
}

/**
 * Funcion mejorada de log militar (no sobrescribir si ya existe)
 */
if (!function_exists('logMilitaryEvent')) {
    function logMilitaryEvent($event_type, $description, $classification = 'UNCLASSIFIED') {
        $timestamp = date('Y-m-d H:i:s.u');
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'SYSTEM';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        
        $log_entry = [
            'timestamp' => $timestamp,
            'classification' => $classification,
            'event_type' => $event_type,
            'user_id' => $user_id,
            'ip_address' => $ip,
            'description' => $description,
            'system' => 'GuardianIA_MILITARY',
            'version' => defined('APP_VERSION') ? APP_VERSION : '3.0.0-MILITARY'
        ];
        
        $log_dir = __DIR__ . '/logs/military';
        if (!file_exists($log_dir)) {
            @mkdir($log_dir, 0700, true);
        }
        
        $log_file = $log_dir . '/military.log';
        $log_line = json_encode($log_entry) . PHP_EOL;
        @file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Funcion para generar claves cuanticas
 */
if (!function_exists('generateQuantumKey')) {
    function generateQuantumKey($length = 256) {
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            if (function_exists('random_bytes')) {
                $entropy = random_bytes(1);
                $bit = ord($entropy) & 1;
            } else {
                $bit = mt_rand(0, 1);
            }
            $key .= $bit;
        }
        return $key;
    }
}

// Log de inicializacion del sistema militar
logMilitaryEvent('MILITARY_CONFIG_LOADED', 'Configuracion militar cargada exitosamente', 'UNCLASSIFIED');

?>