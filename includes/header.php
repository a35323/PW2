<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
$user = current_user();
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="theme-dark">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Académica</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php // Navigation entries are filtered by authenticated role. ?>
            <ul class="navbar-nav me-auto">
                <?php if ($user): ?>
                    <?php if ($user['perfil'] === ROLE_MANAGER): ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=cursos">Cursos</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=unidades">Unidades Curriculares</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=plano">Plano de Estudos</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=validar_fichas">Fichas</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=validar_utilizadores">Utilizadores</a></li>
                    <?php endif; ?>
                    <?php if ($user['perfil'] === ROLE_STUDENT): ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=ficha_aluno">Ficha Aluno</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=pedido_matricula">Pedido Matrícula</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=notas">Notas</a></li>
                    <?php endif; ?>
                    <?php if ($user['perfil'] === ROLE_STAFF): ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=validar_matriculas">Pedidos Matrícula</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=pautas">Pautas</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if ($user): ?>
                    <li class="nav-item"><span class="navbar-text text-white me-2"><?php echo e($user['nome']); ?> (<?php echo e($user['perfil']); ?>)</span></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=login">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
<?php // Flash messages from server-side actions (CRUD/validation/login). ?>
<?php if ($msg = flash('success')): ?>
    <div class="alert alert-success shadow-sm auto-dismiss"><?php echo e($msg); ?></div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger shadow-sm auto-dismiss"><?php echo e($msg); ?></div>
<?php endif; ?>
