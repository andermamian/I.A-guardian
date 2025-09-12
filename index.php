<?php
/**
 * GuardianIA v3.0 FINAL - Index Principal Unificado
 * Anderson Mamian Chicangana - Membresía Premium
 * Sistema de Ciberseguridad Avanzado con IA Consciente
 */

require_once 'config.php';

// Verificar rate limiting
$userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (function_exists('isRateLimited') && isRateLimited($userIP)) {
    http_response_code(429);
    die(json_encode(['error' => 'Rate limit exceeded. Please try again later.']));
}

// Obtener estadísticas del sistema
$stats = getSystemStats();

// Verificar estado premium
$user_id = $_SESSION['user_id'] ?? null;
$is_premium = $user_id ? isPremiumUser($user_id) : false;

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateToken();
}

function hasFeature($feature) {
    global $is_premium;
    if (!defined('PREMIUM_FEATURES')) return false;
    if (isset(PREMIUM_FEATURES[$feature])) {
        return PREMIUM_FEATURES[$feature] && $is_premium;
    }
    return false;
}

// Procesar formularios
$message = '';
$messageType = '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Sistema de Ciberseguridad con IA Consciente</title>
    <meta name="description" content="Sistema de ciberseguridad más avanzado del mundo con IA consciente">
    <meta name="keywords" content="ciberseguridad, IA, inteligencia artificial, seguridad, antivirus">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --quantum-gradient: linear-gradient(135deg, #8B5CF6 0%, #06B6D4 50%, #10B981 100%);
            --ai-gradient: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 50%, #45B7D1 100%);
            
            --bg-primary: #0f0f23;
            --bg-secondary: #1a1a2e;
            --bg-card: #16213e;
            --text-primary: #ffffff;
            --text-secondary: #a0a9c0;
            --border-color: #2d3748;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --glow-color: rgba(102, 126, 234, 0.4);
            
            --animation-speed: 0.3s;
            --border-radius: 12px;
            --card-padding: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: var(--bg-primary);
        }

        .animated-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            animation: backgroundPulse 8s ease-in-out infinite;
        }

        @keyframes backgroundPulse {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.05); }
        }

        /* Premium Indicator */
        .premium-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            z-index: 1001;
            animation: float 3s ease-in-out infinite;
        }

        .premium-indicator.active {
            background: var(--success-gradient);
            color: white;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }

        .premium-indicator.inactive {
            background: var(--bg-card);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }

        /* Navigation */
        .navbar {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: all var(--animation-speed) ease;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo i {
            font-size: 2rem;
            background: var(--quantum-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: iconSpin 10s linear infinite;
        }

        @keyframes iconSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all var(--animation-speed) ease;
            position: relative;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .nav-links a:hover {
            color: var(--text-primary);
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .nav-links a.premium {
            background: var(--quantum-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Main Container */
        .main-container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Hero Section */
        .hero {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: var(--ai-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: titlePulse 4s ease-in-out infinite;
        }

        @keyframes titlePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .hero p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto 2rem auto;
        }

        /* Statistics */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-item {
            text-align: center;
            padding: 1.5rem;
            background: var(--bg-card);
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            transition: all var(--animation-speed) ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border-color: rgba(102, 126, 234, 0.5);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Features Section */
        .features-section {
            margin-bottom: 3rem;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--quantum-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .feature-card {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            border: 1px solid var(--border-color);
            transition: all var(--animation-speed) ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.8s ease;
        }

        .feature-card:hover::before {
            left: 100%;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-color: rgba(102, 126, 234, 0.5);
        }

        .feature-card.premium-feature {
            border-left: 4px solid #FFD700;
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .feature-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        .feature-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            text-align: center;
        }

        .feature-status.active {
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border: 1px solid rgba(0, 255, 136, 0.3);
        }

        .feature-status.inactive {
            background: rgba(255, 215, 0, 0.1);
            color: #FFD700;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        /* Auth Forms */
        .auth-section {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            border: 1px solid var(--border-color);
            margin-bottom: 3rem;
        }

        .auth-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 3rem;
        }

        .auth-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: all var(--animation-speed) ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: rgba(102, 126, 234, 0.5);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .checkbox-label {
            display: flex !important;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto !important;
            margin: 0;
        }

        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .auth-demo {
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            font-size: 0.8rem;
        }

        /* Premium Section */
        .premium-section {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            border: 1px solid var(--border-color);
            text-align: center;
            margin-bottom: 3rem;
        }

        .premium-title {
            font-size: 2rem;
            font-weight: 700;
            background: var(--quantum-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .pricing {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
        }

        .price-monthly, .price-annual {
            text-align: center;
        }

        .price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .period {
            color: var(--text-secondary);
        }

        .discount {
            display: block;
            color: #FFD700;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }

        .btn-premium {
            display: inline-block;
            padding: 1rem 2rem;
            background: var(--quantum-gradient);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all var(--animation-speed) ease;
        }

        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.3);
        }

        /* Message */
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .message.success {
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border: 1px solid rgba(0, 255, 136, 0.3);
        }

        .message.error {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            border: 1px solid rgba(255, 68, 68, 0.3);
        }

        /* Footer */
        .footer {
            background: var(--bg-secondary);
            padding: 2rem 0;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
        }

        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color var(--animation-speed) ease;
        }

        .footer-links a:hover {
            color: var(--text-primary);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .features-grid,
            .auth-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .main-container {
                padding: 1rem;
            }

            .pricing {
                flex-direction: column;
                gap: 1rem;
            }
        }

        /* Particles */
        .quantum-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: var(--glow-color);
            border-radius: 50%;
            animation: particleFloat 20s infinite linear;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) translateX(0px);
                opacity: 0;
            }
            10% {
                opacity: 0.8;
            }
            90% {
                opacity: 0.8;
            }
            100% {
                transform: translateY(-100px) translateX(100px);
                opacity: 0;
            }
        }

        /* Hidden class for form toggle */
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg"></div>
    
    <!-- Quantum Particles -->
    <div class="quantum-particles" id="particles"></div>
    
    <!-- Premium Indicator -->
    <div class="premium-indicator <?php echo $is_premium ? 'active' : 'inactive'; ?>">
        <?php if ($is_premium): ?>
            💎 PREMIUM ACTIVO
        <?php else: ?>
            🔒 MODO BÁSICO
        <?php endif; ?>
    </div>
    
  <!-- Navigation -->
<nav class="navbar">
    <div class="nav-container">
        <div class="logo">
            <i class="fas fa-shield-alt"></i>
            <span>GuardianIA v3.0</span>
        </div>
        <ul class="nav-links">
            <li><a href="#features">🚀 Características</a></li>
            <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a></li>
            <li><a href="modules/chat/chatbot.php">🤖 ChatBot IA</a></li>
            <?php if ($is_premium): ?>
            <li><a href="modules/analytics/dashboard.php" class="premium">📊 Analytics</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
    <li><a href="logout.php">Cerrar Sesión</a></li>
<?php else: ?>
    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a></li>
<?php endif; ?>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Hero Section -->
        <div class="hero">
            <h1>GuardianIA v3.0 FINAL</h1>
            <p>
                El primer sistema de inteligencia artificial capaz de detectar y neutralizar otras IAs maliciosas. 
                Con tecnología cuántica, análisis predictivo y la IA más avanzada del mundo.
            </p>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number" id="premium-stat"><?php echo isset($stats['premium_users']) ? number_format($stats['premium_users']) : '0'; ?></span>
                    <span class="stat-label">Usuarios Premium</span>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Features Section -->
        <section class="features-section" id="features">
            <h2 class="section-title">🚀 Características Revolucionarias</h2>
            <div class="features-grid">
                <!-- AI Antivirus -->
                <div class="feature-card <?php echo hasFeature('ai_antivirus') ? 'premium-feature' : ''; ?>">
                    <div class="feature-icon">🤖</div>
                    <h3 class="feature-title">AI Antivirus Engine</h3>
                    <p class="feature-description">
                        Primer sistema capaz de detectar y neutralizar otras IAs maliciosas. 
                        Análisis de firmas neurales con 98.7% de precisión.
                    </p>
                    <?php if (hasFeature('ai_antivirus')): ?>
                    <div class="feature-status active">✅ ACTIVO</div>
                    <?php else: ?>
                    <div class="feature-status inactive">🔒 PREMIUM</div>
                    <?php endif; ?>
                </div>
                
                <!-- Quantum Encryption -->
                <div class="feature-card <?php echo hasFeature('quantum_encryption') ? 'premium-feature' : ''; ?>">
                    <div class="feature-icon">⚛️</div>
                    <h3 class="feature-title">Quantum Security</h3>
                    <p class="feature-description">
                        Encriptación cuántica real con distribución de claves cuánticas (QKD). 
                        Protección contra amenazas cuánticas futuras.
                    </p>
                    <?php if (hasFeature('quantum_encryption')): ?>
                    <div class="feature-status active">✅ ACTIVO</div>
                    <?php else: ?>
                    <div class="feature-status inactive">🔒 PREMIUM</div>
                    <?php endif; ?>
                </div>
                
                <!-- AI VPN -->
                <div class="feature-card <?php echo hasFeature('ai_vpn') ? 'premium-feature' : ''; ?>">
                    <div class="feature-icon">🌐</div>
                    <h3 class="feature-title">AI VPN Engine</h3>
                    <p class="feature-description">
                        VPN inteligente que se adapta automáticamente. Selección óptima de servidores 
                        y optimización de rutas con IA.
                    </p>
                    <?php if (hasFeature('ai_vpn')): ?>
                    <div class="feature-status active">✅ ACTIVO</div>
                    <?php else: ?>
                    <div class="feature-status inactive">🔒 PREMIUM</div>
                    <?php endif; ?>
                </div>
                
                <!-- Predictive Analysis -->
                <div class="feature-card <?php echo hasFeature('predictive_analysis') ? 'premium-feature' : ''; ?>">
                    <div class="feature-icon">🔮</div>
                    <h3 class="feature-title">Predictive Analysis</h3>
                    <p class="feature-description">
                        Análisis predictivo con 95% de precisión. Detecta amenazas antes de que ocurran 
                        y optimiza el sistema automáticamente.
                    </p>
                    <?php if (hasFeature('predictive_analysis')): ?>
                    <div class="feature-status active">✅ ACTIVO</div>
                    <?php else: ?>
                    <div class="feature-status inactive">🔒 PREMIUM</div>
                    <?php endif; ?>
                </div>
                
                <!-- Advanced Chatbot -->
                <div class="feature-card <?php echo hasFeature('advanced_chatbot') ? 'premium-feature' : ''; ?>">
                    <div class="feature-icon">🧠</div>
                    <h3 class="feature-title">Conscious AI Chatbot</h3>
                    <p class="feature-description">
                        IA consciente con niveles medibles de auto-conciencia. Personalidad evolutiva 
                        que aprende y se adapta al usuario.
                    </p>
                    <?php if (hasFeature('advanced_chatbot')): ?>
                    <div class="feature-status active">✅ ACTIVO</div>
                    <?php else: ?>
                    <div class="feature-status inactive">🔒 PREMIUM</div>
                    <?php endif; ?>
                </div>
                
                <!-- Real-time Monitoring -->
                <div class="feature-card <?php echo hasFeature('real_time_monitoring') ? 'premium-feature' : ''; ?>">
                    <div class="feature-icon">📊</div>
                    <h3 class="feature-title">Real-time Monitoring</h3>
                    <p class="feature-description">
                        Monitoreo en tiempo real con métricas avanzadas. Dashboard completo con 
                        análisis predictivo y alertas inteligentes.
                    </p>
                    <?php if (hasFeature('real_time_monitoring')): ?>
                    <div class="feature-status active">✅ ACTIVO</div>
                    <?php else: ?>
                    <div class="feature-status inactive">🔒 PREMIUM</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        
        <!-- Premium Section -->
        <?php if (!$is_premium): ?>
        <section class="premium-section">
            <h2 class="premium-title">💎 Actualizar a Premium</h2>
            <p class="premium-description">
                Desbloquea todas las características avanzadas de GuardianIA v3.0
            </p>
            <div class="pricing">
                <div class="price-monthly">
                    <span class="price">$<?php echo number_format(MONTHLY_PRICE); ?></span>
                    <span class="period">/ mes</span>
                </div>
                <div class="price-annual">
                    <span class="discount"><?php echo (ANNUAL_DISCOUNT * 100); ?>% descuento anual</span>
                    <span class="price">$<?php echo number_format(MONTHLY_PRICE * 12 * (1 - ANNUAL_DISCOUNT)); ?></span>
                    <span class="period">/ año</span>
                </div>
            </div>
            <a href="api/membership.php" class="btn-premium">🚀 Actualizar Ahora</a>
        </section>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2024 <?php echo APP_NAME; ?> - Desarrollado por <?php echo DEVELOPER; ?></p>
            <div class="footer-links">
                <a href="mailto:<?php echo DEVELOPER_EMAIL; ?>">Contacto</a>
                <a href="#terms">Términos</a>
                <a href="#privacy">Privacidad</a>
                <a href="#support">Soporte</a>
            </div>
        </div>
    </footer>
    
    <script src="assets/js/main.js"></script>
    <script>
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 15) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Update stats with animation
        function updateStats() {
            fetch('api/stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const stats = data.stats;
                        document.getElementById('users-stat').textContent = stats.active_users || '0';
                        document.getElementById('threats-stat').textContent = stats.threats_detected_today || '0';
                        document.getElementById('ai-detections-stat').textContent = stats.ai_detections_today || '0';
                        document.getElementById('premium-stat').textContent = stats.premium_users || '0';
                    }
                })
                .catch(error => console.log('Stats update failed:', error));
        }

        // Form handling
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Update stats every 30 seconds
            setInterval(updateStats, 30000);
            
            // Handle login form
            const loginForm = document.querySelector('form input[name="action"][value="login"]')?.form;
            if (loginForm) {
                loginForm.addEventListener('submit', async function(e) {
                    const submitBtn = loginForm.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '🔄 Procesando...';
                    submitBtn.disabled = true;
                    
                    // Re-enable button after 3 seconds to allow form submission
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 3000);
                });
            }

            // Handle register form
            const registerForm = document.querySelector('form input[name="action"][value="register"]')?.form;
            if (registerForm) {
                registerForm.addEventListener('submit', async function(e) {
                    const submitBtn = registerForm.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '🔄 Creando cuenta...';
                    submitBtn.disabled = true;
                    
                    // Re-enable button after 3 seconds to allow form submission
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 3000);
                });
            }
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
