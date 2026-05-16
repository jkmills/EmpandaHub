<?php
declare(strict_types=1);

abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = __DIR__ . '/../' . ltrim($view, '/');
        if (!file_exists($viewFile)) {
            $this->abort(500, 'View not found: ' . $view);
        }
        require $viewFile;
    }

    protected function redirect(string $path, int $status = 302): void
    {
        $url = str_starts_with($path, 'http') ? $path : APP_URL . '/' . ltrim($path, '/');
        header('Location: ' . $url, true, $status);
        exit;
    }

    protected function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        $errorView = __DIR__ . '/../views/errors/' . $code . '.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo htmlspecialchars($message ?: 'Error ' . $code, ENT_QUOTES, 'UTF-8');
        }
        exit;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->abort(405);
        }
        Csrf::verify();
    }
}
