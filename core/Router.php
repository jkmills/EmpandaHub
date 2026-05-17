<?php
declare(strict_types=1);

class Router
{
    private array  $routes        = [];
    private string $currentModule = '';

    public function add(string $method, string $pattern, string $controller, string $action): void
    {
        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $pattern,
            'controller' => $controller,
            'action'     => $action,
            'module'     => $this->currentModule,
        ];
    }

    public function get(string $pattern, string $controller, string $action): void
    {
        $this->add('GET', $pattern, $controller, $action);
    }

    public function post(string $pattern, string $controller, string $action): void
    {
        $this->add('POST', $pattern, $controller, $action);
    }

    /**
     * Group routes under a toggleable module. Any route registered inside the
     * callback is automatically gated: if the module is disabled in settings,
     * hitting the URL redirects to the dashboard instead of loading the page.
     * Adding a new module only requires wrapping its routes in this call.
     */
    public function module(string $module, callable $callback): void
    {
        $prev = $this->currentModule;
        $this->currentModule = $module;
        $callback($this);
        $this->currentModule = $prev;
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            if (strtoupper($method) !== strtoupper($route['method'])) {
                continue;
            }

            $params = $this->match($route['pattern'], $uri);
            if ($params === null) {
                continue;
            }

            // Module gate: if this route belongs to a toggleable module and the
            // user is authenticated, verify the module is enabled for their org.
            if ($route['module'] !== '' && Auth::check()) {
                $modules = Auth::orgModules();
                if (isset($modules[$route['module']]) && !$modules[$route['module']]) {
                    Flash::warning(ucfirst($route['module']) . ' is not enabled for your organization.');
                    header('Location: ' . APP_URL . '/dashboard');
                    exit;
                }
            }

            $controllerFile = $this->resolveController($route['controller']);
            if (!$controllerFile) {
                http_response_code(500);
                die('Controller not found: ' . htmlspecialchars($route['controller'], ENT_QUOTES, 'UTF-8'));
            }

            require_once $controllerFile;
            $ctrl = new $route['controller']();
            $ctrl->{$route['action']}($params);
            return;
        }

        http_response_code(404);
        require __DIR__ . '/../views/errors/404.php';
    }

    private function match(string $pattern, string $uri): ?array
    {
        $regex = preg_replace('#:([a-zA-Z_]+)#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }

        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    private function resolveController(string $name): ?string
    {
        $map = [
            'AuthController'        => 'auth',
            'DashboardController'   => 'dashboard',
            'CrmController'         => 'crm',
            'MembershipController'  => 'membership',
            'DonorController'       => 'donors',
            'VolunteerController'   => 'volunteers',
            'EventController'       => 'events',
            'GrantController'       => 'grants',
            'FinanceController'     => 'finance',
            'SettingsController'    => 'settings',
            'DataController'        => 'data',
            'DocumentController'    => 'documents',
        ];

        $module = $map[$name] ?? null;
        if (!$module) return null;

        $path = __DIR__ . '/../modules/' . $module . '/controllers/' . $name . '.php';
        return file_exists($path) ? $path : null;
    }
}
