# GuardianIA v3.0 - Guía de Instalación Completa

## 🚀 Instalación del Sistema de Ciberseguridad Más Avanzado del Mundo

Esta guía te llevará paso a paso para instalar y configurar GuardianIA v3.0, el primer sistema capaz de ser **el antivirus de otras inteligencias artificiales**.

---

## 📋 Requisitos del Sistema

### Requisitos Mínimos
```
🖥️ Servidor Web: Apache 2.4+ o Nginx 1.18+
🐘 PHP: 8.0 o superior
🗄️ Base de Datos: MySQL 8.0+ o MariaDB 10.5+
💾 RAM: 4GB mínimo (8GB recomendado)
💿 Espacio en Disco: 2GB libres
🌐 Conexión a Internet: Estable
```

### Requisitos Recomendados
```
🖥️ Servidor Web: Apache 2.4+ con mod_rewrite
🐘 PHP: 8.1+ con OPcache habilitado
🗄️ Base de Datos: MySQL 8.0+ con InnoDB
💾 RAM: 16GB para rendimiento óptimo
💿 Espacio en Disco: 10GB para logs y cache
⚡ CPU: 4+ cores para análisis paralelo
🔒 SSL: Certificado válido requerido
```

### Extensiones PHP Requeridas
```php
✅ pdo
✅ pdo_mysql
✅ openssl
✅ curl
✅ json
✅ mbstring
✅ gd
✅ zip
✅ xml
✅ intl
✅ bcmath (para cálculos cuánticos)
✅ sodium (para encriptación avanzada)
```

### Verificar Extensiones
```bash
php -m | grep -E "(pdo|openssl|curl|json|mbstring|gd|zip|xml|intl|bcmath|sodium)"
```

---

## 📦 Descarga e Instalación

### Paso 1: Descargar GuardianIA v3.0
```bash
# Extraer el archivo ZIP
unzip GuardianIA_v3.0_COMPLETE_SYSTEM.zip
cd GuardianIA_PHP_v3.0
```

### Paso 2: Verificar Archivos
```bash
# Verificar que todos los archivos estén presentes
ls -la

# Deberías ver estos archivos principales:
# ✅ index_v3.php (Interfaz principal mejorada)
# ✅ AIAntivirusEngine.php (Motor antivirus de IAs)
# ✅ AIVPNEngine.php (Motor VPN inteligente)
# ✅ AdvancedConfigurationEngine.php (Configuración automática)
# ✅ PredictiveAnalysisEngine.php (Análisis predictivo)
# ✅ comprehensive_test_suite.php (Suite de tests)
# ✅ database_setup.sql (Estructura de BD)
# ✅ config.php (Configuración)
```

### Paso 3: Configurar Permisos
```bash
# Establecer permisos correctos
chmod 755 *.php
chmod 777 logs/
chmod 644 *.md *.sql

# Crear directorios necesarios
mkdir -p logs cache temp uploads
chmod 777 logs cache temp uploads
```

---

## 🗄️ Configuración de Base de Datos

### Paso 1: Crear Base de Datos
```sql
-- Conectar a MySQL como root
mysql -u root -p

-- Crear base de datos
CREATE DATABASE guardian_ia_v3 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario dedicado
CREATE USER 'guardian_user'@'localhost' IDENTIFIED BY 'TU_PASSWORD_SEGURA';
GRANT ALL PRIVILEGES ON guardian_ia_v3.* TO 'guardian_user'@'localhost';
FLUSH PRIVILEGES;

-- Salir de MySQL
EXIT;
```

### Paso 2: Importar Estructura
```bash
# Importar estructura de base de datos
mysql -u guardian_user -p guardian_ia_v3 < database_setup.sql

# Verificar importación
mysql -u guardian_user -p guardian_ia_v3 -e "SHOW TABLES;"
```

### Paso 3: Verificar Tablas Creadas
```sql
-- Deberías ver estas tablas:
✅ users                    (Usuarios del sistema)
✅ threat_logs             (Logs de amenazas)
✅ performance_metrics     (Métricas de rendimiento)
✅ ai_scan_results         (Resultados de escaneos de IA)
✅ vpn_connections         (Conexiones VPN)
✅ quantum_keys            (Claves cuánticas)
✅ predictive_analyses     (Análisis predictivos)
✅ configuration_history   (Historial de configuraciones)
✅ system_logs             (Logs del sistema)
✅ neural_signatures       (Firmas neurales de IAs)
✅ threat_intelligence     (Inteligencia de amenazas)
✅ user_preferences        (Preferencias de usuario)
```

