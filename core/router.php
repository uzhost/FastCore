<?php
/**
 * core/router.php
 * Safe, minimal router for FastCore.
 *
 * How it resolves:
 *  1) Parses REQUEST_URI (without query string)
 *  2) Normalizes to a "route" like "help" or "blog/post"
 *  3) If a global $ROUTES array (from routes.php) exists, tries it first
 *  4) Otherwise falls back to pages/<route>.php (and pages/index.php for "/")
 *  5) Includes pages/404.php on miss (or emits a minimal 404)
 *
 * Exposes router_dispatch() that index.php can call.
 */

if (!defined('FastCore')) {
    // Keep this short and header-safe
    http_response_code(403);
    exit('Forbidden');
}

final class Router
{
    /** Absolute project root (parent of /core). @var string */
    private string $root;

    /** Absolute pages dir path. @var string */
    private string $pagesDir;

    /** Optional admin prefix (e.g., 'admin'); define('ADMIN_PREFIX','admin') in config. */
    private string $adminPrefix;

    /** Parsed route (like "help" or "news/view"). @var string */
    private string $route = '';

    /** Remaining URL segments after the route (params). @var string[] */
    private array $params = [];

    public function __construct(string $root)
    {
        $this->root       = rtrim($root, '/');
        $this->pagesDir   = $this->root . '/pages';
        $this->adminPrefix = defined('ADMIN_PREFIX') && ADMIN_PREFIX !== '' ? (string)ADMIN_PREFIX : 'admin';
        $this->parseRequest();
    }

    /**
     * Dispatch the current request.
     * - First try the global $ROUTES mapping if present.
     * - Else fall back to pages/<route>.php or pages/index.php.
     */
    public function dispatch(): void
    {
        // Try explicit routes first (from routes.php)
        if (isset($GLOBALS['ROUTES']) && is_array($GLOBALS['ROUTES'])) {
            if ($this->dispatchViaRoutesArray($GLOBALS['ROUTES'])) {
                return;
            }
        }

        // Fallback: filesystem-based routing (pages/<route>.php)
        $this->dispatchViaFilesystem();
    }

    /**
     * Return current route (for templates).
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * Return remaining URL segments after the route.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Parse REQUEST_URI into $this->route and $this->params.
     */
    private function parseRequest(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Strip query string
        $qpos = strpos($uri, '?');
        if ($qpos !== false) {
            $uri = substr($uri, 0, $qpos);
        }

        // Decode and normalize
        $uri = rawurldecode($uri);
        $uri = '/' . ltrim($uri, '/');

        // Basic security: block directory traversal and control chars
        if (strpos($uri, "\0") !== false || strpos($uri, '..') !== false) {
            $this->emitHttpStatus(400, 'Bad Request');
            exit;
        }

        // Remove trailing slash (but leave root "/")
        if (strlen($uri) > 1) {
            $uri = rtrim($uri, '/');
        }

        // Remove any base path if app is in a subfolder (optional improvement)
        $base = $this->detectBasePath();
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
            if ($uri === '') $uri = '/';
        }

        // Route segments
        $segments = array_values(array_filter(explode('/', trim($uri, '/')), 'strlen'));

        // Empty => home
        if (empty($segments)) {
            $this->route  = 'index'; // pages/index.php
            $this->params = [];
            return;
        }

        // Admin prefix handling (optional)
        if ($segments[0] === $this->adminPrefix) {
            // Keep full admin path as route: e.g. "admin", "admin/users", "admin/users/edit"
            $this->route  = implode('/', $segments);
            $this->params = []; // you can also choose to shift one as controller and rest as params
            return;
        }

        // General case: first 1–2 segments form route; rest are params
        // Try the longest plausible route path to allow nested pages (e.g., news/view)
        // Check pages/<seg0>/<seg1>.php first, then pages/<seg0>.php
        $cand2 = $this->sanitizeRoute($segments[0] . '/' . ($segments[1] ?? ''));
        $cand1 = $this->sanitizeRoute($segments[0]);

