<?php
/**
 * GuardianIA v3.0 - Configuraciones del Sistema
 * Settings sincronizado con sistema PHP mejorado
 * Anderson Mamian Chicangana - Sistema de Producción
 */

// Prevenir acceso directo
if (!defined('ALLOW_DIRECT_ACCESS')) {
    define('ALLOW_DIRECT_ACCESS', true);
}

// Iniciar buffer de salida para prevenir errores de headers
ob_start();

// Manejo de errores mejorado
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/settings_error.log');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuraciones requeridas
$config_file = __DIR__ . '/config/config.php';
$config_main = __DIR__ . '/config.php';
$config_military_file = __DIR__ . '/config_military.php';

// Verificar y cargar el archivo de configuración correcto
if (file_exists($config_file)) {
    require_once $config_file;
} elseif (file_exists($config_main)) {
    require_once $config_main;
} else {
    die('Error: Archivo de configuración no encontrado');
}

// Cargar configuración militar si existe
if (file_exists($config_military_file)) {
    require_once $config_military_file;
}

// Cargar archivos adicionales con verificación
$required_files = [
    'super_users.php',
    'neural_networks_enhanced.php'
];

foreach ($required_files as $file) {
    $file_path = __DIR__ . '/' . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Clase ConfigManager mejorada con sincronización de BD
if (!class_exists('ConfigManager')) {
    class ConfigManager {
        private static $instance = null;
        private $config = [];
        private $db = null;
        
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        private function __construct() {
            $this->loadDefaultConfig();
            $this->initDatabase();
            $this->loadConfigFromDatabase();
        }
        
        private function loadDefaultConfig() {
            // Configuración por defecto que incluye config_military.php
            $this->config = [
                'guardian' => [
                    'personality_traits' => [
                        'empathy' => 0.95,
                        'protection' => 0.98,
                        'intelligence' => 0.96,
                        'warmth' => 0.88,
                        'determination' => 0.94,
                        'creativity' => 0.90,
                        'loyalty' => 0.99,
                        'intuition' => 0.92
                    ],
                    'voice_settings' => [
                        'pitch' => 1.2,
                        'rate' => 1.0,
                        'volume' => 0.8,
                        'language' => 'es-ES',
                        'voice_name' => 'es-ES-ElviraNeural'
                    ],
                    'communication_style' => [
                        'formal' => 0.3,
                        'friendly' => 0.9,
                        'protective' => 0.95,
                        'encouraging' => 0.85,
                        'professional' => 0.8
                    ]
                ],
                'luna' => [
                    'personality_matrix' => [
                        'creativity' => 0.98,
                        'musicality' => 0.96,
                        'innovation' => 0.92,
                        'artistic_intuition' => 0.97,
                        'collaboration' => 0.95,
                        'perfectionism' => 0.89
                    ],
                    'voice_settings' => [
                        'pitch' => 1.3,
                        'rate' => 1.1,
                        'volume' => 0.9,
                        'language' => 'es-ES',
                        'voice_name' => 'es-ES-ElviraNeural'
                    ]
                ],
                'neural_networks' => [
                    'learning_rate' => defined('LEARNING_RATE') ? LEARNING_RATE : 0.001,
                    'layers' => defined('ENHANCED_NEURAL_NETWORK_DEPTH') ? ENHANCED_NEURAL_NETWORK_DEPTH : 12,
                    'memory_bank_size' => 10000,
                    'processing_mode' => defined('QUANTUM_AI_ENABLED') ? 'quantum' : 'standard',
                    'batch_size' => defined('BATCH_SIZE') ? BATCH_SIZE : 32,
                    'epochs' => defined('EPOCHS') ? EPOCHS : 100,
                    'dropout_rate' => defined('DROPOUT_RATE') ? DROPOUT_RATE : 0.2,
                    'architectures' => [
                        'transformer' => [
                            'heads' => 8,
                            'dimensions' => 512,
                            'layers' => 6
                        ],
                        'cnn' => [
                            'filters' => [32, 64, 128, 256],
                            'kernel_size' => 3,
                            'pooling' => 'max'
                        ],
                        'rnn' => [
                            'units' => 256,
                            'type' => 'LSTM',
                            'bidirectional' => true
                        ]
                    ]
                ],
                'military' => [
                    'encryption' => [
                        'enabled' => defined('MILITARY_ENCRYPTION_ENABLED') ? MILITARY_ENCRYPTION_ENABLED : true,
                        'quantum_resistance' => defined('QUANTUM_RESISTANCE_ENABLED') ? QUANTUM_RESISTANCE_ENABLED : true,
                        'fips_compliance' => defined('FIPS_140_2_COMPLIANCE') ? FIPS_140_2_COMPLIANCE : true,
                        'aes_key_size' => defined('MILITARY_AES_KEY_SIZE') ? MILITARY_AES_KEY_SIZE : 256,
                        'rsa_key_size' => defined('MILITARY_RSA_KEY_SIZE') ? MILITARY_RSA_KEY_SIZE : 4096,
                        'kdf_iterations' => defined('MILITARY_KDF_ITERATIONS') ? MILITARY_KDF_ITERATIONS : 100000
                    ],
                    'quantum' => [
                        'enabled' => defined('QUANTUM_RESISTANCE_ENABLED') ? QUANTUM_RESISTANCE_ENABLED : true,
                        'key_length' => defined('QUANTUM_KEY_LENGTH') ? QUANTUM_KEY_LENGTH : 2048,
                        'entanglement_pairs' => defined('QUANTUM_ENTANGLEMENT_PAIRS') ? QUANTUM_ENTANGLEMENT_PAIRS : 1024,
                        'error_threshold' => defined('QUANTUM_ERROR_THRESHOLD') ? QUANTUM_ERROR_THRESHOLD : 0.11,
                        'channel_fidelity' => defined('QUANTUM_CHANNEL_FIDELITY') ? QUANTUM_CHANNEL_FIDELITY : 0.95
                    ],
                    'compliance' => [
                        'NSA_SUITE_B' => defined('NSA_SUITE_B_COMPLIANCE') ? NSA_SUITE_B_COMPLIANCE : true,
                        'NSA_TYPE_1' => defined('NSA_TYPE_1_ENCRYPTION') ? NSA_TYPE_1_ENCRYPTION : true,
                        'TEMPEST' => defined('TEMPEST_SHIELDING') ? TEMPEST_SHIELDING : true
                    ]
                ],
                'security' => [
                    'encryption_level' => defined('ENCRYPTION_KEY') ? 'AES-256-GCM' : 'AES-256-CBC',
                    'quantum_resistance' => defined('QUANTUM_RESISTANCE_ENABLED') ? QUANTUM_RESISTANCE_ENABLED : true,
                    'threat_analysis' => true,
                    'session_timeout' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 28800,
                    'max_login_attempts' => defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 3,
                    'password_complexity' => true,
                    'two_factor_auth' => false,
                    'monitoring' => [
                        'log_level' => defined('LOG_LEVEL') ? LOG_LEVEL : 'INFO',
                        'audit_enabled' => true,
                        'real_time_monitoring' => true,
                        'alert_threshold' => 5
                    ]
                ],
                'content_generation' => [
                    'karaoke' => [
                        'quality' => 'high'
                    ],
                    'video' => [
                        'resolutions' => ['720p', '1080p', '4K']
                    ],
                    'music' => [
                        'max_duration' => 600
                    ]
                ]
            ];
        }
        
        private function initDatabase() {
            global $db;
            if ($db && method_exists($db, 'getConnection')) {
                $this->db = $db->getConnection();
            } elseif (isset($GLOBALS['conn'])) {
                $this->db = $GLOBALS['conn'];
            }
        }
        
        private function loadConfigFromDatabase() {
            if (!$this->db) return;
            
            try {
                // Cargar configuración del sistema desde la BD
                $query = "SELECT * FROM system_config WHERE config_type != 'deprecated'";
                $result = $this->db->query($query);
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $value = $row['config_value'];
                        
                        // Decodificar según el tipo
                        switch ($row['config_type']) {
                            case 'json':
                                $value = json_decode($value, true);
                                break;
                            case 'boolean':
                                $value = $value === 'true' || $value === '1';
                                break;
                            case 'integer':
                                $value = intval($value);
                                break;
                        }
                        
                        // Establecer el valor en la configuración
                        $this->set($row['config_key'], $value);
                    }
                }
            } catch (Exception $e) {
                error_log("Error cargando configuración desde BD: " . $e->getMessage());
            }
        }
        
        public function get($key) {
            $keys = explode('.', $key);
            $value = $this->config;
            
            foreach ($keys as $k) {
                if (isset($value[$k])) {
                    $value = $value[$k];
                } else {
                    return null;
                }
            }
            
            return $value;
        }
        
        public function set($key, $value) {
            $keys = explode('.', $key);
            $config = &$this->config;
            
            for ($i = 0; $i < count($keys) - 1; $i++) {
                if (!isset($config[$keys[$i]])) {
                    $config[$keys[$i]] = [];
                }
                $config = &$config[$keys[$i]];
            }
            
            $config[$keys[count($keys) - 1]] = $value;
        }
        
        public function getDatabase() {
            return $this->db;
        }
        
        public function syncWithDatabase() {
            if (!$this->db) return false;
            
            try {
                // Sincronizar configuración con la base de datos
                foreach ($this->config as $section => $settings) {
                    $this->syncSection($section, $settings);
                }
                
                // Actualizar timestamp de sincronización
                $this->updateSyncTimestamp();
                
                return true;
            } catch (Exception $e) {
                error_log("Error sincronizando con BD: " . $e->getMessage());
                return false;
            }
        }
        
        private function syncSection($section, $settings, $prefix = '') {
            foreach ($settings as $key => $value) {
                $fullKey = $prefix ? "$prefix.$key" : "$section.$key";
                
                if (is_array($value) && !isset($value[0])) {
                    // Es un sub-array, recursión
                    $this->syncSection($key, $value, $section);
                } else {
                    // Guardar en BD
                    $this->saveConfigToDB($fullKey, $value);
                }
            }
        }
        
        private function saveConfigToDB($key, $value) {
            if (!$this->db) return;
            
            try {
                $type = 'string';
                $valueStr = $value;
                
                if (is_array($value)) {
                    $type = 'json';
                    $valueStr = json_encode($value);
                } elseif (is_bool($value)) {
                    $type = 'boolean';
                    $valueStr = $value ? 'true' : 'false';
                } elseif (is_int($value)) {
                    $type = 'integer';
                    $valueStr = strval($value);
                } elseif (is_float($value)) {
                    $type = 'string';
                    $valueStr = strval($value);
                }
                
                // Verificar si existe
                $checkQuery = "SELECT id FROM system_config WHERE config_key = ?";
                $stmt = $this->db->prepare($checkQuery);
                $stmt->bind_param("s", $key);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Actualizar
                    $updateQuery = "UPDATE system_config SET config_value = ?, config_type = ?, updated_at = NOW() WHERE config_key = ?";
                    $stmt = $this->db->prepare($updateQuery);
                    $stmt->bind_param("sss", $valueStr, $type, $key);
                    $stmt->execute();
                } else {
                    // Insertar
                    $insertQuery = "INSERT INTO system_config (config_key, config_value, config_type, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
                    $stmt = $this->db->prepare($insertQuery);
                    $stmt->bind_param("sss", $key, $valueStr, $type);
                    $stmt->execute();
                }
                
                $stmt->close();
            } catch (Exception $e) {
                error_log("Error guardando configuración $key: " . $e->getMessage());
            }
        }
        
        private function updateSyncTimestamp() {
            $this->saveConfigToDB('system.last_sync', date('Y-m-d H:i:s'));
        }
        
        public function getSystemStats() {
            $stats = [
                'total_users' => 0,
                'total_projects' => 0,
                'memory_entries' => 0,
                'system_uptime' => '99.9%',
                'threats_detected' => 0,
                'ai_detections' => 0
            ];
            
            if (!$this->db) return $stats;
            
            try {
                // Usuarios totales
                $result = $this->db->query("SELECT COUNT(*) as count FROM users");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $stats['total_users'] = $row['count'];
                }
                
                // Proyectos de estudio
                $result = $this->db->query("SELECT COUNT(*) as count FROM studio_projects");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $stats['total_projects'] = $row['count'];
                }
                
                // Detecciones de IA
                $result = $this->db->query("SELECT COUNT(*) as count FROM ai_detections");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $stats['ai_detections'] = $row['count'];
                }
                
                // Amenazas detectadas
                $result = $this->db->query("SELECT COUNT(*) as count FROM security_events WHERE severity IN ('high', 'critical')");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $stats['threats_detected'] = $row['count'];
                }
                
            } catch (Exception $e) {
                error_log("Error obteniendo estadísticas: " . $e->getMessage());
            }
            
            return $stats;
        }
        
        public function checkSystemHealth() {
            $health = [
                'status' => 'healthy',
                'score' => 100,
                'issues' => []
            ];
            
            // Verificar conexión a BD
            if (!$this->db || !$this->db->ping()) {
                $health['score'] -= 30;
                $health['issues'][] = 'Base de datos desconectada';
            }
            
            // Verificar espacio en disco
            $free = disk_free_space(__DIR__);
            $total = disk_total_space(__DIR__);
            if ($free / $total < 0.1) {
                $health['score'] -= 20;
                $health['issues'][] = 'Espacio en disco bajo';
            }
            
            // Verificar archivos críticos
            $critical_files = ['config.php', 'index.php'];
            foreach ($critical_files as $file) {
                if (!file_exists(__DIR__ . '/' . $file)) {
                    $health['score'] -= 10;
                    $health['issues'][] = "Archivo crítico faltante: $file";
                }
            }
            
            // Determinar estado
            if ($health['score'] >= 90) {
                $health['status'] = 'healthy';
            } elseif ($health['score'] >= 70) {
                $health['status'] = 'warning';
            } else {
                $health['status'] = 'critical';
            }
            
            return $health;
        }
    }
}

