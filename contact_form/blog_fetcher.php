<?php
/**
 * Auto Blog Fetcher - Fetches news from RSS feeds and websites
 * Topics: Semiconductors, Robotics, AI, Quantum Computing
 * Amin Adineh Website
 */

// Set execution limits to prevent crashes
set_time_limit(10); // Max 10 seconds execution
ini_set('memory_limit', '32M'); // Limit memory usage

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600'); // 1 hour cache
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// RSS Feed Sources
$RSS_FEEDS = [
    // Science Daily - Quantum Computing (fast and reliable)
    [
        'url' => 'https://www.sciencedaily.com/rss/matter_energy/quantum_computing.xml',
        'category' => 'Quantum Computing',
        'source' => 'Science Daily'
    ],
    // Science Daily - Robotics (fast and reliable)
    [
        'url' => 'https://www.sciencedaily.com/rss/matter_energy/robotics.xml',
        'category' => 'Robotics',
        'source' => 'Science Daily'
    ],
    // Science Daily - Semiconductors (fast and reliable)
    [
        'url' => 'https://www.sciencedaily.com/rss/matter_energy/semiconductors.xml',
        'category' => 'Semiconductors',
        'source' => 'Science Daily'
    ]
];

// Websites to scrape directly - DISABLED for performance
$SCRAPE_SITES = [];

// Cache settings - optimized for performance
define('CACHE_FILE', __DIR__ . '/blog_cache.json');
define('CACHE_DURATION', 3600); // 1 hour (longer cache = faster)
define('MAX_POSTS_PER_FEED', 2); // Only 2 posts per feed
define('MAX_TOTAL_POSTS', 6); // Total of 6 posts only

// Enable gzip compression for faster response
if (extension_loaded('zlib') && !ob_get_level()) {
    ob_start('ob_gzhandler');
}

/**
 * Fetch and parse RSS feed
 */
function fetchRSSFeed($feedUrl, $category, $source, $filter = null) {
    $posts = [];
    
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 3, // Very short timeout - 3 seconds max
                'user_agent' => 'Mozilla/5.0',
                'header' => "Accept: application/rss+xml\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $content = @file_get_contents($feedUrl, false, $context);
        
        if (!$content || strlen($content) > 500000) { // Limit content size
            return $posts;
        }
        
        // Suppress XML errors and limit parsing
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($content);
        
        if (!$xml) {
            return $posts;
        }
    } catch (Exception $e) {
        return $posts; // Return empty on any error
    }
    
    $count = 0;
    $maxItems = 2; // Exactly 2 items per feed
    
    try {
        // Handle RSS 2.0 format
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                if ($count >= $maxItems) break;
                
                $post = parseRSSItem($item, $category, $source);
                if ($post) {
                    $posts[] = $post;
                    $count++;
                }
            }
        }
        // Handle Atom format
        elseif (isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                if ($count >= $maxItems) break;
                
                $post = parseAtomEntry($entry, $category, $source);
                if ($post) {
                    $posts[] = $post;
                    $count++;
                }
            }
        }
    } catch (Exception $e) {
        // Return whatever we have so far
        return $posts;
    }
    
    return $posts;
}

/**
 * Parse RSS 2.0 item
 */
function parseRSSItem($item, $category, $source) {
    $title = trim((string)$item->title);
    $link = trim((string)$item->link);
    $description = trim((string)$item->description);
    $pubDate = (string)$item->pubDate;
    
    if (empty($title) || empty($link)) {
        return null;
    }
    
    // Clean description
    $description = strip_tags($description);
    $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
    $description = preg_replace('/\s+/', ' ', $description);
    $description = trim($description);
    if (strlen($description) > 300) {
        $description = substr($description, 0, 300) . '...';
    }
    
    // Extract image if available
    $image = extractImage($item);
    
    return [
        'id' => md5($link),
        'title' => $title,
        'link' => $link,
        'description' => $description,
        'image' => $image,
        'date' => $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : date('Y-m-d H:i:s'),
        'timestamp' => $pubDate ? strtotime($pubDate) : time(),
        'category' => $category,
        'source' => $source
    ];
}

