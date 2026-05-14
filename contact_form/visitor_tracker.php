<?php
/**
 * Visitor Tracker - Collects anonymous visitor analytics
 * Amin Adineh Website
 * 
 * Collects: IP, Location (via IP), User Agent, Browser, OS, Referrer, Email (if provided)
 * Note: MAC addresses cannot be collected via web (browser security restriction)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuration
define('VISITOR_LOG_FILE', __DIR__ . '/visitors_log.json');
define('MAX_LOG_ENTRIES', 1000); // Keep last 1000 visitors
define('IP_API_URL', 'http://ip-api.com/json/'); // Free IP geolocation API

/**
 * Get the real IP address of the visitor
 */
function getVisitorIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Can contain multiple IPs, get the first one
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Validate IP
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return 'Unknown';
}

/**
 * Get location info from IP using free API
 */
function getLocationFromIP($ip) {
    $location = [
        'country' => 'Unknown',
        'country_code' => '',
        'region' => 'Unknown',
        'city' => 'Unknown',
        'zip' => '',
        'lat' => null,
        'lon' => null,
        'timezone' => '',
        'isp' => '',
        'org' => ''
    ];
    
    // Don't lookup local IPs
    if ($ip === '127.0.0.1' || $ip === '::1' || $ip === 'Unknown' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        $location['city'] = 'Local Network';
        $location['country'] = 'Local';
        return $location;
    }
    
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents(IP_API_URL . urlencode($ip), false, $context);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['status']) && $data['status'] === 'success') {
                $location['country'] = $data['country'] ?? 'Unknown';
                $location['country_code'] = $data['countryCode'] ?? '';
                $location['region'] = $data['regionName'] ?? 'Unknown';
                $location['city'] = $data['city'] ?? 'Unknown';
                $location['zip'] = $data['zip'] ?? '';
                $location['lat'] = $data['lat'] ?? null;
                $location['lon'] = $data['lon'] ?? null;
                $location['timezone'] = $data['timezone'] ?? '';
                $location['isp'] = $data['isp'] ?? '';
                $location['org'] = $data['org'] ?? '';
            }
        }
    } catch (Exception $e) {
        // Silently fail, use defaults
    }
    
    return $location;
}

/**
 * Parse User Agent to extract browser and OS info
 */
function parseUserAgent($userAgent) {
    $result = [
        'browser' => 'Unknown',
        'browser_version' => '',
        'os' => 'Unknown',
        'os_version' => '',
        'device' => 'Desktop',
        'raw' => $userAgent
    ];
    
    if (empty($userAgent)) {
        return $result;
    }
    
    // Detect Browser
    $browsers = [
        'Edge' => '/Edge\/([0-9.]+)/',
        'Edg' => '/Edg\/([0-9.]+)/',
        'Chrome' => '/Chrome\/([0-9.]+)/',
        'Firefox' => '/Firefox\/([0-9.]+)/',
        'Safari' => '/Version\/([0-9.]+).*Safari/',
        'Opera' => '/OPR\/([0-9.]+)/',
        'IE' => '/MSIE ([0-9.]+)/',
        'Trident' => '/Trident\/.*rv:([0-9.]+)/'
    ];
    
    foreach ($browsers as $browser => $pattern) {
        if (preg_match($pattern, $userAgent, $matches)) {
            $result['browser'] = ($browser === 'Edg') ? 'Edge' : (($browser === 'Trident') ? 'IE' : $browser);
            $result['browser_version'] = $matches[1];
            break;
        }
    }
    
    // Detect OS
    $osPatterns = [
        'Windows 11' => '/Windows NT 10\.0.*Win64/',
        'Windows 10' => '/Windows NT 10\.0/',
        'Windows 8.1' => '/Windows NT 6\.3/',
        'Windows 8' => '/Windows NT 6\.2/',
        'Windows 7' => '/Windows NT 6\.1/',
        'macOS' => '/Mac OS X ([0-9_]+)/',
        'iOS' => '/iPhone OS ([0-9_]+)/',
        'Android' => '/Android ([0-9.]+)/',
        'Linux' => '/Linux/',
        'Ubuntu' => '/Ubuntu/'
    ];
    
    foreach ($osPatterns as $os => $pattern) {
        if (preg_match($pattern, $userAgent, $matches)) {
            $result['os'] = $os;
            if (isset($matches[1])) {
                $result['os_version'] = str_replace('_', '.', $matches[1]);
            }
            break;
        }
    }
    
    // Detect Device Type
    if (preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent)) {
        $result['device'] = preg_match('/iPad|Tablet/i', $userAgent) ? 'Tablet' : 'Mobile';
    }
    
    return $result;
}

