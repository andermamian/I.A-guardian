# 🔧 GuardianIA - Documentación Técnica del Sistema de Usuario

## 📋 Arquitectura del Sistema

### Stack Tecnológico
- **Backend:** PHP 8.1+ con programación orientada a objetos
- **Base de Datos:** MySQL 8.0+ con índices optimizados
- **Frontend:** HTML5, CSS3, JavaScript ES6+
- **Seguridad:** bcrypt, CSRF tokens, prepared statements
- **APIs:** RESTful con respuestas JSON

### Estructura del Proyecto
```
GuardianIA_PHP/
├── config.php                 # Configuración de base de datos
├── auth.php                   # Sistema de autenticación
├── login.php                  # Página de login/registro
├── user_dashboard.php         # Dashboard principal de usuario
├── user_security.php          # Centro de seguridad
├── user_performance.php       # Optimizador de rendimiento
├── user_assistant.php         # Asistente IA
├── user_settings.php          # Configuraciones de usuario
├── user_test_suite.php        # Suite de testing
├── database_setup.sql         # Script de base de datos
├── ThreatDetectionEngine.php  # Motor de detección de amenazas
├── PerformanceOptimizer.php   # Optimizador de rendimiento
├── GuardianAIChatbot.php      # Chatbot inteligente
├── AILearningEngine.php       # Motor de aprendizaje IA
├── assets/                    # Recursos estáticos
│   ├── css/                   # Hojas de estilo
│   ├── js/                    # Scripts JavaScript
│   └── images/                # Imágenes y iconos
└── logs/                      # Archivos de log
    ├── application.log
    ├── security.log
    └── error.log
```

## 🗄️ Esquema de Base de Datos

### Tabla: users
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    language VARCHAR(10) DEFAULT 'es',
    timezone VARCHAR(50) DEFAULT 'America/Mexico_City',
    theme VARCHAR(20) DEFAULT 'dark',
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(64),
    is_active BOOLEAN DEFAULT TRUE,
    failed_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_verification_token (email_verification_token),
    INDEX idx_active (is_active),
    INDEX idx_created (created_at)
);
```

### Tabla: user_settings
```sql
CREATE TABLE user_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_setting (user_id, setting_key),
    INDEX idx_user_id (user_id),
    INDEX idx_setting_key (setting_key)
);
```

### Tabla: chatbot_conversations
```sql
CREATE TABLE chatbot_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    status ENUM('active', 'archived', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);
```

### Tabla: chatbot_messages
```sql
CREATE TABLE chatbot_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    sender ENUM('user', 'ai') NOT NULL,
    message TEXT NOT NULL,
    intent_category VARCHAR(50),
    confidence_score DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (conversation_id) REFERENCES chatbot_conversations(id) ON DELETE CASCADE,
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_sender (sender),
    INDEX idx_created (created_at)
);
```

### Tabla: threat_detections
```sql
CREATE TABLE threat_detections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    threat_type VARCHAR(50) NOT NULL,
    threat_level ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    file_path VARCHAR(500),
    threat_signature VARCHAR(255),
    status ENUM('detected', 'quarantined', 'removed', 'false_positive') DEFAULT 'detected',
    action_taken VARCHAR(255),
    detection_method VARCHAR(100),
    confidence_score DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_threat_type (threat_type),
    INDEX idx_threat_level (threat_level),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);
```

### Tabla: performance_optimizations
```sql
CREATE TABLE performance_optimizations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    optimization_type ENUM('ram', 'storage', 'battery', 'cpu') NOT NULL,
    before_value DECIMAL(10,2),
    after_value DECIMAL(10,2),
    improvement_percentage DECIMAL(5,2),
    files_processed INT DEFAULT 0,
    space_freed_mb DECIMAL(10,2) DEFAULT 0,
    execution_time_seconds INT,
    status ENUM('running', 'completed', 'failed') DEFAULT 'running',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_optimization_type (optimization_type),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);
