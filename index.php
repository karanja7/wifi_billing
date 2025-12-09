<?php
// Simple documentation index page
$pages = [
    [
        'title' => 'QUICKSTART.md',
        'desc' => '5-minute quick start guide to get the system running',
        'icon' => '⚡'
    ],
    [
        'title' => 'SYSTEM_SUMMARY.md',
        'desc' => 'Complete overview of the redesigned system architecture',
        'icon' => '📋'
    ],
    [
        'title' => 'DEPLOYMENT_GUIDE_v2.md',
        'desc' => 'Comprehensive deployment guide with all setup steps',
        'icon' => '📖'
    ],
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WiFi Billing System Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container-docs {
            max-width: 900px;
            width: 100%;
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
        .doc-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
        }
        .doc-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            color: #667eea;
        }
        .doc-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .doc-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .doc-desc {
            color: #666;
            font-size: 0.95rem;
        }
        .quick-links {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
        }
        .quick-links h3 {
            margin-bottom: 1rem;
        }
        .quick-links a {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 10px;
            margin-right: 1rem;
            margin-bottom: 0.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .quick-links a:hover {
            background: rgba(255,255,255,0.3);
        }
        .system-status {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
        }
        .status-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .status-item:last-child {
            border: none;
        }
        .status-check {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-docs">
        <!-- Header -->
        <div class="header">
            <h1>📶 WiFi Billing System</h1>
            <p>Captive Portal with M-PESA Payment Integration</p>
        </div>

        <!-- Quick Links -->
        <div class="quick-links">
            <h3>🔗 Quick Access</h3>
            <a href="portal.php">🌐 Portal (Users)</a>
            <a href="admin/index.php">🔐 Admin Dashboard</a>
            <a href="portal.php?mac=FF:FF:FF:FF:FF:FF">📱 Test Portal</a>
        </div>

        <!-- System Status -->
        <div class="system-status">
            <h3>✅ System Status</h3>
            <div class="status-item">
                <span class="status-check">✓</span> Database schema updated (v2.0)
            </div>
            <div class="status-item">
                <span class="status-check">✓</span> Portal interface created
            </div>
            <div class="status-item">
                <span class="status-check">✓</span> M-PESA integration ready
            </div>
            <div class="status-item">
                <span class="status-check">✓</span> Router integration ready
            </div>
            <div class="status-item">
                <span class="status-check">✓</span> Admin dashboard created
            </div>
            <div class="status-item">
                <span class="status-check">⏳</span> M-PESA credentials (pending)
            </div>
            <div class="status-item">
                <span class="status-check">⏳</span> Router configuration (pending)
            </div>
        </div>

        <!-- Documentation Cards -->
        <h2 style="color: white; margin-bottom: 2rem; text-align: center;">📚 Documentation</h2>

        <?php foreach ($pages as $page): ?>
            <a href="<?php echo strtolower(str_replace('.md', '.html', $page['title'])); ?>" class="doc-card">
                <div class="doc-icon"><?php echo $page['icon']; ?></div>
                <div class="doc-title"><?php echo htmlspecialchars($page['title']); ?></div>
                <div class="doc-desc"><?php echo htmlspecialchars($page['desc']); ?></div>
            </a>
        <?php endforeach; ?>

        <!-- Info Box -->
        <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 15px; padding: 2rem; color: white; text-align: center; margin-top: 2rem;">
            <h4>📖 How to Use This Documentation</h4>
            <p style="margin-bottom: 0;">
                <strong>New to the system?</strong> Start with <strong>QUICKSTART.md</strong><br>
                <strong>Want to understand the architecture?</strong> Read <strong>SYSTEM_SUMMARY.md</strong><br>
                <strong>Setting up for production?</strong> Follow <strong>DEPLOYMENT_GUIDE_v2.md</strong>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
