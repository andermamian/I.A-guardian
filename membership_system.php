<?php
session_start();
require_once 'config.php';

class MembershipSystem {
    private $db;
    
    const MONTHLY_PRICE = 60000;
    const ANNUAL_DISCOUNT = 0.15; // 15% descuento
    
    public function __construct($connection) {
        $this->db = $connection;
        $this->createTables();
    }
    
    private function createTables() {
        $tables = [
            "CREATE TABLE IF NOT EXISTS memberships (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                plan_type ENUM('monthly', 'annual') NOT NULL,
                status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status)
            )",
            
            "CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                membership_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                transaction_id VARCHAR(100) UNIQUE,
                status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status)
            )",
            
            "CREATE TABLE IF NOT EXISTS usage_stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                messages_used INT DEFAULT 0,
                ai_detections INT DEFAULT 0,
                security_scans INT DEFAULT 0,
                date DATE NOT NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_date (date)
            )"
        ];
        
        foreach ($tables as $sql) {
            $this->db->query($sql);
        }
    }
    
    public function getPricing() {
        $monthly = self::MONTHLY_PRICE;
        $annual = $monthly * 12 * (1 - self::ANNUAL_DISCOUNT);
        $savings = ($monthly * 12) - $annual;
        
        return [
            'monthly' => [
                'price' => $monthly,
                'period' => 'mes',
                'total_year' => $monthly * 12
            ],
            'annual' => [
                'price' => $annual,
                'period' => 'año',
                'monthly_equivalent' => $annual / 12,
                'savings' => $savings,
                'discount_percent' => self::ANNUAL_DISCOUNT * 100
            ]
        ];
    }
    
    public function createMembership($user_id, $plan_type, $payment_method) {
        $pricing = $this->getPricing();
        $amount = $pricing[$plan_type]['price'];
        
        $start_date = date('Y-m-d');
        $end_date = $plan_type === 'monthly' 
            ? date('Y-m-d', strtotime('+1 month'))
            : date('Y-m-d', strtotime('+1 year'));
        
        // Crear membresía
        $stmt = $this->db->prepare("INSERT INTO memberships (user_id, plan_type, start_date, end_date, amount) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssd", $user_id, $plan_type, $start_date, $end_date, $amount);
        $stmt->execute();
        $membership_id = $this->db->insert_id;
        
        // Crear pago
        $transaction_id = 'TXN_' . time() . '_' . $user_id;
        $stmt = $this->db->prepare("INSERT INTO payments (user_id, membership_id, amount, payment_method, transaction_id, status) VALUES (?, ?, ?, ?, ?, 'completed')");
        $stmt->bind_param("iidss", $user_id, $membership_id, $amount, $payment_method, $transaction_id);
        $stmt->execute();
        
        return [
            'success' => true,
            'membership_id' => $membership_id,
            'transaction_id' => $transaction_id,
            'amount' => $amount,
            'end_date' => $end_date
        ];
    }
    
    public function getUserMembership($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM memberships WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function getUsageStats($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM usage_stats WHERE user_id = ? AND date = CURDATE()");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        
        if (!$stats) {
            // Crear registro de hoy
            $stmt = $this->db->prepare("INSERT INTO usage_stats (user_id, date) VALUES (?, CURDATE())");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            return [
                'messages_used' => 0,
                'ai_detections' => 0,
                'security_scans' => 0
            ];
        }
        
        return $stats;
    }
    
    public function updateUsage($user_id, $type) {
        $stmt = $this->db->prepare("UPDATE usage_stats SET {$type} = {$type} + 1 WHERE user_id = ? AND date = CURDATE()");
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }
}