```

### Tabla: user_activity_logs
```sql
CREATE TABLE user_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    INDEX idx_ip_address (ip_address)
);
```

### Tabla: remember_tokens
```sql
CREATE TABLE remember_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires (expires_at)
);
```

### Tabla: password_resets
```sql
CREATE TABLE password_resets (
    user_id INT PRIMARY KEY,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token),
    INDEX idx_expires (expires_at)
);
```

## 🔐 Sistema de Autenticación

### Flujo de Autenticación

#### Registro de Usuario
```php
function registerUser($conn, $full_name, $email, $password, $confirm_password) {
    // 1. Validación de entrada
    // 2. Verificación de email único
    // 3. Hash de contraseña con bcrypt
    // 4. Generación de token de verificación
    // 5. Inserción en base de datos
    // 6. Envío de email de verificación
    // 7. Log de actividad
}
```

#### Proceso de Login
```php
function authenticateUser($conn, $email, $password, $remember = false) {
    // 1. Validación de entrada
    // 2. Búsqueda de usuario por email
    // 3. Verificación de bloqueo de cuenta
    // 4. Verificación de contraseña
    // 5. Verificación de cuenta activa
    // 6. Verificación de email
    // 7. Creación de sesión
    // 8. Generación de token "recordar" (opcional)
    // 9. Log de actividad
}
```

#### Seguridad Implementada
- **Protección contra fuerza bruta:** Bloqueo temporal tras 5 intentos fallidos
- **Tokens seguros:** Generación criptográficamente segura
- **Sesiones seguras:** Configuración de cookies con flags de seguridad
- **Validación de entrada:** Sanitización y validación de todos los inputs
- **Logs de auditoría:** Registro completo de actividades de autenticación

## 🛡️ Motor de Detección de Amenazas

### Clase ThreatDetectionEngine

#### Métodos Principales
```php
class ThreatDetectionEngine {
    public function scanForThreats($user_id, $scan_type = 'quick') {
        // Implementación de escaneo de amenazas
    }
    
    public function analyzeFile($file_path) {
        // Análisis de archivo individual
    }
    
    public function quarantineFile($threat_id) {
        // Cuarentena de archivo malicioso
    }
    
    public function getThreatsHistory($user_id, $limit = 50) {
        // Historial de amenazas detectadas
    }
}
```

#### Algoritmos de Detección
1. **Análisis de firmas:** Comparación con base de datos de malware conocido
2. **Análisis heurístico:** Detección de comportamientos sospechosos
3. **Machine Learning:** Clasificación basada en características del archivo
4. **Análisis de red:** Monitoreo de conexiones sospechosas

#### Tipos de Amenazas Detectadas
- **Malware:** Virus, troyanos, gusanos
- **Spyware:** Software de espionaje
- **Adware:** Publicidad maliciosa
- **Ransomware:** Software de secuestro
- **Rootkits:** Software de ocultación
- **Phishing:** Sitios web fraudulentos

## ⚡ Optimizador de Rendimiento

### Clase PerformanceOptimizer

#### Métodos de Optimización
```php
class PerformanceOptimizer {
    public function optimizeRAM($user_id) {
        // Liberación de memoria no utilizada
        // Cierre de procesos innecesarios
        // Optimización de caché
    }
    
    public function cleanupStorage($user_id) {
        // Eliminación de archivos temporales
        // Limpieza de caché de aplicaciones
        // Detección de archivos duplicados
    }
    
    public function optimizeBattery($user_id) {
        // Análisis de consumo por aplicación
        // Ajuste de configuraciones de energía
        // Optimización de conectividad
    }
}
```

#### Métricas de Rendimiento
- **CPU Usage:** Porcentaje de uso del procesador
- **Memory Usage:** Uso de RAM en MB y porcentaje
- **Disk Usage:** Espacio utilizado y disponible
- **Battery Level:** Nivel de batería y tiempo estimado
- **Network Activity:** Velocidad de descarga/subida

#### Algoritmos de Optimización
1. **Análisis de procesos:** Identificación de procesos que consumen recursos
2. **Limpieza inteligente:** Eliminación segura de archivos innecesarios
3. **Optimización de memoria:** Liberación de RAM no utilizada
4. **Gestión de energía:** Ajuste automático de configuraciones

## 🤖 Asistente IA

### Clase GuardianAIChatbot

#### Procesamiento de Lenguaje Natural
```php
class GuardianAIChatbot {
    public function processUserMessage($user_id, $message) {
        // 1. Análisis de intención
        // 2. Extracción de entidades
        // 3. Generación de respuesta
        // 4. Ejecución de acciones
    }
    
    private function analyzeIntent($message) {
        // Clasificación de intención usando ML
    }
    
    private function generateResponse($intent, $entities) {
        // Generación de respuesta contextual
    }
}
```

#### Categorías de Intención
- **security:** Consultas sobre seguridad y amenazas
- **performance:** Optimización y rendimiento del sistema
- **settings:** Configuraciones y preferencias
- **help:** Ayuda y soporte técnico
- **general:** Consultas generales

#### Capacidades del Chatbot
- **Procesamiento contextual:** Mantiene contexto de conversación
- **Ejecución de comandos:** Puede ejecutar acciones del sistema
- **Aprendizaje adaptativo:** Mejora con cada interacción
- **Respuestas multimodales:** Texto, enlaces, acciones

## 📊 Sistema de Métricas

### Recolección de Datos
```php
class MetricsCollector {
    public function collectSecurityMetrics($user_id) {
        // Métricas de seguridad en tiempo real
    }
    
    public function collectPerformanceMetrics($user_id) {
        // Métricas de rendimiento del sistema
    }
    
