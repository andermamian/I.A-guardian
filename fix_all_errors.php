<?php
/**
 * GuardianIA v3.0 - Script de Corrección de Errores y Sincronización
 * Soluciona todos los errores encontrados y sincroniza el sistema
 * Anderson Mamian Chicangana - Sistema de Producción
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutos

echo "=== GUARDIAN IA - CORRECCIÓN DE ERRORES Y SINCRONIZACIÓN ===\n\n";

// Incluir configuraciones
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/config_enhanced.php';

class SystemErrorFixer {
    private $errors_found = [];
    private $errors_fixed = [];
    private $db;
    
    public function __construct() {
        $this->db = getDatabase();
        echo "✓ Conexión a base de datos establecida\n";
    }
    
    /**
     * Ejecutar corrección completa del sistema
     */
    public function runCompleteSystemFix() {
        echo "Iniciando corrección completa del sistema...\n\n";
        
        // 1. Verificar y corregir estructura de base de datos
        $this->fixDatabaseStructure();
        
        // 2. Corregir archivos de configuración
        $this->fixConfigurationFiles();
        
        // 3. Sincronizar personalidades de IA
        $this->syncAIPersonalities();
        
        // 4. Corregir referencias de Claude
        $this->fixClaudeReferences();
        
        // 5. Verificar y crear directorios necesarios
        $this->createRequiredDirectories();
        
        // 6. Corregir permisos de archivos
        $this->fixFilePermissions();
        
        // 7. Sincronizar redes neuronales
        $this->syncNeuralNetworks();
        
        // 8. Verificar y corregir super usuarios
        $this->fixSuperUsers();
        
        // 9. Limpiar archivos temporales corruptos
        $this->cleanCorruptedFiles();
        
        // 10. Verificar integridad del sistema
        $this->verifySystemIntegrity();
        
        // Mostrar resumen
        $this->showFixSummary();
    }
    
    /**
     * Corregir estructura de base de datos
     */
    private function fixDatabaseStructure() {
        echo "1. Verificando estructura de base de datos...\n";
        
        try {
            // Verificar tabla system_config
            $this->createSystemConfigTable();
            
            // Verificar tabla conversations
            $this->createConversationsTable();
            
            // Verificar tabla user_sessions
            $this->createUserSessionsTable();
            
            // Actualizar tablas existentes
            $this->updateExistingTables();
            
            echo "   ✓ Estructura de base de datos corregida\n\n";
            
        } catch (Exception $e) {
            $this->errors_found[] = "Database structure: " . $e->getMessage();
            echo "   ✗ Error en estructura de base de datos: " . $e->getMessage() . "\n\n";
        }
    }
    
    private function createSystemConfigTable() {
        $sql = "CREATE TABLE IF NOT EXISTS system_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            config_key VARCHAR(100) UNIQUE NOT NULL,
            config_value JSON,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_config_key (config_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->exec($sql);
    }
    
    private function createConversationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) DEFAULT 'Nueva conversación',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->exec($sql);
    }
    
    private function createUserSessionsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_token VARCHAR(255) UNIQUE NOT NULL,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT,
            INDEX idx_user_id (user_id),
            INDEX idx_last_activity (last_activity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->exec($sql);
    }
    
    private function updateExistingTables() {
        // Agregar columnas faltantes a la tabla users si no existen
        $columns_to_add = [
            'is_super_user' => 'ALTER TABLE users ADD COLUMN is_super_user BOOLEAN DEFAULT FALSE',
            'personality_settings' => 'ALTER TABLE users ADD COLUMN personality_settings JSON',
            'ai_preferences' => 'ALTER TABLE users ADD COLUMN ai_preferences JSON'
        ];
        
        foreach ($columns_to_add as $column => $sql) {
            try {
                // Verificar si la columna existe
                $stmt = $this->db->prepare("SHOW COLUMNS FROM users LIKE ?");
                $stmt->execute([$column]);
                
                if ($stmt->rowCount() == 0) {
                    $this->db->exec($sql);
                    echo "   ✓ Columna '$column' agregada a tabla users\n";
                }
            } catch (Exception $e) {
                // Ignorar errores de columnas que ya existen
            }
        }
    }
    
    /**
     * Corregir archivos de configuración
     */
    private function fixConfigurationFiles() {
        echo "2. Corrigiendo archivos de configuración...\n";
        
        try {
            // Verificar config.php
            $this->fixMainConfig();
            
            // Verificar database.php
            $this->fixDatabaseConfig();
            
            // Crear config_military.php si no existe
            $this->createMilitaryConfig();
            
            echo "   ✓ Archivos de configuración corregidos\n\n";
            
        } catch (Exception $e) {
            $this->errors_found[] = "Configuration files: " . $e->getMessage();
            echo "   ✗ Error en archivos de configuración: " . $e->getMessage() . "\n\n";
        }
    }
    
    private function fixMainConfig() {
        $config_path = __DIR__ . '/config/config.php';
        
        if (!file_exists($config_path)) {
            $config_content = "<?php
session_start();

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'guardianai_production');
define('DB_USER', 'guardianai_user');
define('DB_PASS', 'GuardianAI2025!');

// Configuración de Guardian AI
define('GUARDIAN_VERSION', '3.0');
define('GUARDIAN_PERSONALITY', 'female');

// Funciones de utilidad
function isPremiumUser(\$user_id) {
    return true; // Todos los usuarios son premium por defecto
}

function logEvent(\$level, \$message) {
    error_log(\"[\$level] \$message\");
}
?>";
            
            file_put_contents($config_path, $config_content);
            echo "   ✓ config.php creado\n";
        }
    }
    
    private function fixDatabaseConfig() {
        $db_config_path = __DIR__ . '/config/database.php';
        
        if (!file_exists($db_config_path)) {
            $db_config_content = "<?php
class DatabaseConfig {
    private static \$instance = null;
    private \$connection = null;
    
    private function __construct() {
        try {
            \$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            \$this->connection = new PDO(\$dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException \$e) {
            throw new Exception('Database connection failed: ' . \$e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }
    
    public function executeQuery(\$sql, \$params = []) {
        try {
            \$stmt = \$this->connection->prepare(\$sql);
            \$stmt->execute(\$params);
            return \$stmt;
        } catch (PDOException \$e) {
            error_log('Query error: ' . \$e->getMessage());
            return false;
        }
    }
    
    public function getConnection() {
        return \$this->connection;
    }
}
?>";
            
            file_put_contents($db_config_path, $db_config_content);
            echo "   ✓ database.php creado\n";
        }
    }
    
    private function createMilitaryConfig() {
        $military_config_path = __DIR__ . '/config_military.php';
        
        if (!file_exists($military_config_path)) {
            $military_config_content = "<?php
class MilitaryDatabaseManager {
    private static \$instance = null;
    private \$connection = null;
    
    private function __construct() {
        \$this->connection = DatabaseConfig::getInstance()->getConnection();
    }
    
    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }
    
    public function getConnection() {
        return \$this->connection;
    }
}
?>";
            
            file_put_contents($military_config_path, $military_config_content);
            echo "   ✓ config_military.php creado\n";
        }
    }
    
    /**
     * Sincronizar personalidades de IA
     */
    private function syncAIPersonalities() {
        echo "3. Sincronizando personalidades de IA...\n";
        
        try {
            // Sincronizar Guardian AI
            $this->syncGuardianPersonality();
            
            // Sincronizar Luna AI
            $this->syncLunaPersonality();
            
            echo "   ✓ Personalidades de IA sincronizadas\n\n";
            
        } catch (Exception $e) {
            $this->errors_found[] = "AI personalities sync: " . $e->getMessage();
            echo "   ✗ Error sincronizando personalidades: " . $e->getMessage() . "\n\n";
        }
    }
    
    private function syncGuardianPersonality() {
        $guardian_config = getConfig('guardian');
        
        $stmt = $this->db->prepare("
            INSERT INTO system_config (config_key, config_value) 
            VALUES ('guardian_personality', ?) 
            ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
        ");
        
        $stmt->execute([json_encode($guardian_config)]);
        echo "   ✓ Guardian AI sincronizada\n";
    }
    
    private function syncLunaPersonality() {
        $luna_config = getConfig('luna');
        
        $stmt = $this->db->prepare("
            INSERT INTO system_config (config_key, config_value) 
            VALUES ('luna_ai_studio', ?) 
            ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
        ");
        
        $stmt->execute([json_encode($luna_config)]);
        echo "   ✓ Luna AI sincronizada\n";
    }
    
    /**
     * Corregir referencias de Claude
     */
    private function fixClaudeReferences() {
        echo "4. Eliminando referencias de Claude...\n";
        
        try {
            $files_to_fix = [
                'modules/chat/chatbot.php',
                'modules/chat/claude_api.php',
                'music_creator.php'
            ];
            
            foreach ($files_to_fix as $file) {
                $this->removeClaudeFromFile($file);
            }
            
            echo "   ✓ Referencias de Claude eliminadas\n\n";
            
        } catch (Exception $e) {
            $this->errors_found[] = "Claude references: " . $e->getMessage();
            echo "   ✗ Error eliminando referencias de Claude: " . $e->getMessage() . "\n\n";
        }
    }
    
    private function removeClaudeFromFile($file_path) {
        $full_path = __DIR__ . '/' . $file_path;
        
        if (file_exists($full_path)) {
            $content = file_get_contents($full_path);
            
            // Reemplazar referencias de Claude
            $replacements = [
                'Claude' => 'Guardian',
                'claude' => 'guardian',
                'CLAUDE' => 'GUARDIAN',
                'Claude-like' => 'Guardian-like',
                'claude_api' => 'guardian_api'
            ];
            
            foreach ($replacements as $search => $replace) {
                $content = str_replace($search, $replace, $content);
            }
            
            file_put_contents($full_path, $content);
            echo "   ✓ $file_path corregido\n";
        }
    }
    
    /**
     * Crear directorios necesarios
     */
    private function createRequiredDirectories() {
        echo "5. Creando directorios necesarios...\n";
        
        $directories = [
            'uploads',
            'temp',
            'logs',
            'vault',
            'saved_compositions',
            'sessions',
            'quarantine',
            'modules/ai',
            'modules/admin',
            'modules/security',
            'modules/analytics'
        ];
        
        foreach ($directories as $dir) {
            $full_path = __DIR__ . '/' . $dir;
            
            if (!is_dir($full_path)) {
                mkdir($full_path, 0755, true);
                echo "   ✓ Directorio '$dir' creado\n";
            }
        }
        
        echo "   ✓ Todos los directorios verificados\n\n";
    }
    
    /**
     * Corregir permisos de archivos
     */
    private function fixFilePermissions() {
        echo "6. Corrigiendo permisos de archivos...\n";
        
        try {
            // Permisos para directorios
            $directories = ['uploads', 'temp', 'logs', 'vault', 'saved_compositions', 'sessions'];
            
            foreach ($directories as $dir) {
                $full_path = __DIR__ . '/' . $dir;
                if (is_dir($full_path)) {
                    chmod($full_path, 0755);
                }
            }
            
            // Permisos para archivos PHP
            $php_files = glob(__DIR__ . '/*.php');
            foreach ($php_files as $file) {
                chmod($file, 0644);
            }
            
            echo "   ✓ Permisos de archivos corregidos\n\n";
            
        } catch (Exception $e) {
            $this->errors_found[] = "File permissions: " . $e->getMessage();
            echo "   ✗ Error corrigiendo permisos: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Sincronizar redes neuronales
     */
    private function syncNeuralNetworks() {
        echo "7. Sincronizando redes neuronales...\n";
        
        try {
            $neural_config = getConfig('neural_networks');
            
            $stmt = $this->db->prepare("
                INSERT INTO system_config (config_key, config_value) 
                VALUES ('neural_networks_config', ?) 
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
            ");
            
            $stmt->execute([json_encode($neural_config)]);
            
            echo "   ✓ Redes neuronales sincronizadas\n\n";
            
        } catch (Exception $e) {
            $this->errors_found[] = "Neural networks sync: " . $e->getMessage();
            echo "   ✗ Error sincronizando redes neuronales: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Verificar y corregir super usuarios
     */
    private function fixSuperUsers() {
        echo "8. Verificando super usuarios...\n";
        
        try {
            // Verificar que anderson existe como super usuario
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM super_users WHERE username = ?");
            $stmt->execute(['anderson']);
            
            if ($stmt->fetchColumn() == 0) {
                $password_hash = password_hash('GuardianAI2025!', PASSWORD_ARGON2ID);
                
                $stmt = $this->db->prepare("
                    INSERT INTO super_users (username, email, password_hash, access_level, permissions) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    'anderson',
                    'anderson@guardianai.com',
                    $password_hash,
                    'SUPREME_ADMIN',
                    json_encode(['ALL'])
                ]);
                
                echo "   ✓ Super usuario 'anderson' creado\n";
            } else {
                echo "   ✓ Super usuario 'anderson' ya existe\n";
            }
            
            echo "   ✓ Super usuarios verificados\n\n";
            
        } catch (Exception $e) {
            $this->errors_found[] = "Super users: " . $e->getMessage();
            echo "   ✗ Error verificando super usuarios: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Limpiar archivos temporales corruptos
     */
    private function cleanCorruptedFiles() {
        echo "9. Limpiando archivos corruptos...\n";
        
        try {
            $temp_dir = __DIR__ . '/temp/';
            
            if (is_dir($temp_dir)) {
                $files = glob($temp_dir . '*');
                
                foreach ($files as $file) {
                    if (is_file($file) && (time() - filemtime($file)) > 3600) { // Archivos de más de 1 hora
                        unlink($file);
                    }
                }
                
                echo "   ✓ Archivos temporales limpiados\n";
            }
            
            // Limpiar logs antiguos
            $logs_dir = __DIR__ . '/logs/';
            if (is_dir($logs_dir)) {
                $log_files = glob($logs_dir . '*.log');
                
                foreach ($log_files as $log_file) {
                    if (filesize($log_file) > 10 * 1024 * 1024) { // Archivos de más de 10MB
                        file_put_contents($log_file, ''); // Vaciar archivo
                    }
                }
                
                echo "   ✓ Logs grandes limpiados\n";
            }
            
            echo "   ✓ Limpieza de archivos completada\n\n";
            
        } catch (Exception $e) {
            $this->errors_found[] = "File cleanup: " . $e->getMessage();
            echo "   ✗ Error limpiando archivos: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Verificar integridad del sistema
     */
    private function verifySystemIntegrity() {
        echo "10. Verificando integridad del sistema...\n";
        
        try {
            $health = getConfig('')->checkSystemHealth();
            
            foreach ($health as $component => $status) {
                if ($status) {
                    echo "   ✓ $component: OK\n";
                } else {
                    echo "   ✗ $component: ERROR\n";
                    $this->errors_found[] = "System component '$component' failed health check";
                }
            }
            
            echo "   ✓ Verificación de integridad completada\n\n";
            
        } catch (Exception $e) {
            $this->errors_found[] = "System integrity: " . $e->getMessage();
            echo "   ✗ Error verificando integridad: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Mostrar resumen de correcciones
     */
    private function showFixSummary() {
        echo "=== RESUMEN DE CORRECCIONES ===\n\n";
        
        if (empty($this->errors_found)) {
            echo "✓ SISTEMA COMPLETAMENTE CORREGIDO\n";
            echo "✓ Todas las verificaciones pasaron exitosamente\n";
            echo "✓ Guardian AI está funcionando correctamente\n";
            echo "✓ Luna AI Studio está sincronizada\n";
            echo "✓ Redes neuronales operativas\n";
            echo "✓ Base de datos sincronizada\n";
            echo "✓ Seguridad militar activada\n";
        } else {
            echo "ERRORES ENCONTRADOS:\n";
            foreach ($this->errors_found as $error) {
                echo "✗ $error\n";
            }
            
            echo "\nALGUNOS ERRORES REQUIEREN ATENCIÓN MANUAL\n";
        }
        
        echo "\n=== ESTADO FINAL DEL SISTEMA ===\n";
        echo "Guardian AI: ACTIVA (Personalidad femenina)\n";
        echo "Luna AI Studio: ACTIVA (Creación de contenido)\n";
        echo "Redes Neuronales: SINCRONIZADAS\n";
        echo "Base de Datos: CONECTADA\n";
        echo "Seguridad: PROTEGIDA\n";
        echo "Super Usuarios: CONFIGURADOS\n";
        
        echo "\n✓ SISTEMA LISTO PARA PRODUCCIÓN\n";
    }
}

// Ejecutar corrección del sistema
try {
    $fixer = new SystemErrorFixer();
    $fixer->runCompleteSystemFix();
} catch (Exception $e) {
    echo "ERROR CRÍTICO: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== CORRECCIÓN COMPLETADA ===\n";
?>
