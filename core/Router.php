<?php
declare(strict_types=1);

class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, string $controller, string $action): void
    {
        $this->routes[] = compact('method', 'pattern', 'controller', 'action');
    }

    public function get(string $pattern, string $controller, string $action): void
    {
        $this->add('GET', $pattern, $controller, $action);
    }

    public function post(string $pattern, string $controller, string $action): void
    {
        $this->add('POST', $pattern, $controller, $action);
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
        // Convert :param placeholders to named regex groups
        $regex = preg_replace('#:([a-zA-Z_]+)#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }

        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    private function resolveController(string $name): ?string
    {
        // Map controller class names to module directories
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
        ];

        $module = $map[$name] ?? null;
        if (!$module) return null;

        $path = __DIR__ . '/../modules/' . $module . '/controllers/' . $name . '.php';
        return file_exists($path) ? $path : null;
    }
}