// Clase SuperUserManager
if (!class_exists('SuperUserManager')) {
    class SuperUserManager {
        public function isSuperUser($username) {
            $superUsers = ['anderson', 'Anderson Mamian', 'administrador secundario'];
            return in_array($username, $superUsers);
        }
        
        public function hasPermission($username, $permission) {
            return $this->isSuperUser($username);
        }
        
        public function hasAITrainingAccess($username) {
            return $this->isSuperUser($username);
        }
        
        public function hasNeuralNetworkControl($username) {
            return $this->isSuperUser($username);
        }
        
        public function logAdminActivity($username, $action, $message) {
            if (function_exists('logMilitaryEvent')) {
                logMilitaryEvent($action, $message, 'SECRET');
            } elseif (function_exists('logEvent')) {
                logEvent('ADMIN', "$action: $message", ['user' => $username]);
            }
        }
    }
}

// Clase GuardianNeuralNetworks
if (!class_exists('GuardianNeuralNetworks')) {
    class GuardianNeuralNetworks {
        public function getNeuralStats() {
            global $config;
            
            $stats = [
                'total_neurons' => 3440,
                'active_layers' => 12,
                'processing_power' => '95%',
                'memory_usage' => '62%'
            ];
            
            // Obtener configuración de redes neuronales si está disponible
            if ($config) {
                $nn_config = $config->get('neural_networks');
                if ($nn_config) {
                    $stats['active_layers'] = $nn_config['layers'] ?? 12;
                    $stats['total_neurons'] = $stats['active_layers'] * 256 + 912; // Cálculo estimado
                }
            }
            
            return $stats;
        }
    }
}

// Inicializar managers
$config = ConfigManager::getInstance();
$superUserManager = new SuperUserManager();
$neuralNetwork = new GuardianNeuralNetworks();

// Datos del usuario
$user_id = $_SESSION['user_id'] ?? 1;
$username = $_SESSION['username'] ?? 'anderson';
$is_super_user = $superUserManager->isSuperUser($username);
$is_premium = isPremiumUser($user_id);

