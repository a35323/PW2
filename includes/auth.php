<?php
require_once __DIR__ . '/db.php';

function find_user_by_email(string $email): ?array
{
    $stmt = get_pdo()->prepare('SELECT * FROM utilizadores WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function find_user_by_id(int $id): ?array
{
    $stmt = get_pdo()->prepare('SELECT * FROM utilizadores WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function login(string $email, string $password): bool
{
    $user = find_user_by_email($email);
    if (!$user || !password_verify($password, $user['hash_senha'])) {
        return false;
    }
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role'] = $user['perfil'];
    $_SESSION['last_seen'] = time();
    return true;
}

function logout(): void
{
    session_unset();
    session_destroy();
    session_start();
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return find_user_by_id((int)$_SESSION['user_id']);
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: index.php?page=login');
        exit;
    }
}

function require_role(array $roles): void
{
    require_login();
    $user = current_user();
    if (!$user || !in_array($user['perfil'], $roles, true)) {
        http_response_code(403);
        echo '<div class="alert alert-danger">Acesso negado.</div>';
        exit;
    }
}
?>
