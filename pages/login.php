<?php require_once __DIR__ . '/../includes/helpers.php'; ?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | <?php echo e(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/login.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card card-login p-4">
                <div class="mb-4 text-center">
                    <div class="brand-mark">Académica</div>
                    <p class="small-text mb-0">Acesso à área interna</p>
                </div>
                <?php if ($msg = flash('error')): ?>
                    <div class="alert alert-danger border-0">
                        <?php echo e($msg); ?>
                    </div>
                <?php endif; ?>
                <?php if ($msg = flash('success')): ?>
                    <div class="alert alert-success border-0">
                        <?php echo e($msg); ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="index.php?page=login" class="d-grid gap-3">
                    <input type="hidden" name="action" value="login">
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="exemplo@dominio.pt" required>
                    </div>
                    <div>
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control" id="password" placeholder="••••••" required>
                            <span class="input-group-text toggle-password" id="togglePass">Mostrar</span>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Entrar</button>
                </form>
                <hr class="border-secondary opacity-25 my-4">
                <h6 class="text-center mb-3">Pedir novo acesso</h6>
                <form method="post" action="index.php?page=login" class="d-grid gap-3">
                    <input type="hidden" name="action" value="register">
                    <div>
                        <label class="form-label">Nome</label>
                        <input type="text" name="name" class="form-control" placeholder="Nome completo" required>
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="exemplo@dominio.pt" required>
                    </div>
                    <div>
                        <label class="form-label">Cargo</label>
                        <select name="role" class="form-select" required>
                            <option value="<?php echo e(ROLE_STUDENT); ?>">Aluno</option>
                            <option value="<?php echo e(ROLE_STAFF); ?>">Funcionário</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Crie uma password" required>
                    </div>
                    <button class="btn btn-secondary w-100" type="submit">Enviar Pedido</button>
                </form>
                <p class="text-center small-text mt-3 mb-0">O gestor valida o pedido antes da ativação.</p>
            </div>
        </div>
    </div>
</div>
<script>
    const toggle = document.getElementById('togglePass');
    const pass = document.getElementById('password');
    toggle?.addEventListener('click', () => {
        const isText = pass.type === 'text';
        pass.type = isText ? 'password' : 'text';
        toggle.textContent = isText ? 'Mostrar' : 'Esconder';
    });
</script>
</body>
</html>
