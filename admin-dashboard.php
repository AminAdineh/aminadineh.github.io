<?php
/**
 * Visitor Analytics Dashboard - Password Protected
 * Amin Adineh Website
 */

session_start();

// ============================================
// LOGIN CREDENTIALS - KEEP THESE SECRET!
// ============================================
define('ADMIN_USERNAME', 'amin');
define('ADMIN_PASSWORD', 'Aa@Visitors202');
// ============================================

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin-dashboard.php');
    exit;
}

// Handle login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['login_time'] = time();
    } else {
        $login_error = 'Invalid username or password';
    }
}

// Check if logged in (session expires after 2 hours)
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
if ($is_logged_in && isset($_SESSION['login_time'])) {
    if (time() - $_SESSION['login_time'] > 7200) { // 2 hours
        session_destroy();
        $is_logged_in = false;
    }
}

// If not logged in, show login form
if (!$is_logged_in):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Amin Adineh</title>
    <link rel="shortcut icon" href="favicon.ico">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header .icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #ff714a 0%, #FF9800 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
            color: white;
        }
        
        .login-header h1 {
            font-size: 22px;
            color: #333;
            font-weight: 600;
        }
        
        .login-header p {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #444;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #ff714a;
            box-shadow: 0 0 0 4px rgba(255, 113, 74, 0.1);
        }
        
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ff714a 0%, #FF9800 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 113, 74, 0.4);
        }
        
        .error-msg {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-size: 13px;
        }
        
        .back-link:hover {
            color: #ff714a;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="icon">🔐</div>
            <h1>Admin Dashboard</h1>
            <p>Enter your credentials to continue</p>
        </div>
        
        <?php if ($login_error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            
            <button type="submit" name="login" class="login-btn">Sign In</button>
        </form>
        
        <a href="index.html" class="back-link">← Back to Website</a>
    </div>
</body>
</html>
<?php
exit;
endif;

// ============================================
// DASHBOARD (Only shown when logged in)
// ============================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Analytics - Amin Adineh</title>
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="stylesheet" href="css/fonts/fontawesome-free-5.12.1-web/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Space+Grotesk:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-dark: #0a0a0f;
            --bg-card: #12121a;
            --bg-hover: #1a1a24;
            --accent: #ff714a;
            --accent-glow: rgba(255, 113, 74, 0.3);
            --text-primary: #e8e8e8;
            --text-secondary: #8b8b9a;
            --text-muted: #5a5a68;
            --border: #2a2a3a;
            --danger: #ff6b6b;
            --warning: #ffd93d;
            --info: #6bcbff;
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(255, 113, 74, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(255, 152, 0, 0.06) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
            position: relative;
            z-index: 1;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 15px;
        }

        header h1 {
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        header h1 i {
            color: var(--accent);
        }

        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .refresh-btn, .logout-btn {
            background: linear-gradient(135deg, var(--accent) 0%, #FF9800 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .logout-btn {
            background: var(--bg-card);
            border: 1px solid var(--border);
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--accent-glow);
        }

        .logout-btn:hover {
            background: var(--bg-hover);
            border-color: var(--accent);
        }

        .clear-logs-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .clear-logs-btn:hover {
            background: #ff5252;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: var(--accent);
            box-shadow: 0 10px 40px rgba(255, 113, 74, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), #FF9800);
        }

        .stat-card h3 {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 36px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-primary);
            line-height: 1;
        }

        .stat-card .icon {
            position: absolute;
            top: 18px;
            right: 18px;
            font-size: 28px;
            color: var(--accent);
            opacity: 0.3;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--accent);
        }

        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 40px;
        }

        @media (max-width: 900px) {
            .data-grid {
                grid-template-columns: 1fr;
            }
        }

        .data-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .data-card-header {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-hover);
        }

        .data-card-header h3 {
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .data-card-body {
            padding: 18px;
            max-height: 320px;
            overflow-y: auto;
        }

        .data-card-body::-webkit-scrollbar {
            width: 5px;
        }

        .data-card-body::-webkit-scrollbar-track {
            background: var(--bg-dark);
        }

        .data-card-body::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 3px;
        }

        .country-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }

        .country-item:last-child {
            border-bottom: none;
        }

        .country-name {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .country-flag {
            font-size: 18px;
        }

        .country-count {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            color: var(--accent);
            background: rgba(255, 113, 74, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
        }

        .device-bar {
            margin-bottom: 14px;
        }

        .device-bar-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .device-bar-track {
            height: 8px;
            background: var(--bg-hover);
            border-radius: 4px;
            overflow: hidden;
        }

        .device-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .device-bar-fill.desktop {
            background: linear-gradient(90deg, #ff714a, #FF9800);
        }

        .device-bar-fill.mobile {
            background: linear-gradient(90deg, #22c55e, #16a34a);
        }

        .device-bar-fill.tablet {
            background: linear-gradient(90deg, #6bcbff, #0ea5e9);
        }

        .visitors-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .visitors-table th {
            text-align: left;
            padding: 12px 10px;
            background: var(--bg-hover);
            color: var(--text-secondary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 10px;
        }

        .visitors-table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .visitors-table tr:hover {
            background: var(--bg-hover);
        }

        .visitor-ip {
            font-family: 'JetBrains Mono', monospace;
            color: var(--info);
            font-size: 11px;
        }

        .visitor-location {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .visitor-device {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-secondary);
        }

        .visitor-time {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            color: var(--text-muted);
        }

        .badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 500;
        }

        .badge.email {
            background: rgba(255, 113, 74, 0.15);
            color: var(--accent);
        }

        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px;
            color: var(--text-muted);
        }

        .loading i {
            font-size: 22px;
            animation: spin 1s linear infinite;
            margin-right: 12px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 35px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 12px;
            opacity: 0.3;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 13px;
            margin-bottom: 18px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--accent);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .user-info {
            font-size: 12px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.html" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Website
        </a>

        <header>
            <h1><i class="fas fa-chart-line"></i> Visitor Analytics</h1>
            <div class="header-actions">
                <span class="user-info">Logged in as <strong><?php echo ADMIN_USERNAME; ?></strong></span>
                <button class="refresh-btn" onclick="loadStats()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button class="clear-logs-btn" onclick="clearLogs()" title="Clear all visitor logs">
                    <i class="fas fa-trash-alt"></i> Clear Logs
                </button>
                <a href="?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-eye"></i></div>
                <h3>Total Visits</h3>
                <div class="value" id="total-visits">-</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h3>Unique Visitors</h3>
                <div class="value" id="unique-visitors">-</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <h3>Last 24 Hours</h3>
                <div class="value" id="visits-24h">-</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-calendar-week"></i></div>
                <h3>Last 7 Days</h3>
                <div class="value" id="visits-7d">-</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <h3>Last 30 Days</h3>
                <div class="value" id="visits-30d">-</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-globe"></i></div>
                <h3>Countries</h3>
                <div class="value" id="countries-count">-</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-envelope"></i></div>
                <h3>Subscribers</h3>
                <div class="value" id="subscribers-count">-</div>
            </div>
        </div>

        <div class="data-grid">
            <div class="data-card">
                <div class="data-card-header">
                    <h3><i class="fas fa-globe-americas"></i> Top Countries</h3>
                </div>
                <div class="data-card-body" id="countries-list">
                    <div class="loading"><i class="fas fa-spinner"></i> Loading...</div>
                </div>
            </div>

            <div class="data-card">
                <div class="data-card-header">
                    <h3><i class="fas fa-desktop"></i> Devices</h3>
                </div>
                <div class="data-card-body" id="devices-chart">
                    <div class="loading"><i class="fas fa-spinner"></i> Loading...</div>
                </div>
            </div>
        </div>

        <div class="data-grid">
            <div class="data-card">
                <div class="data-card-header">
                    <h3><i class="fas fa-window-maximize"></i> Top Browsers</h3>
                </div>
                <div class="data-card-body" id="browsers-list">
                    <div class="loading"><i class="fas fa-spinner"></i> Loading...</div>
                </div>
            </div>

            <div class="data-card">
                <div class="data-card-header">
                    <h3><i class="fas fa-laptop"></i> Operating Systems</h3>
                </div>
                <div class="data-card-body" id="os-list">
                    <div class="loading"><i class="fas fa-spinner"></i> Loading...</div>
                </div>
            </div>
        </div>

        <div class="data-grid">
            <div class="data-card">
                <div class="data-card-header">
                    <h3><i class="fas fa-file-alt"></i> Top Pages</h3>
                </div>
                <div class="data-card-body" id="pages-list">
                    <div class="loading"><i class="fas fa-spinner"></i> Loading...</div>
                </div>
            </div>

            <div class="data-card">
                <div class="data-card-header">
                    <h3><i class="fas fa-link"></i> Top Referrers</h3>
                </div>
                <div class="data-card-body" id="referrers-list">
                    <div class="loading"><i class="fas fa-spinner"></i> Loading...</div>
                </div>
            </div>
        </div>

        <div class="section-title">
            <i class="fas fa-clock"></i> Recent Visitors
        </div>
        <div class="data-card">
            <div class="table-wrapper">
                <table class="visitors-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>IP Address</th>
                            <th>Location</th>
                            <th>Device / Browser</th>
                            <th>Page</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody id="visitors-tbody">
                        <tr>
                            <td colspan="6" class="loading">
                                <i class="fas fa-spinner"></i> Loading visitors...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function getCountryFlag(countryCode) {
            if (!countryCode || countryCode.length !== 2) return '🌍';
            const codePoints = [...countryCode.toUpperCase()].map(c => 127397 + c.charCodeAt(0));
            return String.fromCodePoint(...codePoints);
        }

        function formatTime(timestamp) {
            if (!timestamp) return '-';
            let date;
            if (typeof timestamp === 'string') {
                date = new Date(timestamp);
            } else if (typeof timestamp === 'number') {
                date = new Date(timestamp * 1000);
            } else {
                date = new Date(timestamp);
            }
            
            if (isNaN(date.getTime())) return '-';
            
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return 'Just now';
            if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
            if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        async function loadStats() {
            try {
                const response = await fetch('contact_form/visitor_tracker.php?action=stats');
                const data = await response.json();
                
                if (data.success) {
                    renderStats(data.stats);
                }
            } catch (err) {
                console.error('Failed to load stats:', err);
                document.getElementById('total-visits').textContent = 'Error';
            }
        }

        function renderStats(stats) {
            document.getElementById('total-visits').textContent = stats.total_visits || 0;
            document.getElementById('unique-visitors').textContent = stats.unique_visitors || 0;
            document.getElementById('visits-24h').textContent = stats.visits_last_24h || 0;
            document.getElementById('visits-7d').textContent = stats.visits_last_7d || 0;
            document.getElementById('visits-30d').textContent = stats.visits_last_30d || 0;
            document.getElementById('countries-count').textContent = Object.keys(stats.top_countries || {}).length;
            
            const subscriberCount = (stats.recent_visitors || []).filter(v => v.email).length;
            document.getElementById('subscribers-count').textContent = subscriberCount;

            renderCountries(stats.top_countries || {});
            renderDevices(stats.devices || {});
            renderBrowsers(stats.browsers || {});
            renderOperatingSystems(stats.operating_systems || {});
            renderPages(stats.page_views || {});
            renderReferrers(stats.referrers || {});
            renderVisitors(stats.recent_visitors || []);
        }

        function renderCountries(countries) {
            const container = document.getElementById('countries-list');
            
            if (Object.keys(countries).length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-globe"></i><p>No location data yet</p></div>';
                return;
            }

            let html = '';
            for (const [country, count] of Object.entries(countries)) {
                html += `
                    <div class="country-item">
                        <div class="country-name">
                            <span class="country-flag">${getCountryFlag(country.substring(0,2))}</span>
                            <span>${country}</span>
                        </div>
                        <span class="country-count">${count}</span>
                    </div>
                `;
            }
            container.innerHTML = html;
        }

        function renderDevices(devices) {
            const container = document.getElementById('devices-chart');
            const total = (devices.Desktop || 0) + (devices.Mobile || 0) + (devices.Tablet || 0);
            
            if (total === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-desktop"></i><p>No device data yet</p></div>';
                return;
            }

            const deviceData = [
                { name: 'Desktop', count: devices.Desktop || 0, class: 'desktop', icon: 'fas fa-desktop' },
                { name: 'Mobile', count: devices.Mobile || 0, class: 'mobile', icon: 'fas fa-mobile-alt' },
                { name: 'Tablet', count: devices.Tablet || 0, class: 'tablet', icon: 'fas fa-tablet-alt' }
            ];

            let html = '';
            for (const device of deviceData) {
                const percent = total > 0 ? Math.round((device.count / total) * 100) : 0;
                html += `
                    <div class="device-bar">
                        <div class="device-bar-header">
                            <span><i class="${device.icon}"></i> ${device.name}</span>
                            <span>${device.count} (${percent}%)</span>
                        </div>
                        <div class="device-bar-track">
                            <div class="device-bar-fill ${device.class}" style="width: ${percent}%"></div>
                        </div>
                    </div>
                `;
            }
            container.innerHTML = html;
        }

        function renderBrowsers(browsers) {
            const container = document.getElementById('browsers-list');
            
            if (Object.keys(browsers).length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-window-maximize"></i><p>No browser data yet</p></div>';
                return;
            }

            const total = Object.values(browsers).reduce((sum, count) => sum + count, 0);
            let html = '';
            for (const [browser, count] of Object.entries(browsers)) {
                const percent = total > 0 ? Math.round((count / total) * 100) : 0;
                html += `
                    <div class="country-item">
                        <div class="country-name">
                            <span>${browser}</span>
                        </div>
                        <span class="country-count">${count} (${percent}%)</span>
                    </div>
                `;
            }
            container.innerHTML = html;
        }

        function renderOperatingSystems(os) {
            const container = document.getElementById('os-list');
            
            if (Object.keys(os).length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-laptop"></i><p>No OS data yet</p></div>';
                return;
            }

            const total = Object.values(os).reduce((sum, count) => sum + count, 0);
            let html = '';
            for (const [osName, count] of Object.entries(os)) {
                const percent = total > 0 ? Math.round((count / total) * 100) : 0;
                html += `
                    <div class="country-item">
                        <div class="country-name">
                            <span>${osName}</span>
                        </div>
                        <span class="country-count">${count} (${percent}%)</span>
                    </div>
                `;
            }
            container.innerHTML = html;
        }

        function renderPages(pages) {
            const container = document.getElementById('pages-list');
            
            if (Object.keys(pages).length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-file-alt"></i><p>No page data yet</p></div>';
                return;
            }

            let html = '';
            for (const [page, count] of Object.entries(pages)) {
                const pageName = page === '/' ? 'Home' : page.replace(/^\//, '').replace(/\.html$/, '') || '/';
                html += `
                    <div class="country-item">
                        <div class="country-name">
                            <span>${pageName}</span>
                        </div>
                        <span class="country-count">${count}</span>
                    </div>
                `;
            }
            container.innerHTML = html;
        }

        function renderReferrers(referrers) {
            const container = document.getElementById('referrers-list');
            
            if (Object.keys(referrers).length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-link"></i><p>No referrer data yet</p></div>';
                return;
            }

            let html = '';
            for (const [referrer, count] of Object.entries(referrers)) {
                html += `
                    <div class="country-item">
                        <div class="country-name">
                            <span>${referrer}</span>
                        </div>
                        <span class="country-count">${count}</span>
                    </div>
                `;
            }
            container.innerHTML = html;
        }

        async function clearLogs() {
            if (!confirm('Are you sure you want to clear ALL visitor logs? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('contact_form/visitor_tracker.php?action=clear');
                const data = await response.json();
                
                if (data.success) {
                    alert('All visitor logs have been cleared successfully!');
                    loadStats(); // Reload stats
                } else {
                    alert('Error clearing logs: ' + (data.message || 'Unknown error'));
                }
            } catch (err) {
                console.error('Failed to clear logs:', err);
                alert('Error clearing logs. Please try again.');
            }
        }

        function renderVisitors(visitors) {
            const tbody = document.getElementById('visitors-tbody');
            
            if (visitors.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><i class="fas fa-users"></i><p>No visitors yet</p></td></tr>';
                return;
            }

            let html = '';
            for (const v of visitors) {
                const location = v.location || {};
                const device = v.device || {};
                
                const timestamp = v.timestamp_unix ? v.timestamp_unix * 1000 : (v.timestamp || '');
                html += `
                    <tr>
                        <td class="visitor-time">${formatTime(v.timestamp_unix || v.timestamp)}</td>
                        <td class="visitor-ip">${v.ip || '-'}</td>
                        <td class="visitor-location">
                            <span>${getCountryFlag(location.country_code)}</span>
                            ${location.city || '-'}, ${location.country || '-'}
                        </td>
                        <td class="visitor-device">
                            <i class="fas fa-${device.device === 'Mobile' ? 'mobile-alt' : (device.device === 'Tablet' ? 'tablet-alt' : 'desktop')}"></i>
                            ${device.browser || '-'} / ${device.os || '-'}
                        </td>
                        <td>${v.page || '/'}</td>
                        <td>${v.email ? '<span class="badge email">' + v.email + '</span>' : '-'}</td>
                    </tr>
                `;
            }
            tbody.innerHTML = html;
        }

        document.addEventListener('DOMContentLoaded', loadStats);
    </script>
</body>
</html>

