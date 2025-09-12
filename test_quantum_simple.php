<?php
session_start();
require_once __DIR__ . "/config.php";

// Simular login para prueba
$_SESSION["logged_in"] = true;
$_SESSION["username"] = "anderson";
$_SESSION["user_id"] = 1;

// Incluir configuración militar
require_once __DIR__ . '/config_military.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Quantum - GuardianIA v3.0</title>
    <style>
        body {
            font-family: monospace;
            background: #0a0a0a;
            color: #00ff88;
            padding: 20px;
        }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        pre { 
            background: rgba(0,255,136,0.1); 
            padding: 10px; 
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>Test del Sistema Cuántico</h1>
    
    <?php
    try {
        echo "<p>Cargando módulo cuántico...</p>";
        
        // Verificar que existe el archivo
        if (!file_exists(__DIR__ . '/quantum_encryption.php')) {
            echo "<p class='error'>❌ El archivo quantum_encryption.php no existe</p>";
            exit;
        }
        
        // Incluir el módulo
        require_once __DIR__ . '/quantum_encryption.php';
        echo "<p class='success'>✅ Módulo cargado</p>";
        
        // Crear instancia
        echo "<p>Inicializando sistema cuántico...</p>";
        $quantum = new AdvancedQuantumEncryption();
        echo "<p class='success'>✅ Sistema inicializado</p>";
        
        // Obtener métricas
        echo "<h2>Métricas del Sistema:</h2>";
        $metrics = $quantum->getAdvancedMetrics();
        echo "<pre>";
        echo "Total Qubits: " . $metrics['total_qubits'] . "\n";
        echo "Qubits Entrelazados: " . $metrics['entangled_qubits'] . "\n";
        echo "Fidelidad del Canal: " . number_format($metrics['channel_fidelity'] * 100, 2) . "%\n";
        echo "Nivel de Seguridad BB84: " . number_format($metrics['bb84_security_level'] * 100, 2) . "%\n";
        echo "</pre>";
        
        echo "<p class='success'>✅ Sistema cuántico operativo</p>";
        echo "<p><a href='firewall_quantum.php' style='color: #00ff88;'>Ir al Firewall Quantum</a></p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    ?>
</body>
</html>