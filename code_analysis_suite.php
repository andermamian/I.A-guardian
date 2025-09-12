<?php
/**
 * GuardianIA v3.0 - Análisis de Código y Detección de Errores
 * Suite completa para identificar problemas y mejoras necesarias
 * Anderson Mamian Chicangana
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

class CodeAnalysisSuite {
    private $errors = [];
    private $warnings = [];
    private $suggestions = [];
    private $critical_issues = [];
    private $security_issues = [];
    private $performance_issues = [];
    private $code_quality_issues = [];
    private $db_issues = [];
    private $config_issues = [];
    
    private $files_to_analyze = [];
    private $total_lines = 0;
    private $problematic_lines = 0;
    
    public function __construct() {
        $this->scanProjectFiles();
    }
    
    private function scanProjectFiles() {
        $this->files_to_analyze = [
            'config.php',
            'config_military.php',
            'login.php',
            'dashboard.php',
            'chat.php',
            'index.php',
            'security_center.php',
            'music_creator.php',
            'assistant.php'
        ];
        
        // Agregar todos los archivos PHP del directorio
        $files = glob(__DIR__ . '/*.php');
        foreach ($files as $file) {
            $filename = basename($file);
            if (!in_array($filename, $this->files_to_analyze)) {
                $this->files_to_analyze[] = $filename;
            }
        }
    }
    
    public function runFullAnalysis() {
        $this->showHeader();
        
        // 1. Análisis de configuración
        $this->analyzeConfiguration();
        
        // 2. Análisis de seguridad
        $this->analyzeSecurityIssues();
        
        // 3. Análisis de base de datos
        $this->analyzeDatabaseIssues();
        
        // 4. Análisis de código PHP
        $this->analyzePHPCode();
        
        // 5. Análisis de dependencias
        $this->analyzeDependencies();
        
        // 6. Análisis de rendimiento
        $this->analyzePerformance();
        
        // 7. Análisis de archivos y permisos
        $this->analyzeFileSystem();
        
        // 8. Análisis de sesiones
        $this->analyzeSessionSecurity();
        
        // 9. Análisis de funciones deprecadas
        $this->analyzeDeprecatedFeatures();
        
        // 10. Análisis de mejores prácticas
        $this->analyzeBestPractices();
        
        // Generar reporte
        $this->generateReport();
    }
    
    private function showHeader() {
        if (php_sapi_name() === 'cli') {
            echo "\n╔══════════════════════════════════════════════════════════════╗\n";
            echo "║     ANÁLISIS DE CÓDIGO - DETECCIÓN DE ERRORES Y MEJORAS     ║\n";
            echo "╚══════════════════════════════════════════════════════════════╝\n\n";
        } else {
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Análisis de Código - GuardianIA</title>
    <style>
        body {
            background: #0a0a0a;
            color: #00ff00;
            font-family: "Courier New", monospace;
            padding: 20px;
            line-height: 1.6;
        }
        .header {
            background: linear-gradient(45deg, #ff0000, #ff6600);
            padding: 20px;
            border-radius: 10px;
            color: white;
            margin-bottom: 20px;
            text-align: center;
        }
        .critical {
            background: rgba(255,0,0,0.2);
            border-left: 5px solid #ff0000;
            padding: 10px;
            margin: 10px 0;
        }
        .error {
            background: rgba(255,100,0,0.2);
            border-left: 5px solid #ff6400;
            padding: 10px;
            margin: 10px 0;
        }
        .warning {
            background: rgba(255,165,0,0.2);
            border-left: 5px solid #ffa500;
            padding: 10px;
            margin: 10px 0;
        }
        .suggestion {
            background: rgba(0,255,0,0.1);
            border-left: 5px solid #00ff00;
            padding: 10px;
            margin: 10px 0;
        }
        .section {
            border: 1px solid #333;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        h2 {
            color: #ff6600;
            border-bottom: 2px solid #ff6600;
            padding-bottom: 10px;
        }
        code {
            background: #222;
            padding: 2px 6px;
            border-radius: 3px;
            color: #0ff;
        }
        .fix-code {
            background: #111;
            border: 1px solid #0f0;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            white-space: pre;
            overflow-x: auto;
        }
        .score {
            font-size: 48px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
        }
        .score-bad { background: rgba(255,0,0,0.3); color: #ff0000; }
        .score-poor { background: rgba(255,100,0,0.3); color: #ff6400; }
        .score-fair { background: rgba(255,255,0,0.3); color: #ffff00; }
        .score-good { background: rgba(0,255,0,0.3); color: #00ff00; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #333;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #222;
            color: #ff6600;
        }
        .progress {
            background: #222;
            border-radius: 10px;
            padding: 5px;
            margin: 10px 0;
        }
        .progress-bar {
            background: linear-gradient(90deg, #ff0000, #ffff00, #00ff00);
            height: 20px;
            border-radius: 5px;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔍 ANÁLISIS DE CÓDIGO Y DETECCIÓN DE ERRORES</h1>
        <p>Sistema GuardianIA v3.0 - Identificando problemas y mejoras</p>
    </div>';
        }
    }
    
    private function analyzeConfiguration() {
        $this->outputSection("ANÁLISIS DE CONFIGURACIÓN");
        
        // Verificar config.php
        if (file_exists('config.php')) {
            $config_content = file_get_contents('config.php');
            
            // Problema: Credenciales hardcodeadas
            if (strpos($config_content, "'Soyelmejor2025'") !== false) {
                $this->addCritical(
                    "Credenciales en código",
                    "La contraseña de base de datos está hardcodeada en config.php",
                    "Usar variables de entorno: \$_ENV['DB_PASSWORD'] o archivo .env"
                );
            }
            
            // Problema: Sin validación de SSL
            if (strpos($config_content, 'MYSQLI_OPT_SSL_VERIFY_SERVER_CERT') === false) {
                $this->addWarning(
                    "SSL no verificado",
                    "La conexión a base de datos no verifica certificados SSL",
                    "Agregar verificación SSL para conexiones seguras"
                );
            }
            
            // Problema: Claves de encriptación débiles
            if (strpos($config_content, "hash('sha256'") !== false) {
                $this->addError(
                    "Generación de claves débil",
                    "Uso de SHA256 simple para generar claves de encriptación",
                    "Usar PBKDF2 o Argon2 para derivación de claves"
                );
            }
            
            // Problema: Sin rotación de claves
            if (!defined('KEY_ROTATION_INTERVAL') || KEY_ROTATION_INTERVAL > 7200) {
                $this->addWarning(
                    "Rotación de claves",
                    "Intervalo de rotación de claves muy largo o no definido",
                    "Reducir KEY_ROTATION_INTERVAL a máximo 3600 segundos"
                );
            }
        } else {
            $this->addCritical(
                "Archivo faltante",
                "config.php no encontrado",
                "Crear archivo de configuración con las credenciales necesarias"
            );
        }
        
        // Verificar variables de entorno
        if (empty($_ENV) || count($_ENV) < 5) {
            $this->addSuggestion(
                "Variables de entorno",
                "No se están usando variables de entorno para configuración sensible",
                "Implementar dotenv para manejar configuración sensible"
            );
        }
    }
    
    private function analyzeSecurityIssues() {
        $this->outputSection("ANÁLISIS DE SEGURIDAD");
        
        // XSS Vulnerabilities
        $files_with_xss = [];
        foreach ($this->files_to_analyze as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                // Buscar echo directo de $_GET, $_POST, $_REQUEST
                if (preg_match('/echo\s+\$_(GET|POST|REQUEST)\[/', $content)) {
                    $files_with_xss[] = $file;
                }
                
                // Buscar print sin escape
                if (preg_match('/print\s+\$_(GET|POST|REQUEST)\[/', $content)) {
                    $files_with_xss[] = $file;
                }
            }
        }
        
        if (!empty($files_with_xss)) {
            $this->addCritical(
                "Vulnerabilidad XSS",
                "Archivos con posible XSS: " . implode(', ', $files_with_xss),
                "Usar htmlspecialchars() o htmlentities() para todo output de usuario"
            );
        }
        
        // SQL Injection
        $files_with_sqli = [];
        foreach ($this->files_to_analyze as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                // Buscar queries concatenadas
                if (preg_match('/query\(["\'].*\.\s*\$_(GET|POST|REQUEST)/', $content)) {
                    $files_with_sqli[] = $file;
                }
                
                // Buscar mysql_query (deprecated y peligroso)
                if (strpos($content, 'mysql_query') !== false) {
                    $files_with_sqli[] = $file;
                }
            }
        }
        
        if (!empty($files_with_sqli)) {
            $this->addCritical(
                "Vulnerabilidad SQL Injection",
                "Archivos con posible SQLi: " . implode(', ', $files_with_sqli),
                "Usar prepared statements con bind_param()"
            );
        }
        
        // CSRF Protection
        $csrf_protected = false;
        foreach ($this->files_to_analyze as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                if (strpos($content, 'csrf_token') !== false) {
                    $csrf_protected = true;
                    break;
                }
            }
        }
        
        if (!$csrf_protected) {
            $this->addError(
                "Sin protección CSRF",
                "No se detectó implementación de tokens CSRF",
                "Implementar tokens CSRF en todos los formularios"
            );
        }
        
        // Headers de seguridad
        $headers = headers_list();
        $required_headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000',
            'Content-Security-Policy' => "default-src 'self'"
        ];
        
        foreach ($required_headers as $header => $value) {
            $found = false;
            foreach ($headers as $sent_header) {
                if (stripos($sent_header, $header) !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->addError(
                    "Header de seguridad faltante",
                    "No se encontró el header: $header",
                    "Agregar: header('$header: $value');"
                );
            }
        }
        
        // Verificar HTTPS
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            $this->addCritical(
                "Sin HTTPS",
                "El sitio no está usando HTTPS",
                "Configurar certificado SSL y forzar HTTPS"
            );
        }
    }
    
    private function analyzeDatabaseIssues() {
        $this->outputSection("ANÁLISIS DE BASE DE DATOS");
        
        // Verificar conexión
        try {
            if (class_exists('MilitaryDatabaseManager')) {
                $db = MilitaryDatabaseManager::getInstance();
                
                if (!$db->isConnected()) {
                    $this->addCritical(
                        "Sin conexión a BD",
                        "No se puede conectar a la base de datos",
                        "Verificar credenciales y servidor MySQL"
                    );
                } else {
                    // Verificar tablas faltantes
                    $required_tables = [
                        'users', 'conversations', 'conversation_messages',
                        'security_events', 'system_logs', 'ai_detections'
                    ];
                    
                    foreach ($required_tables as $table) {
                        $result = $db->query("SHOW TABLES LIKE '$table'");
                        if (!$result || $result->num_rows == 0) {
                            $this->addError(
                                "Tabla faltante",
                                "La tabla '$table' no existe en la base de datos",
                                "Ejecutar el script SQL de creación de tablas"
                            );
                        }
                    }
                    
                    // Verificar índices
                    $result = $db->query("SHOW INDEX FROM users WHERE Key_name != 'PRIMARY'");
                    if (!$result || $result->num_rows < 2) {
                        $this->addWarning(
                            "Índices faltantes",
                            "La tabla 'users' tiene pocos índices",
                            "Agregar índices en username, email para mejorar rendimiento"
                        );
                    }
                }
            }
        } catch (Exception $e) {
            $this->addCritical(
                "Error de BD",
                "Error al analizar base de datos: " . $e->getMessage(),
                "Revisar la configuración de base de datos"
            );
        }
        
        // Verificar consultas sin límite
        foreach ($this->files_to_analyze as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                // Buscar SELECT * sin LIMIT
                if (preg_match('/SELECT\s+\*\s+FROM\s+\w+(?!\s+LIMIT)/i', $content)) {
                    $this->addWarning(
                        "Query sin límite",
                        "Archivo $file tiene SELECT * sin LIMIT",
                        "Agregar LIMIT a las consultas para evitar sobrecarga"
                    );
                }
            }
        }
    }
    
    private function analyzePHPCode() {
        $this->outputSection("ANÁLISIS DE CÓDIGO PHP");
        
        // Versión de PHP
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $this->addCritical(
                "PHP obsoleto",
                "Versión de PHP: " . PHP_VERSION . " (requiere >= 7.4)",
                "Actualizar a PHP 7.4 o superior"
            );
        }
        
        // Buscar funciones peligrosas
        $dangerous_functions = [
            'eval' => 'Permite ejecución de código arbitrario',
            'exec' => 'Permite ejecución de comandos del sistema',
            'system' => 'Permite ejecución de comandos del sistema',
            'shell_exec' => 'Permite ejecución de comandos shell',
            'passthru' => 'Permite ejecución de comandos',
            'file_get_contents' => 'Puede leer archivos remotos sin validación',
            'include' => 'Puede incluir archivos remotos',
            'require' => 'Puede requerir archivos remotos'
        ];
        
        foreach ($this->files_to_analyze as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $lines = explode("\n", $content);
                $this->total_lines += count($lines);
                
                foreach ($dangerous_functions as $func => $risk) {
                    if (strpos($content, $func . '(') !== false) {
                        $this->addWarning(
                            "Función peligrosa",
                            "Uso de $func() en $file - $risk",
                            "Evitar o sanitizar el uso de $func()"
                        );
                        $this->problematic_lines++;
                    }
                }
                
                // Buscar variables sin inicializar
                if (preg_match('/\$\w+\[.*\](?!\s*=)/', $content)) {
                    $this->addWarning(
                        "Variable sin verificar",
                        "Posible uso de array sin verificar en $file",
                        "Usar isset() o empty() antes de acceder a arrays"
                    );
                }
                
                // Buscar errores suprimidos con @
                $error_suppression_count = substr_count($content, '@');
                if ($error_suppression_count > 5) {
                    $this->addWarning(
                        "Supresión de errores excesiva",
                        "Archivo $file usa @ para suprimir errores ($error_suppression_count veces)",
                        "Manejar errores apropiadamente en lugar de suprimirlos"
                    );
                }
            }
        }
    }
    
    private function analyzeDependencies() {
        $this->outputSection("ANÁLISIS DE DEPENDENCIAS");
        
        // Extensiones requeridas
        $required_extensions = [
            'mysqli' => 'Base de datos',
            'openssl' => 'Encriptación',
            'mbstring' => 'Manejo de strings UTF-8',
            'json' => 'Procesamiento JSON',
            'session' => 'Manejo de sesiones',
            'gd' => 'Procesamiento de imágenes',
            'curl' => 'Peticiones HTTP'
        ];
        
        foreach ($required_extensions as $ext => $description) {
            if (!extension_loaded($ext)) {
                $this->addError(
                    "Extensión faltante",
                    "PHP extension '$ext' no está instalada ($description)",
                    "Instalar: sudo apt-get install php-$ext"
                );
            }
        }
        
        // Verificar composer
        if (!file_exists('composer.json')) {
            $this->addSuggestion(
                "Sin Composer",
                "No se usa Composer para manejo de dependencias",
                "Inicializar Composer y usar autoloading PSR-4"
            );
        }
    }
    
    private function analyzePerformance() {
        $this->outputSection("ANÁLISIS DE RENDIMIENTO");
        
        // Memory limit
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit != '-1') {
            $bytes = $this->convertToBytes($memory_limit);
            if ($bytes < 256 * 1024 * 1024) {
                $this->addWarning(
                    "Límite de memoria bajo",
                    "memory_limit = $memory_limit (recomendado: 256M mínimo)",
                    "Aumentar memory_limit en php.ini"
                );
            }
        }
        
        // Max execution time
        $max_time = ini_get('max_execution_time');
        if ($max_time > 0 && $max_time < 30) {
            $this->addWarning(
                "Tiempo de ejecución corto",
                "max_execution_time = $max_time segundos",
                "Aumentar a 30 o más para operaciones complejas"
            );
        }
        
        // OPcache
        if (!function_exists('opcache_get_status')) {
            $this->addError(
                "Sin OPcache",
                "OPcache no está habilitado",
                "Habilitar OPcache para mejorar rendimiento"
            );
        } else {
            $status = opcache_get_status();
            if (!$status['opcache_enabled']) {
                $this->addError(
                    "OPcache deshabilitado",
                    "OPcache está instalado pero no activo",
                    "Habilitar opcache.enable=1 en php.ini"
                );
            }
        }
        
        // Búsqueda de código ineficiente
        foreach ($this->files_to_analyze as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                // Loops con queries
                if (preg_match('/while.*fetch.*\{.*query\(/s', $content)) {
                    $this->addError(
                        "N+1 Query Problem",
                        "Posible N+1 query problem en $file",
                        "Usar JOINs en lugar de queries en loops"
                    );
                }
                
                // file_get_contents en loop
                if (preg_match('/(for|while|foreach).*\{.*file_get_contents/s', $content)) {
                    $this->addWarning(
                        "I/O en loop",
                        "file_get_contents dentro de loop en $file",
                        "Cargar archivos fuera del loop"
                    );
                }
            }
        }
    }
    
    private function analyzeFileSystem() {
        $this->outputSection("ANÁLISIS DE SISTEMA DE ARCHIVOS");
        
        // Directorios requeridos
        $required_dirs = [
            'logs' => '0755',
            'uploads' => '0755',
            'cache' => '0755',
            'compositions' => '0755'
        ];
        
        foreach ($required_dirs as $dir => $perms) {
            $path = __DIR__ . '/' . $dir;
            
            if (!is_dir($path)) {
                $this->addError(
                    "Directorio faltante",
                    "El directorio '$dir' no existe",
                    "mkdir -p $dir && chmod $perms $dir"
                );
            } else {
                if (!is_writable($path)) {
                    $this->addError(
                        "Sin permisos de escritura",
                        "No se puede escribir en '$dir'",
                        "chmod $perms $dir"
                    );
                }
                
                // Verificar permisos
                $current_perms = substr(sprintf('%o', fileperms($path)), -4);
                if ($current_perms == '0777') {
                    $this->addWarning(
                        "Permisos muy abiertos",
                        "Directorio '$dir' tiene permisos 777",
                        "Cambiar a 755: chmod 755 $dir"
                    );
                }
            }
        }
        
        // Archivos sensibles expuestos
        $sensitive_files = ['.env', '.git', 'config.php', '.htaccess'];
        foreach ($sensitive_files as $file) {
            if (file_exists($file)) {
                // Verificar si es accesible desde web
                $url = "http://{$_SERVER['HTTP_HOST']}/$file";
                $headers = @get_headers($url);
                if ($headers && strpos($headers[0], '200') !== false) {
                    $this->addCritical(
                        "Archivo sensible expuesto",
                        "El archivo '$file' es accesible públicamente",
                        "Configurar .htaccess para denegar acceso"
                    );
                }
            }
        }
    }
    
    private function analyzeSessionSecurity() {
        $this->outputSection("ANÁLISIS DE SEGURIDAD DE SESIONES");
        
        // Configuración de cookies
        if (!ini_get('session.cookie_httponly')) {
            $this->addError(
                "Cookies sin HttpOnly",
                "Las cookies de sesión son accesibles por JavaScript",
                "Configurar: session.cookie_httponly = 1"
            );
        }
        
        if (!ini_get('session.cookie_secure') && isset($_SERVER['HTTPS'])) {
            $this->addError(
                "Cookies sin Secure flag",
                "Las cookies no tienen flag Secure en HTTPS",
                "Configurar: session.cookie_secure = 1"
            );
        }
        
        $samesite = ini_get('session.cookie_samesite');
        if (!$samesite || $samesite == 'None') {
            $this->addWarning(
                "Sin SameSite",
                "Cookies sin protección SameSite",
                "Configurar: session.cookie_samesite = 'Strict'"
            );
        }
        
        // Session fixation
        $session_regenerate_found = false;
        foreach ($this->files_to_analyze as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                if (strpos($content, 'session_regenerate_id') !== false) {
                    $session_regenerate_found = true;
                    break;
                }
            }
        }
        
        if (!$session_regenerate_found) {
            $this->addError(
                "Sin regeneración de ID",
                "No se regenera el ID de sesión después del login",
                "Usar session_regenerate_id(true) después de autenticación"
            );
        }
    }
    
    private function analyzeDeprecatedFeatures() {
        $this->outputSection("ANÁLISIS DE CARACTERÍSTICAS DEPRECADAS");
        
        $deprecated_functions = [
            'mysql_connect' => 'mysqli_connect',
            'mysql_query' => 'mysqli_query',
            'ereg' => 'preg_match',
            'eregi' => 'preg_match con flag i',
            'split' => 'explode o preg_split',
            'each' => 'foreach',
            'create_function' => 'funciones anónimas'
        ];
        
        foreach ($this->files_to_analyze as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                foreach ($deprecated_functions as $old => $new) {
                    if (strpos($content, $old . '(') !== false) {
                        $this->addError(
                            "Función deprecada",
                            "Uso de $old() en $file",
                            "Reemplazar con $new"
                        );
                    }
                }
                
                // Magic quotes (removido en PHP 5.4)
                if (strpos($content, 'get_magic_quotes') !== false) {
                    $this->addError(
                        "Magic quotes",
                        "Referencia a magic quotes en $file",
                        "Magic quotes fue removido, eliminar estas verificaciones"
                    );
                }
            }
        }
    }
    
    private function analyzeBestPractices() {
        $this->outputSection("ANÁLISIS DE MEJORES PRÁCTICAS");
        
        // PSR Standards
        $psr_issues = [];
        
        foreach ($this->files_to_analyze as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $lines = explode("\n", $content);
                
                // Verificar indentación (PSR-2: 4 espacios)
                foreach ($lines as $line) {
                    if (preg_match('/^\t/', $line)) {
                        $psr_issues[] = "Uso de tabs en lugar de espacios en $file";
                        break;
                    }
                }
                
                // Verificar nombres de clases (PSR-1: StudlyCaps)
                if (preg_match('/class\s+[a-z]/', $content)) {
                    $psr_issues[] = "Nombre de clase no sigue StudlyCaps en $file";
                }
                
                // Verificar comentarios
                if (!strpos($content, '/**') && strlen($content) > 500) {
                    $this->addSuggestion(
                        "Sin documentación",
                        "Archivo $file no tiene comentarios PHPDoc",
                        "Agregar documentación PHPDoc a clases y métodos"
                    );
                }
            }
        }
        
        if (!empty($psr_issues)) {
            foreach ($psr_issues as $issue) {
                $this->addSuggestion(
                    "PSR Standard",
                    $issue,
                    "Seguir estándares PSR-1 y PSR-2"
                );
            }
        }
        
        // Verificar uso de namespaces
        $uses_namespaces = false;
        foreach ($this->files_to_analyze as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                if (strpos($content, 'namespace ') !== false) {
                    $uses_namespaces = true;
                    break;
                }
            }
        }
        
        if (!$uses_namespaces) {
            $this->addSuggestion(
                "Sin namespaces",
                "El proyecto no usa namespaces",
                "Implementar namespaces siguiendo PSR-4"
            );
        }
        
        // Verificar tests
        if (!is_dir('tests') && !is_dir('test')) {
            $this->addSuggestion(
                "Sin tests",
                "No se encontraron tests unitarios",
                "Implementar PHPUnit para testing"
            );
        }
    }
    
    private function generateReport() {
        $total_issues = count($this->critical_issues) + count($this->errors) + 
                       count($this->warnings) + count($this->suggestions);
        
        $score = 100;
        $score -= count($this->critical_issues) * 20;
        $score -= count($this->errors) * 10;
        $score -= count($this->warnings) * 5;
        $score -= count($this->suggestions) * 2;
        $score = max(0, $score);
        
        $this->outputSection("📊 REPORTE FINAL");
        
        // Score visual
        $score_class = 'score-bad';
        if ($score >= 80) $score_class = 'score-good';
        elseif ($score >= 60) $score_class = 'score-fair';
        elseif ($score >= 40) $score_class = 'score-poor';
        
        echo "<div class='score $score_class'>Puntuación: $score/100</div>";
        
        // Resumen
        echo "<div class='section'>";
        echo "<h2>Resumen de Problemas Encontrados</h2>";
        echo "<table>";
        echo "<tr><th>Tipo</th><th>Cantidad</th><th>Impacto</th></tr>";
        echo "<tr><td style='color: #ff0000'>Críticos</td><td>" . count($this->critical_issues) . "</td><td>Muy Alto</td></tr>";
        echo "<tr><td style='color: #ff6400'>Errores</td><td>" . count($this->errors) . "</td><td>Alto</td></tr>";
        echo "<tr><td style='color: #ffa500'>Advertencias</td><td>" . count($this->warnings) . "</td><td>Medio</td></tr>";
        echo "<tr><td style='color: #00ff00'>Sugerencias</td><td>" . count($this->suggestions) . "</td><td>Bajo</td></tr>";
        echo "<tr><th>Total</th><th>$total_issues</th><th>-</th></tr>";
        echo "</table>";
        echo "</div>";
        
        // Problemas críticos
        if (!empty($this->critical_issues)) {
            echo "<div class='section'>";
            echo "<h2>🔴 PROBLEMAS CRÍTICOS (Resolver inmediatamente)</h2>";
            foreach ($this->critical_issues as $issue) {
                echo "<div class='critical'>";
                echo "<strong>{$issue['title']}</strong><br>";
                echo "Problema: {$issue['description']}<br>";
                echo "Solución: {$issue['solution']}";
                if (isset($issue['code'])) {
                    echo "<div class='fix-code'>{$issue['code']}</div>";
                }
                echo "</div>";
            }
            echo "</div>";
        }
        
        // Errores
        if (!empty($this->errors)) {
            echo "<div class='section'>";
            echo "<h2>🟠 ERRORES (Resolver pronto)</h2>";
            foreach ($this->errors as $issue) {
                echo "<div class='error'>";
                echo "<strong>{$issue['title']}</strong><br>";
                echo "Problema: {$issue['description']}<br>";
                echo "Solución: {$issue['solution']}";
                echo "</div>";
            }
            echo "</div>";
        }
        
        // Advertencias
        if (!empty($this->warnings)) {
            echo "<div class='section'>";
            echo "<h2>🟡 ADVERTENCIAS (Considerar resolver)</h2>";
            foreach ($this->warnings as $issue) {
                echo "<div class='warning'>";
                echo "<strong>{$issue['title']}</strong><br>";
                echo "Problema: {$issue['description']}<br>";
                echo "Solución: {$issue['solution']}";
                echo "</div>";
            }
            echo "</div>";
        }
        
        // Sugerencias
        if (!empty($this->suggestions)) {
            echo "<div class='section'>";
            echo "<h2>🟢 SUGERENCIAS DE MEJORA</h2>";
            foreach ($this->suggestions as $issue) {
                echo "<div class='suggestion'>";
                echo "<strong>{$issue['title']}</strong><br>";
                echo "Mejora: {$issue['description']}<br>";
                echo "Recomendación: {$issue['solution']}";
                echo "</div>";
            }
            echo "</div>";
        }
        
        // Plan de acción
        echo "<div class='section'>";
        echo "<h2>📋 PLAN DE ACCIÓN RECOMENDADO</h2>";
        echo "<ol>";
        echo "<li><strong>Fase 1 - Crítico (Inmediato):</strong>";
        echo "<ul>";
        echo "<li>Configurar HTTPS con certificado SSL</li>";
        echo "<li>Mover credenciales a variables de entorno</li>";
        echo "<li>Corregir vulnerabilidades XSS y SQL Injection</li>";
        echo "</ul></li>";
        
        echo "<li><strong>Fase 2 - Seguridad (Esta semana):</strong>";
        echo "<ul>";
        echo "<li>Implementar tokens CSRF</li>";
        echo "<li>Agregar headers de seguridad</li>";
        echo "<li>Configurar sesiones seguras</li>";
        echo "</ul></li>";
        
        echo "<li><strong>Fase 3 - Rendimiento (Este mes):</strong>";
        echo "<ul>";
        echo "<li>Habilitar OPcache</li>";
        echo "<li>Optimizar queries de base de datos</li>";
        echo "<li>Implementar caché</li>";
        echo "</ul></li>";
        
        echo "<li><strong>Fase 4 - Calidad (Continuo):</strong>";
        echo "<ul>";
        echo "<li>Implementar PSR standards</li>";
        echo "<li>Agregar tests unitarios</li>";
        echo "<li>Documentar código con PHPDoc</li>";
        echo "</ul></li>";
        echo "</ol>";
        echo "</div>";
        
        // Estadísticas
        echo "<div class='section'>";
        echo "<h2>📈 ESTADÍSTICAS DEL CÓDIGO</h2>";
        echo "<table>";
        echo "<tr><td>Archivos analizados</td><td>" . count($this->files_to_analyze) . "</td></tr>";
        echo "<tr><td>Líneas de código totales</td><td>$this->total_lines</td></tr>";
        echo "<tr><td>Líneas problemáticas</td><td>$this->problematic_lines</td></tr>";
        $problem_percentage = $this->total_lines > 0 ? 
            round(($this->problematic_lines / $this->total_lines) * 100, 2) : 0;
        echo "<tr><td>Porcentaje problemático</td><td>$problem_percentage%</td></tr>";
        echo "</table>";
        echo "</div>";
        
        echo "</body></html>";
    }
    
    private function addCritical($title, $description, $solution, $code = null) {
        $this->critical_issues[] = [
            'title' => $title,
            'description' => $description,
            'solution' => $solution,
            'code' => $code
        ];
    }
    
    private function addError($title, $description, $solution) {
        $this->errors[] = [
            'title' => $title,
            'description' => $description,
            'solution' => $solution
        ];
    }
    
    private function addWarning($title, $description, $solution) {
        $this->warnings[] = [
            'title' => $title,
            'description' => $description,
            'solution' => $solution
        ];
    }
    
    private function addSuggestion($title, $description, $solution) {
        $this->suggestions[] = [
            'title' => $title,
            'description' => $description,
            'solution' => $solution
        ];
    }
    
    private function outputSection($title) {
        if (php_sapi_name() === 'cli') {
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "$title\n";
            echo str_repeat("=", 60) . "\n";
        }
        // En modo web, los sections se muestran en el HTML
    }
    
    private function convertToBytes($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        $value = (int)$value;
        
        switch($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
}

// Ejecutar análisis
$analyzer = new CodeAnalysisSuite();
$analyzer->runFullAnalysis();
?>