        if ($this->pageExists($cand2)) {
            $this->route  = $cand2;
            $this->params = array_slice($segments, 2);
        } else {
            $this->route  = $cand1;
            $this->params = array_slice($segments, 1);
        }
    }

    /**
     * Try to dispatch using a $ROUTES array.
     * Each entry can be either:
     *  - 'slug' => 'relative/file.php'
     *  - 'slug' => ['file' => 'relative/file.php', 'title' => '...', 'handler' => callable]
     * The first matching slug to current route wins.
     */
    private function dispatchViaRoutesArray(array $routes): bool
    {
        $routeKey = $this->route;

        // Direct hit
        if (isset($routes[$routeKey])) {
            return $this->includeRouteTarget($routes[$routeKey]);
        }

        // If route has params, allow mapping only by the first segment
        $first = explode('/', $routeKey, 2)[0];
        if (isset($routes[$first])) {
            // Provide params to the included file via $ROUTER for convenience
            return $this->includeRouteTarget($routes[$first]);
        }

        return false;
    }

    /**
     * Fallback: include pages/<route>.php (or pages/index.php for "/").
     */
    private function dispatchViaFilesystem(): void
    {
        $file = $this->pagesDir . '/' . $this->route . '.php';
        if ($this->pageExists($this->route)) {
            $ROUTER = $this; // expose router to the page if needed
            require $file;
            return;
        }

        // index.php for root-like access
        if ($this->route === 'index' && is_file($this->pagesDir . '/index.php')) {
            $ROUTER = $this;
            require $this->pagesDir . '/index.php';
            return;
        }

        // 404 handler
        $this->emit404();
    }

    /**
     * Include a target from $ROUTES.
     *
     * @param mixed $target string path or array with 'file'/'handler'
     */
    private function includeRouteTarget(mixed $target): bool
    {
        $ROUTER = $this; // expose to route file/handler

        if (is_string($target)) {
            $file = $this->normalizePagePath($target);
            if (!$file) {
                $this->emit404();
                return true;
            }
            require $file;
            return true;
        }

        if (is_array($target)) {
            if (isset($target['handler']) && is_callable($target['handler'])) {
                // Handler can echo/return response
                ($target['handler'])($ROUTER);
                return true;
            }
            if (isset($target['file']) && is_string($target['file'])) {
                $file = $this->normalizePagePath($target['file']);
                if (!$file) {
                    $this->emit404();
                    return true;
                }
                require $file;
                return true;
            }
        }

        return false;
    }

    /**
     * Ensure the given route "foo" or "foo/bar" is a safe page file.
     */
    private function pageExists(string $route): bool
    {
        $route = $this->sanitizeRoute($route);
        $file  = $this->pagesDir . '/' . $route . '.php';
        return is_file($file);
    }

    /**
     * Convert relative page path from routes array to absolute, safely.
     * Returns absolute path or null if invalid/missing.
     */
    private function normalizePagePath(string $rel): ?string
    {
        $rel = ltrim($rel, '/');
        if ($rel === '' || str_contains($rel, "\0") || str_contains($rel, '..')) {
            return null;
        }
        $abs = $this->pagesDir . '/' . $rel;
        if (!str_ends_with($abs, '.php')) {
            $abs .= '.php';
        }
        return is_file($abs) ? $abs : null;
    }

    /**
     * Allow only safe route characters.
     */
    private function sanitizeRoute(string $route): string
    {
        $route = trim($route, '/');
        // Only letters, digits, underscore, dash, and forward slash for subroutes
        $route = preg_replace('~[^a-z0-9_/\-]+~i', '', $route) ?? '';
        // Collapse multiple slashes
        $route = preg_replace('~/{2,}~', '/', $route) ?? '';
        return $route;
    }

    /**
     * Try to detect a base path if app is in a subfolder under document root.
     */
    private function detectBasePath(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir  = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        return $scriptDir === '/' ? '' : $scriptDir;
    }

    private function emit404(): void
    {
        $this->emitHttpStatus(404, 'Not Found');
        $custom = $this->pagesDir . '/404.php';
        if (is_file($custom)) {
            $ROUTER = $this;
            require $custom;
            return;
        }
        // Minimal fallback to avoid blank page
        echo '<!doctype html><meta charset="utf-8"><title>404 Not Found</title><h1>404 Not Found</h1>';
    }

    private function emitHttpStatus(int $code, string $text): void
    {
        if (!headers_sent()) {
            http_response_code($code);
            header("Status: {$code} {$text}");
        }
    }
}

/**
 * Public entrypoint used by index.php
 */
function router_dispatch(): void
{
    static $router = null;
    if ($router === null) {
        $router = new Router(dirname(__DIR__));
    }
    $router->dispatch();
}
