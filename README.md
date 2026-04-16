# GuardianIA v3.0 FINAL - Backend Python/Flask

Sistema de seguridad con inteligencia artificial, convertido de PHP a Python/Flask con soporte para MySQL via AppServ/phpMyAdmin.

## Descripción

GuardianIA es un sistema integral de seguridad con IA que incluye más de 40 módulos funcionales: chatbot inteligente, antivirus IA, firewall cuántico, encriptación militar, monitoreo en tiempo real, VPN, análisis predictivo, y mucho más.

**Desarrollado por:** Anderson Mamian Chicangana

## Requisitos

- Python 3.11+
- MySQL 5.7+ (via AppServ, XAMPP o WAMP)
- phpMyAdmin (para gestión de base de datos)

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/andermamian/I.A-guardian.git
cd guardian-ia-python
```

### 2. Instalar dependencias

```bash
pip install -r requirements.txt
```

### 3. Configurar la base de datos

Copiar el archivo de ejemplo y configurar las credenciales de MySQL de AppServ:

```bash
cp .env.example .env
```

Editar `.env` con las credenciales de tu MySQL local:

```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=guardianai_local
DB_PORT=3306
```

### 4. Crear la base de datos en phpMyAdmin

Acceder a phpMyAdmin (`http://localhost/phpMyAdmin`) y crear la base de datos `guardianai_local`.

La aplicación creará las tablas automáticamente al iniciar.

### 5. Ejecutar la aplicación

```bash
python app.py
```

La aplicación estará disponible en `http://localhost:8080`

## Credenciales por defecto

| Usuario | Contraseña | Tipo | Nivel de Seguridad |
|---------|-----------|------|-------------------|
| anderson | Ander12345@ | Admin Premium | TOP_SECRET |
| admin | admin123 | Admin | SECRET |

## Estructura del Proyecto

```
guardian-ia-python/
├── app.py                    # Aplicación principal Flask
├── config.py                 # Configuración (DB, seguridad, IA)
├── requirements.txt          # Dependencias Python
├── .env                      # Variables de entorno (local)
├── .env.example              # Ejemplo de configuración
├── blueprints/               # Módulos del backend
│   ├── auth.py               # Autenticación (login, register, logout)
│   ├── admin.py              # Panel de administración
│   ├── user.py               # Dashboard y módulos de usuario
│   ├── chatbot.py            # Chatbot IA y comunicaciones
│   ├── security.py           # Seguridad (antivirus, firewall, etc.)
│   ├── ai_modules.py         # IA avanzada (consciousness, learning, etc.)
│   ├── system.py             # Sistema (settings, backup, VPN, etc.)
│   └── pages.py              # Páginas públicas
├── models/                   # Modelos de base de datos SQLAlchemy
│   └── __init__.py
├── utils/                    # Utilidades
│   ├── security.py           # Funciones de seguridad y encriptación
│   └── database.py           # Conexión dual MySQL/SQLite
├── templates/                # 52 plantillas HTML (Jinja2)
│   ├── index.html
│   ├── login.html
│   ├── admin_dashboard.html
│   ├── user_dashboard.html
│   └── ... (48 más)
├── logs/                     # Logs del sistema
├── uploads/                  # Archivos subidos
├── cache/                    # Caché del sistema
├── military/                 # Datos militares
└── keys/                     # Claves de encriptación
```

## Módulos Incluidos

### Seguridad
- Antivirus IA (`/ai_antivirus`)
- Firewall Cuántico (`/quantum_firewall`)
- Encriptación Cuántica (`/quantum_encryption`)
- Centro de Amenazas (`/threat_intelligence`)
- VPN IA (`/ai_vpn`)
- Auditoría de Seguridad (`/security_audit`)
- Anti-Robo (`/anti_theft`)
- Escudo Web (`/web_shield`)
- Vigilancia de Apps (`/app_vigilance`)

### Inteligencia Artificial
- Chatbot Guardian (`/guardian_ai_chatbot`)
- Chatbot Clásico (`/chatbot`)
- Consciencia IA (`/ai_consciousness`)
- Aprendizaje IA (`/ai_learning`)
- Red Neuronal (`/neural_network`)
- Análisis Predictivo (`/predictive_analysis`)
- Vinculación Emocional (`/emotional_bonding`)
- Diseñador de Personalidad (`/personality_designer`)

### Sistema
- Monitor en Tiempo Real (`/real_time_monitor`)
- Diagnósticos (`/system_diagnostics`)
- Backup (`/backup_system`)
- Rendimiento (`/optimize_performance`)
- Actualizaciones (`/update_system`)
- Bóveda Digital (`/digital_vault`)
- Centro de Operaciones (`/operations_command`)
- Creador de Música (`/music_creator`)
- Hub de Comunicaciones (`/communication_hub`)
- Suite de Análisis de Código (`/code_analysis_suite`)

### Membresías
- Sistema de Membresías (`/membership_system`)
- Gestión de Membresías (`/membership_management`)

## Tecnologías

| Componente | Tecnología |
|-----------|-----------|
| Backend | Python 3.11 + Flask |
| Base de Datos | MySQL via AppServ/phpMyAdmin |
| ORM | SQLAlchemy + PyMySQL |
| Templates | Jinja2 (HTML) |
| Autenticación | Flask-Login + sesiones seguras |
| Seguridad | CSRF, rate limiting, encriptación militar |
| Fallback DB | SQLite (para desarrollo sin MySQL) |

## Notas

- La aplicación usa SQLite como fallback si MySQL no está disponible.
- Las tablas se crean automáticamente al iniciar la aplicación.
- Los usuarios por defecto se crean si la tabla de usuarios está vacía.
- Compatible con AppServ, XAMPP, WAMP y cualquier servidor MySQL.
