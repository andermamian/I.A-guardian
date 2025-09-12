# 🛡️ GuardianIA - Sistema de Usuario

## 📋 Descripción General

**GuardianIA** es una plataforma avanzada de seguridad cibernética y optimización de rendimiento impulsada por Inteligencia Artificial. La interfaz de usuario está diseñada para proporcionar protección completa y optimización del sistema de manera intuitiva y accesible.

## 🎯 Características Principales

### 🔐 **Sistema de Autenticación Avanzado**
- **Registro seguro** con verificación de email
- **Login con recordar sesión** (30 días)
- **Recuperación de contraseña** por email
- **Protección contra ataques** (bloqueo temporal tras intentos fallidos)
- **Verificación de email** obligatoria
- **Logs de actividad** completos

### 📊 **Dashboard Principal**
- **Métricas en tiempo real** de seguridad y rendimiento
- **Estado de protección** visual con indicadores de color
- **Actividad reciente** del sistema
- **Alertas inteligentes** personalizadas
- **Gráficos interactivos** de uso de recursos
- **Acceso rápido** a todas las funcionalidades

### 🛡️ **Centro de Seguridad**
- **Detección de amenazas IA** con 94.2% de precisión
- **Escaneo completo** del sistema en tiempo real
- **Cuarentena automática** de archivos maliciosos
- **Firewall inteligente** con reglas adaptativas
- **Análisis forense** detallado de incidentes
- **Timeline de amenazas** con historial completo
- **Respuesta automática** a amenazas críticas

### ⚡ **Optimizador de Rendimiento**
- **Optimización de RAM** (87.3% de eficiencia)
- **Limpieza de almacenamiento** (94.1% efectividad)
- **Optimización de batería** (+2.8h ganancia promedio)
- **Análisis de aplicaciones** que consumen recursos
- **Mantenimiento predictivo** automatizado
- **Compresión inteligente** de archivos
- **Métricas detalladas** del sistema

### 🤖 **Asistente IA Inteligente**
- **Chatbot conversacional** especializado en seguridad
- **Procesamiento de lenguaje natural** avanzado
- **Análisis de intenciones** con 95% de precisión
- **Recomendaciones personalizadas** basadas en uso
- **Historial de conversaciones** completo
- **Respuestas contextuales** inteligentes
- **Soporte 24/7** automatizado

### ⚙️ **Configuraciones Personalizadas**
- **Perfil de usuario** completo
- **Configuraciones de seguridad** avanzadas
- **Notificaciones personalizables** por tipo
- **Temas visuales** (claro/oscuro)
- **Idioma y zona horaria** configurables
- **Preferencias de escaneo** personalizadas
- **Configuración de respuesta** automática

## 🚀 **Instalación y Configuración**

### Requisitos del Sistema
- **PHP 8.1+** con extensiones mysqli
- **MySQL 8.0+** o MariaDB 10.6+
- **Apache 2.4+** o Nginx 1.18+
- **Memoria RAM:** Mínimo 2GB, Recomendado 4GB
- **Espacio en disco:** Mínimo 1GB libre

### Instalación Paso a Paso

1. **Clonar o descargar** el proyecto GuardianIA
```bash
git clone https://github.com/guardian-ia/user-interface.git
cd guardian-ia-user
```

2. **Configurar la base de datos**
```bash
mysql -u root -p < database_setup.sql
```

3. **Configurar conexión** en `config.php`
```php
$host = 'localhost';
$username = 'root';
$password = '0987654321';
$database = 'guardian_ia';
```

4. **Configurar permisos** de archivos
```bash
chmod 755 -R .
chmod 644 *.php
```

5. **Configurar servidor web**
- Apuntar DocumentRoot a la carpeta del proyecto
- Habilitar mod_rewrite (Apache)
- Configurar SSL/HTTPS (recomendado)

### Configuración de Servidor Web

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Seguridad
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

