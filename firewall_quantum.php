<?php
session_start();
require_once __DIR__ . "/config.php";

// Verificar autenticación
if (!isset($_SESSION["logged_in"]) || !$_SESSION["logged_in"]) {
    header("Location: login.php");
    exit;
}

// Incluir configuración militar
require_once __DIR__ . '/config_military.php';
require_once __DIR__ . '/quantum_encryption.php';

// Crear instancia del sistema cuántico
try {
    $quantum = new AdvancedQuantumEncryption();
    $metrics = $quantum->getAdvancedMetrics();
    $quantum_active = true;
} catch (Exception $e) {
    $quantum_active = false;
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firewall Quantum - GuardianIA v3.0</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
            color: #00ff88;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: rgba(0, 255, 136, 0.1);
            border: 2px solid #00ff88;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        h1 {
            font-size: 2.5em;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .metric-card {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff88;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        .metric-value {
            font-size: 2em;
            font-weight: bold;
            color: #00ff88;
            margin: 10px 0;
        }
        .metric-label {
            color: rgba(0, 255, 136, 0.7);
            font-size: 0.9em;
        }
        .status-active {
            color: #00ff00;
        }
        .status-inactive {
            color: #ff6666;
        }
        .control-panel {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ Firewall Quantum</h1>
            <p>Sistema de Encriptación Cuántica Avanzada</p>
            <p>Usuario: <?php echo htmlspecialchars($_SESSION["username"] ?? "Usuario"); ?></p>
        </div>

        <?php if ($quantum_active): ?>
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-label">Total Qubits</div>
                    <div class="metric-value"><?php echo $metrics['total_qubits']; ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Qubits Entrelazados</div>
                    <div class="metric-value"><?php echo $metrics['entangled_qubits']; ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Fidelidad del Canal</div>
                    <div class="metric-value"><?php echo number_format($metrics['channel_fidelity'] * 100, 1); ?>%</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Seguridad BB84</div>
                    <div class="metric-value"><?php echo number_format($metrics['bb84_security_level'] * 100, 1); ?>%</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Volumen Cuántico</div>
                    <div class="metric-value"><?php echo number_format($metrics['quantum_volume'], 0); ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Estado</div>
                    <div class="metric-value status-active">ACTIVO</div>
                </div>
            </div>

            <div class="control-panel">
                <h2>Panel de Control</h2>
                <button class="btn" onclick="alert('Ejecutando protocolo BB84...')">Ejecutar BB84</button>
                <button class="btn" onclick="alert('Generando claves cuánticas...')">Generar Claves</button>
                <button class="btn" onclick="alert('Ejecutando test de Bell...')">Test de Bell</button>
                <button class="btn" onclick="location.reload()">Actualizar Métricas</button>
            </div>
        <?php else: ?>
            <div class="control-panel">
                <h2 class="status-inactive">Sistema Cuántico No Disponible</h2>
                <p>Error: <?php echo htmlspecialchars($error_message ?? 'Error desconocido'); ?></p>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="admin_dashboard.php" class="btn">← Volver al Dashboard</a>
        </div>
    </div>
</body>
</html>