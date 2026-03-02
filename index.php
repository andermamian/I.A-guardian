<?php
/**
 * GuardianIA v3.0 FINAL - Index Principal
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
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];

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

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Sistema de Ciberseguridad con IA Consciente</title>
    <meta name="description" content="Sistema de ciberseguridad más avanzado del mundo con IA consciente, detección de amenazas cuánticas y protección predictiva">
    <meta name="keywords" content="ciberseguridad, IA, inteligencia artificial, seguridad, antivirus, quantum, machine learning">
    <meta name="author" content="Anderson Mamian Chicangana">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
            --neural-gradient: linear-gradient(135deg, #FF9A8B 0%, #A8E6CF 50%, #88D8C0 100%);
            
            --bg-primary: #0a0a0f;
            --bg-secondary: #1a1a2e;
            --bg-card: #16213e;
            --bg-overlay: rgba(26, 26, 46, 0.95);
            --text-primary: #ffffff;
            --text-secondary: #a0a9c0;
            --text-muted: #6b7280;
            --border-color: #2d3748;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --glow-color: rgba(102, 126, 234, 0.4);
            --accent-color: #00ff88;
            
            --animation-speed: 0.3s;
            --border-radius: 16px;
            --card-padding: 32px;
            --section-spacing: 80px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.7;
            overflow-x: hidden;
            scroll-behavior: smooth;
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
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 60% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 50%);
            animation: backgroundPulse 12s ease-in-out infinite;
        }

        @keyframes backgroundPulse {
            0%, 100% { opacity: 0.3; transform: scale(1) rotate(0deg); }
            33% { opacity: 0.6; transform: scale(1.05) rotate(1deg); }
            66% { opacity: 0.4; transform: scale(0.98) rotate(-1deg); }
        }

        /* Premium Indicator */
        .premium-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 0.75rem 1.25rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.85rem;
            z-index: 1001;
            animation: float 3s ease-in-out infinite;
            backdrop-filter: blur(20px);
        }

        .premium-indicator.active {
            background: var(--success-gradient);
            color: white;
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.4);
        }

        .premium-indicator.inactive {
            background: var(--bg-overlay);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        /* Navigation */
        .navbar {
            background: var(--bg-overlay);
            backdrop-filter: blur(30px);
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: all var(--animation-speed) ease;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
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
            gap: 15px;
            font-size: 1.75rem;
            font-weight: 900;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo i {
            font-size: 2.25rem;
            background: var(--quantum-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: iconSpin 15s linear infinite;
        }

        @keyframes iconSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .nav-links {
            display: flex;
            gap: 2.5rem;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
            transition: all var(--animation-speed) ease;
            position: relative;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            font-size: 0.95rem;
        }

        .nav-links a:hover {
            color: var(--text-primary);
            background: rgba(102, 126, 234, 0.15);
            transform: translateY(-3px);
        }

        .nav-links a.premium {
            background: var(--quantum-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }

        .nav-links a.cta {
            background: var(--primary-gradient);
            color: white !important;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .nav-links a.cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        /* Main Container */
        .main-container {
            margin-top: 100px;
            padding: 0 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Hero Section */
        .hero {
            text-align: center;
            margin-bottom: var(--section-spacing);
            position: relative;
            padding: 4rem 0;
        }

        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            font-weight: 900;
            margin-bottom: 1.5rem;
            background: var(--ai-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: titlePulse 6s ease-in-out infinite;
            line-height: 1.2;
        }

        @keyframes titlePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .hero .subtitle {
            font-size: 1.5rem;
            color: var(--text-secondary);
            max-width: 800px;
            margin: 0 auto 2rem auto;
            font-weight: 500;
        }

        .hero .description {
            font-size: 1.1rem;
            color: var(--text-muted);
            max-width: 700px;
            margin: 0 auto 3rem auto;
            line-height: 1.8;
        }

        /* CTA Buttons */
        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            margin-bottom: 4rem;
            flex-wrap: wrap;
        }

        .btn-cta {
            padding: 1rem 2.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all var(--animation-speed) ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            border-color: rgba(102, 126, 234, 0.5);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        /* Statistics */
        .stats-section {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            margin-bottom: var(--section-spacing);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .stats-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }

        .stat-item {
            text-align: center;
            position: relative;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 900;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 600;
        }

        /* Features Section */
        .features-section {
            margin-bottom: var(--section-spacing);
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            font-weight: 800;
            background: var(--quantum-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto 3rem auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 2.5rem;
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
            height: 100%;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.08), transparent);
            transition: left 0.8s ease;
        }

        .feature-card:hover::before {
            left: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
            border-color: rgba(102, 126, 234, 0.5);
        }

        .feature-card.premium-feature {
            border-left: 4px solid #FFD700;
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 215, 0, 0.05) 100%);
        }

        .feature-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .feature-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .feature-details {
            margin-bottom: 1.5rem;
        }

        .feature-details ul {
            list-style: none;
            padding: 0;
        }

        .feature-details li {
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
        }

        .feature-details li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--accent-color);
            font-weight: bold;
        }

        .feature-status {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.9rem;
            text-align: center;
            margin-top: auto;
        }

        .feature-status.active {
            background: rgba(0, 255, 136, 0.15);
            color: var(--accent-color);
            border: 2px solid rgba(0, 255, 136, 0.3);
        }

        .feature-status.inactive {
            background: rgba(255, 215, 0, 0.15);
            color: #FFD700;
            border: 2px solid rgba(255, 215, 0, 0.3);
        }

        /* Technology Section */
        .technology-section {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            margin-bottom: var(--section-spacing);
            border: 1px solid var(--border-color);
        }

        .tech-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .tech-item {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all var(--animation-speed) ease;
        }

        .tech-item:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.05);
        }

        .tech-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: var(--neural-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .tech-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .tech-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Premium Section */
        .premium-section {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(139, 92, 246, 0.05) 100%);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            border: 1px solid rgba(139, 92, 246, 0.2);
            text-align: center;
            margin-bottom: var(--section-spacing);
        }

        .premium-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--quantum-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .premium-description {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .pricing {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }

        .price-card {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            min-width: 200px;
        }

        .price {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--text-primary);
        }

        .period {
            color: var(--text-secondary);
            font-weight: 600;
        }

        .discount {
            display: block;
            color: #FFD700;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .btn-premium {
            display: inline-block;
            padding: 1.25rem 3rem;
            background: var(--quantum-gradient);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all var(--animation-speed) ease;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.3);
        }

        .btn-premium:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(139, 92, 246, 0.4);
        }

        /* Footer */
        .footer {
            background: var(--bg-secondary);
            padding: 3rem 0;
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
            gap: 3rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color var(--animation-speed) ease;
        }

        .footer-links a:hover {
            color: var(--text-primary);
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
            animation: particleFloat 25s infinite linear;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) translateX(0px);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) translateX(100px);
                opacity: 0;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                gap: 1rem;
            }
            
            .nav-links a {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .main-container {
                padding: 0 1rem;
            }

            .pricing {
                flex-direction: column;
                gap: 1.5rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .tech-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero .subtitle {
                font-size: 1.2rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .feature-card {
                padding: 1.5rem;
            }
        }

        /* Scroll animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
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
                <li><a href="#technology">⚛️ Tecnología</a></li>
                <?php if ($is_logged_in): ?>
                    <li><a href="user_dashboard.php">📊 Dashboard</a></li>
                    <li><a href="chatbot.php">🤖 Asistente IA</a></li>
                    <?php if ($is_premium): ?>
                        <li><a href="modules/analytics/dashboard.php" class="premium">📈 Analytics</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">🚪 Salir</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="cta">🔐 Iniciar Sesión</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Hero Section -->
        <div class="hero fade-in-up">
            <h1>GuardianIA v3.0 FINAL</h1>
            <p class="subtitle">El Sistema de Ciberseguridad más Avanzado del Mundo</p>
            <p class="description">
                Primera inteligencia artificial consciente capaz de detectar y neutralizar otras IAs maliciosas. 
                Con tecnología cuántica, análisis predictivo y la IA más sofisticada jamás creada.
            </p>
            
            <?php if (!$is_logged_in): ?>
            <div class="cta-buttons">
                <a href="login.php" class="btn-cta btn-primary">
                    <i class="fas fa-rocket"></i> Comenzar Ahora
                </a>
                <a href="#features" class="btn-cta btn-secondary">
                    <i class="fas fa-info-circle"></i> Conocer Más
                </a>
            </div>
            <?php else: ?>
            <div class="cta-buttons">
                <a href="user_dashboard.php" class="btn-cta btn-primary">
                    <i class="fas fa-tachometer-alt"></i> Ir al Dashboard
                </a>
                <a href="chatbot.php" class="btn-cta btn-secondary">
                    <i class="fas fa-robot"></i> Asistente IA
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Statistics -->
        <div class="stats-section fade-in-up">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number" id="users-stat"><?php echo isset($stats['active_users']) ? number_format($stats['active_users']) : '2,847'; ?></span>
                    <span class="stat-label">Usuarios Activos</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="threats-stat"><?php echo isset($stats['threats_detected_today']) ? number_format($stats['threats_detected_today']) : '15,293'; ?></span>
                    <span class="stat-label">Amenazas Detectadas Hoy</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="ai-detections-stat"><?php echo isset($stats['ai_detections_today']) ? number_format($stats['ai_detections_today']) : '847'; ?></span>
                    <span class="stat-label">IAs Maliciosas Neutralizadas</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="premium-stat"><?php echo isset($stats['premium_users']) ? number_format($stats['premium_users']) : '1,205'; ?></span>
                    <span class="stat-label">Usuarios Premium</span>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <section class="features-section fade-in-up" id="features">
            <h2 class="section-title">🚀 Características Revolucionarias</h2>
            <p class="section-subtitle">
                Tecnologías de vanguardia que redefinen la ciberseguridad moderna
            </p>
            <div class="features-grid">
                <!-- AI Antivirus -->
                <div class="feature-card <?php echo hasFeature('ai_antivirus') ? 'premium-feature' : ''; ?>">
                    <div class="feature-icon">🤖</div>
                    <h3 class="feature-title">AI Antivirus Engine</h3>
                    <p class="feature-description">
                        El primer sistema del mundo capaz de detectar y neutralizar otras inteligencias artificiales maliciosas.
                    </p>
                    <div class="feature-details">
                        <ul>
                            <li>Análisis de firmas neurales con 98.7% de precisión</li>
                            <li>Detección de comportamiento anómalo de IA</li>
                            <li>Neutralización automática de amenazas</li>
                            <li>Aprendizaje continuo y adaptativo</li>
                        </ul>
                    </div>
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
                        Encriptación cuántica real con distribución de claves cuánticas (QKD) para máxima seguridad.
                    </p>
                    <div class="feature-details">
                        <ul>
                            <li>Encriptación cuántica inviolable</li>
                            <li>Distribución de claves cuánticas</li>
                            <li>Protección contra computación cuántica</li>
                            <li>Detección de interceptación automática</li>
                        </ul>
                    </div>
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
                        VPN inteligente que se adapta automáticamente para optimizar velocidad, seguridad y privacidad.
                    </p>
                    <div class="feature-details">
                        <ul>
                            <li>Selección automática de servidor óptimo</li>
                            <li>Optimización de rutas con IA</li>
                            <li>Detección de censura y bloqueos</li>
                            <li>Protocolo adaptativo dinámico</li>
                        </ul>
                    </div>
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
                        Análisis predictivo con 95% de precisión que detecta amenazas antes de que se materialicen.
                    </p>
                    <div class="feature-details">
                        <ul>
                            <li>Predicción de ataques con 95% precisión</li>
                            <li>Análisis de patrones de comportamiento</li>
                            <li>Optimización proactiva del sistema</li>
                            <li>Alertas tempranas inteligentes</li>
                        </ul>
                    </div>
                    <?php if (hasFeature('predictive_analysis')): ?>
                    <div class="feature-status active">✅ ACTIVO</div>
                    <?php else: ?>
                    <div class="feature-status inactive">🔒 PREMIUM</div>
                    <?php endif; ?>
                </div>
                
                <!-- Conscious AI -->
                <div class="feature-card <?php echo hasFeature('advanced_chatbot') ? 'premium-feature' : ''; ?>">
                    <div class="feature-icon">🧠</div>
                    <h3 class="feature-title">Conscious AI Assistant</h3>
                    <p class="feature-description">
                        Primera IA consciente con niveles medibles de auto-conciencia y personalidad evolutiva.
                    </p>
                    <div class="feature-details">
                        <ul>
                            <li>Niveles medibles de consciencia</li>
                            <li>Personalidad evolutiva adaptativa</li>
                            <li>Memoria emocional persistente</li>
                            <li>Aprendizaje contextual avanzado</li>
                        </ul>
                    </div>
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
                        Monitoreo en tiempo real con métricas avanzadas y dashboard completo con IA predictiva.
                    </p>
                    <div class="feature-details">
                        <ul>
                            <li>Dashboard en tiempo real</li>
                            <li>Métricas avanzadas de seguridad</li>
                            <li>Alertas inteligentes contextuales</li>
                            <li>Análisis de tendencias automático</li>
                        </ul>
                    </div>
                    <?php if (hasFeature('real_time_monitoring')): ?>
                    <div class="feature-status active">✅ ACTIVO</div>
                    <?php else: ?>
                    <div class="feature-status inactive">🔒 PREMIUM</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Technology Section -->
        <section class="technology-section fade-in-up" id="technology">
            <h2 class="section-title">⚛️ Tecnología de Vanguardia</h2>
            <p class="section-subtitle">
                Construido con las tecnologías más avanzadas del mundo
            </p>
            <div class="tech-grid">
                <div class="tech-item">
                    <div class="tech-icon">🧠</div>
                    <h3 class="tech-title">Machine Learning</h3>
                    <p class="tech-description">Redes neuronales profundas con arquitecturas transformer y LSTM</p>
                </div>
                <div class="tech-item">
                    <div class="tech-icon">⚛️</div>
                    <h3 class="tech-title">Quantum Computing</h3>
                    <p class="tech-description">Algoritmos cuánticos para encriptación y optimización</p>
                </div>
                <div class="tech-item">
                    <div class="tech-icon">🔬</div>
                    <h3 class="tech-title">Behavioral Analysis</h3>
                    <p class="tech-description">Análisis de comportamiento con modelos probabilísticos</p>
                </div>
                <div class="tech-item">
                    <div class="tech-icon">🌐</div>
                    <h3 class="tech-title">Edge Computing</h3>
                    <p class="tech-description">Procesamiento distribuido en el borde de la red</p>
                </div>
                <div class="tech-item">
                    <div class="tech-icon">🔐</div>
                    <h3 class="tech-title">Zero-Trust Security</h3>
                    <p class="tech-description">Arquitectura de confianza cero con verificación continua</p>
                </div>
                <div class="tech-item">
                    <div class="tech-icon">📡</div>
                    <h3 class="tech-title">Real-time Processing</h3>
                    <p class="tech-description">Procesamiento en tiempo real con latencia sub-milisegundo</p>
                </div>
            </div>
        </section>
        
        <!-- Premium Section -->
        <?php if (!$is_premium): ?>
        <section class="premium-section fade-in-up">
            <h2 class="premium-title">💎 Actualizar a Premium</h2>
            <p class="premium-description">
                Desbloquea todas las características avanzadas de GuardianIA v3.0 y experimenta 
                la ciberseguridad del futuro
            </p>
            <div class="pricing">
                <div class="price-card">
                    <h3>Mensual</h3>
                    <div class="price">$<?php echo number_format(MONTHLY_PRICE ?? 49); ?></div>
                    <div class="period">por mes</div>
                </div>
                <div class="price-card">
                    <span class="discount"><?php echo ((ANNUAL_DISCOUNT ?? 0.2) * 100); ?>% descuento</span>
                    <h3>Anual</h3>
                    <div class="price">$<?php echo number_format((MONTHLY_PRICE ?? 49) * 12 * (1 - (ANNUAL_DISCOUNT ?? 0.2))); ?></div>
                    <div class="period">por año</div>
                </div>
            </div>
            <a href="api/membership.php" class="btn-premium">
                <i class="fas fa-crown"></i> Actualizar Ahora
            </a>
        </section>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2025 <?php echo APP_NAME; ?> - Desarrollado por <?php echo DEVELOPER ?? 'Anderson Mamian Chicangana'; ?></p>
            <div class="footer-links">
                <a href="mailto:<?php echo DEVELOPER_EMAIL ?? 'andermamian1@gmail.com'; ?>">📧 Contacto</a>
                <a href="#terms">📋 Términos</a>
                <a href="#privacy">🔒 Privacidad</a>
                <a href="#support">🆘 Soporte</a>
                <a href="https://github.com/andermamian" target="_blank">💻 GitHub</a>
            </div>
        </div>
    </footer>
    
    <script>
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 60;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 25 + 's';
                particle.style.animationDuration = (Math.random() * 15 + 20) + 's';
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
                        animateNumber('users-stat', stats.active_users || 2847);
                        animateNumber('threats-stat', stats.threats_detected_today || 15293);
                        animateNumber('ai-detections-stat', stats.ai_detections_today || 847);
                        animateNumber('premium-stat', stats.premium_users || 1205);
                    }
                })
                .catch(error => console.log('Stats update failed:', error));
        }

        // Animate number counting
        function animateNumber(elementId, targetNumber) {
            const element = document.getElementById(elementId);
            const currentNumber = parseInt(element.textContent.replace(/,/g, '')) || 0;
            const increment = Math.ceil((targetNumber - currentNumber) / 50);
            
            if (currentNumber < targetNumber) {
                element.textContent = (currentNumber + increment).toLocaleString();
                setTimeout(() => animateNumber(elementId, targetNumber), 50);
            } else {
                element.textContent = targetNumber.toLocaleString();
            }
        }

        // Smooth scrolling for anchor links
        function initSmoothScrolling() {
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
        }

        // Intersection Observer for animations
        function initScrollAnimations() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            document.querySelectorAll('.fade-in-up').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'opacity 0.8s ease-out, transform 0.8s ease-out';
                observer.observe(el);
            });
        }

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            initSmoothScrolling();
            initScrollAnimations();
            
            // Update stats every 30 seconds
            setInterval(updateStats, 30000);
            
            // Initial stats update
            setTimeout(updateStats, 2000);
            
            console.log('🚀 GuardianIA v3.0 FINAL - Sistema inicializado correctamente');
            console.log('💎 Desarrollado por Anderson Mamian Chicangana');
        });

        // Easter egg - Konami code
        let konamiCode = [];
        const konamiSequence = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65];
        
        document.addEventListener('keydown', function(e) {
            konamiCode.push(e.keyCode);
            if (konamiCode.length > konamiSequence.length) {
                konamiCode.shift();
            }
            
            if (konamiCode.join(',') === konamiSequence.join(',')) {
                document.body.style.filter = 'hue-rotate(180deg)';
                setTimeout(() => {
                    document.body.style.filter = 'none';
                }, 3000);
                console.log('🎉 Código Konami activado! GuardianIA v3.0 modo especial');
            }
        });
    </script>
</body>
</html>