    public function generateReport($user_id, $period) {
        // Generación de reportes personalizados
    }
}
```

### Tipos de Métricas
1. **Métricas de Seguridad**
   - Amenazas detectadas/bloqueadas
   - Tiempo de respuesta de detección
   - Falsos positivos
   - Estado del firewall

2. **Métricas de Rendimiento**
   - Uso de CPU, RAM, disco
   - Velocidad del sistema
   - Tiempo de arranque
   - Eficiencia energética

3. **Métricas de Usuario**
   - Tiempo de sesión
   - Funciones más utilizadas
   - Patrones de uso
   - Satisfacción del usuario

## 🔧 APIs y Endpoints

### Estructura de API RESTful

#### Autenticación
```
POST /api/auth/login
POST /api/auth/register
POST /api/auth/logout
POST /api/auth/forgot-password
POST /api/auth/reset-password
```

#### Usuario
```
GET /api/user/profile
PUT /api/user/profile
GET /api/user/settings
PUT /api/user/settings
GET /api/user/activity
```

#### Seguridad
```
POST /api/security/scan
GET /api/security/threats
POST /api/security/quarantine
GET /api/security/status
```

#### Rendimiento
```
POST /api/performance/optimize
GET /api/performance/metrics
GET /api/performance/history
```

#### Chatbot
```
POST /api/chatbot/message
GET /api/chatbot/conversations
POST /api/chatbot/conversation
```

### Formato de Respuesta
```json
{
    "success": true,
    "data": {
        // Datos de respuesta
    },
    "message": "Operación exitosa",
    "timestamp": "2024-12-08T10:30:00Z",
    "request_id": "uuid-here"
}
```

### Códigos de Estado HTTP
- **200 OK:** Operación exitosa
- **201 Created:** Recurso creado
- **400 Bad Request:** Error en la solicitud
- **401 Unauthorized:** No autenticado
- **403 Forbidden:** Sin permisos
- **404 Not Found:** Recurso no encontrado
- **500 Internal Server Error:** Error del servidor

## 🧪 Testing y Calidad

### Suite de Testing

#### Tipos de Tests
1. **Tests Unitarios:** Funciones individuales
2. **Tests de Integración:** Comunicación entre módulos
3. **Tests de Sistema:** Funcionalidad completa
4. **Tests de Rendimiento:** Velocidad y eficiencia
5. **Tests de Seguridad:** Vulnerabilidades y protección

#### Cobertura de Testing
```php
class GuardianIAUserTestSuite {
    public function testAuthentication() {
        // Tests del sistema de autenticación
    }
    
    public function testSecurityCenter() {
        // Tests del centro de seguridad
    }
    
    public function testPerformanceOptimizer() {
        // Tests del optimizador
    }
    
    public function testAIAssistant() {
        // Tests del asistente IA
    }
}
```

#### Métricas de Calidad
- **Cobertura de código:** >90%
- **Tiempo de respuesta:** <500ms promedio
- **Disponibilidad:** 99.9% uptime
- **Precisión de IA:** >94% en detección de amenazas

## 🚀 Optimización y Rendimiento

### Optimizaciones de Base de Datos

#### Índices Estratégicos
```sql
-- Índices para consultas frecuentes
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_threat_user_date ON threat_detections(user_id, created_at);
CREATE INDEX idx_performance_user_type ON performance_optimizations(user_id, optimization_type);
```

#### Consultas Optimizadas
```php
// Uso de prepared statements para prevenir SQL injection
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
$stmt->bind_param("s", $email);
$stmt->execute();
```

### Optimizaciones de Frontend

#### Carga Asíncrona
```javascript
// Carga de datos sin bloquear la interfaz
async function loadDashboardData() {
    const [security, performance, activity] = await Promise.all([
        fetch('/api/security/status'),
        fetch('/api/performance/metrics'),
        fetch('/api/user/activity')
    ]);
}
```

#### Caché del Cliente
```javascript
// Implementación de caché local
class CacheManager {
    static set(key, data, ttl = 300000) { // 5 minutos por defecto
        const item = {
            data: data,
            timestamp: Date.now(),
            ttl: ttl
        };
        localStorage.setItem(key, JSON.stringify(item));
    }
    