---

## ⚙️ Configuración del Sistema

### Paso 1: Configurar config.php
```php
<?php
// Editar config.php con tus datos
$config = [
    // Configuración de Base de Datos
    'database' => [
        'host' => 'localhost',
        'dbname' => 'guardian_ia_v3',
        'username' => 'guardian_user',
        'password' => 'TU_PASSWORD_SEGURA',
        'charset' => 'utf8mb4'
    ],
    
    // Configuración de Seguridad
    'security' => [
        'encryption_key' => 'TU_CLAVE_ENCRIPTACION_256_BITS',
        'jwt_secret' => 'TU_JWT_SECRET_SEGURO',
        'session_timeout' => 3600,
        'max_login_attempts' => 5
    ],
    
    // Configuración de IA
    'ai_settings' => [
        'antivirus_sensitivity' => 'high',
        'vpn_optimization' => 'balanced',
        'quantum_security' => true,
        'predictive_analysis' => true,
        'auto_configuration' => true
    ],
    
    // Configuración de Rendimiento
    'performance' => [
        'cache_enabled' => true,
        'cache_ttl' => 3600,
        'max_concurrent_scans' => 10,
        'log_level' => 'INFO'
    ]
];
?>
```

### Paso 2: Generar Claves de Seguridad
```bash
# Generar clave de encriptación segura
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

# Generar JWT secret
php -r "echo bin2hex(random_bytes(64)) . PHP_EOL;"
```

### Paso 3: Configurar Servidor Web

#### Apache (.htaccess)
```apache
RewriteEngine On

# Redirigir a HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Proteger archivos sensibles
<Files "config.php">
    Require all denied
</Files>

<Files "database_setup.sql">
    Require all denied
</Files>

# Habilitar compresión
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache headers
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name tu-dominio.com;
    root /path/to/GuardianIA_PHP_v3.0;
    index index_v3.php;

    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;

    # PHP Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index_v3.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Proteger archivos sensibles
    location ~ ^/(config\.php|database_setup\.sql|logs/.*) {
        deny all;
        return 404;
    }

    # Compresión
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
}
```

---

## 🧪 Verificación y Testing

### Paso 1: Ejecutar Tests del Sistema
```bash
# Navegar al directorio de GuardianIA
cd /path/to/GuardianIA_PHP_v3.0

# Ejecutar suite de tests completa
php comprehensive_test_suite.php

# O ejecutar via navegador
# https://tu-dominio.com/comprehensive_test_suite.php
```

### Paso 2: Verificar Componentes Principales

#### Test de Conectividad de BD
```bash
php -r "
require 'config.php';
try {
    \$pdo = new PDO('mysql:host='.\$config['database']['host'].';dbname='.\$config['database']['dbname'], \$config['database']['username'], \$config['database']['password']);
    echo 'Conexión a BD: ✅ EXITOSA\n';
} catch (Exception \$e) {
    echo 'Conexión a BD: ❌ ERROR - ' . \$e->getMessage() . '\n';
}
"
```

#### Test de AI Antivirus Engine
```bash
php -r "
require 'AIAntivirusEngine.php';
require 'config.php';
\$pdo = new PDO('mysql:host='.\$config['database']['host'].';dbname='.\$config['database']['dbname'], \$config['database']['username'], \$config['database']['password']);
\$engine = new AIAntivirusEngine(\$pdo);
echo 'AI Antivirus Engine: ✅ INICIALIZADO\n';
"
```

#### Test de AI VPN Engine
```bash
php -r "
require 'AIVPNEngine.php';
require 'config.php';
\$pdo = new PDO('mysql:host='.\$config['database']['host'].';dbname='.\$config['database']['dbname'], \$config['database']['username'], \$config['database']['password']);
\$engine = new AIVPNEngine(\$pdo);
echo 'AI VPN Engine: ✅ INICIALIZADO\n';
"
```

### Paso 3: Verificar Interfaz Web
```bash
# Acceder via navegador
https://tu-dominio.com/index_v3.php

# Deberías ver:
✅ Interfaz con animaciones cuánticas
✅ Dashboard de estado del sistema
✅ Métricas en tiempo real
✅ Controles de AI Antivirus
✅ Configuración de AI VPN
✅ Panel de análisis predictivo
```