/**
 * Extract image from RSS item
 */
function extractImage($item) {
    $image = null;
    
    // Check enclosure
    if (isset($item->enclosure) && isset($item->enclosure['url'])) {
        $type = (string)$item->enclosure['type'];
        if (strpos($type, 'image') !== false || empty($type)) {
            $image = (string)$item->enclosure['url'];
        }
    }
    
    // Check media:content
    if (!$image) {
        $namespaces = $item->getNamespaces(true);
        if (isset($namespaces['media'])) {
            $media = $item->children($namespaces['media']);
            if (isset($media->content) && isset($media->content['url'])) {
                $image = (string)$media->content['url'];
            } elseif (isset($media->thumbnail) && isset($media->thumbnail['url'])) {
                $image = (string)$media->thumbnail['url'];
            }
        }
    }
    
    // Check content for img tags
    if (!$image) {
        $content = (string)$item->description;
        if (isset($item->children('content', true)->encoded)) {
            $content = (string)$item->children('content', true)->encoded;
        }
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $matches)) {
            $image = $matches[1];
        }
    }
    
    return $image;
}

/**
 * Parse Atom entry
 */
function parseAtomEntry($entry, $category, $source) {
    $title = trim((string)$entry->title);
    
    $link = '';
    if (isset($entry->link)) {
        foreach ($entry->link as $l) {
            if ((string)$l['rel'] === 'alternate' || empty((string)$l['rel'])) {
                $link = (string)$l['href'];
                break;
            }
        }
        if (empty($link) && isset($entry->link['href'])) {
            $link = (string)$entry->link['href'];
        }
    }
    
    $description = isset($entry->summary) ? trim((string)$entry->summary) : '';
    if (empty($description) && isset($entry->content)) {
        $description = trim((string)$entry->content);
    }
    
    $pubDate = isset($entry->published) ? (string)$entry->published : (isset($entry->updated) ? (string)$entry->updated : '');
    
    if (empty($title) || empty($link)) {
        return null;
    }
    
    // Clean description
    $description = strip_tags($description);
    $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
    $description = preg_replace('/\s+/', ' ', $description);
    $description = trim($description);
    if (strlen($description) > 300) {
        $description = substr($description, 0, 300) . '...';
    }
    
    // Extract image from content
    $image = null;
    if (isset($entry->content)) {
        $content = (string)$entry->content;
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $matches)) {
            $image = $matches[1];
        }
    }
    
    return [
        'id' => md5($link),
        'title' => $title,
        'link' => $link,
        'description' => $description,
        'image' => $image,
        'date' => $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : date('Y-m-d H:i:s'),
        'timestamp' => $pubDate ? strtotime($pubDate) : time(),
        'category' => $category,
        'source' => $source
    ];
}

/**
 * Scrape website for news articles
 */
function scrapeWebsite($url, $siteName, $category) {
    $posts = [];
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 20,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'header' => "Accept: text/html,application/xhtml+xml\r\nAccept-Language: en-US,en;q=0.9\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $html = @file_get_contents($url, false, $context);
    
    if (!$html) {
        error_log("Failed to scrape: $url");
        return $posts;
    }
    
    // Suppress DOM errors
    libxml_use_internal_errors(true);
    
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    
    // Try to find article links - generic patterns
    $articlePatterns = [
        '//article//a[contains(@href, "/news") or contains(@href, "/article") or contains(@href, "/post")]',
        '//div[contains(@class, "news") or contains(@class, "article") or contains(@class, "post")]//a',
        '//h2/a | //h3/a | //h4/a',
        '//a[contains(@class, "title") or contains(@class, "headline")]'
    ];
    
    $foundLinks = [];
    $count = 0;
    
    foreach ($articlePatterns as $pattern) {
        $links = $xpath->query($pattern);
        
        if ($links && $links->length > 0) {
            foreach ($links as $link) {
                if ($count >= 5) break 2;
                
                $href = $link->getAttribute('href');
                $title = trim($link->textContent);
                
                // Skip empty or short titles
                if (empty($title) || strlen($title) < 10) continue;
                
                // Make absolute URL
                if (strpos($href, 'http') !== 0) {
                    $parsedUrl = parse_url($url);
                    $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                    $href = $baseUrl . ($href[0] === '/' ? '' : '/') . $href;
                }
                
                // Skip duplicates
                if (isset($foundLinks[$href])) continue;
                $foundLinks[$href] = true;
                
                // Try to get description and image from the article page
                $articleData = scrapeArticlePage($href);
                
                $posts[] = [
                    'id' => md5($href),
                    'title' => $title,
                    'link' => $href,
                    'description' => $articleData['description'] ?: "Latest news from $siteName",
                    'image' => $articleData['image'],
                    'date' => date('Y-m-d H:i:s'),
                    'timestamp' => time(),
                    'category' => $category,
                    'source' => $siteName
                ];
                
                $count++;
            }
        }
    }
    
    return $posts;
}

