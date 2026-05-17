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

    protected function loadOrgData(array &$data): void
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM organizations WHERE id = ?');
        $stmt->execute([Auth::orgId()]);
        $row = $stmt->fetch() ?: [];
        $data['org']        = $row;
        $data['orgName']    = $row['name']          ?? APP_NAME;
        $data['orgColor']   = $row['primary_color'] ?? '#2563eb';
        $data['orgLogo']    = $row['logo']           ?? '';
        $cfg = json_decode($row['config_json'] ?? '{}', true) ?: [];
        $data['orgModules']      = $cfg['modules']      ?? [];
        $data['orgSidebarStyle'] = $cfg['sidebar_style'] ?? 'dark';
    }

    protected function renderLayout(string $view, array $data = []): void
    {
        if (!isset($data['orgName'])) {
            $this->loadOrgData($data);
        }
        $data['body_class'] = $data['body_class'] ?? 'app-layout';

        ob_start();
        $this->render($view, $data);
        $content = ob_get_clean();

        // Extract into this scope so header.php / nav.php receive $orgColor, $orgName, etc.
        extract($data, EXTR_SKIP);

        require ROOT . '/views/layout/header.php';
        require ROOT . '/views/layout/nav.php';
        echo '<div class="flash-messages">';
        foreach (Flash::get() as $msg) {
            echo '<div class="flash flash-' . htmlspecialchars($msg['type'], ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8')
                . '<button class="flash-close" aria-label="Dismiss" onclick="this.parentElement.remove()">&times;</button>'
                . '</div>';
        }
        echo '</div>';
        echo $content;
        require ROOT . '/views/layout/footer.php';
    }

    protected function renderPrint(string $view, array $data = []): void
    {
        if (!isset($data['orgName'])) {
            $this->loadOrgData($data);
        }
        ob_start();
        $this->render($view, $data);
        $content = ob_get_clean();
        extract($data, EXTR_SKIP);
        require ROOT . '/views/layout/print.php';
        exit;
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