---

## 🔐 Configuración de Seguridad Avanzada

### Paso 1: Configurar SSL/TLS
```bash
# Generar certificado SSL (Let's Encrypt recomendado)
certbot --apache -d tu-dominio.com

# O para Nginx
certbot --nginx -d tu-dominio.com
```

### Paso 2: Configurar Firewall
```bash
# UFW (Ubuntu)
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable

# iptables
iptables -A INPUT -p tcp --dport 22 -j ACCEPT
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT
iptables -A INPUT -j DROP
```

### Paso 3: Configurar Monitoreo
```bash
# Crear script de monitoreo
cat > /usr/local/bin/guardian_monitor.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/guardian_ia_monitor.log"
GUARDIAN_DIR="/path/to/GuardianIA_PHP_v3.0"

# Verificar servicios
systemctl is-active apache2 >/dev/null || echo "$(date): Apache down" >> $LOG_FILE
systemctl is-active mysql >/dev/null || echo "$(date): MySQL down" >> $LOG_FILE

# Verificar espacio en disco
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 90 ]; then
    echo "$(date): Disk usage high: ${DISK_USAGE}%" >> $LOG_FILE
fi

# Verificar logs de GuardianIA
if [ -f "$GUARDIAN_DIR/logs/system.log" ]; then
    ERRORS=$(tail -100 "$GUARDIAN_DIR/logs/system.log" | grep -c "ERROR")
    if [ $ERRORS -gt 10 ]; then
        echo "$(date): High error count: $ERRORS" >> $LOG_FILE
    fi
fi
EOF

chmod +x /usr/local/bin/guardian_monitor.sh

# Agregar a crontab
echo "*/5 * * * * /usr/local/bin/guardian_monitor.sh" | crontab -
```

---

## 🚀 Primer Uso

### Paso 1: Acceso Inicial
```
1. Abrir navegador web
2. Navegar a: https://tu-dominio.com/index_v3.php
3. Crear cuenta de administrador inicial
4. Configurar preferencias básicas
```

### Paso 2: Configuración Automática
```
1. Ir a "Configuración Avanzada"
2. Hacer clic en "Auto-Configurar Sistema"
3. Seleccionar perfil: "Quantum" para máxima seguridad
4. Esperar configuración automática (2-5 minutos)
```

### Paso 3: Activar Protecciones
```
1. AI Antivirus:
   - Activar "Protección en Tiempo Real"
   - Configurar "Análisis de IAs Automático"
   - Habilitar "Detección de Firmas Neurales"

2. AI VPN:
   - Configurar "Selección Automática de Servidor"
   - Activar "Encriptación Cuántica"
   - Habilitar "Optimización Inteligente"

3. Análisis Predictivo:
   - Activar "Predicción de Amenazas"
   - Configurar "Recomendaciones Proactivas"
   - Habilitar "Aprendizaje Continuo"
```

### Paso 4: Verificar Funcionamiento
```
1. Dashboard Principal:
   ✅ Estado del sistema: "Protegido"
   ✅ AI Antivirus: "Activo"
   ✅ AI VPN: "Conectado"
   ✅ Quantum Security: "Estable"
   ✅ Análisis Predictivo: "Funcionando"

2. Métricas en Tiempo Real:
   ✅ Amenazas bloqueadas: 0+
   ✅ IAs analizadas: 0+
   ✅ Conexiones VPN: 1+
   ✅ Predicciones generadas: 0+
```

---

## 🔧 Mantenimiento y Actualizaciones

### Mantenimiento Diario
```bash
# Script de mantenimiento diario
cat > /usr/local/bin/guardian_maintenance.sh << 'EOF'
#!/bin/bash
GUARDIAN_DIR="/path/to/GuardianIA_PHP_v3.0"

# Limpiar logs antiguos (más de 30 días)
find $GUARDIAN_DIR/logs -name "*.log" -mtime +30 -delete

# Optimizar base de datos
mysql -u guardian_user -p guardian_ia_v3 -e "OPTIMIZE TABLE threat_logs, performance_metrics, ai_scan_results;"

# Verificar integridad de archivos
cd $GUARDIAN_DIR
sha256sum -c checksums.txt >/dev/null || echo "$(date): File integrity check failed" >> logs/maintenance.log

# Backup de configuración
cp config.php backups/config_$(date +%Y%m%d).php

echo "$(date): Maintenance completed" >> logs/maintenance.log
EOF

chmod +x /usr/local/bin/guardian_maintenance.sh

# Ejecutar diariamente a las 2 AM
echo "0 2 * * * /usr/local/bin/guardian_maintenance.sh" | crontab -
```

