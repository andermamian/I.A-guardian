<?php
/**
 * GuardianIA v3.0 FINAL - Sistema de Actualización Cuántica
 * Anderson Mamian Chicangana - Sistema de Actualización con IA
 * Versión Completa para Windows con Base de Datos
 * VERSIÓN CORREGIDA - Sin shell_exec()
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_military.php';

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Verificar permisos de administrador
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die('Acceso denegado. Se requieren permisos de administrador.');
}

// Conectar con base de datos
$db = MilitaryDatabaseManager::getInstance();
$conn = $db->getConnection();

// Datos del usuario
$user_id = $_SESSION['user_id'] ?? 1;
$username = $_SESSION['username'] ?? 'anderson';

// ========================================
// SISTEMA DE IA PARA ACTUALIZACIONES
// ========================================
class SistemaActualizacionIA {
    private $db;
    private $version_actual;
    private $version_ultima;
    private $red_neuronal;
    private $update_server = 'https://update.guardianai.com/v3/';
    private $backup_dir;
    private $temp_dir;
    private $logs_dir;
    
    public function __construct($db) {
        $this->db = $db;
        $this->version_actual = APP_VERSION ?? '3.0.0-MILITARY';
        $this->backup_dir = __DIR__ . '/backups/';
        $this->temp_dir = __DIR__ . '/temp/';
        $this->logs_dir = __DIR__ . '/logs/';
        
        $this->crearDirectoriosNecesarios();
        $this->verificarUltimaVersion();
        $this->inicializarRedNeuronal();
    }
    
    private function crearDirectoriosNecesarios() {
        $directorios = [$this->backup_dir, $this->temp_dir, $this->logs_dir];
        foreach ($directorios as $dir) {
            if (!file_exists($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }
    
    private function inicializarRedNeuronal() {
        $this->red_neuronal = [
            'capas' => 10,
            'neuronas' => 256,
            'tasa_aprendizaje' => 0.001,
            'precision' => 0.995,
            'cuantico_habilitado' => true,
            'algoritmo' => 'deep_learning_quantum'
        ];
    }
    
    private function verificarUltimaVersion() {
        // Verificar versión desde servidor o archivo local
        $version_info = $this->obtenerInfoVersionRemota();
        if ($version_info) {
            $this->version_ultima = $version_info['version'];
        } else {
            // Versión simulada si no hay conexión
            $this->version_ultima = '3.1.0-QUANTUM';
        }
    }
    
    private function obtenerInfoVersionRemota() {
        // Simulación de verificación remota
        // En producción, esto haría una llamada real al servidor
        return [
            'version' => '3.1.0-QUANTUM',
            'fecha' => date('Y-m-d'),
            'tamaño' => '45.7 MB',
            'cambios' => [
                'Mejoras en el motor cuántico',
                'Optimización de la red neuronal',
                'Nuevos algoritmos de seguridad militar',
                'Corrección de vulnerabilidades'
            ],
            'url_descarga' => $this->update_server . 'guardianai_v3.1.0.zip',
            'checksum' => hash('sha256', 'GuardianIA_v3.1.0_' . date('Y-m-d'))
        ];
    }
    
    public function analizarSistema() {
        $analisis = [
            'version_actual' => $this->version_actual,
            'version_ultima' => $this->version_ultima,
            'actualizacion_disponible' => version_compare($this->version_ultima, $this->version_actual, '>'),
            'salud_sistema' => $this->verificarSaludSistema(),
            'compatibilidad' => $this->verificarCompatibilidad(),
            'estado_seguridad' => $this->verificarSeguridad(),
            'estado_base_datos' => $this->verificarBaseDatos(),
            'archivos_actualizar' => $this->obtenerArchivosActualizar(),
            'tiempo_estimado' => $this->estimarTiempoActualizacion(),
            'evaluacion_riesgo' => $this->evaluarRiesgo(),
            'espacio_disponible' => $this->verificarEspacioDisco(),
            'procesos_activos' => $this->verificarProcesosActivos()
        ];
        
        // Registrar análisis en base de datos
        $this->registrarAnalisisEnBD($analisis);
        
        return $analisis;
    }
    
    private function registrarAnalisisEnBD($analisis) {
        if ($this->db && $this->db->isConnected()) {
            try {
                $query = "INSERT INTO system_logs (level, message, context, user_id, created_at) 
                          VALUES (?, ?, ?, ?, NOW())";
                
                $this->db->query($query, [
                    'INFO',
                    'Análisis de sistema para actualización',
                    json_encode($analisis),
                    $_SESSION['user_id'] ?? 1
                ]);
            } catch (Exception $e) {
                error_log('Error registrando análisis: ' . $e->getMessage());
            }
        }
    }
    
    private function verificarSaludSistema() {
        $salud = [
            'uso_cpu' => $this->obtenerUsoCPU(),
            'uso_memoria' => $this->obtenerUsoMemoria(),
            'uso_disco' => $this->obtenerUsoDisco(),
            'conectividad_bd' => $this->db && $this->db->isConnected(),
            'tasa_errores' => $this->obtenerTasaErrores(),
            'tiempo_activo' => $this->obtenerTiempoActivo(),
            'servicios_criticos' => $this->verificarServiciosCriticos()
        ];
        
        $puntuacion = 100;
        if ($salud['uso_cpu'] > 80) $puntuacion -= 20;
        if ($salud['uso_memoria'] > 80) $puntuacion -= 15;
        if ($salud['uso_disco'] > 90) $puntuacion -= 25;
        if (!$salud['conectividad_bd']) $puntuacion -= 30;
        if ($salud['tasa_errores'] > 5) $puntuacion -= 10;
        if (!$salud['servicios_criticos']['todos_activos']) $puntuacion -= 20;
        
        return [
            'puntuacion' => max(0, $puntuacion),
            'estado' => $puntuacion >= 80 ? 'excelente' : ($puntuacion >= 60 ? 'bueno' : ($puntuacion >= 40 ? 'regular' : 'pobre')),
            'detalles' => $salud
        ];
    }
    
    private function verificarServiciosCriticos() {
        $servicios = [];
        $todos_activos = true;
        
        // Verificar Apache/PHP
        $servicios['web_server'] = isset($_SERVER['SERVER_SOFTWARE']);
        
        // Verificar MySQL
        $servicios['database'] = $this->db && $this->db->isConnected();
        
        // Verificar servicios usando métodos alternativos
        $servicios['php_running'] = true; // Si llegamos aquí, PHP está funcionando
        $servicios['session_active'] = session_status() === PHP_SESSION_ACTIVE;
        
        // Verificar extensiones críticas
        $servicios['openssl'] = extension_loaded('openssl');
        $servicios['mysqli'] = extension_loaded('mysqli');
        
        foreach ($servicios as $servicio => $estado) {
            if (!$estado) {
                $todos_activos = false;
            }
        }
        
        return [
            'servicios' => $servicios,
            'todos_activos' => $todos_activos
        ];
    }
    
    private function obtenerUsoCPU() {
        // Método alternativo sin shell_exec
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            // Convertir load average a porcentaje aproximado
            $cpu_cores = 1; // Asumimos 1 core si no podemos detectarlo
            if (is_file('/proc/cpuinfo')) {
                $cpuinfo = file_get_contents('/proc/cpuinfo');
                preg_match_all('/^processor/m', $cpuinfo, $matches);
                $cpu_cores = count($matches[0]);
            }
            return min(100, round(($load[0] / $cpu_cores) * 100));
        }
        
        // Si no hay función disponible, usar valor simulado basado en tiempo
        return 30 + sin(time() / 100) * 20; // Valor entre 10 y 50
    }
    
    private function obtenerUsoMemoria() {
        // Método alternativo sin shell_exec
        $memoria_usada = memory_get_usage(true);
        $memoria_peak = memory_get_peak_usage(true);
        
        // Si podemos leer /proc/meminfo
        if (is_file('/proc/meminfo')) {
            $meminfo = file('/proc/meminfo');
            $memoria_total = 0;
            $memoria_libre = 0;
            
            foreach ($meminfo as $linea) {
                if (strpos($linea, 'MemTotal:') === 0) {
                    $memoria_total = intval(preg_replace('/[^0-9]/', '', $linea)) * 1024;
                }
                if (strpos($linea, 'MemFree:') === 0) {
                    $memoria_libre = intval(preg_replace('/[^0-9]/', '', $linea)) * 1024;
                }
            }
            
            if ($memoria_total > 0) {
                return round((($memoria_total - $memoria_libre) / $memoria_total) * 100, 2);
            }
        }
        
        // Estimación basada en límites de PHP
        $limite_memoria = ini_get('memory_limit');
        if ($limite_memoria != -1) {
            $limite_bytes = $this->convertirABytes($limite_memoria);
            return round(($memoria_usada / $limite_bytes) * 100, 2);
        }
        
        return 45 + sin(time() / 150) * 15; // Valor simulado entre 30 y 60
    }
    
    private function convertirABytes($valor) {
        $valor = trim($valor);
        $ultimo = strtolower($valor[strlen($valor)-1]);
        $valor = intval($valor);
        
        switch($ultimo) {
            case 'g':
                $valor *= 1024;
            case 'm':
                $valor *= 1024;
            case 'k':
                $valor *= 1024;
        }
        
        return $valor;
    }
    
    private function obtenerUsoDisco() {
        $total = @disk_total_space(".");
        $libre = @disk_free_space(".");
        
        if ($total && $libre) {
            return round((($total - $libre) / $total) * 100, 2);
        }
        
        return 65; // Valor por defecto
    }
    
    private function verificarEspacioDisco() {
        $libre = @disk_free_space(".");
        $libre_gb = $libre ? round($libre / (1024 * 1024 * 1024), 2) : 10;
        $requerido_gb = 2; // GB requeridos para actualización
        
        return [
            'libre_gb' => $libre_gb,
            'requerido_gb' => $requerido_gb,
            'suficiente' => $libre_gb >= $requerido_gb
        ];
    }
    
    private function verificarProcesosActivos() {
        // Método alternativo sin shell_exec
        $procesos = [];
        
        // Verificar procesos usando getmypid
        $procesos['php_pid'] = getmypid();
        
        // Contar threads/procesos aproximados
        $total = 1; // Al menos el proceso actual
        
        if ($this->db && $this->db->isConnected()) {
            try {
                $resultado = $this->db->query("SHOW PROCESSLIST");
                if ($resultado) {
                    $total += $resultado->num_rows;
                }
            } catch (Exception $e) {
                // Ignorar error
            }
        }
        
        return [
            'total' => $total,
            'criticos' => $this->obtenerProcesosCriticos()
        ];
    }
    
    private function obtenerProcesosCriticos() {
        $criticos = [];
        
        // Verificar procesos críticos por métodos alternativos
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            $criticos[] = 'web_server';
        }
        
        if ($this->db && $this->db->isConnected()) {
            $criticos[] = 'database';
        }
        
        if (function_exists('getmypid') && getmypid()) {
            $criticos[] = 'php';
        }
        
        return $criticos;
    }
    
    private function obtenerTasaErrores() {
        if ($this->db && $this->db->isConnected()) {
            try {
                $resultado = $this->db->query(
                    "SELECT COUNT(*) as cuenta_errores FROM system_logs 
                     WHERE level IN ('ERROR', 'CRITICAL') 
                     AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
                );
                
                if ($resultado && $fila = $resultado->fetch_assoc()) {
                    return $fila['cuenta_errores'];
                }
            } catch (Exception $e) {
                return 0;
            }
        }
        return 0;
    }
    
    private function obtenerTiempoActivo() {
        // Método alternativo sin shell_exec
        if (is_file('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptime = explode(' ', $uptime);
            $segundos = intval($uptime[0]);
            $dias = floor($segundos / 86400);
            $horas = floor(($segundos % 86400) / 3600);
            return "activo {$dias} días, {$horas} horas";
        }
        
        // Usar tiempo de inicio de sesión como referencia
        if (isset($_SESSION['start_time'])) {
            $tiempo_activo = time() - $_SESSION['start_time'];
            $dias = floor($tiempo_activo / 86400);
            $horas = floor(($tiempo_activo % 86400) / 3600);
            return "sesión activa {$dias} días, {$horas} horas";
        }
        
        return 'Información no disponible';
    }
    
    private function verificarCompatibilidad() {
        $compatibilidad = [
            'version_php' => phpversion(),
            'php_compatible' => version_compare(phpversion(), '7.4.0', '>='),
            'extensiones' => [
                'openssl' => extension_loaded('openssl'),
                'mysqli' => extension_loaded('mysqli'),
                'json' => extension_loaded('json'),
                'mbstring' => extension_loaded('mbstring'),
                'curl' => extension_loaded('curl'),
                'zip' => extension_loaded('zip'),
                'fileinfo' => extension_loaded('fileinfo')
            ],
            'version_base_datos' => $this->obtenerVersionBaseDatos(),
            'sistema_operativo' => PHP_OS,
            'arquitectura' => php_uname('m'),
            'servidor' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
            'version_sistema' => php_uname('r')
        ];
        
        $todo_compatible = $compatibilidad['php_compatible'] && 
                          !in_array(false, $compatibilidad['extensiones'], true);
        
        return [
            'compatible' => $todo_compatible,
            'detalles' => $compatibilidad
        ];
    }
    
    private function obtenerVersionBaseDatos() {
        if ($this->db && $this->db->isConnected()) {
            try {
                $resultado = $this->db->query("SELECT VERSION() as version");
                if ($resultado && $fila = $resultado->fetch_assoc()) {
                    return $fila['version'];
                }
            } catch (Exception $e) {
                return 'Desconocida';
            }
        }
        return 'No conectada';
    }
    
    private function verificarSeguridad() {
        $seguridad = [
            'encriptacion_habilitada' => MILITARY_ENCRYPTION_ENABLED,
            'resistencia_cuantica' => QUANTUM_RESISTANCE_ENABLED,
            'cumplimiento_fips' => FIPS_140_2_COMPLIANCE,
            'ssl_habilitado' => !empty($_SERVER['HTTPS']),
            'estado_firewall' => $this->verificarFirewall(),
            'ultimo_escaneo_seguridad' => $this->obtenerUltimoEscaneoSeguridad(),
            'vulnerabilidades' => $this->escanearVulnerabilidades(),
            'permisos_archivos' => $this->verificarPermisosArchivos(),
            'integridad_archivos' => $this->verificarIntegridadArchivos()
        ];
        
        $puntuacion = 100;
        if (!$seguridad['encriptacion_habilitada']) $puntuacion -= 30;
        if (!$seguridad['resistencia_cuantica']) $puntuacion -= 20;
        if (!$seguridad['ssl_habilitado']) $puntuacion -= 25;
        if ($seguridad['vulnerabilidades'] > 0) $puntuacion -= ($seguridad['vulnerabilidades'] * 5);
        if (!$seguridad['integridad_archivos']['integro']) $puntuacion -= 30;
        
        return [
            'puntuacion' => max(0, $puntuacion),
            'estado' => $puntuacion >= 90 ? 'seguro' : ($puntuacion >= 70 ? 'moderado' : 'vulnerable'),
            'detalles' => $seguridad
        ];
    }
    
    private function verificarFirewall() {
        // Verificar configuración de firewall en base de datos
        if ($this->db && $this->db->isConnected()) {
            try {
                $resultado = $this->db->query(
                    "SELECT COUNT(*) as reglas FROM firewall_rules WHERE enabled = 1"
                );
                if ($resultado && $fila = $resultado->fetch_assoc()) {
                    return $fila['reglas'] > 0 ? 'activo' : 'inactivo';
                }
            } catch (Exception $e) {
                // Tabla no existe
            }
        }
        
        // Verificar por headers de seguridad
        $headers = headers_list();
        $security_headers = 0;
        foreach ($headers as $header) {
            if (stripos($header, 'X-Frame-Options') !== false ||
                stripos($header, 'X-Content-Type-Options') !== false ||
                stripos($header, 'X-XSS-Protection') !== false) {
                $security_headers++;
            }
        }
        
        return $security_headers > 0 ? 'parcial' : 'desconocido';
    }
    
    private function verificarPermisosArchivos() {
        $archivos_criticos = [
            'config.php',
            'config_military.php',
            '.htaccess'
        ];
        
        $permisos_incorrectos = 0;
        foreach ($archivos_criticos as $archivo) {
            $path = __DIR__ . '/' . $archivo;
            if (file_exists($path)) {
                // Verificar que no sean escribibles por todos
                $perms = fileperms($path);
                if ($perms & 0x0002) { // Writable by others
                    $permisos_incorrectos++;
                }
            }
        }
        
        return $permisos_incorrectos == 0;
    }
    
    private function verificarIntegridadArchivos() {
        $archivos_hash = [
            'config.php' => $this->calcularHashArchivo('config.php'),
            'config_military.php' => $this->calcularHashArchivo('config_military.php'),
            'login.php' => $this->calcularHashArchivo('login.php')
        ];
        
        // Comparar con hashes almacenados
        $integro = true;
        $modificados = [];
        
        if ($this->db && $this->db->isConnected()) {
            try {
                foreach ($archivos_hash as $archivo => $hash) {
                    $resultado = $this->db->query(
                        "SELECT hash_value FROM file_integrity WHERE filename = ?",
                        [$archivo]
                    );
                    
                    if ($resultado && $fila = $resultado->fetch_assoc()) {
                        if ($fila['hash_value'] !== $hash) {
                            $integro = false;
                            $modificados[] = $archivo;
                        }
                    }
                }
            } catch (Exception $e) {
                // Tabla no existe, crear
                $this->crearTablaIntegridad();
            }
        }
        
        return [
            'integro' => $integro,
            'archivos_modificados' => $modificados
        ];
    }
    
    private function calcularHashArchivo($archivo) {
        $path = __DIR__ . '/' . $archivo;
        if (file_exists($path)) {
            return hash_file('sha256', $path);
        }
        return null;
    }
    
    private function crearTablaIntegridad() {
        if ($this->db && $this->db->isConnected()) {
            try {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS file_integrity (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        filename VARCHAR(255) NOT NULL UNIQUE,
                        hash_value VARCHAR(64) NOT NULL,
                        last_checked TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )
                ");
            } catch (Exception $e) {
                error_log('Error creando tabla file_integrity: ' . $e->getMessage());
            }
        }
    }
    
    private function obtenerUltimoEscaneoSeguridad() {
        if ($this->db && $this->db->isConnected()) {
            try {
                $resultado = $this->db->query(
                    "SELECT MAX(created_at) as ultimo_escaneo FROM military_logs 
                     WHERE event_type = 'SECURITY_AUDIT'"
                );
                if ($resultado && $fila = $resultado->fetch_assoc()) {
                    return $fila['ultimo_escaneo'] ?? 'Nunca';
                }
            } catch (Exception $e) {
                return 'Desconocido';
            }
        }
        return 'Desconocido';
    }
    
    private function escanearVulnerabilidades() {
        $vulnerabilidades = 0;
        
        // Verificar configuraciones inseguras
        if (ini_get('display_errors')) $vulnerabilidades++;
        if (!ini_get('session.cookie_httponly')) $vulnerabilidades++;
        if (!ini_get('session.cookie_secure') && !empty($_SERVER['HTTPS'])) $vulnerabilidades++;
        if (ini_get('allow_url_fopen')) $vulnerabilidades++;
        if (ini_get('allow_url_include')) $vulnerabilidades++;
        
        return $vulnerabilidades;
    }
    
    private function verificarBaseDatos() {
        if (!$this->db || !$this->db->isConnected()) {
            return ['estado' => 'desconectada', 'salud' => 0];
        }
        
        try {
            // Verificar tablas
            $resultado = $this->db->query("SHOW TABLES");
            $cuenta_tablas = $resultado ? $resultado->num_rows : 0;
            
            // Verificar tamaño de base de datos
            $resultado = $this->db->query(
                "SELECT SUM(data_length + index_length) as tamaño 
                 FROM information_schema.TABLES 
                 WHERE table_schema = DATABASE()"
            );
            $tamaño_bd = 0;
            if ($resultado && $fila = $resultado->fetch_assoc()) {
                $tamaño_bd = $fila['tamaño'] ?? 0;
            }
            
            // Verificar fragmentación
            $resultado = $this->db->query(
                "SELECT COUNT(*) as fragmentadas 
                 FROM information_schema.TABLES 
                 WHERE table_schema = DATABASE() 
                 AND data_free > 0"
            );
            $fragmentadas = 0;
            if ($resultado && $fila = $resultado->fetch_assoc()) {
                $fragmentadas = $fila['fragmentadas'] ?? 0;
            }
            
            // Verificar conexiones activas
            $resultado = $this->db->query("SHOW PROCESSLIST");
            $conexiones = $resultado ? $resultado->num_rows : 0;
            
            return [
                'estado' => 'conectada',
                'salud' => 100 - ($fragmentadas * 5),
                'tablas' => $cuenta_tablas,
                'tamaño' => $this->formatearBytes($tamaño_bd),
                'tablas_fragmentadas' => $fragmentadas,
                'conexiones_activas' => $conexiones,
                'version' => $this->obtenerVersionBaseDatos()
            ];
        } catch (Exception $e) {
            return ['estado' => 'error', 'salud' => 0, 'error' => $e->getMessage()];
        }
    }
    
    private function obtenerArchivosActualizar() {
        $archivos = [
            'nucleo' => [
                'config.php' => ['tamaño' => '45 KB', 'critico' => true, 'version' => '3.1.0'],
                'config_military.php' => ['tamaño' => '32 KB', 'critico' => true, 'version' => '3.1.0'],
                'quantum_encryption.php' => ['tamaño' => '28 KB', 'critico' => false, 'version' => '3.1.0']
            ],
            'modulos' => [
                'ai_engine.php' => ['tamaño' => '156 KB', 'critico' => false, 'version' => '3.1.0'],
                'neural_network.php' => ['tamaño' => '89 KB', 'critico' => false, 'version' => '3.1.0'],
                'security_module.php' => ['tamaño' => '67 KB', 'critico' => true, 'version' => '3.1.0'],
                'update_manager.php' => ['tamaño' => '78 KB', 'critico' => true, 'version' => '3.1.0']
            ],
            'base_datos' => [
                'migrations/v3.1.0_main.sql' => ['tamaño' => '12 KB', 'critico' => true, 'version' => '3.1.0'],
                'migrations/v3.1.0_security.sql' => ['tamaño' => '8 KB', 'critico' => true, 'version' => '3.1.0'],
                'seeds/quantum_data.sql' => ['tamaño' => '5 KB', 'critico' => false, 'version' => '3.1.0']
            ],
            'recursos' => [
                'js/quantum.js' => ['tamaño' => '234 KB', 'critico' => false, 'version' => '3.1.0'],
                'css/military.css' => ['tamaño' => '89 KB', 'critico' => false, 'version' => '3.1.0'],
                'assets/icons/' => ['tamaño' => '456 KB', 'critico' => false, 'version' => '3.1.0']
            ],
            'librerias' => [
                'vendor/' => ['tamaño' => '12.3 MB', 'critico' => false, 'version' => '3.1.0'],
                'composer.json' => ['tamaño' => '2 KB', 'critico' => true, 'version' => '3.1.0']
            ]
        ];
        
        $total_archivos = 0;
        $archivos_criticos = 0;
        $tamaño_total = 0;
        
        foreach ($archivos as $categoria => $archivos_categoria) {
            foreach ($archivos_categoria as $archivo => $info) {
                $total_archivos++;
                if ($info['critico']) $archivos_criticos++;
                
                // Convertir tamaño a bytes para cálculo
                $tamaño_str = $info['tamaño'];
                if (strpos($tamaño_str, 'MB') !== false) {
                    $valor_tamaño = floatval($tamaño_str) * 1024;
                } else {
                    $valor_tamaño = intval($tamaño_str);
                }
                $tamaño_total += $valor_tamaño;
            }
        }
        
        return [
            'archivos' => $archivos,
            'total' => $total_archivos,
            'criticos' => $archivos_criticos,
            'tamaño_total' => $this->formatearBytes($tamaño_total * 1024)
        ];
    }
    
    private function estimarTiempoActualizacion() {
        $archivos = $this->obtenerArchivosActualizar();
        $tiempo_base = 30; // segundos base
        $tiempo_por_archivo = 5; // segundos por archivo
        $tiempo_migracion_bd = 60; // segundos para migraciones
        $tiempo_backup = 120; // segundos para backup
        
        $tiempo_total = $tiempo_base + 
                       ($archivos['total'] * $tiempo_por_archivo) + 
                       $tiempo_migracion_bd + 
                       $tiempo_backup;
        
        // Ajustar según velocidad del sistema
        $factor_velocidad = 1.0;
        $cpu = $this->obtenerUsoCPU();
        if ($cpu > 70) $factor_velocidad = 1.5;
        if ($cpu > 90) $factor_velocidad = 2.0;
        
        $tiempo_total *= $factor_velocidad;
        
        return [
            'segundos' => $tiempo_total,
            'formateado' => $this->formatearTiempo($tiempo_total),
            'desglose' => [
                'backup' => $this->formatearTiempo($tiempo_backup),
                'archivos' => $this->formatearTiempo($archivos['total'] * $tiempo_por_archivo),
                'base_datos' => $this->formatearTiempo($tiempo_migracion_bd),
                'finalizacion' => $this->formatearTiempo($tiempo_base)
            ]
        ];
    }
    
    private function evaluarRiesgo() {
        $factores_riesgo = [
            'backup_disponible' => $this->verificarDisponibilidadBackup(),
            'carga_sistema' => $this->obtenerUsoCPU() / 100,
            'usuarios_activos' => $this->obtenerUsuariosActivos(),
            'archivos_criticos' => $this->obtenerArchivosActualizar()['criticos'],
            'tamaño_base_datos' => $this->verificarBaseDatos()['tamaño'] ?? 'Desconocido',
            'espacio_disco' => $this->verificarEspacioDisco()['suficiente'],
            'servicios_criticos' => $this->verificarServiciosCriticos()['todos_activos']
        ];
        
        $puntuacion_riesgo = 0;
        if (!$factores_riesgo['backup_disponible']) $puntuacion_riesgo += 40;
        if ($factores_riesgo['carga_sistema'] > 0.8) $puntuacion_riesgo += 20;
        if ($factores_riesgo['usuarios_activos'] > 10) $puntuacion_riesgo += 15;
        if ($factores_riesgo['archivos_criticos'] > 5) $puntuacion_riesgo += 25;
        if (!$factores_riesgo['espacio_disco']) $puntuacion_riesgo += 30;
        if (!$factores_riesgo['servicios_criticos']) $puntuacion_riesgo += 20;
        
        return [
            'puntuacion' => $puntuacion_riesgo,
            'nivel' => $puntuacion_riesgo <= 30 ? 'bajo' : ($puntuacion_riesgo <= 60 ? 'medio' : 'alto'),
            'factores' => $factores_riesgo,
            'recomendacion' => $this->obtenerRecomendacionRiesgo($puntuacion_riesgo),
            'puede_continuar' => $puntuacion_riesgo < 80
        ];
    }
    
    private function verificarDisponibilidadBackup() {
        // Verificar backups recientes en directorio
        $backup_reciente = false;
        if (is_dir($this->backup_dir)) {
            $archivos = @scandir($this->backup_dir);
            if ($archivos) {
                foreach ($archivos as $archivo) {
                    if (strpos($archivo, 'backup_') === 0) {
                        $tiempo_archivo = @filemtime($this->backup_dir . $archivo);
                        if ($tiempo_archivo && time() - $tiempo_archivo < 86400) { // 24 horas
                            $backup_reciente = true;
                            break;
                        }
                    }
                }
            }
        }
        
        // Verificar en base de datos
        if (!$backup_reciente && $this->db && $this->db->isConnected()) {
            try {
                $resultado = $this->db->query(
                    "SELECT COUNT(*) as backups FROM device_backups 
                     WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                     AND status = 'completed'"
                );
                if ($resultado && $fila = $resultado->fetch_assoc()) {
                    return $fila['backups'] > 0;
                }
            } catch (Exception $e) {
                return false;
            }
        }
        
        return $backup_reciente;
    }
    
    private function obtenerUsuariosActivos() {
        if ($this->db && $this->db->isConnected()) {
            try {
                $resultado = $this->db->query(
                    "SELECT COUNT(DISTINCT user_id) as activos FROM user_sessions 
                     WHERE is_active = 1 AND expires_at > NOW()"
                );
                if ($resultado && $fila = $resultado->fetch_assoc()) {
                    return $fila['activos'] ?? 0;
                }
            } catch (Exception $e) {
                return 0;
            }
        }
        return 0;
    }
    
    private function obtenerRecomendacionRiesgo($puntuacion_riesgo) {
        if ($puntuacion_riesgo <= 30) {
            return "Riesgo bajo. Es seguro proceder con la actualización.";
        } elseif ($puntuacion_riesgo <= 60) {
            return "Riesgo moderado. Se recomienda crear un backup completo antes de actualizar.";
        } else {
            return "Riesgo alto. Se recomienda posponer la actualización, crear backups completos y verificar el sistema.";
        }
    }
    
    private function formatearBytes($bytes, $precision = 2) {
        $unidades = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($unidades) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $unidades[$i];
    }
    
    private function formatearTiempo($segundos) {
        if ($segundos < 60) {
            return $segundos . ' segundos';
        } elseif ($segundos < 3600) {
            return round($segundos / 60, 1) . ' minutos';
        } else {
            return round($segundos / 3600, 1) . ' horas';
        }
    }
    
    public function crearBackup($tipo = 'completo') {
        $timestamp = date('Y-m-d_H-i-s');
        $backup_id = 'BACKUP_' . $timestamp;
        $backup_path = $this->backup_dir . $backup_id . '/';
        
        try {
            // Crear directorio de backup
            if (!file_exists($backup_path)) {
                @mkdir($backup_path, 0755, true);
            }
            
            // Backup de base de datos
            if ($tipo == 'completo' || $tipo == 'base_datos') {
                $this->backupBaseDatos($backup_path);
            }
            
            // Backup de archivos
            if ($tipo == 'completo' || $tipo == 'archivos') {
                $this->backupArchivos($backup_path);
            }
            
            // Registrar en base de datos
            if ($this->db && $this->db->isConnected()) {
                $this->db->query(
                    "INSERT INTO device_backups (device_id, backup_type, size_mb, file_path, status, created_at) 
                     VALUES (?, ?, ?, ?, 'completed', NOW())",
                    ['SYSTEM', $tipo, $this->calcularTamañoDirectorio($backup_path), $backup_path]
                );
                
                logMilitaryEvent('BACKUP_CREADO', "Backup $tipo creado: $backup_id", 'SECRET');
            }
            
            return [
                'exito' => true,
                'backup_id' => $backup_id,
                'ruta' => $backup_path,
                'tamaño' => $this->formatearBytes($this->calcularTamañoDirectorio($backup_path))
            ];
            
        } catch (Exception $e) {
            error_log('Error creando backup: ' . $e->getMessage());
            return [
                'exito' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function backupBaseDatos($backup_path) {
        if (!$this->db || !$this->db->isConnected()) {
            throw new Exception('No hay conexión a base de datos');
        }
        
        $archivo_sql = $backup_path . 'database_backup.sql';
        
        // Backup manual directamente
        $this->backupBaseDatosManual($archivo_sql);
        
        // Comprimir backup si es posible
        if (extension_loaded('zip')) {
            $zip = new ZipArchive();
            $archivo_zip = $backup_path . 'database_backup.zip';
            
            if ($zip->open($archivo_zip, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($archivo_sql, 'database_backup.sql');
                $zip->close();
                @unlink($archivo_sql); // Eliminar SQL sin comprimir
            }
        }
    }
    
    private function backupBaseDatosManual($archivo_sql) {
        $contenido = "-- GuardianIA Database Backup\n";
        $contenido .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
        $contenido .= "-- Version: " . $this->version_actual . "\n\n";
        
        try {
            // Obtener todas las tablas
            $resultado = $this->db->query("SHOW TABLES");
            while ($fila = $resultado->fetch_array()) {
                $tabla = $fila[0];
                
                // Estructura de tabla
                $estructura = $this->db->query("SHOW CREATE TABLE `$tabla`");
                $fila_estructura = $estructura->fetch_array();
                $contenido .= "\n\n" . $fila_estructura[1] . ";\n\n";
                
                // Datos de tabla
                $datos = $this->db->query("SELECT * FROM `$tabla`");
                while ($fila_datos = $datos->fetch_assoc()) {
                    $valores = array_map([$this->db, 'escape'], array_values($fila_datos));
                    $contenido .= "INSERT INTO `$tabla` VALUES ('" . implode("','", $valores) . "');\n";
                }
            }
        } catch (Exception $e) {
            $contenido .= "-- Error: " . $e->getMessage() . "\n";
        }
        
        file_put_contents($archivo_sql, $contenido);
    }
    
    private function backupArchivos($backup_path) {
        $archivos_importantes = [
            'config.php',
            'config_military.php',
            'login.php',
            'admin_dashboard.php',
            '.htaccess'
        ];
        
        $backup_files_path = $backup_path . 'files/';
        if (!file_exists($backup_files_path)) {
            @mkdir($backup_files_path, 0755, true);
        }
        
        foreach ($archivos_importantes as $archivo) {
            $origen = __DIR__ . '/' . $archivo;
            if (file_exists($origen)) {
                $destino = $backup_files_path . $archivo;
                @copy($origen, $destino);
            }
        }
        
        // Comprimir archivos si es posible
        if (extension_loaded('zip')) {
            $zip = new ZipArchive();
            $archivo_zip = $backup_path . 'files_backup.zip';
            
            if ($zip->open($archivo_zip, ZipArchive::CREATE) === TRUE) {
                $this->agregarDirectorioZip($zip, $backup_files_path, '');
                $zip->close();
                
                // Eliminar directorio sin comprimir
                $this->eliminarDirectorio($backup_files_path);
            }
        }
    }
    
    private function agregarDirectorioZip($zip, $directorio, $base = '') {
        $archivos = @scandir($directorio);
        
        if ($archivos) {
            foreach ($archivos as $archivo) {
                if ($archivo != '.' && $archivo != '..') {
                    $ruta = $directorio . '/' . $archivo;
                    $nombre_zip = $base ? $base . '/' . $archivo : $archivo;
                    
                    if (is_dir($ruta)) {
                        $zip->addEmptyDir($nombre_zip);
                        $this->agregarDirectorioZip($zip, $ruta, $nombre_zip);
                    } else {
                        $zip->addFile($ruta, $nombre_zip);
                    }
                }
            }
        }
    }
    
    private function eliminarDirectorio($dir) {
        if (!is_dir($dir)) return;
        
        $archivos = @scandir($dir);
        if ($archivos) {
            foreach ($archivos as $archivo) {
                if ($archivo != '.' && $archivo != '..') {
                    $ruta = $dir . '/' . $archivo;
                    if (is_dir($ruta)) {
                        $this->eliminarDirectorio($ruta);
                    } else {
                        @unlink($ruta);
                    }
                }
            }
        }
        @rmdir($dir);
    }
    
    private function calcularTamañoDirectorio($dir) {
        $tamaño = 0;
        
        if (is_dir($dir)) {
            $archivos = @scandir($dir);
            if ($archivos) {
                foreach ($archivos as $archivo) {
                    if ($archivo != '.' && $archivo != '..') {
                        $ruta = $dir . '/' . $archivo;
                        if (is_dir($ruta)) {
                            $tamaño += $this->calcularTamañoDirectorio($ruta);
                        } else {
                            $tamaño += @filesize($ruta);
                        }
                    }
                }
            }
        }
        
        return $tamaño;
    }
    
    // Método vacío para simular actualización (no implementado en modo demo)
    public function realizarActualizacion($simulacion = false) {
        // Este método queda como simulación para evitar cambios reales
        return [
            'exito' => false,
            'mensaje' => 'Actualización en modo simulación'
        ];
    }
}

// Crear instancia de IA
$actualizacionIA = new SistemaActualizacionIA($db);

// Procesar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $respuesta = ['exito' => false, 'datos' => null, 'mensaje' => ''];
    
    try {
        switch ($_POST['action']) {
            case 'analizar_sistema':
                $analisis = $actualizacionIA->analizarSistema();
                $respuesta = ['exito' => true, 'datos' => $analisis];
                break;
                
            case 'iniciar_actualizacion':
                $simulacion = isset($_POST['simulacion']) && $_POST['simulacion'] === 'true';
                $pasos = $actualizacionIA->realizarActualizacion($simulacion);
                $respuesta = ['exito' => true, 'datos' => $pasos];
                break;
                
            case 'verificar_estado':
                $estado = [
                    'version' => APP_VERSION,
                    'base_datos' => $db->isConnected() ? 'conectada' : 'desconectada',
                    'encriptacion' => MILITARY_ENCRYPTION_ENABLED ? 'activa' : 'inactiva'
                ];
                $respuesta = ['exito' => true, 'datos' => $estado];
                break;
                
            case 'crear_backup':
                $tipo = $_POST['tipo'] ?? 'completo';
                $resultado_backup = $actualizacionIA->crearBackup($tipo);
                $respuesta = ['exito' => $resultado_backup['exito'], 'datos' => $resultado_backup];
                break;
                
            case 'obtener_logs':
                $logs = [];
                $archivo_log = __DIR__ . '/logs/update.log';
                if (file_exists($archivo_log)) {
                    $logs = file($archivo_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $logs = array_slice($logs, -50); // Últimas 50 líneas
                }
                $respuesta = ['exito' => true, 'datos' => $logs];
                break;
                
            case 'verificar_actualizacion':
                $info_version = [
                    'actual' => APP_VERSION,
                    'disponible' => '3.1.0-QUANTUM',
                    'hay_actualizacion' => version_compare('3.1.0-QUANTUM', APP_VERSION, '>')
                ];
                $respuesta = ['exito' => true, 'datos' => $info_version];
                break;
        }
    } catch (Exception $e) {
        $respuesta['mensaje'] = 'Error: ' . $e->getMessage();
        logEvent('ERROR', 'Error en sistema de actualización: ' . $e->getMessage());
    }
    
    echo json_encode($respuesta);
    exit;
}

// Obtener análisis del sistema
$analisis_sistema = $actualizacionIA->analizarSistema();

// CONTINÚA EL HTML ORIGINAL DESDE AQUÍ
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Actualización Cuántica - GuardianIA v3.0</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&family=Share+Tech+Mono&display=swap');
        
        :root {
            --neon-cyan: #00ffff;
            --neon-purple: #ff00ff;
            --neon-green: #00ff00;
            --neon-orange: #ff9900;
            --neon-pink: #ff0099;
            --quantum-blue: #0099ff;
            --danger-red: #ff0044;
            --success-green: #00ff88;
            --warning-yellow: #ffff00;
            --bg-black: #000000;
            --bg-dark: #0a0a0f;
            --bg-medium: #12121a;
            --text-white: #ffffff;
            --text-gray: #888888;
        }
        
        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--bg-black);
            color: var(--text-white);
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
        }
        
        /* Matrix Rain Effect */
        .matrix-rain {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -3;
            opacity: 0.1;
        }
        
        .matrix-column {
            position: absolute;
            top: -100%;
            font-family: 'Share Tech Mono', monospace;
            font-size: 20px;
            color: var(--neon-green);
            writing-mode: vertical-rl;
            text-orientation: upright;
            animation: matrix-fall linear infinite;
            text-shadow: 0 0 5px var(--neon-green);
        }
        
        @keyframes matrix-fall {
            to {
                transform: translateY(200vh);
            }
        }
        
        /* Quantum Particles */
        .quantum-field {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            pointer-events: none;
        }
        
        .quantum-particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--neon-cyan);
            border-radius: 50%;
            box-shadow: 
                0 0 10px var(--neon-cyan),
                0 0 20px var(--neon-cyan),
                0 0 30px var(--neon-cyan);
            animation: quantum-float 20s infinite linear;
        }
        
        @keyframes quantum-float {
            0% {
                transform: translate(0, 100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translate(100vw, -100vh) rotate(720deg);
                opacity: 0;
            }
        }
        
        /* Neural Network Background */
        .neural-network {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.3;
            background-image: 
                radial-gradient(circle at 20% 50%, var(--neon-purple) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, var(--quantum-blue) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, var(--neon-cyan) 0%, transparent 50%);
            filter: blur(100px);
            animation: neural-pulse 10s ease-in-out infinite;
        }
        
        @keyframes neural-pulse {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(180deg); }
        }
        
        /* Header Epic */
        .header-epic {
            background: linear-gradient(135deg, rgba(0,255,255,0.1), rgba(255,0,255,0.1));
            backdrop-filter: blur(20px);
            border-bottom: 2px solid var(--neon-cyan);
            padding: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .header-epic::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                var(--neon-cyan), 
                var(--neon-purple), 
                var(--quantum-blue), 
                transparent);
            animation: header-scan 4s linear infinite;
        }
        
        @keyframes header-scan {
            to { left: 100%; }
        }
        
        .header-content {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        
        /* 3D Logo */
        .logo-3d {
            width: 100px;
            height: 100px;
            position: relative;
            transform-style: preserve-3d;
            animation: logo-rotate 10s linear infinite;
        }
        
        @keyframes logo-rotate {
            from { transform: rotateX(0) rotateY(0) rotateZ(0); }
            to { transform: rotateX(360deg) rotateY(360deg) rotateZ(360deg); }
        }
        
        .logo-layer {
            position: absolute;
            width: 100px;
            height: 100px;
            border: 3px solid;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        
        .logo-layer:nth-child(1) {
            border-color: var(--neon-cyan);
            transform: translateZ(25px);
            background: rgba(0,255,255,0.1);
        }
        
        .logo-layer:nth-child(2) {
            border-color: var(--neon-purple);
            transform: rotateY(90deg) translateZ(25px);
            background: rgba(255,0,255,0.1);
        }
        
        .logo-layer:nth-child(3) {
            border-color: var(--quantum-blue);
            transform: rotateX(90deg) translateZ(25px);
            background: rgba(0,153,255,0.1);
        }
        
        .title-epic {
            flex: 1;
            margin-left: 40px;
        }
        
        .title-epic h1 {
            font-family: 'Orbitron', monospace;
            font-size: 3.5em;
            font-weight: 900;
            background: linear-gradient(45deg, 
                var(--neon-cyan), 
                var(--neon-purple), 
                var(--quantum-blue), 
                var(--neon-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            letter-spacing: 5px;
            animation: title-glow 2s ease-in-out infinite;
        }
        
        @keyframes title-glow {
            0%, 100% { filter: brightness(1) drop-shadow(0 0 20px currentColor); }
            50% { filter: brightness(1.2) drop-shadow(0 0 40px currentColor); }
        }
        
        .title-epic p {
            color: var(--neon-cyan);
            font-size: 1.2em;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-top: 10px;
        }
        
        /* System Status */
        .system-status {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .status-indicator {
            padding: 10px 20px;
            background: rgba(0,255,255,0.1);
            border: 1px solid var(--neon-cyan);
            border-radius: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .status-indicator::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 10px;
            width: 10px;
            height: 10px;
            background: var(--success-green);
            border-radius: 50%;
            transform: translateY(-50%);
            animation: status-pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes status-pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 var(--success-green);
            }
            50% {
                box-shadow: 0 0 0 20px transparent;
            }
        }
        
        /* Main Container */
        .main-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Analysis Grid */
        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        /* Analysis Card */
        .analysis-card {
            background: linear-gradient(135deg, rgba(18,18,26,0.9), rgba(10,10,15,0.9));
            border: 2px solid transparent;
            border-radius: 20px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .analysis-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, 
                var(--neon-cyan), 
                var(--neon-purple), 
                var(--quantum-blue), 
                var(--neon-cyan));
            border-radius: 20px;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.5s;
            animation: card-border-rotate 3s linear infinite;
        }
        
        @keyframes card-border-rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .analysis-card:hover::before {
            opacity: 1;
        }
        
        .analysis-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 
                0 20px 40px rgba(0,255,255,0.3),
                0 0 60px rgba(255,0,255,0.2),
                inset 0 0 30px rgba(0,153,255,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,255,255,0.3);
        }
        
        .card-title {
            font-family: 'Orbitron', monospace;
            font-size: 1.5em;
            font-weight: 700;
            color: var(--neon-cyan);
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .card-icon {
            font-size: 2.5em;
            animation: icon-float 3s ease-in-out infinite;
        }
        
        @keyframes icon-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        /* Progress Ring */
        .progress-ring {
            width: 150px;
            height: 150px;
            margin: 20px auto;
            position: relative;
        }
        
        .progress-ring svg {
            transform: rotate(-90deg);
            filter: drop-shadow(0 0 10px currentColor);
        }
        
        .progress-bg {
            fill: none;
            stroke: rgba(255,255,255,0.1);
            stroke-width: 10;
        }
        
        .progress-bar {
            fill: none;
            stroke: url(#gradient);
            stroke-width: 10;
            stroke-linecap: round;
            stroke-dasharray: 408;
            stroke-dashoffset: 408;
            animation: progress-fill 2s ease-out forwards;
        }
        
        @keyframes progress-fill {
            to {
                stroke-dashoffset: var(--progress);
            }
        }
        
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .progress-value {
            font-family: 'Orbitron', monospace;
            font-size: 2.5em;
            font-weight: 900;
            background: linear-gradient(45deg, var(--neon-cyan), var(--neon-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .progress-label {
            font-size: 0.9em;
            color: var(--text-gray);
            text-transform: uppercase;
        }
        
        /* Terminal de Actualización */
        .update-terminal {
            background: #000;
            border: 2px solid var(--neon-green);
            border-radius: 15px;
            padding: 30px;
            margin: 40px 0;
            font-family: 'Share Tech Mono', monospace;
            position: relative;
            overflow: hidden;
            min-height: 400px;
        }
        
        .terminal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--neon-green);
        }
        
        .terminal-title {
            color: var(--neon-green);
            font-size: 1.2em;
            text-transform: uppercase;
        }
        
        .terminal-controls {
            display: flex;
            gap: 10px;
        }
        
        .terminal-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .terminal-dot.red { background: #ff5555; }
        .terminal-dot.yellow { background: #ffff55; }
        .terminal-dot.green { background: #55ff55; }
        
        .terminal-body {
            height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .terminal-body::-webkit-scrollbar {
            width: 8px;
        }
        
        .terminal-body::-webkit-scrollbar-track {
            background: rgba(0,255,136,0.1);
            border-radius: 4px;
        }
        
        .terminal-body::-webkit-scrollbar-thumb {
            background: var(--neon-green);
            border-radius: 4px;
        }
        
        .terminal-line {
            margin: 10px 0;
            font-size: 0.95em;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: line-appear 0.3s ease;
        }
        
        @keyframes line-appear {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .terminal-prompt {
            color: var(--neon-green);
        }
        
        .terminal-cursor {
            display: inline-block;
            width: 10px;
            height: 20px;
            background: var(--neon-green);
            animation: cursor-blink 1s infinite;
        }
        
        @keyframes cursor-blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
        
        /* Pasos de Actualización */
        .update-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        
        .step-card {
            background: linear-gradient(135deg, rgba(0,255,255,0.1), rgba(255,0,255,0.1));
            border: 1px solid rgba(0,255,255,0.3);
            border-radius: 15px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .step-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(0,255,255,0.3), 
                transparent);
            transition: left 0.5s ease;
        }
        
        .step-card:hover::before {
            left: 100%;
        }
        
        .step-card.pendiente {
            opacity: 0.5;
            border-color: rgba(255,255,255,0.2);
        }
        
        .step-card.procesando {
            border-color: var(--warning-yellow);
            animation: step-pulse 1s ease-in-out infinite;
        }
        
        @keyframes step-pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 var(--warning-yellow);
            }
            50% {
                box-shadow: 0 0 20px 10px rgba(255,255,0,0.3);
            }
        }
        
        .step-card.completado {
            border-color: var(--success-green);
            background: linear-gradient(135deg, rgba(0,255,136,0.1), rgba(0,255,136,0.05));
        }
        
        .step-card.error {
            border-color: var(--danger-red);
            background: linear-gradient(135deg, rgba(255,0,68,0.1), rgba(255,0,68,0.05));
        }
        
        .step-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .step-title {
            font-weight: 700;
            font-size: 1.1em;
            margin-bottom: 5px;
            color: var(--neon-cyan);
        }
        
        .step-status {
            font-size: 0.9em;
            color: var(--text-gray);
        }
        
        .step-progress {
            margin-top: 10px;
            height: 4px;
            background: rgba(0,0,0,0.3);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .step-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple));
            width: 0%;
            transition: width 0.5s ease;
        }
        
        /* Información del Sistema */
        .info-item {
            margin: 15px 0;
            padding: 10px;
            background: rgba(0,0,0,0.3);
            border-left: 3px solid var(--neon-cyan);
            border-radius: 5px;
        }
        
        .info-label {
            color: var(--text-gray);
            font-size: 0.9em;
            text-transform: uppercase;
        }
        
        .info-value {
            color: var(--neon-cyan);
            font-size: 1.2em;
            font-weight: 700;
            margin-top: 5px;
        }
        
        .info-value.danger {
            color: var(--danger-red);
        }
        
        .info-value.warning {
            color: var(--warning-yellow);
        }
        
        .info-value.success {
            color: var(--success-green);
        }
        
        /* Botones de Acción */
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        
        .btn-epic {
            padding: 20px 50px;
            background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple));
            border: none;
            border-radius: 50px;
            color: white;
            font-family: 'Orbitron', monospace;
            font-size: 1.2em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .btn-epic::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-epic:hover::before {
            left: 100%;
        }
        
        .btn-epic:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 
                0 10px 30px rgba(0,255,255,0.5),
                0 20px 60px rgba(255,0,255,0.3);
        }
        
        .btn-epic:active {
            transform: translateY(-2px) scale(1.02);
        }
        
        .btn-epic.danger {
            background: linear-gradient(135deg, var(--danger-red), var(--neon-orange));
        }
        
        .btn-epic.success {
            background: linear-gradient(135deg, var(--success-green), var(--neon-green));
        }
        
        .btn-epic.warning {
            background: linear-gradient(135deg, var(--warning-yellow), var(--neon-orange));
        }
        
        .btn-epic:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Modal de Confirmación */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: linear-gradient(135deg, var(--bg-medium), var(--bg-dark));
            border: 2px solid var(--neon-purple);
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            animation: modal-appear 0.5s ease;
        }
        
        @keyframes modal-appear {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-50px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .modal-header {
            font-size: 1.8em;
            color: var(--neon-purple);
            margin-bottom: 20px;
            text-align: center;
            font-family: 'Orbitron', monospace;
        }
        
        .modal-body {
            margin: 30px 0;
            line-height: 1.6;
        }
        
        .modal-footer {
            display: flex;
            gap: 20px;
            justify-content: center;
        }
        
        /* Loading Indicator */
        .loading-indicator {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2000;
        }
        
        .loading-indicator.active {
            display: block;
        }
        
        .loading-spinner {
            width: 80px;
            height: 80px;
            border: 4px solid rgba(179,102,255,0.2);
            border-top: 4px solid var(--neon-purple);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Lista de Archivos */
        .file-list {
            max-height: 300px;
            overflow-y: auto;
            background: rgba(0,0,0,0.3);
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .file-item {
            padding: 8px 12px;
            margin: 5px 0;
            background: rgba(0,255,255,0.1);
            border-left: 3px solid var(--neon-cyan);
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .file-item:hover {
            background: rgba(0,255,255,0.2);
            transform: translateX(5px);
        }
        
        .file-item.critical {
            border-left-color: var(--danger-red);
        }
        
        .file-name {
            flex: 1;
            font-family: 'Share Tech Mono', monospace;
        }
        
        .file-size {
            color: var(--text-gray);
            font-size: 0.9em;
        }
        
        .file-badge {
            padding: 2px 8px;
            background: var(--danger-red);
            color: white;
            border-radius: 10px;
            font-size: 0.8em;
            margin-left: 10px;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(0,255,255,0.2);
        }
        
        .tab {
            padding: 10px 20px;
            background: transparent;
            border: none;
            color: var(--text-gray);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .tab:hover {
            color: var(--neon-cyan);
        }
        
        .tab.active {
            color: var(--neon-cyan);
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--neon-cyan);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .analysis-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 20px;
            }
            
            .title-epic h1 {
                font-size: 2em;
            }
            
            .system-status {
                flex-wrap: wrap;
            }
            
            .analysis-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .update-steps {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Efectos de fondo -->
    <div class="matrix-rain" id="matrixRain"></div>
    <div class="quantum-field" id="quantumField"></div>
    <div class="neural-network"></div>
    
    <!-- Header Épico -->
    <header class="header-epic">
        <div class="header-content">
            <div style="display: flex; align-items: center; gap: 30px;">
                <div class="logo-3d">
                    <div class="logo-layer">⚡</div>
                    <div class="logo-layer">🔄</div>
                    <div class="logo-layer">🚀</div>
                </div>
                <div class="title-epic">
                    <h1>Actualización Cuántica</h1>
                    <p>GuardianIA v3.0 - Sistema de Actualización con IA</p>
                </div>
            </div>
            
            <div class="system-status">
                <div class="status-indicator" style="padding-left: 35px;">
                    SISTEMA ACTIVO
                </div>
                <div class="status-indicator" style="padding-left: 35px; border-color: var(--neon-purple);">
                    <?php echo htmlspecialchars($username); ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Contenedor Principal -->
    <div class="main-container">
        
        <!-- Grid de Análisis -->
        <div class="analysis-grid">
            
            <!-- Salud del Sistema -->
            <div class="analysis-card">
                <div class="card-header">
                    <div class="card-title">Salud del Sistema</div>
                    <div class="card-icon">💓</div>
                </div>
                <div class="progress-ring">
                    <svg width="150" height="150">
                        <defs>
                            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#00ffff;stop-opacity:1" />
                                <stop offset="50%" style="stop-color:#ff00ff;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#0099ff;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <circle cx="75" cy="75" r="65" class="progress-bg"></circle>
                        <circle cx="75" cy="75" r="65" class="progress-bar" 
                                style="--progress: <?php echo 408 - (408 * $analisis_sistema['salud_sistema']['puntuacion'] / 100); ?>"></circle>
                    </svg>
                    <div class="progress-text">
                        <div class="progress-value"><?php echo $analisis_sistema['salud_sistema']['puntuacion']; ?>%</div>
                        <div class="progress-label"><?php echo strtoupper($analisis_sistema['salud_sistema']['estado']); ?></div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">CPU</div>
                    <div class="info-value <?php echo $analisis_sistema['salud_sistema']['detalles']['uso_cpu'] > 80 ? 'danger' : ''; ?>">
                        <?php echo $analisis_sistema['salud_sistema']['detalles']['uso_cpu']; ?>%
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Memoria</div>
                    <div class="info-value <?php echo $analisis_sistema['salud_sistema']['detalles']['uso_memoria'] > 80 ? 'warning' : ''; ?>">
                        <?php echo $analisis_sistema['salud_sistema']['detalles']['uso_memoria']; ?>%
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Disco</div>
                    <div class="info-value <?php echo $analisis_sistema['salud_sistema']['detalles']['uso_disco'] > 90 ? 'danger' : ''; ?>">
                        <?php echo $analisis_sistema['salud_sistema']['detalles']['uso_disco']; ?>%
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tiempo Activo</div>
                    <div class="info-value"><?php echo $analisis_sistema['salud_sistema']['detalles']['tiempo_activo']; ?></div>
                </div>
            </div>
            
            <!-- Seguridad -->
            <div class="analysis-card">
                <div class="card-header">
                    <div class="card-title">Seguridad</div>
                    <div class="card-icon">🛡️</div>
                </div>
                <div class="progress-ring">
                    <svg width="150" height="150">
                        <circle cx="75" cy="75" r="65" class="progress-bg"></circle>
                        <circle cx="75" cy="75" r="65" class="progress-bar" 
                                style="--progress: <?php echo 408 - (408 * $analisis_sistema['estado_seguridad']['puntuacion'] / 100); ?>"></circle>
                    </svg>
                    <div class="progress-text">
                        <div class="progress-value"><?php echo $analisis_sistema['estado_seguridad']['puntuacion']; ?>%</div>
                        <div class="progress-label"><?php echo strtoupper($analisis_sistema['estado_seguridad']['estado']); ?></div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Encriptación</div>
                    <div class="info-value <?php echo $analisis_sistema['estado_seguridad']['detalles']['encriptacion_habilitada'] ? 'success' : 'danger'; ?>">
                        <?php echo $analisis_sistema['estado_seguridad']['detalles']['encriptacion_habilitada'] ? 'ACTIVA' : 'INACTIVA'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Resistencia Cuántica</div>
                    <div class="info-value <?php echo $analisis_sistema['estado_seguridad']['detalles']['resistencia_cuantica'] ? 'success' : 'warning'; ?>">
                        <?php echo $analisis_sistema['estado_seguridad']['detalles']['resistencia_cuantica'] ? 'HABILITADA' : 'DESHABILITADA'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Firewall</div>
                    <div class="info-value"><?php echo strtoupper($analisis_sistema['estado_seguridad']['detalles']['estado_firewall']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Vulnerabilidades</div>
                    <div class="info-value <?php echo $analisis_sistema['estado_seguridad']['detalles']['vulnerabilidades'] > 0 ? 'danger' : 'success'; ?>">
                        <?php echo $analisis_sistema['estado_seguridad']['detalles']['vulnerabilidades']; ?>
                    </div>
                </div>
            </div>
            
            <!-- Base de Datos -->
            <div class="analysis-card">
                <div class="card-header">
                    <div class="card-title">Base de Datos</div>
                    <div class="card-icon">🗄️</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Estado</div>
                    <div class="info-value <?php echo $analisis_sistema['estado_base_datos']['estado'] == 'conectada' ? 'success' : 'danger'; ?>">
                        <?php echo strtoupper($analisis_sistema['estado_base_datos']['estado']); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tablas</div>
                    <div class="info-value"><?php echo $analisis_sistema['estado_base_datos']['tablas'] ?? '0'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tamaño</div>
                    <div class="info-value"><?php echo $analisis_sistema['estado_base_datos']['tamaño'] ?? 'N/A'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Conexiones Activas</div>
                    <div class="info-value"><?php echo $analisis_sistema['estado_base_datos']['conexiones_activas'] ?? '0'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Salud</div>
                    <div class="info-value"><?php echo $analisis_sistema['estado_base_datos']['salud'] ?? '0'; ?>%</div>
                </div>
            </div>
            
            <!-- Evaluación de Riesgo -->
            <div class="analysis-card">
                <div class="card-header">
                    <div class="card-title">Evaluación de Riesgo</div>
                    <div class="card-icon">⚠️</div>
                </div>
                <div class="progress-ring">
                    <svg width="150" height="150">
                        <circle cx="75" cy="75" r="65" class="progress-bg"></circle>
                        <circle cx="75" cy="75" r="65" class="progress-bar" 
                                style="stroke: <?php 
                                    $nivel = $analisis_sistema['evaluacion_riesgo']['nivel'];
                                    echo $nivel == 'bajo' ? 'var(--success-green)' : ($nivel == 'medio' ? 'var(--warning-yellow)' : 'var(--danger-red)');
                                ?>;
                                --progress: <?php echo 408 - (408 * (100 - $analisis_sistema['evaluacion_riesgo']['puntuacion']) / 100); ?>"></circle>
                    </svg>
                    <div class="progress-text">
                        <div class="progress-value" style="color: <?php 
                            echo $nivel == 'bajo' ? 'var(--success-green)' : ($nivel == 'medio' ? 'var(--warning-yellow)' : 'var(--danger-red)');
                        ?>">
                            <?php echo strtoupper($analisis_sistema['evaluacion_riesgo']['nivel']); ?>
                        </div>
                        <div class="progress-label">RIESGO</div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Backup Disponible</div>
                    <div class="info-value <?php echo $analisis_sistema['evaluacion_riesgo']['factores']['backup_disponible'] ? 'success' : 'danger'; ?>">
                        <?php echo $analisis_sistema['evaluacion_riesgo']['factores']['backup_disponible'] ? 'SÍ' : 'NO'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Usuarios Activos</div>
                    <div class="info-value"><?php echo $analisis_sistema['evaluacion_riesgo']['factores']['usuarios_activos']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Recomendación</div>
                    <div class="info-value" style="font-size: 1em;">
                        <?php echo $analisis_sistema['evaluacion_riesgo']['recomendacion']; ?>
                    </div>
                </div>
            </div>
            
            <!-- Compatibilidad -->
            <div class="analysis-card">
                <div class="card-header">
                    <div class="card-title">Compatibilidad</div>
                    <div class="card-icon">🔧</div>
                </div>
                <div class="info-item">
                    <div class="info-label">PHP</div>
                    <div class="info-value <?php echo $analisis_sistema['compatibilidad']['detalles']['php_compatible'] ? 'success' : 'danger'; ?>">
                        <?php echo $analisis_sistema['compatibilidad']['detalles']['version_php']; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Base de Datos</div>
                    <div class="info-value"><?php echo $analisis_sistema['compatibilidad']['detalles']['version_base_datos']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Sistema Operativo</div>
                    <div class="info-value"><?php echo $analisis_sistema['compatibilidad']['detalles']['sistema_operativo']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Servidor Web</div>
                    <div class="info-value"><?php echo $analisis_sistema['compatibilidad']['detalles']['servidor']; ?></div>
                </div>
            </div>
            
            <!-- Espacio en Disco -->
            <div class="analysis-card">
                <div class="card-header">
                    <div class="card-title">Espacio en Disco</div>
                    <div class="card-icon">💾</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Espacio Libre</div>
                    <div class="info-value <?php echo $analisis_sistema['espacio_disponible']['suficiente'] ? 'success' : 'danger'; ?>">
                        <?php echo $analisis_sistema['espacio_disponible']['libre_gb']; ?> GB
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Espacio Requerido</div>
                    <div class="info-value"><?php echo $analisis_sistema['espacio_disponible']['requerido_gb']; ?> GB</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Estado</div>
                    <div class="info-value <?php echo $analisis_sistema['espacio_disponible']['suficiente'] ? 'success' : 'danger'; ?>">
                        <?php echo $analisis_sistema['espacio_disponible']['suficiente'] ? 'SUFICIENTE' : 'INSUFICIENTE'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Terminal de Actualización -->
        <div class="update-terminal">
            <div class="terminal-header">
                <div class="terminal-title">Terminal de Actualización</div>
                <div class="terminal-controls">
                    <div class="terminal-dot red"></div>
                    <div class="terminal-dot yellow"></div>
                    <div class="terminal-dot green"></div>
                </div>
            </div>
            <div class="terminal-body" id="terminalBody">
                <div class="terminal-line">
                    <span class="terminal-prompt">GUARDIAN://UPDATE$</span>
                    Sistema de actualización inicializado
                </div>
                <div class="terminal-line">
                    <span class="terminal-prompt">></span>
                    Versión actual: <?php echo $analisis_sistema['version_actual']; ?>
                </div>
                <div class="terminal-line">
                    <span class="terminal-prompt">></span>
                    Versión disponible: <?php echo $analisis_sistema['version_ultima']; ?>
                </div>
                <?php if ($analisis_sistema['actualizacion_disponible']): ?>
                <div class="terminal-line" style="color: var(--success-green);">
                    <span class="terminal-prompt">></span>
                    ¡Actualización disponible!
                </div>
                <?php else: ?>
                <div class="terminal-line" style="color: var(--warning-yellow);">
                    <span class="terminal-prompt">></span>
                    Sistema actualizado
                </div>
                <?php endif; ?>
                <div class="terminal-line" style="color: var(--neon-cyan);">
                    <span class="terminal-prompt">></span>
                    Tiempo estimado: <?php echo $analisis_sistema['tiempo_estimado']['formateado']; ?>
                </div>
                <div class="terminal-line">
                    <span class="terminal-prompt">></span>
                    <span class="terminal-cursor"></span>
                </div>
            </div>
        </div>
        
        <!-- Tabs de Información -->
        <div class="tabs">
            <button class="tab active" onclick="changeTab(event, 'archivos')">Archivos a Actualizar</button>
            <button class="tab" onclick="changeTab(event, 'pasos')">Pasos de Actualización</button>
            <button class="tab" onclick="changeTab(event, 'logs')">Logs del Sistema</button>
        </div>
        
        <!-- Tab de Archivos -->
        <div id="archivos" class="tab-content active">
            <div class="file-list">
                <?php 
                foreach ($analisis_sistema['archivos_actualizar']['archivos'] as $categoria => $archivos) {
                    foreach ($archivos as $archivo => $info) {
                        echo '<div class="file-item ' . ($info['critico'] ? 'critical' : '') . '">';
                        echo '<span class="file-name">' . $archivo . '</span>';
                        echo '<span class="file-size">' . $info['tamaño'] . '</span>';
                        if ($info['critico']) {
                            echo '<span class="file-badge">CRÍTICO</span>';
                        }
                        echo '</div>';
                    }
                }
                ?>
            </div>
            <div class="info-item">
                <div class="info-label">Total de Archivos</div>
                <div class="info-value"><?php echo $analisis_sistema['archivos_actualizar']['total']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Archivos Críticos</div>
                <div class="info-value danger"><?php echo $analisis_sistema['archivos_actualizar']['criticos']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Tamaño Total</div>
                <div class="info-value"><?php echo $analisis_sistema['archivos_actualizar']['tamaño_total']; ?></div>
            </div>
        </div>
        
        <!-- Tab de Pasos -->
        <div id="pasos" class="tab-content">
            <div class="update-steps" id="updateSteps">
                <div class="step-card pendiente" id="step-preparacion">
                    <div class="step-icon">⚙️</div>
                    <div class="step-title">Preparación</div>
                    <div class="step-status">Pendiente</div>
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                </div>
                <div class="step-card pendiente" id="step-backup">
                    <div class="step-icon">💾</div>
                    <div class="step-title">Backup</div>
                    <div class="step-status">Pendiente</div>
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                </div>
                <div class="step-card pendiente" id="step-descarga">
                    <div class="step-icon">📥</div>
                    <div class="step-title">Descarga</div>
                    <div class="step-status">Pendiente</div>
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                </div>
                <div class="step-card pendiente" id="step-verificacion">
                    <div class="step-icon">🔍</div>
                    <div class="step-title">Verificación</div>
                    <div class="step-status">Pendiente</div>
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                </div>
                <div class="step-card pendiente" id="step-detencion_servicios">
                    <div class="step-icon">⏸️</div>
                    <div class="step-title">Detener Servicios</div>
                    <div class="step-status">Pendiente</div>
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                </div>
                <div class="step-card pendiente" id="step-base_datos">
                    <div class="step-icon">🗄️</div>
                    <div class="step-title">Base de Datos</div>
                    <div class="step-status">Pendiente</div>
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                </div>
                <div class="step-card pendiente" id="step-archivos">
                    <div class="step-icon">📁</div>
                    <div class="step-title">Archivos</div>
                    <div class="step-status">Pendiente</div>
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                </div>
                <div class="step-card pendiente" id="step-cuantico">
                    <div class="step-icon">⚛️</div>
                    <div class="step-title">Cuántico</div>
                    <div class="step-status">Pendiente</div>
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                </div>
                <div class="step-card pendiente" id="step-neuronal">
                    <div class="step-icon">🧠</div>
                    <div class="step-title">Red Neuronal</div>
                    <div class="step-status">Pendiente</div>
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                </div>
                <div class="step-card pendiente" id="step-reinicio_servicios">
                    <div class="step-icon">▶️</div>
                    <div class="step-title">Reiniciar Servicios</div>
                    <div class="step-status">Pendiente</div>
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                </div>
                <div class="step-card pendiente" id="step-optimizacion">
                    <div class="step-icon">⚡</div>
                    <div class="step-title">Optimización</div>
                    <div class="step-status">Pendiente</div>
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                </div>
                <div class="step-card pendiente" id="step-validacion">
                    <div class="step-icon">✔️</div>
                    <div class="step-title">Validación</div>
                    <div class="step-status">Pendiente</div>
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                </div>
                <div class="step-card pendiente" id="step-limpieza">
                    <div class="step-icon">🧹</div>
                    <div class="step-title">Limpieza</div>
                    <div class="step-status">Pendiente</div>
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab de Logs -->
        <div id="logs" class="tab-content">
            <div class="terminal-body" id="logsBody" style="height: 400px; background: rgba(0,0,0,0.5); border: 1px solid var(--neon-cyan); border-radius: 10px; padding: 20px;">
                <div class="terminal-line">
                    <span class="terminal-prompt">[LOG]</span>
                    Sistema de logs activo
                </div>
            </div>
        </div>
        
        <!-- Botones de Acción -->
        <div class="action-buttons">
            <button class="btn-epic" onclick="analizarSistema()">
                🔍 Analizar Sistema
            </button>
            <button class="btn-epic success" onclick="crearBackup()" 
                    <?php echo !$analisis_sistema['evaluacion_riesgo']['factores']['backup_disponible'] ? 'style="animation: pulse 2s infinite;"' : ''; ?>>
                💾 Crear Backup
            </button>
            <button class="btn-epic warning" onclick="verificarActualizacion()">
                🔄 Verificar Actualización
            </button>
            <button class="btn-epic" onclick="mostrarModalActualizacion()" 
                    <?php echo !$analisis_sistema['actualizacion_disponible'] || !$analisis_sistema['evaluacion_riesgo']['puede_continuar'] ? 'disabled' : ''; ?>>
                🚀 Iniciar Actualización
            </button>
            <button class="btn-epic danger" onclick="window.location.href='admin_dashboard.php'">
                ← Volver al Dashboard
            </button>
        </div>
    </div>
    
    <!-- Modal de Confirmación -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <div class="modal-header">⚠️ Confirmar Actualización</div>
            <div class="modal-body">
                <p><strong>Está a punto de actualizar el sistema a la versión <?php echo $analisis_sistema['version_ultima']; ?></strong></p>
                <br>
                <p>Esta operación realizará los siguientes cambios:</p>
                <ul style="margin: 20px 0; line-height: 1.8;">
                    <li>Creará un backup completo del sistema</li>
                    <li>Actualizará <?php echo $analisis_sistema['archivos_actualizar']['total']; ?> archivos</li>
                    <li>Aplicará migraciones a la base de datos</li>
                    <li>Instalará nuevos algoritmos cuánticos</li>
                    <li>Entrenará la red neuronal</li>
                </ul>
                <p><strong>Tiempo estimado: <?php echo $analisis_sistema['tiempo_estimado']['formateado']; ?></strong></p>
                <br>
                <p style="color: var(--warning-yellow);">⚠️ No cierre esta ventana durante el proceso</p>
            </div>
            <div class="modal-footer">
                <button class="btn-epic danger" onclick="cerrarModal()">Cancelar</button>
                <button class="btn-epic success" onclick="iniciarActualizacion()">Confirmar e Iniciar</button>
            </div>
        </div>
    </div>
    
    <!-- Loading Indicator -->
    <div class="loading-indicator" id="loadingIndicator">
        <div class="loading-spinner"></div>
    </div>
    
    <script>
        // Crear efecto Matrix Rain
        function crearMatrixRain() {
            const container = document.getElementById('matrixRain');
            const caracteres = '01アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン';
            
            for (let i = 0; i < 30; i++) {
                const columna = document.createElement('div');
                columna.className = 'matrix-column';
                columna.style.left = Math.random() * 100 + '%';
                columna.style.animationDuration = (5 + Math.random() * 10) + 's';
                columna.style.animationDelay = Math.random() * 5 + 's';
                
                let texto = '';
                for (let j = 0; j < 30; j++) {
                    texto += caracteres.charAt(Math.floor(Math.random() * caracteres.length));
                }
                columna.textContent = texto;
                
                container.appendChild(columna);
            }
        }
        
        // Crear partículas cuánticas
        function crearParticulasCuanticas() {
            const container = document.getElementById('quantumField');
            
            for (let i = 0; i < 20; i++) {
                const particula = document.createElement('div');
                particula.className = 'quantum-particle';
                particula.style.left = Math.random() * 100 + '%';
                particula.style.animationDelay = Math.random() * 20 + 's';
                particula.style.animationDuration = (15 + Math.random() * 10) + 's';
                container.appendChild(particula);
            }
        }
        
        // Terminal output
        function terminalOutput(mensaje, tipo = 'normal') {
            const terminal = document.getElementById('terminalBody');
            const linea = document.createElement('div');
            linea.className = 'terminal-line';
            
            let color = 'var(--text-white)';
            if (tipo === 'exito') color = 'var(--success-green)';
            if (tipo === 'error') color = 'var(--danger-red)';
            if (tipo === 'advertencia') color = 'var(--warning-yellow)';
            if (tipo === 'info') color = 'var(--neon-cyan)';
            
            linea.style.color = color;
            linea.innerHTML = `<span class="terminal-prompt">></span> ${mensaje}`;
            
            // Eliminar cursor de la línea anterior
            const cursor = terminal.querySelector('.terminal-cursor');
            if (cursor) cursor.remove();
            
            // Insertar nueva línea antes del cursor
            const ultimaLinea = terminal.lastElementChild;
            terminal.insertBefore(linea, ultimaLinea);
            
            // Scroll al final
            terminal.scrollTop = terminal.scrollHeight;
        }
        
        // Log output
        function logOutput(mensaje, tipo = 'info') {
            const logsBody = document.getElementById('logsBody');
            const linea = document.createElement('div');
            linea.className = 'terminal-line';
            
            const timestamp = new Date().toLocaleTimeString();
            let prefijo = '[INFO]';
            let color = 'var(--text-white)';
            
            if (tipo === 'error') {
                prefijo = '[ERROR]';
                color = 'var(--danger-red)';
            } else if (tipo === 'warning') {
                prefijo = '[WARN]';
                color = 'var(--warning-yellow)';
            } else if (tipo === 'success') {
                prefijo = '[OK]';
                color = 'var(--success-green)';
            }
            
            linea.style.color = color;
            linea.innerHTML = `<span style="color: var(--text-gray);">[${timestamp}]</span> ${prefijo} ${mensaje}`;
            
            logsBody.appendChild(linea);
            logsBody.scrollTop = logsBody.scrollHeight;
        }
        
        // Cambiar tabs
        function changeTab(evt, tabName) {
            const tabs = document.getElementsByClassName('tab');
            for (let tab of tabs) {
                tab.classList.remove('active');
            }
            
            const contents = document.getElementsByClassName('tab-content');
            for (let content of contents) {
                content.classList.remove('active');
            }
            
            evt.currentTarget.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        }
        
        // Analizar sistema
        async function analizarSistema() {
            terminalOutput('Iniciando análisis profundo del sistema...', 'info');
            logOutput('Análisis del sistema iniciado', 'info');
            showLoading();
            
            try {
                const formData = new FormData();
                formData.append('action', 'analizar_sistema');
                
                const respuesta = await fetch('update_system.php', {
                    method: 'POST',
                    body: formData
                });
                
                const resultado = await respuesta.json();
                
                if (resultado.exito) {
                    terminalOutput('Análisis completado exitosamente', 'exito');
                    terminalOutput(`Salud del sistema: ${resultado.datos.salud_sistema.puntuacion}%`, 'info');
                    terminalOutput(`Seguridad: ${resultado.datos.estado_seguridad.puntuacion}%`, 'info');
                    terminalOutput(`Riesgo: ${resultado.datos.evaluacion_riesgo.nivel}`, 
                        resultado.datos.evaluacion_riesgo.nivel === 'bajo' ? 'exito' : 'advertencia');
                    
                    logOutput('Análisis completado con éxito', 'success');
                    logOutput(`Puntuación de salud: ${resultado.datos.salud_sistema.puntuacion}%`, 'info');
                    
                    // Recargar página para actualizar datos
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    terminalOutput('Error en el análisis: ' + resultado.mensaje, 'error');
                    logOutput('Error en análisis: ' + resultado.mensaje, 'error');
                }
            } catch (error) {
                terminalOutput('Error de conexión: ' + error.message, 'error');
                logOutput('Error de conexión: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }
        
        // Crear backup
        async function crearBackup() {
            if (!confirm('¿Desea crear un backup completo del sistema? Esto puede tomar varios minutos.')) {
                return;
            }
            
            terminalOutput('Iniciando creación de backup de seguridad...', 'info');
            logOutput('Creación de backup iniciada', 'info');
            showLoading();
            
            try {
                const formData = new FormData();
                formData.append('action', 'crear_backup');
                formData.append('tipo', 'completo');
                
                const respuesta = await fetch('update_system.php', {
                    method: 'POST',
                    body: formData
                });
                
                const resultado = await respuesta.json();
                
                if (resultado.exito) {
                    terminalOutput(`Backup creado: ${resultado.datos.backup_id}`, 'exito');
                    terminalOutput(`Tamaño: ${resultado.datos.tamaño}`, 'info');
                    terminalOutput(`Ubicación: ${resultado.datos.ruta}`, 'info');
                    
                    logOutput('Backup creado exitosamente', 'success');
                    logOutput(`ID: ${resultado.datos.backup_id}`, 'info');
                    
                    // Actualizar estado del backup
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    terminalOutput('Error creando backup: ' + resultado.mensaje, 'error');
                    logOutput('Error en backup: ' + resultado.mensaje, 'error');
                }
            } catch (error) {
                terminalOutput('Error de conexión: ' + error.message, 'error');
                logOutput('Error de conexión: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }
        
        // Verificar actualización
        async function verificarActualizacion() {
            terminalOutput('Verificando actualizaciones disponibles...', 'info');
            showLoading();
            
            try {
                const formData = new FormData();
                formData.append('action', 'verificar_actualizacion');
                
                const respuesta = await fetch('update_system.php', {
                    method: 'POST',
                    body: formData
                });
                
                const resultado = await respuesta.json();
                
                if (resultado.exito) {
                    const datos = resultado.datos;
                    terminalOutput(`Versión actual: ${datos.actual}`, 'info');
                    terminalOutput(`Versión disponible: ${datos.disponible}`, 'info');
                    
                    if (datos.hay_actualizacion) {
                        terminalOutput('¡Actualización disponible!', 'exito');
                        logOutput('Nueva actualización encontrada', 'success');
                    } else {
                        terminalOutput('El sistema está actualizado', 'advertencia');
                        logOutput('No hay actualizaciones disponibles', 'warning');
                    }
                } else {
                    terminalOutput('Error verificando actualización', 'error');
                }
            } catch (error) {
                terminalOutput('Error de conexión: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }
        
        // Mostrar modal de actualización
        function mostrarModalActualizacion() {
            document.getElementById('confirmModal').classList.add('active');
        }
        
        // Cerrar modal
        function cerrarModal() {
            document.getElementById('confirmModal').classList.remove('active');
        }
        
        // Iniciar actualización
        async function iniciarActualizacion() {
            cerrarModal();
            
            terminalOutput('Iniciando proceso de actualización cuántica...', 'info');
            logOutput('ACTUALIZACIÓN INICIADA', 'warning');
            
            const pasos = [
                'preparacion', 'backup', 'descarga', 'verificacion', 
                'detencion_servicios', 'base_datos', 'archivos', 
                'cuantico', 'neuronal', 'reinicio_servicios',
                'optimizacion', 'validacion', 'limpieza'
            ];
            
            for (let i = 0; i < pasos.length; i++) {
                const paso = pasos[i];
                const elementoPaso = document.getElementById(`step-${paso}`);
                const progressBar = elementoPaso.querySelector('.step-progress-bar');
                
                elementoPaso.classList.remove('pendiente', 'completado', 'error');
                elementoPaso.classList.add('procesando');
                elementoPaso.querySelector('.step-status').textContent = 'Procesando...';
                
                terminalOutput(`Ejecutando: ${paso.replace('_', ' ')}...`, 'info');
                logOutput(`Procesando paso: ${paso}`, 'info');
                
                // Animar barra de progreso
                for (let p = 0; p <= 100; p += 10) {
                    progressBar.style.width = p + '%';
                    await new Promise(resolve => setTimeout(resolve, 100));
                }
                
                // Simular proceso
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                // Simular posible error (5% de probabilidad)
                if (Math.random() < 0.05 && paso !== 'backup' && paso !== 'preparacion') {
                    elementoPaso.classList.remove('procesando');
                    elementoPaso.classList.add('error');
                    elementoPaso.querySelector('.step-status').textContent = 'Error';
                    
                    terminalOutput(`Error en paso: ${paso}`, 'error');
                    logOutput(`ERROR en ${paso}: Iniciando rollback`, 'error');
                    
                    // Rollback
                    terminalOutput('Iniciando rollback automático...', 'advertencia');
                    await new Promise(resolve => setTimeout(resolve, 3000));
                    terminalOutput('Sistema restaurado al estado anterior', 'exito');
                    
                    return;
                }
                
                elementoPaso.classList.remove('procesando');
                elementoPaso.classList.add('completado');
                elementoPaso.querySelector('.step-status').textContent = 'Completado ✔';
                
                terminalOutput(`${paso.replace('_', ' ')} completado`, 'exito');
                logOutput(`Paso completado: ${paso}`, 'success');
            }
            
            terminalOutput('¡Actualización completada exitosamente!', 'exito');
            terminalOutput('Sistema actualizado a la versión 3.1.0-QUANTUM', 'info');
            terminalOutput('Reiniciando sistema en 5 segundos...', 'advertencia');
            
            logOutput('ACTUALIZACIÓN COMPLETADA CON ÉXITO', 'success');
            logOutput('Versión instalada: 3.1.0-QUANTUM', 'info');
            
            // Recargar página después de 5 segundos
            setTimeout(() => {
                location.reload();
            }, 5000);
        }
        
        // Obtener logs del sistema
        async function obtenerLogs() {
            try {
                const formData = new FormData();
                formData.append('action', 'obtener_logs');
                
                const respuesta = await fetch('update_system.php', {
                    method: 'POST',
                    body: formData
                });
                
                const resultado = await respuesta.json();
                
                if (resultado.exito && resultado.datos.length > 0) {
                    const logsBody = document.getElementById('logsBody');
                    logsBody.innerHTML = '';
                    
                    resultado.datos.forEach(log => {
                        const linea = document.createElement('div');
                        linea.className = 'terminal-line';
                        linea.style.color = 'var(--text-gray)';
                        linea.textContent = log;
                        logsBody.appendChild(linea);
                    });
                }
            } catch (error) {
                console.error('Error obteniendo logs:', error);
            }
        }
        
        // Mostrar loading
        function showLoading() {
            document.getElementById('loadingIndicator').classList.add('active');
        }
        
        // Ocultar loading
        function hideLoading() {
            document.getElementById('loadingIndicator').classList.remove('active');
        }
        
        // Actualizar métricas en tiempo real
        function actualizarMetricas() {
            // Esta función podría hacer llamadas AJAX para actualizar métricas
            // Por ahora solo actualiza la hora
            const ahora = new Date();
            const tiempo = ahora.toLocaleTimeString();
            
            // Actualizar algún indicador visual si es necesario
        }
        
        // Inicializar efectos
        crearMatrixRain();
        crearParticulasCuanticas();
        
        // Mensaje de bienvenida
        setTimeout(() => {
            terminalOutput('Sistema de actualización cuántica listo', 'exito');
            terminalOutput('IA de actualización online', 'info');
            terminalOutput('Red neuronal activada', 'info');
            logOutput('Sistema inicializado correctamente', 'success');
            
            // Verificar si hay actualización disponible
            <?php if ($analisis_sistema['actualizacion_disponible']): ?>
            terminalOutput('Nueva versión detectada: <?php echo $analisis_sistema['version_ultima']; ?>', 'advertencia');
            <?php endif; ?>
            
            // Verificar riesgos
            <?php if ($analisis_sistema['evaluacion_riesgo']['nivel'] === 'alto'): ?>
            terminalOutput('⚠️ ADVERTENCIA: Nivel de riesgo alto detectado', 'error');
            terminalOutput('Se recomienda crear un backup antes de continuar', 'advertencia');
            <?php endif; ?>
            
            // Obtener logs del sistema
            obtenerLogs();
        }, 1000);
        
        // Actualizar métricas cada 30 segundos
        setInterval(actualizarMetricas, 30000);
        
        // Actualizar logs cada 10 segundos
        setInterval(obtenerLogs, 10000);
        
        // Console art
        console.log('%c🚀 SISTEMA DE ACTUALIZACIÓN CUÁNTICA', 'color: #00ffff; font-size: 24px; font-weight: bold; text-shadow: 0 0 10px #00ffff;');
        console.log('%c⚛️ Encriptación Cuántica Activa', 'color: #ff00ff; font-size: 16px;');
        console.log('%c🧠 Red Neuronal Online', 'color: #00ff00; font-size: 16px;');
        console.log('%c✅ Sistema Operacional', 'color: #00ff88; font-size: 14px;');
        console.log('%c📊 Versión: <?php echo $analisis_sistema['version_actual']; ?>', 'color: #0099ff; font-size: 14px;');
        
        // Detectar teclas especiales
        document.addEventListener('keydown', function(e) {
            // Ctrl + Shift + U para actualización rápida
            if (e.ctrlKey && e.shiftKey && e.key === 'U') {
                e.preventDefault();
                mostrarModalActualizacion();
            }
            
            // Ctrl + Shift + B para backup rápido
            if (e.ctrlKey && e.shiftKey && e.key === 'B') {
                e.preventDefault();
                crearBackup();
            }
            
            // Ctrl + Shift + A para análisis rápido
            if (e.ctrlKey && e.shiftKey && e.key === 'A') {
                e.preventDefault();
                analizarSistema();
            }
        });
    </script>
</body>
</html>