    static get(key) {
        const item = JSON.parse(localStorage.getItem(key));
        if (!item) return null;
        
        if (Date.now() - item.timestamp > item.ttl) {
            localStorage.removeItem(key);
            return null;
        }
        
        return item.data;
    }
}
```

## 🔒 Seguridad y Mejores Prácticas

### Medidas de Seguridad

#### Validación de Entrada
```php
function validateInput($data, $type) {
    switch($type) {
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL);
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT);
        case 'string':
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        default:
            return false;
    }
}
```

#### Protección CSRF
```php
// Generación de token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verificación de token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}
```

#### Headers de Seguridad
```php
// Configuración de headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'');
```

### Mejores Prácticas de Desarrollo

#### Principios SOLID
1. **Single Responsibility:** Cada clase tiene una responsabilidad única
2. **Open/Closed:** Abierto para extensión, cerrado para modificación
3. **Liskov Substitution:** Objetos derivados deben ser sustituibles
4. **Interface Segregation:** Interfaces específicas mejor que generales
5. **Dependency Inversion:** Depender de abstracciones, no de concreciones

#### Patrones de Diseño Utilizados
- **Singleton:** Para conexión de base de datos
- **Factory:** Para creación de objetos de amenazas
- **Observer:** Para notificaciones del sistema
- **Strategy:** Para diferentes algoritmos de optimización

## 📈 Monitoreo y Logging

### Sistema de Logs

#### Configuración de Logs
```php
class Logger {
    const DEBUG = 1;
    const INFO = 2;
    const WARNING = 3;
    const ERROR = 4;
    const CRITICAL = 5;
    
    public static function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $levelName = self::getLevelName($level);
        $contextStr = !empty($context) ? json_encode($context) : '';
        
        $logEntry = "[$timestamp] $levelName: $message $contextStr" . PHP_EOL;
        
        $logFile = self::getLogFile($level);
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
```

#### Métricas de Monitoreo
- **Tiempo de respuesta:** Promedio y percentiles
- **Uso de memoria:** Picos y promedio
- **Errores por minuto:** Tasa de errores
- **Usuarios activos:** Concurrencia y sesiones
- **Operaciones por segundo:** Throughput del sistema

### Alertas Automáticas

#### Configuración de Alertas
```php
class AlertManager {
    public function checkSystemHealth() {
        $metrics = $this->collectMetrics();
        
        if ($metrics['error_rate'] > 0.05) {
            $this->sendAlert('HIGH_ERROR_RATE', $metrics);
        }
        
        if ($metrics['response_time'] > 2000) {
            $this->sendAlert('SLOW_RESPONSE', $metrics);
        }
        
        if ($metrics['memory_usage'] > 0.9) {
            $this->sendAlert('HIGH_MEMORY_USAGE', $metrics);
        }
    }
}
```

## 🔄 Deployment y DevOps

### Configuración de Producción

#### Variables de Entorno
```bash
# Configuración de base de datos
DB_HOST=localhost
DB_USERNAME=guardian_user
DB_PASSWORD=secure_password
DB_DATABASE=guardian_ia_prod

# Configuración de seguridad
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE=true
CSRF_PROTECTION=true

# Configuración de email
MAIL_HOST=smtp.guardian-ia.com
MAIL_PORT=587
MAIL_USERNAME=noreply@guardian-ia.com
MAIL_PASSWORD=mail_password
```

#### Configuración de Servidor Web
```apache
# Apache Virtual Host
<VirtualHost *:443>
    ServerName guardian-ia.com
    DocumentRoot /var/www/guardian-ia/public
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /var/www/guardian-ia/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

### Proceso de Deployment

#### Script de Deployment
```bash
#!/bin/bash
# deploy.sh

echo "Iniciando deployment de GuardianIA..."

# Backup de base de datos
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > backup_$(date +%Y%m%d_%H%M%S).sql

# Actualizar código
git pull origin main

# Instalar dependencias
composer install --no-dev --optimize-autoloader

# Ejecutar migraciones
php migrate.php

# Limpiar caché
php clear_cache.php

# Reiniciar servicios
sudo systemctl reload apache2
sudo systemctl restart php8.1-fpm

echo "Deployment completado exitosamente!"
```

## 📚 Recursos Adicionales

### Documentación de APIs
- **Swagger/OpenAPI:** Documentación interactiva de APIs
- **Postman Collection:** Colección de requests para testing
- **SDK Examples:** Ejemplos de integración en diferentes lenguajes

### Herramientas de Desarrollo
- **PHPUnit:** Framework de testing unitario
- **Xdebug:** Debugger y profiler para PHP
- **Composer:** Gestor de dependencias
- **Git Hooks:** Automatización de tareas pre-commit

### Recursos de Aprendizaje
- **Documentación oficial:** docs.guardian-ia.com
- **Tutoriales en video:** youtube.com/guardian-ia-dev
- **Ejemplos de código:** github.com/guardian-ia/examples
- **Foro de desarrolladores:** dev.guardian-ia.com

---

## 📞 Soporte Técnico

Para soporte técnico o consultas sobre la implementación:

- **Email:** dev-support@guardian-ia.com
- **Slack:** guardian-ia-developers.slack.com
- **GitHub Issues:** github.com/guardian-ia/user-interface/issues
- **Documentación:** docs.guardian-ia.com

---

*Documentación técnica v1.0 - Última actualización: Diciembre 2024*