/**
 * Load existing visitor log
 */
function loadVisitorLog() {
    if (file_exists(VISITOR_LOG_FILE)) {
        $content = file_get_contents(VISITOR_LOG_FILE);
        $data = json_decode($content, true);
        if (is_array($data)) {
            return $data;
        }
    }
    return ['visitors' => [], 'stats' => ['total_visits' => 0, 'unique_ips' => []]];
}

/**
 * Save visitor log
 */
function saveVisitorLog($data) {
    // Trim to max entries
    if (count($data['visitors']) > MAX_LOG_ENTRIES) {
        $data['visitors'] = array_slice($data['visitors'], -MAX_LOG_ENTRIES);
    }
    
    file_put_contents(VISITOR_LOG_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Generate visitor fingerprint (for uniqueness, not for tracking individuals)
 */
function generateFingerprint($ip, $userAgent) {
    return substr(md5($ip . $userAgent), 0, 16);
}

// Main logic
$action = $_REQUEST['action'] ?? 'track';

switch ($action) {
    case 'track':
        // Get visitor data from request
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $ip = getVisitorIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? ($input['user_agent'] ?? '');
        $referrer = $_SERVER['HTTP_REFERER'] ?? ($input['referrer'] ?? '');
        $email = isset($input['email']) ? filter_var($input['email'], FILTER_SANITIZE_EMAIL) : '';
        $page = $input['page'] ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $screenWidth = $input['screen_width'] ?? null;
        $screenHeight = $input['screen_height'] ?? null;
        $language = $input['language'] ?? ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        $timezone = $input['timezone'] ?? '';
        
        // Get location from IP
        $location = getLocationFromIP($ip);
        
        // Parse user agent
        $deviceInfo = parseUserAgent($userAgent);
        
        // Generate fingerprint
        $fingerprint = generateFingerprint($ip, $userAgent);
        
        // Create visitor record
        $visitor = [
            'id' => uniqid('v_'),
            'timestamp' => date('Y-m-d H:i:s'),
            'timestamp_unix' => time(),
            'ip' => $ip,
            'fingerprint' => $fingerprint,
            'location' => $location,
            'device' => $deviceInfo,
            'screen' => [
                'width' => $screenWidth,
                'height' => $screenHeight
            ],
            'page' => $page,
            'referrer' => $referrer,
            'language' => substr($language, 0, 50),
            'timezone' => $timezone,
            'email' => $email
        ];
        
        // Load and update log
        $log = loadVisitorLog();
        $log['visitors'][] = $visitor;
        $log['stats']['total_visits']++;
        
        if (!in_array($ip, $log['stats']['unique_ips'])) {
            $log['stats']['unique_ips'][] = $ip;
        }
        
        // Keep unique IPs list manageable
        if (count($log['stats']['unique_ips']) > 500) {
            $log['stats']['unique_ips'] = array_slice($log['stats']['unique_ips'], -500);
        }
        
        saveVisitorLog($log);
        
        echo json_encode([
            'success' => true,
            'message' => 'Visit tracked',
            'visitor_id' => $visitor['id']
        ]);
        break;
        
    case 'stats':
        // Return comprehensive stats (for admin use)
        $log = loadVisitorLog();
        
        // Calculate some stats
        $totalVisits = $log['stats']['total_visits'] ?? 0;
        $uniqueVisitors = count($log['stats']['unique_ips'] ?? []);
        $recentVisitors = array_slice(array_reverse($log['visitors']), 0, 50);
        
        // Country breakdown
        $countries = [];
        foreach ($log['visitors'] as $v) {
            $country = $v['location']['country'] ?? 'Unknown';
            $countries[$country] = ($countries[$country] ?? 0) + 1;
        }
        arsort($countries);
        
        // Device breakdown
        $devices = ['Desktop' => 0, 'Mobile' => 0, 'Tablet' => 0];
        foreach ($log['visitors'] as $v) {
            $device = $v['device']['device'] ?? 'Desktop';
            $devices[$device] = ($devices[$device] ?? 0) + 1;
        }
        
        // Browser breakdown
        $browsers = [];
        foreach ($log['visitors'] as $v) {
            $browser = $v['device']['browser'] ?? 'Unknown';
            $browsers[$browser] = ($browsers[$browser] ?? 0) + 1;
        }
        arsort($browsers);
        
        // OS breakdown
        $operatingSystems = [];
        foreach ($log['visitors'] as $v) {
            $os = $v['device']['os'] ?? 'Unknown';
            $operatingSystems[$os] = ($operatingSystems[$os] ?? 0) + 1;
        }
        arsort($operatingSystems);
        
        // Page views breakdown
        $pageViews = [];
        foreach ($log['visitors'] as $v) {
            $page = $v['page'] ?? '/';
            // Clean up page paths
            $page = parse_url($page, PHP_URL_PATH) ?: '/';
            $pageViews[$page] = ($pageViews[$page] ?? 0) + 1;
        }
        arsort($pageViews);
        
        // Referrer breakdown
        $referrers = [];
        foreach ($log['visitors'] as $v) {
            $ref = $v['referrer'] ?? 'Direct';
            if (empty($ref) || $ref === '') {
                $ref = 'Direct';
            } else {
                // Extract domain from referrer
                $parsed = parse_url($ref);
                $ref = isset($parsed['host']) ? $parsed['host'] : 'Direct';
            }
            $referrers[$ref] = ($referrers[$ref] ?? 0) + 1;
        }
        arsort($referrers);
        
        // Time-based stats (last 24 hours, 7 days, 30 days)
        $now = time();
        $visitsLast24h = 0;
        $visitsLast7d = 0;
        $visitsLast30d = 0;
        $hourlyDistribution = array_fill(0, 24, 0);
        $dailyDistribution = [];
        
        foreach ($log['visitors'] as $v) {
            $timestamp = isset($v['timestamp_unix']) ? $v['timestamp_unix'] : strtotime($v['timestamp'] ?? 'now');
            $age = $now - $timestamp;
            
            if ($age <= 86400) $visitsLast24h++;
            if ($age <= 604800) $visitsLast7d++;
            if ($age <= 2592000) $visitsLast30d++;
            
            // Hourly distribution
            $hour = (int)date('G', $timestamp);
            $hourlyDistribution[$hour]++;
            
            // Daily distribution (last 30 days)
            $date = date('Y-m-d', $timestamp);
            $dailyDistribution[$date] = ($dailyDistribution[$date] ?? 0) + 1;
        }
        
        // Sort daily distribution
        krsort($dailyDistribution);
        $dailyDistribution = array_slice($dailyDistribution, 0, 30, true);
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_visits' => $totalVisits,
                'unique_visitors' => $uniqueVisitors,
                'visits_last_24h' => $visitsLast24h,
                'visits_last_7d' => $visitsLast7d,
                'visits_last_30d' => $visitsLast30d,
                'top_countries' => array_slice($countries, 0, 10, true),
                'devices' => $devices,
                'browsers' => array_slice($browsers, 0, 10, true),
                'operating_systems' => array_slice($operatingSystems, 0, 10, true),
                'page_views' => array_slice($pageViews, 0, 15, true),
                'referrers' => array_slice($referrers, 0, 15, true),
                'hourly_distribution' => $hourlyDistribution,
                'daily_distribution' => $dailyDistribution,
                'recent_visitors' => $recentVisitors
            ]
        ]);
        break;
        
    case 'clear':
        // Clear all visitor logs (admin action)
        // Reset the log file to empty structure
        $emptyLog = [
            'visitors' => [],
            'stats' => [
                'total_visits' => 0,
                'unique_ips' => []
            ]
        ];
        
        saveVisitorLog($emptyLog);
        
        echo json_encode([
            'success' => true,
            'message' => 'All visitor logs have been cleared'
        ]);
        break;
        
    case 'subscribe':
        // Handle email subscription
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $email = isset($input['email']) ? filter_var($input['email'], FILTER_SANITIZE_EMAIL) : '';
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }
        
        // Save to subscribers file
        $subscribersFile = __DIR__ . '/subscribers.json';
        $subscribers = [];
        if (file_exists($subscribersFile)) {
            $subscribers = json_decode(file_get_contents($subscribersFile), true) ?? [];
        }
        
        // Check for duplicate
        $emails = array_column($subscribers, 'email');
        if (in_array($email, $emails)) {
            echo json_encode(['success' => true, 'message' => 'Already subscribed!']);
            exit;
        }
        
        $subscribers[] = [
            'email' => $email,
            'ip' => getVisitorIP(),
            'subscribed_at' => date('Y-m-d H:i:s'),
            'source' => 'linkedin_popup'
        ];
        
        file_put_contents($subscribersFile, json_encode($subscribers, JSON_PRETTY_PRINT));
        
        echo json_encode(['success' => true, 'message' => 'Subscribed successfully!']);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