/**
 * Scrape individual article page for content
 */
function scrapeArticlePage($url) {
    $result = ['description' => '', 'image' => null];
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $html = @file_get_contents($url, false, $context);
    if (!$html) return $result;
    
    // Extract meta description
    if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/', $html, $matches)) {
        $result['description'] = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
    } elseif (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\']/', $html, $matches)) {
        $result['description'] = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
    }
    
    // Extract og:image
    if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/', $html, $matches)) {
        $result['image'] = $matches[1];
    } elseif (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/', $html, $matches)) {
        $result['image'] = $matches[1];
    }
    
    // If no description, try first paragraph
    if (empty($result['description'])) {
        if (preg_match('/<article[^>]*>.*?<p[^>]*>([^<]+)<\/p>/s', $html, $matches)) {
            $result['description'] = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
        }
    }
    
    // Limit description length
    if (strlen($result['description']) > 300) {
        $result['description'] = substr($result['description'], 0, 300) . '...';
    }
    
    return $result;
}

/**
 * Load cached posts
 */
function loadCache() {
    if (file_exists(CACHE_FILE)) {
        $cache = json_decode(file_get_contents(CACHE_FILE), true);
        if ($cache && isset($cache['timestamp']) && (time() - $cache['timestamp']) < CACHE_DURATION) {
            return $cache;
        }
    }
    return null;
}

/**
 * Save posts to cache
 */
