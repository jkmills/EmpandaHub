<?php
declare(strict_types=1);

class AuthController extends Controller
{
    public function loginForm(array $params): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->render('modules/auth/views/login.php', ['pageTitle' => 'Login']);
    }

    public function login(array $params): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }

        Csrf::verify();

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors   = [];

        if (!$email)    $errors['email']    = 'Email is required.';
        if (!$password) $errors['password'] = 'Password is required.';

        if (!$errors) {
            $db   = Database::getInstance();
            $stmt = $db->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $errors['_global'] = 'Invalid email or password.';
            } else {
                Auth::login($user);
                AuditLog::record('login', 'user', (int)$user['id']);
                $this->redirect('/dashboard');
            }
        }

        $this->render('modules/auth/views/login.php', [
            'pageTitle' => 'Login',
            'errors'    => $errors,
            'email'     => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
        ]);
    }

    public function logout(array $params): void
    {
        AuditLog::record('logout');
        Auth::logout();
        $this->redirect('/auth/login');
    }
}