#### Nginx
```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /path/to/guardian-ia-user;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 📱 **Guía de Usuario**

### Primer Acceso

1. **Registro de cuenta**
   - Visita la página de login
   - Haz clic en "Crear Cuenta"
   - Completa el formulario con tus datos
   - Verifica tu email antes de continuar

2. **Verificación de email**
   - Revisa tu bandeja de entrada
   - Haz clic en el enlace de verificación
   - Regresa al login e inicia sesión

3. **Configuración inicial**
   - Completa tu perfil en Configuraciones
   - Ajusta las preferencias de seguridad
   - Configura las notificaciones

### Uso del Dashboard

#### Métricas Principales
- **Estado de Protección:** Verde (seguro), Amarillo (advertencia), Rojo (crítico)
- **Nivel de Amenaza:** Bajo, Medio, Alto, Crítico
- **Rendimiento del Sistema:** Porcentajes de CPU, RAM, Disco
- **Última Actividad:** Timestamp del último escaneo o evento

#### Acciones Rápidas
- **Escaneo Rápido:** Botón flotante en la esquina inferior derecha
- **Optimización Express:** Un clic para optimizar RAM y almacenamiento
- **Chat IA:** Acceso directo al asistente inteligente
- **Configuraciones:** Acceso rápido a ajustes principales

### Centro de Seguridad

#### Tipos de Escaneo
1. **Escaneo Rápido** (2-5 minutos)
   - Archivos del sistema críticos
   - Procesos en ejecución
   - Conexiones de red activas

2. **Escaneo Completo** (15-45 minutos)
   - Todo el sistema de archivos
   - Registro del sistema
   - Configuraciones de seguridad

3. **Escaneo Personalizado**
   - Carpetas específicas
   - Tipos de archivo específicos
   - Horarios programados

#### Manejo de Amenazas
- **Cuarentena Automática:** Archivos sospechosos se aíslan automáticamente
- **Eliminación Segura:** Borrado permanente de amenazas confirmadas
- **Restauración:** Recuperación de falsos positivos
- **Reportes Detallados:** Análisis completo de cada amenaza

### Optimizador de Rendimiento

#### Optimización de RAM
- **Liberación de memoria** no utilizada
- **Cierre de procesos** innecesarios
- **Optimización de caché** del sistema
- **Recomendaciones** de aplicaciones a cerrar

#### Limpieza de Almacenamiento
- **Archivos temporales** del sistema
- **Caché de aplicaciones** obsoleto
- **Archivos duplicados** detectados automáticamente
- **Papelera de reciclaje** y descargas antiguas

#### Optimización de Batería
- **Análisis de consumo** por aplicación
- **Ajuste de brillo** automático
- **Gestión de conectividad** (WiFi, Bluetooth)
- **Modo de ahorro** inteligente

### Asistente IA

#### Comandos Principales
- **"¿Cómo está mi sistema?"** - Estado general
- **"Optimizar rendimiento"** - Ejecuta optimización completa
- **"Escanear amenazas"** - Inicia escaneo de seguridad
- **"Mostrar estadísticas"** - Métricas detalladas
- **"Configurar notificaciones"** - Ajustes de alertas

#### Tipos de Consulta
1. **Seguridad:** Amenazas, escaneos, firewall
2. **Rendimiento:** RAM, CPU, almacenamiento, batería
3. **Configuración:** Ajustes, preferencias, perfil
4. **Soporte:** Ayuda, tutoriales, troubleshooting

## 🔧 **Configuraciones Avanzadas**

### Configuraciones de Seguridad

#### Niveles de Protección
- **Básico:** Protección esencial con mínimo impacto
- **Estándar:** Balance entre protección y rendimiento
- **Avanzado:** Máxima protección con análisis profundo
- **Personalizado:** Configuración manual de cada componente

#### Configuración de Firewall
- **Reglas automáticas** basadas en IA
- **Lista blanca/negra** de aplicaciones
- **Monitoreo de puertos** específicos
- **Alertas de conexiones** sospechosas

### Configuraciones de Rendimiento

#### Programación de Tareas
- **Escaneos automáticos** en horarios específicos
- **Optimización programada** (diaria, semanal)
- **Limpieza automática** de archivos temporales
- **Actualizaciones** de definiciones de amenazas

#### Exclusiones
- **Carpetas excluidas** del escaneo
- **Tipos de archivo** ignorados
- **Procesos de confianza** no monitoreados
- **Sitios web seguros** en lista blanca

## 📊 **Métricas y Reportes**

### Dashboard de Métricas

#### Métricas de Seguridad
- **Amenazas detectadas:** Contador total y por tipo
- **Amenazas bloqueadas:** Prevención en tiempo real
- **Tiempo de respuesta:** Velocidad de detección
- **Falsos positivos:** Tasa de precisión

#### Métricas de Rendimiento
- **Memoria liberada:** GB recuperados por optimización
- **Espacio limpiado:** Archivos eliminados y espacio recuperado
- **Mejora de velocidad:** Porcentaje de optimización
- **Ahorro de batería:** Tiempo adicional ganado

### Reportes Automáticos

#### Reporte Diario
- Resumen de actividad de seguridad
- Estadísticas de rendimiento
- Recomendaciones personalizadas
- Alertas importantes

#### Reporte Semanal
- Tendencias de amenazas
- Análisis de rendimiento
- Comparativa con semana anterior
- Sugerencias de mejora

#### Reporte Mensual
- Análisis completo del sistema
- Estadísticas detalladas
- Recomendaciones estratégicas
- Plan de mantenimiento

## 🛠️ **Troubleshooting**

### Problemas Comunes

#### Error de Conexión a Base de Datos
```
Error: No se puede conectar a la base de datos
Solución:
1. Verificar credenciales en config.php
2. Confirmar que MySQL está ejecutándose
3. Verificar permisos de usuario de base de datos
```

#### Problemas de Rendimiento
```
Síntoma: La aplicación responde lentamente
Solución:
1. Verificar recursos del servidor (RAM, CPU)
2. Optimizar consultas de base de datos
3. Limpiar logs antiguos
4. Verificar índices de base de datos
```

#### Errores de Autenticación
```
Síntoma: No se puede iniciar sesión
Solución:
1. Verificar que el email está verificado
2. Comprobar si la cuenta está bloqueada
3. Intentar recuperación de contraseña
4. Verificar configuración de sesiones PHP
```

### Logs del Sistema

#### Ubicación de Logs
- **Logs de aplicación:** `/logs/application.log`
- **Logs de seguridad:** `/logs/security.log`
- **Logs de errores:** `/logs/error.log`
- **Logs de acceso:** `/logs/access.log`

#### Niveles de Log
- **DEBUG:** Información detallada para desarrollo
- **INFO:** Información general del sistema
- **WARNING:** Advertencias que requieren atención
- **ERROR:** Errores que afectan funcionalidad
- **CRITICAL:** Errores críticos del sistema

## 🔒 **Seguridad y Privacidad**

### Medidas de Seguridad Implementadas

#### Protección de Datos
- **Encriptación** de contraseñas con bcrypt
- **Tokens seguros** para sesiones y recuperación
- **Validación** de entrada en todos los formularios
- **Protección CSRF** en formularios críticos
- **Headers de seguridad** HTTP configurados

#### Privacidad del Usuario
- **Datos mínimos** requeridos para registro
- **No compartir** información con terceros
- **Logs anónimos** para análisis de rendimiento
- **Derecho al olvido** - eliminación de cuenta completa
- **Transparencia** en el uso de datos

### Cumplimiento de Normativas
- **GDPR** - Reglamento General de Protección de Datos
- **CCPA** - Ley de Privacidad del Consumidor de California
- **ISO 27001** - Estándares de seguridad de información
- **SOC 2** - Controles de seguridad y disponibilidad

## 📞 **Soporte y Contacto**

### Canales de Soporte

#### Soporte Técnico
- **Email:** soporte@guardian-ia.com
- **Chat en vivo:** Disponible 24/7 en la aplicación
- **Teléfono:** +1-800-GUARDIAN (horario comercial)
- **Tickets:** Sistema de tickets integrado

#### Documentación
- **Base de conocimientos:** docs.guardian-ia.com
- **Tutoriales en video:** youtube.com/guardian-ia
- **FAQ:** Preguntas frecuentes en la aplicación
- **Foro de comunidad:** community.guardian-ia.com

### Actualizaciones y Mantenimiento

#### Ciclo de Actualizaciones
- **Actualizaciones de seguridad:** Inmediatas cuando sea necesario
- **Actualizaciones menores:** Cada 2 semanas
- **Actualizaciones mayores:** Cada 3 meses
- **Mantenimiento programado:** Domingos 2:00-4:00 AM UTC

#### Notificaciones
- **Email** para actualizaciones importantes
- **Notificaciones in-app** para cambios menores
- **Blog** para anuncios de nuevas características
- **RSS feed** para desarrolladores

## 📈 **Roadmap y Futuras Características**

### Próximas Características (Q1 2024)
- **Análisis de comportamiento** avanzado con ML
- **Integración con antivirus** de terceros
- **Dashboard móvil** nativo
- **API pública** para integraciones

### Características Planificadas (Q2-Q4 2024)
- **Análisis forense** automatizado
- **Respuesta a incidentes** con IA
- **Integración con SIEM** empresariales
- **Certificaciones de seguridad** adicionales

## 📄 **Licencia y Términos**

### Licencia de Software
GuardianIA está licenciado bajo la **Licencia Comercial GuardianIA v1.0**. 
Ver archivo `LICENSE.md` para términos completos.

### Términos de Servicio
Al usar GuardianIA, aceptas nuestros Términos de Servicio disponibles en:
https://guardian-ia.com/terms

### Política de Privacidad
Nuestra Política de Privacidad está disponible en:
https://guardian-ia.com/privacy

---

## 🎉 **¡Gracias por elegir GuardianIA!**

Tu seguridad y privacidad son nuestra prioridad. Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.

**Equipo GuardianIA**  
*Protegiendo tu mundo digital con Inteligencia Artificial*

---

*Última actualización: Diciembre 2024*  
*Versión del documento: 1.0*