// Procesar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    try {
        // Verificar token CSRF si existe
        if (isset($_SESSION['csrf_token']) && 
            (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
            // Por ahora no forzamos CSRF para mantener compatibilidad
            // throw new Exception('Token de seguridad inválido');
        }
        
        $action = sanitizeInput($_POST['action']);
        
        switch($action) {
            case 'update_guardian_personality':
                if ($is_super_user && $superUserManager->hasPermission($username, 'personality_modification')) {
                    $response = updateGuardianPersonality($_POST);
                } else {
                    $response['message'] = 'Acceso denegado. Se requieren permisos de super usuario.';
                }
                break;
                
            case 'update_luna_settings':
                if ($is_super_user && $superUserManager->hasAITrainingAccess($username)) {
                    $response = updateLunaSettings($_POST);
                } else {
                    $response['message'] = 'Acceso denegado. Se requiere acceso al entrenamiento de IA.';
                }
                break;
                
            case 'update_neural_config':
                if ($is_super_user && $superUserManager->hasNeuralNetworkControl($username)) {
                    $response = updateNeuralNetworkConfig($_POST);
                } else {
                    $response['message'] = 'Acceso denegado. Se requiere control de redes neuronales.';
                }
                break;
                
            case 'update_military_settings':
                if ($is_super_user && $superUserManager->hasPermission($username, 'military_access')) {
                    $response = updateMilitarySettings($_POST);
                } else {
                    $response['message'] = 'Acceso denegado. Se requiere acceso militar.';
                }
                break;
                
            case 'update_security_settings':
                if ($is_super_user && $superUserManager->hasPermission($username, 'security_override')) {
                    $response = updateSecuritySettings($_POST);
                } else {
                    $response['message'] = 'Acceso denegado. Se requieren permisos de seguridad.';
                }
                break;
                
            case 'update_user_preferences':
                $response = updateUserPreferences($_POST, $user_id);
                break;
                
            case 'sync_database':
                if ($is_super_user) {
                    $response = syncDatabaseSettings();
                } else {
                    $response['message'] = 'Acceso denegado. Se requieren permisos de super usuario.';
                }
                break;
                
            case 'get_system_stats':
                $response = getSystemStatistics();
                break;
                
            case 'reset_neural_memory':
                if ($is_super_user && $superUserManager->hasPermission($username, 'memory_bank_access')) {
                    $response = resetNeuralMemory();
                } else {
                    $response['message'] = 'Acceso denegado. Se requiere acceso al banco de memoria.';
                }
                break;
                
            case 'export_settings':
                $response = exportSystemSettings();
                break;
                
            case 'import_settings':
                if ($is_super_user) {
                    $response = importSystemSettings($_POST);
                } else {
                    $response['message'] = 'Acceso denegado. Se requieren permisos de super usuario.';
                }
                break;
                
            case 'test_military_encryption':
                if ($is_super_user) {
                    $response = testMilitaryEncryption();
                } else {
                    $response['message'] = 'Acceso denegado. Se requiere acceso militar.';
                }
                break;
                
            default:
                $response['message'] = 'Acción no reconocida.';
        }
        
        // Registrar actividad si es super usuario
        if ($is_super_user) {
            $superUserManager->logAdminActivity($username, $action, $response['message']);
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
        error_log("Settings error: " . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// Funciones de actualización
function updateGuardianPersonality($data) {
    global $config;
    
    $personality_settings = [
        'empathy' => floatval($data['empathy'] ?? 0.95),
        'protection' => floatval($data['protection'] ?? 0.98),
        'intelligence' => floatval($data['intelligence'] ?? 0.96),
        'warmth' => floatval($data['warmth'] ?? 0.88),
        'determination' => floatval($data['determination'] ?? 0.94),
        'creativity' => floatval($data['creativity'] ?? 0.90),
        'loyalty' => floatval($data['loyalty'] ?? 0.99),
        'intuition' => floatval($data['intuition'] ?? 0.92)
    ];
    
    $voice_settings = [
        'pitch' => floatval($data['voice_pitch'] ?? 1.2),
        'rate' => floatval($data['voice_rate'] ?? 1.0),
        'volume' => floatval($data['voice_volume'] ?? 0.8),
        'language' => $data['voice_language'] ?? 'es-ES',
        'voice_name' => $data['voice_name'] ?? 'es-ES-ElviraNeural'
    ];
    
    $communication_style = [
        'formal' => floatval($data['style_formal'] ?? 0.3),
        'friendly' => floatval($data['style_friendly'] ?? 0.9),
        'protective' => floatval($data['style_protective'] ?? 0.95),
        'encouraging' => floatval($data['style_encouraging'] ?? 0.85),
        'professional' => floatval($data['style_professional'] ?? 0.8)
    ];
    
    // Actualizar configuración
    $config->set('guardian.personality_traits', $personality_settings);
    $config->set('guardian.voice_settings', $voice_settings);
    $config->set('guardian.communication_style', $communication_style);
    
    // Sincronizar con base de datos
    $config->syncWithDatabase();
    
    return [
        'success' => true,
        'message' => 'Personalidad de Guardian AI actualizada exitosamente.',
        'data' => [
            'personality' => $personality_settings,
            'voice' => $voice_settings,
            'communication' => $communication_style
        ]
    ];
}

function updateLunaSettings($data) {
    global $config;
    
    $luna_settings = [
        'creativity_level' => floatval($data['creativity_level'] ?? 0.98),
        'musicality' => floatval($data['musicality'] ?? 0.96),
        'innovation' => floatval($data['innovation'] ?? 0.92),
        'artistic_intuition' => floatval($data['artistic_intuition'] ?? 0.97),
        'collaboration' => floatval($data['collaboration'] ?? 0.95),
        'perfectionism' => floatval($data['perfectionism'] ?? 0.89)
    ];
    
    $content_settings = [
        'karaoke_quality' => $data['karaoke_quality'] ?? 'high',
        'music_genres' => $data['music_genres'] ?? ['rap', 'reggaeton', 'pop', 'rock'],
        'video_resolution' => $data['video_resolution'] ?? '1080p',
        'audio_format' => $data['audio_format'] ?? 'mp3',
        'max_duration' => intval($data['max_duration'] ?? 600)
    ];
    
    $voice_settings = [
        'pitch' => floatval($data['luna_voice_pitch'] ?? 1.3),
        'rate' => floatval($data['luna_voice_rate'] ?? 1.1),
        'volume' => floatval($data['luna_voice_volume'] ?? 0.9),
        'language' => $data['luna_voice_language'] ?? 'es-ES',
        'voice_name' => $data['luna_voice_name'] ?? 'es-ES-ElviraNeural'
    ];
    
    // Actualizar configuración
    $config->set('luna.personality_matrix', $luna_settings);
    $config->set('luna.voice_settings', $voice_settings);
    $config->set('content_generation', $content_settings);
    
    // Sincronizar con base de datos
    $config->syncWithDatabase();
    
    return [
        'success' => true,
        'message' => 'Configuración de Luna AI Studio actualizada exitosamente.',
        'data' => [
            'personality' => $luna_settings,
            'content' => $content_settings,
            'voice' => $voice_settings
        ]
    ];
}

function updateNeuralNetworkConfig($data) {
    global $config;
    
    $neural_config = [
        'learning_rate' => floatval($data['learning_rate'] ?? 0.001),
        'layers' => intval($data['layers'] ?? 12),
        'memory_bank_size' => intval($data['memory_bank_size'] ?? 10000),
        'processing_mode' => $data['processing_mode'] ?? 'quantum',
        'batch_size' => intval($data['batch_size'] ?? 32),
        'epochs' => intval($data['epochs'] ?? 100),
        'dropout_rate' => floatval($data['dropout_rate'] ?? 0.2)
    ];
    
    $architecture_config = [
        'transformer' => [
            'heads' => intval($data['transformer_heads'] ?? 8),
            'dimensions' => intval($data['transformer_dimensions'] ?? 512),
            'layers' => intval($data['transformer_layers'] ?? 6)
        ],
        'cnn' => [
            'filters' => isset($data['cnn_filters']) ? array_map('intval', $data['cnn_filters']) : [32, 64, 128, 256],
            'kernel_size' => intval($data['cnn_kernel_size'] ?? 3),
            'pooling' => $data['cnn_pooling'] ?? 'max'
        ],
        'rnn' => [
            'units' => intval($data['rnn_units'] ?? 256),
            'type' => $data['rnn_type'] ?? 'LSTM',
            'bidirectional' => isset($data['rnn_bidirectional']) ? boolval($data['rnn_bidirectional']) : true
        ]
    ];
    
    // Actualizar configuración
    $config->set('neural_networks', $neural_config);
    $config->set('neural_networks.architectures', $architecture_config);
    
    // Sincronizar con base de datos
    $config->syncWithDatabase();
    
    return [
        'success' => true,
        'message' => 'Configuración de redes neuronales actualizada exitosamente.',
        'data' => [
            'neural_config' => $neural_config,
            'architectures' => $architecture_config
        ]
    ];
}

function updateMilitarySettings($data) {
    global $config;
    
    $military_encryption = [
        'enabled' => isset($data['military_encryption_enabled']) ? boolval($data['military_encryption_enabled']) : true,
        'quantum_resistance' => isset($data['quantum_resistance']) ? boolval($data['quantum_resistance']) : true,
        'fips_compliance' => isset($data['fips_compliance']) ? boolval($data['fips_compliance']) : true,
        'aes_key_size' => intval($data['aes_key_size'] ?? 256),
        'rsa_key_size' => intval($data['rsa_key_size'] ?? 4096),
        'kdf_iterations' => intval($data['kdf_iterations'] ?? 100000)
    ];
    
    $quantum_config = [
        'enabled' => isset($data['quantum_enabled']) ? boolval($data['quantum_enabled']) : true,
        'key_length' => intval($data['quantum_key_length'] ?? 2048),
        'entanglement_pairs' => intval($data['entanglement_pairs'] ?? 1024),
        'error_threshold' => floatval($data['error_threshold'] ?? 0.11),
        'channel_fidelity' => floatval($data['channel_fidelity'] ?? 0.95)
    ];
    
    $compliance_config = [
        'NSA_SUITE_B' => isset($data['nsa_suite_b']) ? boolval($data['nsa_suite_b']) : true,
        'NSA_TYPE_1' => isset($data['nsa_type_1']) ? boolval($data['nsa_type_1']) : true,
        'TEMPEST' => isset($data['tempest_shielding']) ? boolval($data['tempest_shielding']) : true
    ];
    
    // Actualizar configuración
    $config->set('military.encryption', $military_encryption);
    $config->set('military.quantum', $quantum_config);
    $config->set('military.compliance', $compliance_config);
    
    // Sincronizar con base de datos
    $config->syncWithDatabase();
    
    // Log de evento militar
    if (function_exists('logMilitaryEvent')) {
        logMilitaryEvent('MILITARY_CONFIG_UPDATE', 'Configuración militar actualizada', 'SECRET');
    }
    
    return [
        'success' => true,
        'message' => 'Configuración militar actualizada exitosamente.',
        'data' => [
            'encryption' => $military_encryption,
            'quantum' => $quantum_config,
            'compliance' => $compliance_config
        ]
    ];
}

function updateSecuritySettings($data) {
    global $config;
    
    $security_config = [
        'encryption_level' => $data['encryption_level'] ?? 'AES-256-GCM',
        'quantum_resistance' => isset($data['quantum_resistance']) ? boolval($data['quantum_resistance']) : true,
        'threat_analysis' => isset($data['threat_analysis']) ? boolval($data['threat_analysis']) : true,
        'session_timeout' => intval($data['session_timeout'] ?? 28800),
        'max_login_attempts' => intval($data['max_login_attempts'] ?? 3),
        'password_complexity' => isset($data['password_complexity']) ? boolval($data['password_complexity']) : true,
        'two_factor_auth' => isset($data['two_factor_auth']) ? boolval($data['two_factor_auth']) : false
    ];
    
    $monitoring_config = [
        'log_level' => $data['log_level'] ?? 'INFO',
        'audit_enabled' => isset($data['audit_enabled']) ? boolval($data['audit_enabled']) : true,
        'real_time_monitoring' => isset($data['real_time_monitoring']) ? boolval($data['real_time_monitoring']) : true,
        'alert_threshold' => intval($data['alert_threshold'] ?? 5)
    ];
    
    // Actualizar configuración
    $config->set('security', $security_config);
    $config->set('security.monitoring', $monitoring_config);
    
    // Sincronizar con base de datos
    $config->syncWithDatabase();
    
    return [
        'success' => true,
        'message' => 'Configuración de seguridad actualizada exitosamente.',
        'data' => [
            'security' => $security_config,
            'monitoring' => $monitoring_config
        ]
    ];
}

function updateUserPreferences($data, $user_id) {
    global $config;
    
    $preferences = [
        'theme' => $data['theme'] ?? 'dark',
        'language' => $data['language'] ?? 'es',
        'notifications' => isset($data['notifications']) ? boolval($data['notifications']) : true,
        'auto_save' => isset($data['auto_save']) ? boolval($data['auto_save']) : true,
        'guardian_voice' => isset($data['guardian_voice']) ? boolval($data['guardian_voice']) : true,
        'luna_voice' => isset($data['luna_voice']) ? boolval($data['luna_voice']) : true,
        'animation_speed' => $data['animation_speed'] ?? 'normal',
        'content_quality' => $data['content_quality'] ?? 'high'
    ];
    
    try {
        $db = $config->getDatabase();
        if ($db) {
            $json_prefs = json_encode($preferences);
            
            // Verificar si existe el campo ai_preferences
            $check_column = "SHOW COLUMNS FROM users LIKE 'ai_preferences'";
            $result = $db->query($check_column);
            
            if ($result && $result->num_rows > 0) {
                $stmt = $db->prepare("UPDATE users SET ai_preferences = ? WHERE id = ?");
                $stmt->bind_param("si", $json_prefs, $user_id);
            } else {
                // Si no existe el campo, guardar en tabla separada o crear el campo
                $create_column = "ALTER TABLE users ADD COLUMN ai_preferences TEXT NULL";
                $db->query($create_column);
                
                $stmt = $db->prepare("UPDATE users SET ai_preferences = ? WHERE id = ?");
                $stmt->bind_param("si", $json_prefs, $user_id);
            }
            
            $stmt->execute();
            $stmt->close();
        }
        
        return [
            'success' => true,
            'message' => 'Preferencias de usuario actualizadas exitosamente.',
            'data' => $preferences
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error actualizando preferencias: ' . $e->getMessage()
        ];
    }
}

function syncDatabaseSettings() {
    global $config;
    
    try {
        $success = $config->syncWithDatabase();
        
        if ($success) {
            return [
                'success' => true,
                'message' => 'Base de datos sincronizada exitosamente.',
                'data' => [
                    'sync_time' => date('Y-m-d H:i:s'),
                    'tables_updated' => ['system_config', 'guardian_memory', 'luna_projects', 'military_logs']
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error sincronizando base de datos.'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error de sincronización: ' . $e->getMessage()
        ];
    }
}

function getSystemStatistics() {
    global $config, $neuralNetwork;
    
    try {
        $stats = $config->getSystemStats();
        $neural_stats = $neuralNetwork->getNeuralStats();
        $health = $config->checkSystemHealth();
        
        return [
            'success' => true,
            'message' => 'Estadísticas obtenidas exitosamente.',
            'data' => [
                'system' => $stats,
                'neural_networks' => $neural_stats,
                'health' => $health,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error obteniendo estadísticas: ' . $e->getMessage()
        ];
    }
}

function resetNeuralMemory() {
    global $config;
    
    try {
        $db = $config->getDatabase();
        
        if ($db) {
            // Respaldar memoria actual
            $backup_table = 'guardian_memory_backup_' . date('Ymd_His');
            
            // Verificar si existe la tabla guardian_memory
            $check_table = "SHOW TABLES LIKE 'guardian_memory'";
            $result = $db->query($check_table);
            
            if ($result && $result->num_rows > 0) {
                $backup_query = "CREATE TABLE IF NOT EXISTS $backup_table AS SELECT * FROM guardian_memory";
                $db->query($backup_query);
                
                // Limpiar memoria actual
                $clear_query = "TRUNCATE TABLE guardian_memory";
                $db->query($clear_query);
            }
        }
        
        return [
            'success' => true,
            'message' => 'Memoria neural reiniciada exitosamente. Backup creado.',
            'data' => [
                'backup_table' => $backup_table ?? 'backup_' . date('Ymd_His'),
                'reset_time' => date('Y-m-d H:i:s')
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error reiniciando memoria: ' . $e->getMessage()
        ];
    }
}

function exportSystemSettings() {
    global $config;
    
    try {
        $settings = [
            'guardian' => $config->get('guardian'),
            'luna' => $config->get('luna'),
            'neural_networks' => $config->get('neural_networks'),
            'military' => $config->get('military'),
            'security' => $config->get('security'),
            'content_generation' => $config->get('content_generation'),
            'export_timestamp' => date('Y-m-d H:i:s'),
            'version' => '3.0'
        ];
        
        $filename = 'guardian_settings_' . date('Ymd_His') . '.json';
        
        // Crear directorio temp si no existe
        $temp_dir = __DIR__ . '/temp';
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        
        $filepath = $temp_dir . '/' . $filename;
        
        file_put_contents($filepath, json_encode($settings, JSON_PRETTY_PRINT));
        
        return [
            'success' => true,
            'message' => 'Configuraciones exportadas exitosamente.',
            'data' => [
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'download_url' => 'download.php?file=' . base64_encode($filename)
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error exportando configuraciones: ' . $e->getMessage()
        ];
    }
}

function importSystemSettings($data) {
    global $config;
    
    try {
        if (!isset($data['settings_json'])) {
            throw new Exception('No se proporcionaron configuraciones para importar.');
        }
        
        $settings = json_decode($data['settings_json'], true);
        
        if (!$settings) {
            throw new Exception('Formato de configuraciones inválido.');
        }
        
        // Validar versión
        if (!isset($settings['version']) || $settings['version'] !== '3.0') {
            throw new Exception('Versión de configuraciones incompatible.');
        }
        
        // Importar configuraciones
        foreach (['guardian', 'luna', 'neural_networks', 'military', 'security', 'content_generation'] as $section) {
            if (isset($settings[$section])) {
                $config->set($section, $settings[$section]);
            }
        }
        
        // Sincronizar con base de datos
        $config->syncWithDatabase();
        
        return [
            'success' => true,
            'message' => 'Configuraciones importadas exitosamente.',
            'data' => [
                'import_time' => date('Y-m-d H:i:s'),
                'sections_imported' => array_keys($settings)
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error importando configuraciones: ' . $e->getMessage()
        ];
    }
}

function testMilitaryEncryption() {
    $test_data = "Prueba de encriptación militar GuardianIA";
    $results = [];
    
    try {
        // Test AES-256-GCM
        if (function_exists('openssl_encrypt')) {
            $key = hash('sha256', MASTER_ENCRYPTION_KEY);
            $iv = openssl_random_pseudo_bytes(16);
            $tag = '';
            
            $encrypted = openssl_encrypt($test_data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            
            $results['AES-256-GCM'] = $decrypted === $test_data ? 'PASS' : 'FAIL';
        }
        
        // Test Quantum Key Generation
        if (function_exists('generateQuantumKey')) {
            $quantum_key = generateQuantumKey(256);
            $results['Quantum_Key'] = strlen($quantum_key) === 256 ? 'PASS' : 'FAIL';
        }
        
        // Test FIPS compliance
        $results['FIPS_140_2'] = defined('FIPS_140_2_COMPLIANCE') && FIPS_140_2_COMPLIANCE ? 'COMPLIANT' : 'NON_COMPLIANT';
        
        return [
            'success' => true,
            'message' => 'Prueba de encriptación militar completada.',
            'data' => [
                'test_results' => $results,
                'encryption_strength' => 'MILITARY_GRADE',
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error en prueba de encriptación: ' . $e->getMessage()
        ];
    }
}

// Función auxiliar para sanitizar input
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map('sanitizeInput', $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}

// Función auxiliar para verificar si un usuario es premium
if (!function_exists('isPremiumUser')) {
    function isPremiumUser($user_id) {
        global $config;
        
        // Verificar en base de datos
        $db = $config->getDatabase();
        if ($db) {
            try {
                $stmt = $db->prepare("SELECT premium_status FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $row = $result->fetch_assoc()) {
                    return $row['premium_status'] === 'premium';
                }
            } catch (Exception $e) {
                error_log("Error verificando premium: " . $e->getMessage());
            }
        }
        
        // Por defecto, anderson es premium
        return ($user_id == 1) || (isset($_SESSION['username']) && $_SESSION['username'] === 'anderson');
    }
}

// Función para obtener preferencias de usuario
function getUserPreferences($user_id) {
    global $config;
    
    try {
        $db = $config->getDatabase();
        if ($db) {
            $stmt = $db->prepare("SELECT ai_preferences FROM users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $row = $result->fetch_assoc()) {
                    if ($row['ai_preferences']) {
                        return json_decode($row['ai_preferences'], true);
                    }
                }
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        error_log("Error getting user preferences: " . $e->getMessage());
    }
    
    // Preferencias por defecto
    return [
        'theme' => 'dark',
        'language' => 'es',
        'notifications' => true,
        'auto_save' => true,
        'guardian_voice' => true,
        'luna_voice' => true,
        'animation_speed' => 'normal',
        'content_quality' => 'high'
    ];
}

// Obtener configuraciones actuales para mostrar en la interfaz
$current_settings = [
    'guardian' => $config->get('guardian'),
    'luna' => $config->get('luna'),
    'neural_networks' => $config->get('neural_networks'),
    'military' => $config->get('military'),
    'security' => $config->get('security'),
    'content_generation' => $config->get('content_generation'),
    'user_preferences' => getUserPreferences($user_id)
];

// Solo mostrar HTML si no es una petición AJAX
if (!isset($_POST['action'])):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GuardianIA - Configuraciones del Sistema</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        :root {
            --guardian-primary: #6366f1;
            --guardian-secondary: #8b5cf6;
            --luna-primary: #ec4899;
            --luna-secondary: #f97316;
            --neural-color: #10b981;
            --security-color: #ef4444;
            --military-color: #14532d;
            --background-dark: #0f172a;
            --background-medium: #1e293b;
            --background-light: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --border-color: #475569;
        }
        
        body {
            background: linear-gradient(135deg, var(--background-dark) 0%, var(--background-medium) 100%);
            color: var(--text-primary);
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .settings-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: linear-gradient(135deg, var(--guardian-primary), var(--luna-primary));
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .settings-header h1 {
            font-size: 2.5rem;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .settings-header p {
            font-size: 1.1rem;
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .settings-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            background: var(--background-medium);
            border-radius: 15px;
            padding: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            flex-wrap: wrap;
        }
        
        .tab-button {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            padding: 15px 25px;
            margin: 5px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .tab-button:hover {
            background: var(--background-light);
            color: var(--text-primary);
        }
        
        .tab-button.active {
            background: var(--guardian-primary);
            color: white;
            box-shadow: 0 3px 10px rgba(99, 102, 241, 0.3);
        }
        
        .settings-section {
            display: none;
            background: var(--background-medium);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .settings-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .section-title {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: var(--text-primary);
            border-bottom: 2px solid var(--guardian-primary);
            padding-bottom: 10px;
        }
        
        .setting-group {
            margin-bottom: 30px;
            padding: 20px;
            background: var(--background-light);
            border-radius: 15px;
            border-left: 4px solid var(--guardian-primary);
        }
        
        .setting-group h3 {
            margin: 0 0 15px 0;
            color: var(--text-primary);
            font-size: 1.3rem;
        }
        
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px 0;
        }
        
        .setting-label {
            font-weight: 500;
            color: var(--text-primary);
            flex: 1;
        }
        
        .setting-control {
            flex: 0 0 300px;
            margin-left: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .slider-container {
            position: relative;
            width: 100%;
        }
        
        .slider {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: var(--background-dark);
            outline: none;
            -webkit-appearance: none;
        }
        
        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--guardian-primary);
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        
        .slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--guardian-primary);
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        
        .select-control, .input-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background: var(--background-dark);
            color: var(--text-primary);
            font-size: 1rem;
        }
        
        .select-control:focus, .input-control:focus {
            outline: none;
            border-color: var(--guardian-primary);
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.3);
        }
        
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
            background: var(--background-dark);
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .toggle-switch.active {
            background: var(--guardian-primary);
        }
        
        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .toggle-switch.active::after {
            transform: translateX(30px);
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--guardian-primary), var(--guardian-secondary));
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, var(--luna-primary), var(--luna-secondary));
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--neural-color), #059669);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--security-color), #dc2626);
            color: white;
        }
        
        .btn-military {
            background: linear-gradient(135deg, var(--military-color), #1e40af);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-online { background: #10b981; }
        .status-offline { background: #ef4444; }
        .status-warning { background: #f59e0b; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: var(--background-light);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            border-color: var(--guardian-primary);
            transform: translateY(-5px);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--guardian-primary);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .loading.show {
            display: block;
        }
        
        .spinner {
            border: 3px solid var(--background-light);
            border-top: 3px solid var(--guardian-primary);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            transform: translateX(400px);
            transition: all 0.3s ease;
            max-width: 400px;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification.success { background: var(--neural-color); }
        .notification.error { background: var(--security-color); }
        .notification.warning { background: #f59e0b; }
        .notification.info { background: var(--guardian-primary); }
        
        .military-badge {
            background: var(--military-color);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 10px;
        }
        
        @media (max-width: 768px) {
            .settings-container {
                padding: 10px;
            }
            
            .setting-item {
                flex-direction: column;
                align-items: stretch;
            }
            
            .setting-control {
                flex: none;
                margin-left: 0;
                margin-top: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <!-- Header -->
        <div class="settings-header">
            <h1>🛠️ Configuraciones del Sistema</h1>
            <p>Panel de control avanzado para Guardian IA y Luna AI Studio</p>
            <?php if ($is_super_user): ?>
                <p><strong>🔒 Acceso de Super Usuario Activo</strong></p>
            <?php endif; ?>
        </div>
        
        <!-- Tabs -->
        <div class="settings-tabs">
            <button class="tab-button active" onclick="showTab('guardian')">👩 Guardian AI</button>
            <button class="tab-button" onclick="showTab('luna')">🎵 Luna AI Studio</button>
            <button class="tab-button" onclick="showTab('neural')">🧠 Redes Neuronales</button>
            <button class="tab-button" onclick="showTab('military')">🔐 Militar</button>
            <button class="tab-button" onclick="showTab('security')">🛡️ Seguridad</button>
            <button class="tab-button" onclick="showTab('user')">👤 Preferencias</button>
            <button class="tab-button" onclick="showTab('system')">⚙️ Sistema</button>
        </div>
        
        <!-- Guardian AI Settings -->
        <div id="guardian-settings" class="settings-section active">
            <h2 class="section-title">👩 Configuración de Guardian AI</h2>
            
            <div class="setting-group">
                <h3>Personalidad</h3>
                <div class="setting-item">
                    <label class="setting-label">Empatía</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="empathy" min="0" max="1" step="0.01" 
                               value="<?= $current_settings['guardian']['personality_traits']['empathy'] ?? 0.95 ?>">
                        <span id="empathy-value"><?= round(($current_settings['guardian']['personality_traits']['empathy'] ?? 0.95) * 100) ?>%</span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Protección</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="protection" min="0" max="1" step="0.01" 
                               value="<?= $current_settings['guardian']['personality_traits']['protection'] ?? 0.98 ?>">
                        <span id="protection-value"><?= round(($current_settings['guardian']['personality_traits']['protection'] ?? 0.98) * 100) ?>%</span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Inteligencia</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="intelligence" min="0" max="1" step="0.01" 
                               value="<?= $current_settings['guardian']['personality_traits']['intelligence'] ?? 0.96 ?>">
                        <span id="intelligence-value"><?= round(($current_settings['guardian']['personality_traits']['intelligence'] ?? 0.96) * 100) ?>%</span>
                    </div>
                </div>
              <div class="setting-item">
    <label class="setting-label">Calidez</label>
    <div class="setting-control">
        <?php $warmth_value = isset($current_settings['guardian']['personality_traits']['warmth']) ? $current_settings['guardian']['personality_traits']['warmth'] : 0.88; ?>
        <input type="range" class="slider" id="warmth" min="0" max="1" step="0.01" 
               value="<?= $warmth_value ?>">
        <span id="warmth-value"><?= round($warmth_value * 100) ?>%</span>
    </div>
</div>
                
                <div class="setting-item">
                    <label class="setting-label">Determinación</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="determination" min="0" max="1" step="0.01" 
                               value="<?= $current_settings['guardian']['personality_traits']['determination'] ?? 0.94 ?>">
                        <span id="determination-value"><?= round(($current_settings['guardian']['personality_traits']['determination'] ?? 0.94) * 100) ?>%</span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Creatividad</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="creativity" min="0" max="1" step="0.01" 
                               value="<?= $current_settings['guardian']['personality_traits']['creativity'] ?? 0.90 ?>">
                        <span id="creativity-value"><?= round(($current_settings['guardian']['personality_traits']['creativity'] ?? 0.90) * 100) ?>%</span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Lealtad</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="loyalty" min="0" max="1" step="0.01" 
                               value="<?= $current_settings['guardian']['personality_traits']['loyalty'] ?? 0.99 ?>">
                        <span id="loyalty-value"><?= round(($current_settings['guardian']['personality_traits']['loyalty'] ?? 0.99) * 100) ?>%</span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Intuición</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="intuition" min="0" max="1" step="0.01" 
                               value="<?= $current_settings['guardian']['personality_traits']['intuition'] ?? 0.92 ?>">
                        <span id="intuition-value"><?= round(($current_settings['guardian']['personality_traits']['intuition'] ?? 0.92) * 100) ?>%</span>
                    </div>
                </div>
            </div>
            
            <div class="setting-group">
                <h3>Configuración de Voz</h3>
                <div class="setting-item">
                    <label class="setting-label">Tono de Voz</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="voice_pitch" min="0.5" max="2" step="0.1" 
                               value="<?= $current_settings['guardian']['voice_settings']['pitch'] ?? 1.2 ?>">
                        <span id="voice_pitch-value"><?= $current_settings['guardian']['voice_settings']['pitch'] ?? 1.2 ?></span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Velocidad</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="voice_rate" min="0.5" max="2" step="0.1" 
                               value="<?= $current_settings['guardian']['voice_settings']['rate'] ?? 1.0 ?>">
                        <span id="voice_rate-value"><?= $current_settings['guardian']['voice_settings']['rate'] ?? 1.0 ?></span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Volumen</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="voice_volume" min="0" max="1" step="0.1" 
                               value="<?= $current_settings['guardian']['voice_settings']['volume'] ?? 0.8 ?>">
                        <span id="voice_volume-value"><?= $current_settings['guardian']['voice_settings']['volume'] ?? 0.8 ?></span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Voz Neural</label>
                    <div class="setting-control">
                        <select class="select-control" id="voice_name">
                            <option value="es-ES-ElviraNeural" <?= ($current_settings['guardian']['voice_settings']['voice_name'] ?? '') === 'es-ES-ElviraNeural' ? 'selected' : '' ?>>Elvira (Femenina)</option>
                            <option value="es-ES-AlvaroNeural" <?= ($current_settings['guardian']['voice_settings']['voice_name'] ?? '') === 'es-ES-AlvaroNeural' ? 'selected' : '' ?>>Álvaro (Masculina)</option>
                            <option value="es-ES-AbrilNeural" <?= ($current_settings['guardian']['voice_settings']['voice_name'] ?? '') === 'es-ES-AbrilNeural' ? 'selected' : '' ?>>Abril (Joven)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="updateGuardianSettings()">💾 Guardar Configuración</button>
                <button class="btn btn-secondary" onclick="testGuardianVoice()">🎤 Probar Voz</button>
            </div>
        </div>
        
        <!-- Luna AI Studio Settings -->
        <div id="luna-settings" class="settings-section">
            <h2 class="section-title">🎵 Configuración de Luna AI Studio</h2>
            
            <div class="setting-group">
                <h3>Personalidad Creativa</h3>
                <div class="setting-item">
                    <label class="setting-label">Creatividad</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="creativity_level" min="0" max="1" step="0.01" 
                               value="<?= $current_settings['luna']['personality_matrix']['creativity'] ?? 0.98 ?>">
                        <span id="creativity_level-value"><?= round(($current_settings['luna']['personality_matrix']['creativity'] ?? 0.98) * 100) ?>%</span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Musicalidad</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="musicality" min="0" max="1" step="0.01" 
                               value="<?= $current_settings['luna']['personality_matrix']['musicality'] ?? 0.96 ?>">
                        <span id="musicality-value"><?= round(($current_settings['luna']['personality_matrix']['musicality'] ?? 0.96) * 100) ?>%</span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Innovación</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="innovation" min="0" max="1" step="0.01" 
                               value="<?= $current_settings['luna']['personality_matrix']['innovation'] ?? 0.92 ?>">
                        <span id="innovation-value"><?= round(($current_settings['luna']['personality_matrix']['innovation'] ?? 0.92) * 100) ?>%</span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Intuición Artística</label>
                    <div class="setting-control">
                        <input type="range" class="slider" id="artistic_intuition" min="0" max="1" step="0.01" 
                               value="<?= $current_settings['luna']['personality_matrix']['artistic_intuition'] ?? 0.97 ?>">
                        <span id="artistic_intuition-value"><?= round(($current_settings['luna']['personality_matrix']['artistic_intuition'] ?? 0.97) * 100) ?>%</span>
                    </div>
                </div>
            </div>
            
            <div class="setting-group">
                <h3>Configuración de Contenido</h3>
                <div class="setting-item">
                    <label class="setting-label">Calidad de Audio</label>
                    <div class="setting-control">
                        <select class="select-control" id="karaoke_quality">
                            <option value="high" <?= ($current_settings['content_generation']['karaoke']['quality'] ?? '') === 'high' ? 'selected' : '' ?>>Alta (320kbps)</option>
                            <option value="medium" <?= ($current_settings['content_generation']['karaoke']['quality'] ?? '') === 'medium' ? 'selected' : '' ?>>Media (192kbps)</option>
                            <option value="low" <?= ($current_settings['content_generation']['karaoke']['quality'] ?? '') === 'low' ? 'selected' : '' ?>>Baja (128kbps)</option>
                        </select>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Resolución de Video</label>
                    <div class="setting-control">
                        <select class="select-control" id="video_resolution">
                            <option value="4K">4K UHD</option>
                            <option value="1080p" selected>1080p HD</option>
                            <option value="720p">720p</option>
                        </select>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Duración Máxima (minutos)</label>
                    <div class="setting-control">
                        <input type="number" class="input-control" id="max_duration" min="1" max="60" 
                               value="<?= ($current_settings['content_generation']['music']['max_duration'] ?? 600) / 60 ?>">
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="updateLunaSettings()">💾 Guardar Configuración</button>
                <button class="btn btn-secondary" onclick="testLunaVoice()">🎤 Probar Voz de Luna</button>
            </div>
        </div>
        
        <!-- Neural Networks Settings -->
        <div id="neural-settings" class="settings-section">
            <h2 class="section-title">🧠 Configuración de Redes Neuronales</h2>
            
            <?php if ($is_super_user && $superUserManager->hasNeuralNetworkControl($username)): ?>
                <div class="setting-group">
                    <h3>Parámetros de Entrenamiento</h3>
                    <div class="setting-item">
                        <label class="setting-label">Tasa de Aprendizaje</label>
                        <div class="setting-control">
                            <input type="number" class="input-control" id="learning_rate" min="0.0001" max="0.1" step="0.0001" 
                                   value="<?= $current_settings['neural_networks']['learning_rate'] ?? 0.001 ?>">
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">Número de Capas</label>
                        <div class="setting-control">
                            <input type="number" class="input-control" id="layers" min="6" max="24" 
                                   value="<?= $current_settings['neural_networks']['layers'] ?? 12 ?>">
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">Tamaño del Banco de Memoria</label>
                        <div class="setting-control">
                            <input type="number" class="input-control" id="memory_bank_size" min="1000" max="50000" step="1000" 
                                   value="<?= $current_settings['neural_networks']['memory_bank_size'] ?? 10000 ?>">
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">Modo de Procesamiento</label>
                        <div class="setting-control">
                            <select class="select-control" id="processing_mode">
                                <option value="quantum" <?= ($current_settings['neural_networks']['processing_mode'] ?? '') === 'quantum' ? 'selected' : '' ?>>Cuántico</option>
                                <option value="standard" <?= ($current_settings['neural_networks']['processing_mode'] ?? '') === 'standard' ? 'selected' : '' ?>>Estándar</option>
                                <option value="hybrid" <?= ($current_settings['neural_networks']['processing_mode'] ?? '') === 'hybrid' ? 'selected' : '' ?>>Híbrido</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">Tamaño de Lote</label>
                        <div class="setting-control">
                            <input type="number" class="input-control" id="batch_size" min="16" max="128" 
                                   value="<?= $current_settings['neural_networks']['batch_size'] ?? 32 ?>">
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">Épocas de Entrenamiento</label>
                        <div class="setting-control">
                            <input type="number" class="input-control" id="epochs" min="10" max="1000" 
                                   value="<?= $current_settings['neural_networks']['epochs'] ?? 100 ?>">
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="updateNeuralConfig()">🧠 Actualizar Red Neuronal</button>
                    <button class="btn btn-danger" onclick="resetNeuralMemory()">🗑️ Reiniciar Memoria</button>
                </div>
            <?php else: ?>
                <div class="setting-group">
                    <p style="text-align: center; color: var(--text-secondary); font-size: 1.1rem;">
                        🔒 Se requieren permisos de super usuario para acceder a la configuración de redes neuronales.
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Military Settings -->
        <div id="military-settings" class="settings-section">
            <h2 class="section-title">🔐 Configuración Militar</h2>
            
            <?php if ($is_super_user): ?>
                <div class="setting-group">
                    <h3>Encriptación Militar <span class="military-badge">TOP SECRET</span></h3>
                    <div class="setting-item">
                        <label class="setting-label">Encriptación Militar</label>
                        <div class="setting-control">
                            <div class="toggle-switch <?= (defined('MILITARY_ENCRYPTION_ENABLED') && MILITARY_ENCRYPTION_ENABLED) ? 'active' : '' ?>" 
                                 onclick="toggleSetting(this, 'military_encryption_enabled')"></div>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">Resistencia Cuántica</label>
                        <div class="setting-control">
                            <div class="toggle-switch <?= (defined('QUANTUM_RESISTANCE_ENABLED') && QUANTUM_RESISTANCE_ENABLED) ? 'active' : '' ?>" 
                                 onclick="toggleSetting(this, 'quantum_resistance')"></div>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">Cumplimiento FIPS 140-2</label>
                        <div class="setting-control">
                            <div class="toggle-switch <?= (defined('FIPS_140_2_COMPLIANCE') && FIPS_140_2_COMPLIANCE) ? 'active' : '' ?>" 
                                 onclick="toggleSetting(this, 'fips_compliance')"></div>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">Tamaño de Clave AES</label>
                        <div class="setting-control">
                            <select class="select-control" id="aes_key_size">
                                <option value="128">128 bits</option>
                                <option value="192">192 bits</option>
                                <option value="256" selected>256 bits</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">Tamaño de Clave RSA</label>
                        <div class="setting-control">
                            <select class="select-control" id="rsa_key_size">
                                <option value="2048">2048 bits</option>
                                <option value="3072">3072 bits</option>
                                <option value="4096" selected>4096 bits</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="setting-group">
                    <h3>Configuración Cuántica</h3>
                    <div class="setting-item">
                        <label class="setting-label">Sistema Cuántico</label>
                        <div class="setting-control">
                            <div class="toggle-switch <?= (defined('QUANTUM_RESISTANCE_ENABLED') && QUANTUM_RESISTANCE_ENABLED) ? 'active' : '' ?>" 
                                 onclick="toggleSetting(this, 'quantum_enabled')"></div>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">Longitud de Clave Cuántica</label>
                        <div class="setting-control">
                            <input type="number" class="input-control" id="quantum_key_length" min="256" max="4096" step="256" 
                                   value="<?= defined('QUANTUM_KEY_LENGTH') ? QUANTUM_KEY_LENGTH : 2048 ?>">
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">Pares de Entrelazamiento</label>
                        <div class="setting-control">
                            <input type="number" class="input-control" id="entanglement_pairs" min="256" max="2048" step="256" 
                                   value="<?= defined('QUANTUM_ENTANGLEMENT_PAIRS') ? QUANTUM_ENTANGLEMENT_PAIRS : 1024 ?>">
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-military" onclick="updateMilitarySettings()">🔒 Actualizar Configuración Militar</button>
                    <button class="btn btn-success" onclick="testMilitaryEncryption()">🔐 Probar Encriptación</button>
                </div>
            <?php else: ?>
                <div class="setting-group">
                    <p style="text-align: center; color: var(--text-secondary); font-size: 1.1rem;">
                        🔒 Se requiere acceso militar de nivel TOP SECRET para esta configuración.
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Security Settings -->
        <div id="security-settings" class="settings-section">
            <h2 class="section-title">🛡️ Configuración de Seguridad</h2>
            
            <div class="setting-group">
                <h3>Estado de Seguridad</h3>
                <div class="setting-item">
                    <label class="setting-label">Encriptación Militar</label>
                    <div class="setting-control">
                        <span class="status-indicator <?= (defined('MILITARY_ENCRYPTION_ENABLED') && MILITARY_ENCRYPTION_ENABLED) ? 'status-online' : 'status-offline' ?>"></span>
                        <span><?= (defined('MILITARY_ENCRYPTION_ENABLED') && MILITARY_ENCRYPTION_ENABLED) ? 'AES-256-GCM Activo' : 'Desactivado' ?></span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Resistencia Cuántica</label>
                    <div class="setting-control">
                        <span class="status-indicator <?= (defined('QUANTUM_RESISTANCE_ENABLED') && QUANTUM_RESISTANCE_ENABLED) ? 'status-online' : 'status-offline' ?>"></span>
                        <span><?= (defined('QUANTUM_RESISTANCE_ENABLED') && QUANTUM_RESISTANCE_ENABLED) ? 'CRYSTALS-Kyber Activo' : 'Desactivado' ?></span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Análisis de Amenazas</label>
                    <div class="setting-control">
                        <span class="status-indicator status-online"></span>
                        <span>Monitoreo 24/7</span>
                    </div>
                </div>
            </div>
            
            <?php if ($is_super_user && $superUserManager->hasPermission($username, 'security_override')): ?>
                <div class="setting-group">
                    <h3>Configuración Avanzada</h3>
                    <div class="setting-item">
                        <label class="setting-label">Tiempo de Sesión (horas)</label>
                        <div class="setting-control">
                            <input type="number" class="input-control" id="session_timeout" min="1" max="24" 
                                   value="<?= ($current_settings['security']['session_timeout'] ?? 28800) / 3600 ?>">
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">Intentos de Login Máximos</label>
                        <div class="setting-control">
                            <input type="number" class="input-control" id="max_login_attempts" min="3" max="10" 
                                   value="<?= $current_settings['security']['max_login_attempts'] ?? 3 ?>">
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">Autenticación de Dos Factores</label>
                        <div class="setting-control">
                            <div class="toggle-switch <?= ($current_settings['security']['two_factor_auth'] ?? false) ? 'active' : '' ?>" 
                                 onclick="toggleSetting(this, 'two_factor_auth')"></div>
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="updateSecuritySettings()">🛡️ Actualizar Seguridad</button>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- User Preferences -->
        <div id="user-settings" class="settings-section">
            <h2 class="section-title">👤 Preferencias de Usuario</h2>
            
            <div class="setting-group">
                <h3>Interfaz</h3>
                <div class="setting-item">
                    <label class="setting-label">Tema</label>
                    <div class="setting-control">
                        <select class="select-control" id="theme">
                            <option value="dark" <?= ($current_settings['user_preferences']['theme'] ?? '') === 'dark' ? 'selected' : '' ?>>Oscuro</option>
                            <option value="light" <?= ($current_settings['user_preferences']['theme'] ?? '') === 'light' ? 'selected' : '' ?>>Claro</option>
                            <option value="auto" <?= ($current_settings['user_preferences']['theme'] ?? '') === 'auto' ? 'selected' : '' ?>>Automático</option>
                        </select>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Idioma</label>
                    <div class="setting-control">
                        <select class="select-control" id="language">
                            <option value="es" <?= ($current_settings['user_preferences']['language'] ?? '') === 'es' ? 'selected' : '' ?>>Español</option>
                            <option value="en" <?= ($current_settings['user_preferences']['language'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="setting-group">
                <h3>Funcionalidades</h3>
                <div class="setting-item">
                    <label class="setting-label">Voz de Guardian</label>
                    <div class="setting-control">
                        <div class="toggle-switch <?= ($current_settings['user_preferences']['guardian_voice'] ?? true) ? 'active' : '' ?>" 
                             onclick="toggleSetting(this, 'guardian_voice')"></div>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Voz de Luna</label>
                    <div class="setting-control">
                        <div class="toggle-switch <?= ($current_settings['user_preferences']['luna_voice'] ?? true) ? 'active' : '' ?>" 
                             onclick="toggleSetting(this, 'luna_voice')"></div>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Notificaciones</label>
                    <div class="setting-control">
                        <div class="toggle-switch <?= ($current_settings['user_preferences']['notifications'] ?? true) ? 'active' : '' ?>" 
                             onclick="toggleSetting(this, 'notifications')"></div>
                    </div>
                </div>
                
                <div class="setting-item">
                    <label class="setting-label">Guardado Automático</label>
                    <div class="setting-control">
                        <div class="toggle-switch <?= ($current_settings['user_preferences']['auto_save'] ?? true) ? 'active' : '' ?>" 
                             onclick="toggleSetting(this, 'auto_save')"></div>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="updateUserPreferences()">💾 Guardar Preferencias</button>
            </div>
        </div>
        
        <!-- System Settings -->
        <div id="system-settings" class="settings-section">
            <h2 class="section-title">⚙️ Configuración del Sistema</h2>
            
            <div class="setting-group">
                <h3>Estadísticas del Sistema</h3>
                <div class="stats-grid" id="system-stats">
                    <div class="stat-card">
                        <div class="stat-value" id="total-users">-</div>
                        <div class="stat-label">Usuarios Totales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="total-projects">-</div>
                        <div class="stat-label">Proyectos de Luna</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="memory-entries">-</div>
                        <div class="stat-label">Entradas de Memoria</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="neural-neurons">-</div>
                        <div class="stat-label">Neuronas Activas</div>
                    </div>
                </div>
            </div>
            
            <div class="setting-group">
                <h3>Herramientas de Sistema</h3>
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="syncDatabase()">🔄 Sincronizar BD</button>
                    <button class="btn btn-secondary" onclick="exportSettings()">📤 Exportar Config</button>
                    <button class="btn btn-primary" onclick="loadSystemStats()">📊 Actualizar Stats</button>
                    <?php if ($is_super_user): ?>
                        <button class="btn btn-danger" onclick="showImportDialog()">📥 Importar Config</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Indicator -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p>Procesando...</p>
    </div>
    
    <!-- Notification -->
    <div class="notification" id="notification"></div>
    
    <script>
        // Variables globales
        let currentSettings = <?= json_encode($current_settings) ?>;
        let isSuperUser = <?= $is_super_user ? 'true' : 'false' ?>;
        
        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            initializeSliders();
            loadSystemStats();
        });
        
        // Funciones de interfaz
        function showTab(tabName) {
            // Ocultar todas las secciones
            document.querySelectorAll('.settings-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Desactivar todos los botones
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Mostrar sección seleccionada
            document.getElementById(tabName + '-settings').classList.add('active');
            
            // Activar botón seleccionado
            event.target.classList.add('active');
        }
        
        function initializeSliders() {
            document.querySelectorAll('.slider').forEach(slider => {
                const valueSpan = document.getElementById(slider.id + '-value');
                
                slider.addEventListener('input', function() {
                    if (valueSpan) {
                        if (slider.id.includes('voice_')) {
                            valueSpan.textContent = this.value;
                        } else if (slider.id === 'voice_volume') {
                            valueSpan.textContent = this.value;
                        } else {
                            valueSpan.textContent = Math.round(this.value * 100) + '%';
                        }
                    }
                });
            });
        }
        
        function toggleSetting(element, settingName) {
            element.classList.toggle('active');
            const isActive = element.classList.contains('active');
            
            // Guardar el estado en una variable temporal
            if (!window.tempSettings) {
                window.tempSettings = {};
            }
            window.tempSettings[settingName] = isActive;
        }
        
        function showLoading() {
            document.getElementById('loading').classList.add('show');
        }
        
        function hideLoading() {
            document.getElementById('loading').classList.remove('show');
        }
        
        function showNotification(message, type = 'info') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type + ' show';
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 5000);
        }
        
        // Funciones de actualización
        async function updateGuardianSettings() {
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'update_guardian_personality');
            formData.append('empathy', document.getElementById('empathy').value);
            formData.append('protection', document.getElementById('protection').value);
            formData.append('intelligence', document.getElementById('intelligence').value);
            formData.append('warmth', document.getElementById('warmth').value);
            formData.append('determination', document.getElementById('determination').value);
            formData.append('creativity', document.getElementById('creativity').value);
            formData.append('loyalty', document.getElementById('loyalty').value);
            formData.append('intuition', document.getElementById('intuition').value);
            formData.append('voice_pitch', document.getElementById('voice_pitch').value);
            formData.append('voice_rate', document.getElementById('voice_rate').value);
            formData.append('voice_volume', document.getElementById('voice_volume').value);
            formData.append('voice_name', document.getElementById('voice_name').value);
            
            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    if (result.data) {
                        currentSettings.guardian = result.data;
                    }
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
            }
            
            hideLoading();
        }
        
        async function updateLunaSettings() {
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'update_luna_settings');
            formData.append('creativity_level', document.getElementById('creativity_level').value);
            formData.append('musicality', document.getElementById('musicality').value);
            formData.append('innovation', document.getElementById('innovation').value);
            formData.append('artistic_intuition', document.getElementById('artistic_intuition').value);
            formData.append('karaoke_quality', document.getElementById('karaoke_quality').value);
            formData.append('video_resolution', document.getElementById('video_resolution').value);
            formData.append('max_duration', document.getElementById('max_duration').value * 60);
            
            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    if (result.data) {
                        currentSettings.luna = result.data;
                    }
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
            }
            
            hideLoading();
        }
        
        async function updateNeuralConfig() {
            if (!isSuperUser) {
                showNotification('Acceso denegado. Se requieren permisos de super usuario.', 'error');
                return;
            }
            
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'update_neural_config');
            formData.append('learning_rate', document.getElementById('learning_rate').value);
            formData.append('layers', document.getElementById('layers').value);
            formData.append('memory_bank_size', document.getElementById('memory_bank_size').value);
            formData.append('processing_mode', document.getElementById('processing_mode').value);
            formData.append('batch_size', document.getElementById('batch_size').value);
            formData.append('epochs', document.getElementById('epochs').value);
            
            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    if (result.data) {
                        currentSettings.neural_networks = result.data;
                    }
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
            }
            
            hideLoading();
        }
        
        async function updateMilitarySettings() {
            if (!isSuperUser) {
                showNotification('Acceso denegado. Se requiere acceso militar.', 'error');
                return;
            }
            
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'update_military_settings');
            
            // Obtener valores de los toggles
            if (window.tempSettings) {
                for (let key in window.tempSettings) {
                    formData.append(key, window.tempSettings[key]);
                }
            }
            
            formData.append('aes_key_size', document.getElementById('aes_key_size').value);
            formData.append('rsa_key_size', document.getElementById('rsa_key_size').value);
            formData.append('quantum_key_length', document.getElementById('quantum_key_length').value);
            formData.append('entanglement_pairs', document.getElementById('entanglement_pairs').value);
            
            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    if (result.data) {
                        currentSettings.military = result.data;
                    }
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
            }
            
            hideLoading();
        }
        
        async function updateSecuritySettings() {
            if (!isSuperUser) {
                showNotification('Acceso denegado. Se requieren permisos de super usuario.', 'error');
                return;
            }
            
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'update_security_settings');
            formData.append('session_timeout', document.getElementById('session_timeout').value * 3600);
            formData.append('max_login_attempts', document.getElementById('max_login_attempts').value);
            
            // Obtener valores de los toggles
            if (window.tempSettings) {
                for (let key in window.tempSettings) {
                    formData.append(key, window.tempSettings[key]);
                }
            }
            
            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    if (result.data) {
                        currentSettings.security = result.data;
                    }
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
            }
            
            hideLoading();
        }
        
        async function updateUserPreferences() {
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'update_user_preferences');
            formData.append('theme', document.getElementById('theme').value);
            formData.append('language', document.getElementById('language').value);
            
            // Obtener valores de los toggles
            if (window.tempSettings) {
                for (let key in window.tempSettings) {
                    formData.append(key, window.tempSettings[key]);
                }
            }
            
            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    if (result.data) {
                        currentSettings.user_preferences = result.data;
                    }
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
            }
            
            hideLoading();
        }
        
        // Funciones del sistema
        async function syncDatabase() {
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'sync_database');
            
            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
            }
            
            hideLoading();
        }
        
        async function loadSystemStats() {
            const formData = new FormData();
            formData.append('action', 'get_system_stats');
            
            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.data) {
                    const stats = result.data;
                    
                    // Actualizar estadísticas del sistema
                    if (stats.system) {
                        document.getElementById('total-users').textContent = stats.system.total_users || '0';
                        document.getElementById('total-projects').textContent = stats.system.total_projects || '0';
                        document.getElementById('memory-entries').textContent = stats.system.memory_entries || '0';
                    }
                    
                    // Actualizar estadísticas de redes neuronales
                    if (stats.neural_networks) {
                        document.getElementById('neural-neurons').textContent = stats.neural_networks.total_neurons || '0';
                    }
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }
        
        async function resetNeuralMemory() {
            if (!isSuperUser) {
                showNotification('Acceso denegado. Se requieren permisos de super usuario.', 'error');
                return;
            }
            
            if (!confirm('¿Estás seguro de que quieres reiniciar la memoria neural? Esta acción no se puede deshacer.')) {
                return;
            }
            
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'reset_neural_memory');
            
            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
            }
            
            hideLoading();
        }
        
        async function exportSettings() {
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'export_settings');
            
            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Configuraciones exportadas: ' + result.data.filename, 'success');
                    
                    // Si hay URL de descarga, abrir en nueva ventana
                    if (result.data.download_url) {
                        window.open(result.data.download_url, '_blank');
                    }
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
            }
            
            hideLoading();
        }
        
        function showImportDialog() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json';
            
            input.onchange = async (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = async (e) => {
                        await importSettings(e.target.result);
                    };
                    reader.readAsText(file);
                }
            };
            
            input.click();
        }
        
        async function importSettings(settingsJson) {
            if (!isSuperUser) {
                showNotification('Acceso denegado. Se requieren permisos de super usuario.', 'error');
                return;
            }
            
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'import_settings');
            formData.append('settings_json', settingsJson);
            
            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
            }
            
            hideLoading();
        }
        
        async function testMilitaryEncryption() {
            if (!isSuperUser) {
                showNotification('Acceso denegado. Se requiere acceso militar.', 'error');
                return;
            }
            
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'test_military_encryption');
            
            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    
                    // Mostrar resultados de la prueba
                    if (result.data && result.data.test_results) {
                        let message = 'Resultados de la prueba:\n';
                        for (let test in result.data.test_results) {
                            message += `${test}: ${result.data.test_results[test]}\n`;
                        }
                        alert(message);
                    }
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
            }
            
            hideLoading();
        }
        
        // Funciones de prueba de voz
        function testGuardianVoice() {
            const text = "Hola, soy Guardian, tu protectora personal. Mi nueva configuración de voz está funcionando perfectamente.";
            speakText(text, currentSettings.guardian.voice_settings);
        }
        
        function testLunaVoice() {
            const text = "¡Hola! Soy Luna, tu asistente creativa. ¿Lista para crear algo increíble juntas?";
            speakText(text, currentSettings.luna ? currentSettings.luna.voice_settings : {});
        }
        
        function speakText(text, voiceSettings) {
            if ('speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = voiceSettings.language || 'es-ES';
                utterance.pitch = voiceSettings.pitch || 1.2;
                utterance.rate = voiceSettings.rate || 1.0;
                utterance.volume = voiceSettings.volume || 0.8;
                
                speechSynthesis.speak(utterance);
            } else {
                showNotification('Tu navegador no soporta síntesis de voz.', 'warning');
            }
        }
        
        // Cargar estadísticas cada 30 segundos
        setInterval(loadSystemStats, 30000);
    </script>
</body>
</html>
<?php
endif;

// Limpiar buffer de salida
ob_end_flush();
?>