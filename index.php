<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

function default_page_for_user(?array $user): string
{
    if (!$user) {
        return 'login';
    }

    if ($user['perfil'] === ROLE_MANAGER) {
        return 'cursos';
    }

    if ($user['perfil'] === ROLE_STUDENT) {
        return 'ficha_aluno';
    }

    if ($user['perfil'] === ROLE_STAFF) {
        return 'validar_matriculas';
    }

    return 'login';
}

$page = $_GET['page'] ?? '';

if ($page === '' || $page === 'painel') {
    $page = default_page_for_user(current_user());
}

// Handle logout early
if ($page === 'logout') {
    logout();
    header('Location: index.php?page=login');
    exit;
}

// Handle login post
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'register') {
        require_once __DIR__ . '/includes/db.php';
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? ROLE_STUDENT;

        if ($name === '' || $email === '' || $password === '') {
            flash('error', 'Preencha todos os campos do pedido.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Email inválido.');
        } elseif (strlen($password) < 6) {
            flash('error', 'A password deve ter pelo menos 6 caracteres.');
        } elseif (!in_array($role, [ROLE_STUDENT, ROLE_STAFF], true)) {
            flash('error', 'Cargo inválido.');
        } else {
            $pdo = get_pdo();
            $stmt = $pdo->prepare('SELECT id FROM utilizadores WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch();

            $stmt = $pdo->prepare('SELECT id, estado FROM pedidos_utilizador WHERE email = ? ORDER BY criado_em DESC LIMIT 1');
            $stmt->execute([$email]);
            $lastRequest = $stmt->fetch();

            if ($existingUser) {
                flash('error', 'Já existe um utilizador com este email.');
            } elseif ($lastRequest && $lastRequest['estado'] === 'pendente') {
                flash('error', 'Já existe um pedido pendente com este email.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO pedidos_utilizador (nome, email, hash_senha, perfil_sugerido, estado, criado_em) VALUES (?, ?, ?, ?, "pendente", NOW())');
                $stmt->execute([$name, $email, $hash, $role]);
                flash('success', 'Pedido enviado. Aguarde aprovação do gestor.');
            }
        }

        header('Location: index.php?page=login');
        exit;
    }

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if (login($email, $password)) {
        flash('success', 'Sessão iniciada.');
        header('Location: index.php');
        exit;
    }
    flash('error', 'Credenciais inválidas.');
}

// Login page does not require auth
if ($page === 'login') {
    include __DIR__ . '/pages/login.php';
    exit;
}

// All other pages require login
require_login();

if (in_array($page, ['export_notas', 'export_matricula'], true)) {
    if ($page === 'export_notas') {
        include __DIR__ . '/pages/export_notas.php';
    } else {
        include __DIR__ . '/pages/export_matricula.php';
    }
    exit;
}

include __DIR__ . '/includes/header.php';

switch ($page) {
    case 'cursos':
        include __DIR__ . '/pages/cursos.php';
        break;
    case 'unidades':
        include __DIR__ . '/pages/unidades_curriculares.php';
        break;
    case 'plano':
        include __DIR__ . '/pages/plano_estudos.php';
        break;
    case 'ficha_aluno':
        include __DIR__ . '/pages/ficha_aluno.php';
        break;
    case 'validar_fichas':
        include __DIR__ . '/pages/validar_fichas.php';
        break;
    case 'pedido_matricula':
        include __DIR__ . '/pages/pedido_matricula.php';
        break;
    case 'notas':
        include __DIR__ . '/pages/notas.php';
        break;
    case 'validar_matriculas':
        include __DIR__ . '/pages/validar_matriculas.php';
        break;
    case 'validar_utilizadores':
        include __DIR__ . '/pages/validar_utilizadores.php';
        break;
    case 'pautas':
        include __DIR__ . '/pages/pautas.php';
        break;
    default:
        echo '<div class="alert alert-warning">Página não encontrada.</div>';
}

include __DIR__ . '/includes/footer.php';
?>
