<?php
require_once 'db_config.php';

session_start();

// Get device info
$mac_address = $_GET['mac'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
$redirect_url = $_GET['redirect'] ?? 'http://www.google.com';

// Get all active plans
$plans_result = $conn->prepare("SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC");
$plans_result->execute();
$plans = $plans_result->get_result()->fetch_all(MYSQLI_ASSOC);
$plans_result->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WiFi Billing - Get Connected</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container-custom {
            width: 100%;
            max-width: 1200px;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .plan-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .plan-card.popular {
            border: 3px solid #667eea;
            transform: scale(1.05);
        }
        
        .plan-card.popular::before {
            content: "POPULAR";
            position: absolute;
            top: -35px;
            left: 50%;
            transform: translateX(-50%) rotate(45deg);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 40px;
            font-weight: 700;
            font-size: 0.75rem;
            width: 150px;
            text-align: center;
        }
        
        .plan-duration {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .plan-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.3rem;
        }
        
        .plan-price-currency {
            font-size: 1.2rem;
        }
        
        .plan-description {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }
        
        .plan-features {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .plan-features li {
            padding: 0.5rem 0;
            color: #555;
            font-size: 0.9rem;
            border-bottom: 1px solid #eee;
        }
        
        .plan-features li:before {
            content: "✓ ";
            color: #667eea;
            font-weight: 700;
            margin-right: 0.5rem;
        }
        
        .btn-buy {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-buy:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .form-section {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 2rem;
        }
        
        .form-section h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .info-section {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
        }
        
        .info-section h3 {
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .info-section ul {
            list-style: none;
            padding-left: 0;
        }
        
        .info-section li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
        }
        
        .info-section li:before {
            content: "→";
            position: absolute;
            left: 0;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid #c3e6cb;
        }
        
        .phone-input-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .phone-prefix {
            background: #f8f9fa;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .phone-input-group input {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
            }
            
            .plan-card.popular {
                transform: scale(1);
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-custom">
        <!-- Header -->
        <div class="header">
            <h1>📶 Welcome to WiFi Billing</h1>
            <p>Get instant internet access via M-PESA</p>
        </div>
        
        <!-- Info Section -->
        <div class="info-section">
            <h3>⚡ How It Works</h3>
            <ul>
                <li>Select a plan and enter your phone number</li>
                <li>Pay via M-PESA (you'll get a prompt on your phone)</li>
                <li>Confirm the payment with your M-PESA PIN</li>
                <li>Get instant WiFi access immediately</li>
            </ul>
        </div>
        
        <!-- Plans Grid -->
        <div class="plans-grid">
            <?php foreach ($plans as $plan): ?>
                <div class="plan-card <?php echo $plan['id'] == 3 ? 'popular' : ''; ?>">
                    <div class="plan-duration">
                        <?php echo htmlspecialchars($plan['name']); ?>
                    </div>
                    <div class="plan-price">
                        <span class="plan-price-currency">Ksh</span>
                        <?php echo htmlspecialchars($plan['price']); ?>
                    </div>
                    <div class="plan-description">
                        <?php echo htmlspecialchars($plan['description']); ?>
                    </div>
                    
                    <ul class="plan-features">
                        <li><?php echo $plan['duration_hours']; ?> hour<?php echo $plan['duration_hours'] > 1 ? 's' : ''; ?> of access</li>
                        <li>Unlimited bandwidth</li>
                        <li>Automatic activation</li>
                    </ul>
                    
                    <button class="btn-buy" onclick="selectPlan(<?php echo $plan['id']; ?>)">
                        Choose Plan
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Payment Form -->
        <div class="form-section" id="paymentForm" style="display: none;">
            <h3>💳 Enter Your Details</h3>
            
            <form id="checkoutForm" method="POST" action="api/mpesa/stk_push.php">
                <input type="hidden" name="plan_id" id="plan_id" value="">
                <input type="hidden" name="mac_address" value="<?php echo htmlspecialchars($mac_address); ?>">
                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($redirect_url); ?>">
                
                <div class="form-group">
                    <label for="phone">📱 Phone Number</label>
                    <div class="phone-input-group">
                        <div class="phone-prefix">+254</div>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               placeholder="712 345 678"
                               required
                               pattern="[0-9]{9}"
                               maxlength="9">
                    </div>
                    <small class="text-muted">Enter your M-PESA registered phone number (without country code)</small>
                </div>
                
                <div class="form-group">
                    <label for="plan_display">📦 Selected Plan</label>
                    <input type="text" id="plan_display" disabled>
                </div>
                
                <div class="form-group">
                    <label for="amount_display">💰 Amount to Pay</label>
                    <input type="text" id="amount_display" disabled>
                </div>
                
                <button type="submit" class="btn-buy">
                    Pay via M-PESA
                </button>
                
                <button type="button" class="btn btn-outline-secondary w-100 mt-2" onclick="resetForm()">
                    Cancel
                </button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Plan data from PHP
        const plans = <?php echo json_encode($plans); ?>;
        
        function selectPlan(planId) {
            const plan = plans.find(p => p.id == planId);
            if (!plan) return;
            
            document.getElementById('plan_id').value = planId;
            document.getElementById('plan_display').value = plan.name + ' - Ksh ' + plan.price;
            document.getElementById('amount_display').value = 'Ksh ' + plan.price;
            
            document.getElementById('paymentForm').style.display = 'block';
            document.getElementById('phone').focus();
            
            // Scroll to form
            document.getElementById('paymentForm').scrollIntoView({ behavior: 'smooth' });
        }
        
        function resetForm() {
            document.getElementById('paymentForm').style.display = 'none';
            document.getElementById('checkoutForm').reset();
            document.getElementById('plan_id').value = '';
        }
    </script>
</body>
</html>
