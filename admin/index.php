<?php
require_once '../db_config.php';
require_once '../functions.php';
require_once 'admin_auth.php';

// Check admin authentication
check_admin_auth();

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    admin_logout();
}

$admin_info = get_admin_info();

// total users
$t_result = $conn->query("SELECT COUNT(*) AS cnt FROM users");
$t = $t_result ? $t_result->fetch_assoc()['cnt'] : 0;

// active sessions
$a_result = $conn->query("SELECT COUNT(*) AS cnt FROM sessions WHERE status='active' AND end_time > NOW()");
$a = $a_result ? $a_result->fetch_assoc()['cnt'] : 0;

// revenue today (payments today)
$rev_result = $conn->query("SELECT IFNULL(SUM(amount),0) AS total FROM payments WHERE status='success' AND DATE(paid_at) = CURDATE()");
$rev = $rev_result ? $rev_result->fetch_assoc()['total'] : 0;

// total revenue
$total_rev = $conn->query("SELECT IFNULL(SUM(amount),0) AS total FROM payments WHERE status='success'");
$total_revenue = $total_rev ? $total_rev->fetch_assoc()['total'] : 0;

// recent transactions
$transactions = $conn->query("
    SELECT p.*, u.phone_number, pl.name as plan_name 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    JOIN plans pl ON p.plan_id = pl.id 
    WHERE p.status = 'success'
    ORDER BY p.paid_at DESC 
    LIMIT 10
");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - WiFi Billing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .sidebar {
            background: white;
            border-right: 1px solid #ddd;
            min-height: calc(100vh - 70px);
            padding: 20px 0;
            position: fixed;
            left: 0;
            top: 70px;
            width: 250px;
        }
        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }
        .sidebar a:hover,
        .sidebar a.active {
            background: #f8f9fa;
            border-left-color: #667eea;
            color: #667eea;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }
        .stat-label {
            color: #999;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">📶 WiFi Billing Admin</a>
            <div class="ms-auto">
                <span class="text-white me-3">Welcome, <?php echo htmlspecialchars($admin_info['username']); ?></span>
                <a href="?logout=1" class="btn btn-sm btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="index.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
        <a href="users.php"><i class="fas fa-users"></i> Users</a>
        <a href="sessions.php"><i class="fas fa-wifi"></i> Active Sessions</a>
        <a href="plans.php"><i class="fas fa-cube"></i> Plans</a>
        <a href="router.php"><i class="fas fa-network-wired"></i> Router Config</a>
        <a href="mpesa.php"><i class="fas fa-mobile"></i> M-PESA Config</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="mb-4">Dashboard</h1>
        
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($t); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value">Ksh <?php echo number_format($rev, 2); ?></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $a; ?></div>
                    <div class="stat-label">Active Sessions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value">Ksh <?php echo number_format($total_revenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div style="background: white; border-radius: 15px; padding: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
            <h5 class="mb-3">Recent Successful Payments</h5>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Phone</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($tx = $transactions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tx['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($tx['plan_name']); ?></td>
                                <td>Ksh <?php echo number_format($tx['amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $tx['status']; ?>">
                                        <?php echo ucfirst($tx['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $tx['paid_at'] ? date('M d, Y H:i', strtotime($tx['paid_at'])) : 'Pending'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin - Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark">
  <div class="container">
    <span class="navbar-brand mb-0 h1">🔐 Admin Panel</span>
    <div>
      <span class="text-light me-3">Welcome, <?php echo htmlspecialchars($admin_info['username']); ?></span>
      <a href="?logout=1" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-5">
  <h3>Admin Dashboard</h3>
  <div class="row">
    <div class="col-md-4">
      <div class="card text-white bg-primary mb-3"><div class="card-body"><h5>Total Users</h5><h2><?php echo $t; ?></h2></div></div>
    </div>
    <div class="col-md-4">
      <div class="card text-white bg-success mb-3"><div class="card-body"><h5>Active Subscriptions</h5><h2><?php echo $a; ?></h2></div></div>
    </div>
    <div class="col-md-4">
      <div class="card text-white bg-info mb-3"><div class="card-body"><h5>Revenue Today (KES)</h5><h2><?php echo number_format($rev,2); ?></h2></div></div>
    </div>
  </div>

  <h4>Admin Functions</h4>
  <div class="row mb-4">
    <div class="col-md-4">
      <a href="generate_voucher.php" class="btn btn-primary btn-lg w-100">📝 Generate Vouchers</a>
    </div>
    <div class="col-md-4">
      <a href="manage_vouchers.php" class="btn btn-info btn-lg w-100">📊 Manage Vouchers</a>
    </div>
    <div class="col-md-4">
      <a href="analytics.php" class="btn btn-warning btn-lg w-100">📈 Analytics</a>
    </div>
  </div>

  <h4>Recent Payments</h4>
  <table class="table table-bordered">
    <thead><tr><th>ID</th><th>User ID</th><th>Plan</th><th>Amount</th><th>When</th></tr></thead>
    <tbody>
      <?php
      $res = $conn->query("SELECT p.*, pl.name AS plan_name FROM payments p JOIN plans pl ON p.plan_id = pl.id ORDER BY p.paid_at DESC LIMIT 10");
      while($r = $res->fetch_assoc()){
          echo "<tr><td>{$r['id']}</td><td>{$r['user_id']}</td><td>".htmlspecialchars($r['plan_name'])."</td><td>".number_format($r['amount'],2)."</td><td>{$r['paid_at']}</td></tr>";
      }
      ?>
    </tbody>
  </table>
</div>
</body>
</html>
