<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Main Entry Point
 * ================================================================
 * All requests are routed through this file
 * ================================================================
 */

// Start output buffering
ob_start();

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Define base path
define('BASE_PATH', __DIR__);

// Check if installed
$configFile = BASE_PATH . '/config/installed.php';
$isInstalled = file_exists($configFile);

// Load core configuration
require_once BASE_PATH . '/config/constants.php';

// Auto-create required directories
$requiredDirs = [
    UPLOADS_PATH,
    LOGOS_PATH,
    FAVICONS_PATH,
    DOCUMENTS_PATH,
    AVATARS_PATH,
    CACHE_PATH,
    LOGS_PATH
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Load helpers
require_once BASE_PATH . '/helpers/functions.php';

// Handle installation
$requestUri = $_GET['_url'] ?? '';
$requestUri = trim($requestUri, '/');

if (!$isInstalled && strpos($requestUri, 'install') !== 0 && $requestUri !== '') {
    redirect('/install');
    exit;
}

// Load database if installed
if ($isInstalled) {
    require_once BASE_PATH . '/config/database.php';
}

// Load session handler
require_once BASE_PATH . '/config/session.php';

// Start session
Session::start();

// Check remember me token
if ($isInstalled && !Session::isLoggedIn()) {
    Auth::checkRememberToken();
}

// Load routes
$routes = require BASE_PATH . '/config/routes.php';

/**
 * Router Class
 */
class Router {
    
    private $routes = [];
    private $params = [];
    
    public function __construct(array $routes) {
        $this->routes = $routes;
    }
    
    /**
     * Match current request to route
     */
    public function match(string $method, string $uri): ?array {
        $uri = '/' . trim($uri, '/');
        
        foreach ($this->routes as $pattern => $handler) {
            // Parse route pattern
            list($routeMethod, $routePath) = explode(' ', $pattern, 2);
            
            if ($routeMethod !== $method) {
                continue;
            }
            
            // Convert route pattern to regex
            $regex = $this->patternToRegex($routePath);
            
            if (preg_match($regex, $uri, $matches)) {
                // Extract named parameters
                $this->params = $this->extractParams($routePath, $matches);
                
                return [
                    'controller' => $handler[0],
                    'method' => $handler[1],
                    'middleware' => $handler[2] ?? null,
                    'params' => $this->params
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Convert route pattern to regex
     */
    private function patternToRegex(string $pattern): string {
        // First, extract parameter patterns before escaping
        $paramPatterns = [];
        $pattern = preg_replace_callback(
            '/{(\w+)(?::([^}]+))?}/',
            function ($matches) use (&$paramPatterns) {
                $placeholder = '___PARAM_' . count($paramPatterns) . '___';
                $name = $matches[1];
                $constraint = $matches[2] ?? '[^/]+';
                $paramPatterns[$placeholder] = "(?P<{$name}>{$constraint})";
                return $placeholder;
            },
            $pattern
        );
        
        // Now escape special regex characters
        $regex = preg_quote($pattern, '/');
        
        // Replace placeholders with actual regex patterns
        foreach ($paramPatterns as $placeholder => $replacement) {
            $regex = str_replace($placeholder, $replacement, $regex);
        }
        
        return '/^' . $regex . '$/';
    }
    
    /**
     * Extract parameters from matches
     */
    private function extractParams(string $pattern, array $matches): array {
        $params = [];
        
        preg_match_all('/{(\w+)/', $pattern, $paramNames);
        
        foreach ($paramNames[1] as $name) {
            if (isset($matches[$name])) {
                $params[$name] = $matches[$name];
            }
        }
        
        return $params;
    }
    
    /**
     * Get parameters
     */
    public function getParams(): array {
        return $this->params;
    }
}

/**
 * Handle Middleware
 * Supports comma-separated middleware, e.g. 'auth,feature:passenger' —
 * each one runs in order, first failure wins.
 */
function handleMiddleware(?string $middleware): bool {
    if (!$middleware) {
        return true;
    }

    foreach (explode(',', $middleware) as $single) {
        if (!handleSingleMiddleware(trim($single))) {
            return false;
        }
    }

    return true;
}

function handleSingleMiddleware(string $middleware): bool {
    // Parse middleware
    $parts = explode(':', $middleware);
    $type = $parts[0];
    $param = $parts[1] ?? null;

    switch ($type) {
        case 'auth':
            if (!Session::isLoggedIn()) {
                if (isAjax()) {
                    jsonResponse(['error' => 'Unauthorized'], 401);
                }
                Session::set('redirect_after_login', $_SERVER['REQUEST_URI']);
                redirect('/login');
                return false;
            }
            
            // Check permission if specified
            if ($param && $param !== 'superadmin') {
                if (!Session::can($param)) {
                    if (isAjax()) {
                        jsonResponse(['error' => 'Forbidden'], 403);
                    }
                    Session::flash('error', 'Anda tidak memiliki akses.');
                    redirect('/dashboard');
                    return false;
                }
            }
            
            // Super admin only check
            if ($param === 'superadmin' && !Session::isSuperAdmin()) {
                if (isAjax()) {
                    jsonResponse(['error' => 'Forbidden'], 403);
                }
                Session::flash('error', 'Hanya Super Admin yang dapat mengakses halaman ini.');
                redirect('/dashboard');
                return false;
            }
            break;
            
        case 'guest':
            if (Session::isLoggedIn()) {
                redirect('/dashboard');
                return false;
            }
            break;

        case 'feature':
            // Route gated behind a feature flag constant, e.g. 'feature:passenger'
            // -> checks FEATURE_PASSENGER_MANAGEMENT. Kept separate from 'auth'
            // so a disabled feature 404s cleanly instead of looking like a
            // permission error.
            $flagName = 'FEATURE_' . strtoupper($param ?? '');
            if (defined($flagName) && !constant($flagName)) {
                show404('Fitur ini sedang tidak aktif.');
                return false;
            }
            break;

        case 'page':
            // Route gated by Super Admin's per-role page access (Pengaturan >
            // Akses Halaman), e.g. 'page:orders'. Requires 'auth' to run first
            // (needs a logged-in user) — always pair as 'auth,page:xxx'.
            if (Session::isLoggedIn() && !Session::isSuperAdmin()) {
                $role = Session::userRole();
                if ($role && !isPageEnabledForRole($role, (string)$param)) {
                    Session::flash('error', 'Anda tidak memiliki akses ke halaman ini.');
                    redirect('/dashboard');
                    return false;
                }
            }
            break;
    }
    
    return true;
}

/**
 * Load Controller and Execute Method
 */
function dispatch(array $route): void {
    $controllerName = $route['controller'];
    $methodName = $route['method'];
    $params = $route['params'];
    
    $controllerFile = CONTROLLERS_PATH . '/' . $controllerName . '.php';
    
    if (!file_exists($controllerFile)) {
        show404("Controller not found: {$controllerName}");
        return;
    }
    
    require_once $controllerFile;
    
    if (!class_exists($controllerName)) {
        show404("Controller class not found: {$controllerName}");
        return;
    }
    
    $controller = new $controllerName();
    
    if (!method_exists($controller, $methodName)) {
        show404("Method not found: {$controllerName}::{$methodName}");
        return;
    }
    
    // Call method with parameters
    call_user_func_array([$controller, $methodName], $params);
}

/**
 * Show 404 Page
 */
function show404(string $message = 'Page not found'): void {
    http_response_code(404);
    
    if (isAjax()) {
        jsonResponse(['error' => $message], 404);
        return;
    }
    
    // Check if custom 404 view exists
    $errorView = VIEWS_PATH . '/errors/404.php';
    if (file_exists($errorView)) {
        include $errorView;
    } else {
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>404 - Halaman Tidak Ditemukan</title>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background: linear-gradient(135deg, #574314 0%, #c89b2c 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                }
                .container {
                    text-align: center;
                    padding: 2rem;
                }
                h1 { font-size: 8rem; font-weight: 700; opacity: 0.3; }
                h2 { font-size: 1.5rem; margin: 1rem 0; }
                p { opacity: 0.8; margin-bottom: 2rem; }
                a {
                    display: inline-block;
                    padding: 0.75rem 2rem;
                    background: rgba(255,255,255,0.2);
                    color: white;
                    text-decoration: none;
                    border-radius: 0.5rem;
                    backdrop-filter: blur(10px);
                    transition: background 0.3s;
                }
                a:hover { background: rgba(255,255,255,0.3); }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>404</h1>
                <h2>Halaman Tidak Ditemukan</h2>
                <p>Maaf, halaman yang Anda cari tidak tersedia.</p>
                <a href="' . BASE_URL . '/dashboard">Kembali ke Dashboard</a>
            </div>
        </body>
        </html>';
    }
}

/**
 * Show Error Page
 */
function showError(int $code, string $message = ''): void {
    http_response_code($code);
    
    $titles = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error'
    ];
    
    $title = $titles[$code] ?? 'Error';
    
    if (isAjax()) {
        jsonResponse(['error' => $message ?: $title], $code);
        return;
    }
    
    $errorView = VIEWS_PATH . '/errors/' . $code . '.php';
    if (file_exists($errorView)) {
        include $errorView;
    } else {
        echo "<h1>{$code} - {$title}</h1>";
        if ($message) {
            echo "<p>{$message}</p>";
        }
    }
}

// ================================================================
// MAIN ROUTING LOGIC
// ================================================================

// Handle error pages
if (isset($_GET['_error'])) {
    showError((int)$_GET['_error']);
    exit;
}

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = $requestUri;

// Handle PUT and DELETE from POST
if ($method === 'POST') {
    $inputMethod = $_POST['_method'] ?? null;
    if (in_array($inputMethod, ['PUT', 'DELETE', 'PATCH'])) {
        $method = $inputMethod;
    }
}

// Verify CSRF token for non-GET requests (if logged in)
if ($method !== 'GET' && Session::isLoggedIn() && strpos($uri, 'api/') === false) {
    $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!Session::verifyCsrfToken($token)) {
        if (isAjax()) {
            jsonResponse(['error' => 'Invalid CSRF token'], 403);
        } else {
            Session::flash('error', 'Token keamanan tidak valid. Silakan coba lagi.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
        }
        exit;
    }
}

// Create router and match route
$router = new Router($routes);
$route = $router->match($method, $uri);

if ($route) {
    // Handle middleware
    if (handleMiddleware($route['middleware'])) {
        // Dispatch to controller
        dispatch($route);
    }
} else {
    // No route matched
    show404();
}

// End output buffering and send
ob_end_flush();