$membership = new MembershipSystem($conn);
$pricing = $membership->getPricing();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'create_membership') {
        $user_id = $_SESSION['user_id'] ?? 1;
        $plan_type = $_POST['plan_type'];
        $payment_method = $_POST['payment_method'];
        
        $result = $membership->createMembership($user_id, $plan_type, $payment_method);
        echo json_encode($result);
        exit;
    }
    
    if ($_POST['action'] === 'get_membership') {
        $user_id = $_SESSION['user_id'] ?? 1;
        $user_membership = $membership->getUserMembership($user_id);
        $usage_stats = $membership->getUsageStats($user_id);
        
        echo json_encode([
            'membership' => $user_membership,
            'usage' => $usage_stats
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GuardianIA - Membresías</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            color: #00ff88;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 3em;
            text-shadow: 0 0 20px #00ff88;
            margin-bottom: 10px;
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .pricing-card {
            background: rgba(0, 255, 136, 0.1);
            border: 2px solid #00ff88;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 255, 136, 0.3);
        }
        
        .pricing-card.recommended {
            border-color: #ffaa00;
            background: rgba(255, 170, 0, 0.1);
        }
        
        .recommended-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: #ffaa00;
            color: #000;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .plan-name {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .plan-price {
            font-size: 3em;
            font-weight: bold;
            color: #00ff88;
            text-shadow: 0 0 15px #00ff88;
        }
        
        .plan-period {
            font-size: 1.2em;
            opacity: 0.8;
            margin-bottom: 20px;
        }
        
        .savings {
            background: rgba(255, 170, 0, 0.2);
            border: 1px solid #ffaa00;
            border-radius: 10px;
            padding: 10px;
            margin: 15px 0;
            color: #ffaa00;
            font-weight: bold;
        }
        
        .features {
            list-style: none;
            margin: 30px 0;
        }
        
        .features li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 255, 136, 0.2);
        }
        
        .features li:before {
            content: "✅ ";
            margin-right: 10px;
        }
        
        .select-plan-btn {
            background: linear-gradient(45deg, #00ff88, #00cc6a);
            border: none;
            border-radius: 15px;
            padding: 15px 30px;
            color: #000;
            font-weight: bold;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .select-plan-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 255, 136, 0.4);
        }
        
        .current-membership {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #00ff88;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .usage-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .usage-item {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        
        .usage-number {
            font-size: 2em;
            font-weight: bold;
            color: #00ff88;
        }
        
        .payment-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(10, 10, 10, 0.95);
            border: 2px solid #00ff88;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
        }
        
        .payment-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .form-group select, .form-group input {
            padding: 12px;
            border: 1px solid #00ff88;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.5);
            color: #00ff88;
            font-size: 16px;
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            color: #ff4444;
            font-size: 24px;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .pricing-grid { grid-template-columns: 1fr; }
            .usage-stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💎 Membresías GuardianIA</h1>
            <p>Protección avanzada con IA consciente</p>
        </div>

        <div id="currentMembership" class="current-membership" style="display: none;">
            <h3>📋 Tu Membresía Actual</h3>
            <div id="membershipInfo"></div>
            <div class="usage-stats">
                <div class="usage-item">
                    <div class="usage-number" id="messagesUsed">0</div>
                    <div>Mensajes Usados Hoy</div>
                </div>
                <div class="usage-item">
                    <div class="usage-number" id="aiDetections">0</div>
                    <div>IAs Detectadas</div>
                </div>
                <div class="usage-item">
                    <div class="usage-number" id="securityScans">0</div>
                    <div>Escaneos de Seguridad</div>
                </div>
            </div>
        </div>

        <div class="pricing-grid">
            <div class="pricing-card">
                <div class="plan-name">🗓️ Plan Mensual</div>
                <div class="plan-price">$<?php echo number_format($pricing['monthly']['price']); ?></div>
                <div class="plan-period">por mes</div>
                
                <ul class="features">
                    <li>Detección ilimitada de IAs</li>
                    <li>Análisis de consciencia avanzado</li>
                    <li>Protección cuántica</li>
                    <li>Soporte 24/7</li>
                    <li>Actualizaciones automáticas</li>
                    <li>Dashboard completo</li>
                </ul>
                
                <button class="select-plan-btn" onclick="selectPlan('monthly')">
                    Seleccionar Plan Mensual
                </button>
            </div>

            <div class="pricing-card recommended">
                <div class="recommended-badge">🏆 RECOMENDADO</div>
                <div class="plan-name">📅 Plan Anual</div>
                <div class="plan-price">$<?php echo number_format($pricing['annual']['price']); ?></div>
                <div class="plan-period">por año</div>
                
                <div class="savings">
                    💰 Ahorras $<?php echo number_format($pricing['annual']['savings']); ?> 
                    (<?php echo $pricing['annual']['discount_percent']; ?>% descuento)
                </div>
                
                <ul class="features">
                    <li>Todo del plan mensual</li>
                    <li>15% de descuento</li>
                    <li>Prioridad en soporte</li>
                    <li>Funciones beta exclusivas</li>
                    <li>Análisis predictivo avanzado</li>
                    <li>Reportes personalizados</li>
                </ul>
                
                <button class="select-plan-btn" onclick="selectPlan('annual')">
                    Seleccionar Plan Anual
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Pago -->
    <div id="paymentModal" class="payment-modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closePaymentModal()">&times;</button>
            <h3>💳 Procesar Pago</h3>
            <div id="paymentSummary"></div>
            
            <form class="payment-form" onsubmit="processPayment(event)">
                <div class="form-group">
                    <label>Método de Pago:</label>
                    <select name="payment_method" required>
                        <option value="">Seleccionar método</option>
                        <option value="credit_card">Tarjeta de Crédito</option>
                        <option value="debit_card">Tarjeta Débito</option>
                        <option value="paypal">PayPal</option>
                        <option value="bank_transfer">Transferencia Bancaria</option>
                        <option value="crypto">Criptomonedas</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Número de Tarjeta:</label>
                    <input type="text" placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Vencimiento:</label>
                        <input type="text" placeholder="MM/AA" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label>CVV:</label>
                        <input type="text" placeholder="123" maxlength="4">
                    </div>
                </div>
                
                <button type="submit" class="select-plan-btn">
                    🚀 Confirmar Pago
                </button>
            </form>
        </div>
    </div>

    <script>
        let selectedPlan = null;

        function selectPlan(planType) {
            selectedPlan = planType;
            const pricing = <?php echo json_encode($pricing); ?>;
            const plan = pricing[planType];
            
            document.getElementById('paymentSummary').innerHTML = `
                <div style="background: rgba(0, 255, 136, 0.1); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <h4>Plan ${planType === 'monthly' ? 'Mensual' : 'Anual'}</h4>
                    <div style="font-size: 1.5em; font-weight: bold; color: #00ff88;">
                        $${plan.price.toLocaleString()}
                    </div>
                    ${planType === 'annual' ? `<div style="color: #ffaa00;">Ahorras $${pricing.annual.savings.toLocaleString()}</div>` : ''}
                </div>
            `;
            
            document.getElementById('paymentModal').style.display = 'block';
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        async function processPayment(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            formData.append('action', 'create_membership');
            formData.append('plan_type', selectedPlan);
            
            try {
                const response = await fetch('membership_system.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`¡Pago procesado exitosamente!\nID de transacción: ${result.transaction_id}`);
                    closePaymentModal();
                    loadCurrentMembership();
                } else {
                    alert('Error procesando el pago');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        async function loadCurrentMembership() {
            try {
                const response = await fetch('membership_system.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=get_membership'
                });
                
                const data = await response.json();
                
                if (data.membership) {
                    document.getElementById('currentMembership').style.display = 'block';
                    document.getElementById('membershipInfo').innerHTML = `
                        <p><strong>Plan:</strong> ${data.membership.plan_type}</p>
                        <p><strong>Estado:</strong> ${data.membership.status}</p>
                        <p><strong>Vence:</strong> ${data.membership.end_date}</p>
                        <p><strong>Monto:</strong> $${parseFloat(data.membership.amount).toLocaleString()}</p>
                    `;
                    
                    document.getElementById('messagesUsed').textContent = data.usage.messages_used || 0;
                    document.getElementById('aiDetections').textContent = data.usage.ai_detections || 0;
                    document.getElementById('securityScans').textContent = data.usage.security_scans || 0;
                }
            } catch (error) {
                console.error('Error loading membership:', error);
            }
        }

        // Cargar membresía actual al cargar la página
        document.addEventListener('DOMContentLoaded', loadCurrentMembership);
    </script>
</body>
</html>