function saveCache($posts) {
    $cache = [
        'timestamp' => time(),
        'posts' => $posts
    ];
    file_put_contents(CACHE_FILE, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Get custom posts (LinkedIn-style manual posts)
 */
function getCustomPosts() {
    $customFile = __DIR__ . '/custom_posts.json';
    if (file_exists($customFile)) {
        $posts = json_decode(file_get_contents($customFile), true);
        return is_array($posts) ? $posts : [];
    }
    return [];
}

/**
 * Add a custom post (for LinkedIn-style updates)
 */
function addCustomPost($title, $description, $category = 'Personal', $link = '', $image = '') {
    $customFile = __DIR__ . '/custom_posts.json';
    $posts = getCustomPosts();
    
    $newPost = [
        'id' => uniqid('custom_'),
        'title' => $title,
        'link' => $link ?: '#',
        'description' => $description,
        'image' => $image,
        'date' => date('Y-m-d H:i:s'),
        'timestamp' => time(),
        'category' => $category,
        'source' => 'Amin Adineh',
        'is_custom' => true
    ];
    
    array_unshift($posts, $newPost);
    
    // Keep only last 50 custom posts
    $posts = array_slice($posts, 0, 50);
    
    file_put_contents($customFile, json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    return $newPost;
}

// Main logic
$action = $_REQUEST['action'] ?? 'fetch';

switch ($action) {
    case 'fetch':
        try {
            // Check cache first
            $cache = loadCache();
            
            if ($cache && isset($cache['posts']) && is_array($cache['posts'])) {
                echo json_encode([
                    'success' => true,
                    'posts' => array_slice($cache['posts'], 0, 6), // Ensure max 6
                    'cached' => true,
                    'cache_age' => time() - $cache['timestamp']
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Fetch fresh content - limit to 2 feeds max for speed
            $allPosts = [];
            $feedCount = 0;
            $maxFeeds = 2; // Only 2 feeds to prevent crashes
            
            foreach (array_slice($RSS_FEEDS, 0, $maxFeeds) as $feed) {
                if ($feedCount >= $maxFeeds || count($allPosts) >= 6) {
                    break;
                }
                
                try {
                    $posts = fetchRSSFeed($feed['url'], $feed['category'], $feed['source']);
                    if (is_array($posts)) {
                        $allPosts = array_merge($allPosts, $posts);
                    }
                    $feedCount++;
                    
                    // Stop if we have enough
                    if (count($allPosts) >= 6) {
                        break;
                    }
                } catch (Exception $e) {
                    continue; // Skip failed feeds
                }
            }
        
            // Remove duplicates by ID (simple and fast)
            $uniquePosts = [];
            $seenIds = [];
            foreach ($allPosts as $post) {
                if (!isset($post['id']) || !isset($seenIds[$post['id']])) {
                    if (isset($post['id'])) {
                        $seenIds[$post['id']] = true;
                    }
                    $uniquePosts[] = $post;
                    if (count($uniquePosts) >= 6) {
                        break; // Stop at 6
                    }
                }
            }
            
            // Simple sort by timestamp
            if (count($uniquePosts) > 1) {
                usort($uniquePosts, function($a, $b) {
                    $tsA = isset($a['timestamp']) ? $a['timestamp'] : 0;
                    $tsB = isset($b['timestamp']) ? $b['timestamp'] : 0;
                    return $tsB - $tsA;
                });
            }
            
            // Ensure exactly 6 posts max
            $uniquePosts = array_slice($uniquePosts, 0, 6);
            
            // Save to cache
            if (count($uniquePosts) > 0) {
                saveCache($uniquePosts);
            }
            
            echo json_encode([
                'success' => true,
                'posts' => $uniquePosts,
                'cached' => false,
                'total' => count($uniquePosts)
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            // Return empty on any error to prevent crashes
            echo json_encode([
                'success' => true,
                'posts' => [],
                'cached' => false,
                'error' => 'Unable to load posts'
            ], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'refresh':
        // Force refresh - delete cache
        if (file_exists(CACHE_FILE)) {
            unlink(CACHE_FILE);
        }
        
        // Redirect to fetch
        header('Location: ?action=fetch');
        exit;
        break;
        
    case 'add':
        // Add custom post (requires POST)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $title = $input['title'] ?? '';
        $description = $input['description'] ?? '';
        $category = $input['category'] ?? 'Personal';
        $link = $input['link'] ?? '';
        $image = $input['image'] ?? '';
        
        if (empty($title) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Title and description required']);
            exit;
        }
        
        $post = addCustomPost($title, $description, $category, $link, $image);
        
        // Clear cache to show new post
        if (file_exists(CACHE_FILE)) {
            unlink(CACHE_FILE);
        }
        
        echo json_encode(['success' => true, 'post' => $post]);
        break;
        
    case 'categories':
        // Return available categories
        $categories = ['All', 'Semiconductors', 'Robotics', 'Robotics & AI', 'Quantum Computing', 'Quantum Physics', 'Electronics', 'Personal'];
        echo json_encode(['success' => true, 'categories' => $categories]);
        break;
        
    case 'sources':
        // Return configured sources
        $sources = [];
        foreach ($RSS_FEEDS as $feed) {
            $sources[] = [
                'name' => $feed['source'],
                'category' => $feed['category'],
                'type' => 'RSS'
            ];
        }
        foreach ($SCRAPE_SITES as $site) {
            $sources[] = [
                'name' => $site['name'],
                'category' => $site['category'],
                'type' => 'Scrape'
            ];
        }
        echo json_encode(['success' => true, 'sources' => $sources]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
