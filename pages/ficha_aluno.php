<?php
require_role([ROLE_STUDENT]);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
$pdo = get_pdo();
$user = current_user();

// Garantir ficha existente
$stmt = $pdo->prepare('SELECT * FROM fichas_aluno WHERE utilizador_id = ? LIMIT 1');
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();
if (!$profile) {
    $stmt = $pdo->prepare('INSERT INTO fichas_aluno (utilizador_id, estado) VALUES (?, "rascunho")');
    $stmt->execute([$user['id']]);
    $stmt = $pdo->prepare('SELECT * FROM fichas_aluno WHERE utilizador_id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $profile = $stmt->fetch();
}

$courses = $pdo->query('SELECT * FROM cursos WHERE ativo = 1 ORDER BY nome')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Draft/update/submit flow is controlled by profile status transitions.
    $name = trim($_POST['name'] ?? ($user['nome'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $course_id = (int)($_POST['course_id'] ?? 0);
    $action = $_POST['action'] ?? 'save';
    $status = $profile['estado'];

    if ($action === 'reopen') {
        if (!in_array($status, ['submetida', 'aprovada'], true)) {
            flash('error', 'Ficha nao pode ser reaberta neste estado.');
        } else {
            $stmt = $pdo->prepare('UPDATE fichas_aluno SET estado = "rascunho", observacoes = NULL WHERE id = ?');
            $stmt->execute([$profile['id']]);
            flash('success', 'Ficha reaberta para edicao.');
        }
        header('Location: index.php?page=ficha_aluno');
        exit;
    }

    if (!in_array($status, ['rascunho', 'rejeitada'], true)) {
        flash('error', 'Ficha não pode ser alterada neste estado.');
        header('Location: index.php?page=ficha_aluno');
        exit;
    }

    if ($name === '') {
        flash('error', 'O nome é obrigatório.');
        header('Location: index.php?page=ficha_aluno');
        exit;
    }

    $stmt = $pdo->prepare('UPDATE utilizadores SET nome = ? WHERE id = ?');
    $stmt->execute([$name, $user['id']]);

    $photoPath = $profile['caminho_foto'];
    try {
        if (isset($_FILES['photo'])) {
            $upload = handle_photo_upload($_FILES['photo']);
            if ($upload) {
                $photoPath = $upload;
            }
        }
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
        header('Location: index.php?page=ficha_aluno');
        exit;
    }

    $newStatus = $status;
    if ($action === 'submit') {
        // Business rule: only students with approved enrollment can submit profile.
        $stmt = $pdo->prepare('SELECT estado FROM pedidos_matricula WHERE utilizador_id = ? AND estado = "aprovado" LIMIT 1');
        $stmt->execute([$user['id']]);
        if (!$stmt->fetch()) {
            flash('error', 'Não pode submeter ficha sem ter matrícula aprovada.');
            header('Location: index.php?page=ficha_aluno');
            exit;
        }
        $newStatus = 'submetida';
    }

    $stmt = $pdo->prepare('UPDATE fichas_aluno SET telefone = ?, morada = ?, curso_id = ?, caminho_foto = ?, estado = ?, observacoes = NULL WHERE id = ?');
    $stmt->execute([$phone, $address, $course_id ?: null, $photoPath, $newStatus, $profile['id']]);
    flash('success', $action === 'submit' ? 'Ficha submetida.' : 'Ficha guardada.');
    header('Location: index.php?page=ficha_aluno');
    exit;
}

// Refresh profile
$stmt = $pdo->prepare('SELECT sp.*, c.nome AS course_name FROM fichas_aluno sp LEFT JOIN cursos c ON sp.curso_id = c.id WHERE sp.id = ?');
$stmt->execute([$profile['id']]);
$profile = $stmt->fetch();

// Verificar se tem pedido de matrícula aprovado
$stmt = $pdo->prepare('SELECT estado FROM pedidos_matricula WHERE utilizador_id = ? AND estado = "aprovado" LIMIT 1');
$stmt->execute([$user['id']]);
$hasApprovedMatricula = $stmt->fetch() !== false;
?>
<h2 class="h4 mb-3">Ficha de Aluno</h2>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-body">
                <p><strong>Estado:</strong> <?php echo e($profile['estado']); ?></p>
                <?php if ($profile['observacoes']): ?>
                    <div class="alert alert-warning">Observações: <?php echo e($profile['observacoes']); ?></div>
                <?php endif; ?>
                <?php if (!$hasApprovedMatricula && in_array($profile['estado'], ['rascunho', 'rejeitada'], true)): ?>
                    <div class="alert alert-info">Para submeter a ficha, necessita de ter um pedido de matrícula aprovado.</div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="name" class="form-control" value="<?php echo e($user['nome'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?php echo e($user['email'] ?? ''); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo e($profile['telefone'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Morada</label>
                        <input type="text" name="address" class="form-control" value="<?php echo e($profile['morada'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Curso pretendido</label>
                        <select name="course_id" class="form-select" required>
                            <option value="">-- selecione --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo e($c['id']); ?>" <?php echo ($profile['curso_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo e($c['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fotografia (JPG/PNG, máx 2MB)</label>
                        <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png">
                        <?php if ($profile['caminho_foto']): ?>
                            <div class="mt-3">
                                <div class="text-muted small mb-2">Fotografia atual</div>
                                <img src="<?php echo e($profile['caminho_foto']); ?>" alt="Fotografia do aluno" class="img-fluid rounded shadow-sm" style="max-height: 220px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-secondary" type="submit" name="action" value="save">Guardar Rascunho</button>
                        <button class="btn btn-primary" type="submit" name="action" value="submit" <?php echo !$hasApprovedMatricula ? 'disabled' : ''; ?>>Submeter</button>
                        <?php if (in_array($profile['estado'], ['submetida', 'aprovada'], true)): ?>
                            <button class="btn btn-outline-warning" type="submit" name="action" value="reopen">Atualizar</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