### Backup Automático
```bash
# Script de backup
cat > /usr/local/bin/guardian_backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/backups/guardian_ia"
GUARDIAN_DIR="/path/to/GuardianIA_PHP_v3.0"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup de base de datos
mysqldump -u guardian_user -p guardian_ia_v3 > $BACKUP_DIR/db_backup_$DATE.sql

# Backup de archivos
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz -C $GUARDIAN_DIR .

# Limpiar backups antiguos (más de 7 días)
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "$(date): Backup completed" >> $GUARDIAN_DIR/logs/backup.log
EOF

chmod +x /usr/local/bin/guardian_backup.sh

# Ejecutar cada 6 horas
echo "0 */6 * * * /usr/local/bin/guardian_backup.sh" | crontab -
```

---

## 🆘 Solución de Problemas

### Problemas Comunes

#### Error de Conexión a Base de Datos
```bash
# Verificar servicio MySQL
systemctl status mysql

# Verificar credenciales
mysql -u guardian_user -p guardian_ia_v3 -e "SELECT 1;"

# Verificar permisos
mysql -u root -p -e "SHOW GRANTS FOR 'guardian_user'@'localhost';"
```

#### Error de Permisos de Archivos
```bash
# Restablecer permisos
cd /path/to/GuardianIA_PHP_v3.0
chmod 755 *.php
chmod 777 logs/ cache/ temp/ uploads/
chown -R www-data:www-data .
```

#### Error de Extensiones PHP
```bash
# Instalar extensiones faltantes (Ubuntu/Debian)
apt update
apt install php8.1-mysql php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-zip php8.1-intl php8.1-bcmath

# Reiniciar servidor web
systemctl restart apache2
# o
systemctl restart nginx
systemctl restart php8.1-fpm
```

#### Error de Memoria PHP
```bash
# Editar php.ini
nano /etc/php/8.1/apache2/php.ini

# Aumentar límites
memory_limit = 512M
max_execution_time = 300
max_input_vars = 3000

# Reiniciar Apache
systemctl restart apache2
```

### Logs de Diagnóstico
```bash
# Ver logs del sistema
tail -f /path/to/GuardianIA_PHP_v3.0/logs/system.log

# Ver logs de errores PHP
tail -f /var/log/apache2/error.log

# Ver logs de MySQL
tail -f /var/log/mysql/error.log

# Ver logs de acceso
tail -f /var/log/apache2/access.log
```

---

## 📞 Soporte y Recursos

### Documentación
- **README_v3.md**: Documentación principal
- **CHANGELOG_v3.md**: Historial de cambios
- **API_DOCUMENTATION.md**: Documentación de APIs

### Soporte Técnico
- **Email**: support@guardian-ia.com
- **Chat IA 24/7**: Disponible en el sistema
- **Foro**: community.guardian-ia.com
- **GitHub**: github.com/guardian-ia/v3

### Recursos Adicionales
- **Videos de instalación**: youtube.com/guardian-ia
- **Webinars**: webinars.guardian-ia.com
- **Certificaciones**: training.guardian-ia.com

---

## ✅ Checklist de Instalación

```
□ Verificar requisitos del sistema
□ Descargar e extraer GuardianIA v3.0
□ Configurar permisos de archivos
□ Crear base de datos MySQL
□ Importar estructura de BD
□ Configurar config.php
□ Configurar servidor web (Apache/Nginx)
□ Configurar SSL/TLS
□ Ejecutar tests del sistema
□ Verificar interfaz web
□ Configurar firewall
□ Configurar monitoreo
□ Configurar backups automáticos
□ Crear cuenta de administrador
□ Activar protecciones principales
□ Verificar funcionamiento completo
```

---

**🎉 ¡Felicitaciones! GuardianIA v3.0 está instalado y listo para proteger contra las amenazas más avanzadas del mundo, incluyendo IAs maliciosas.**

**🛡️ Ahora tienes el sistema de ciberseguridad más avanzado del planeta funcionando en tu infraestructura.**

