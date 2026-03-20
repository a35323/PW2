<?php
require_role([ROLE_MANAGER]);
require_once __DIR__ . '/../includes/db.php';

$pdo = get_pdo();

$editId = (int)($_GET['edit_id'] ?? 0);
$editingCourse = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM cursos WHERE id = ? LIMIT 1');
    $stmt->execute([$editId]);
    $editingCourse = $stmt->fetch();
    if (!$editingCourse) {
        flash('error', 'Curso nao encontrado.');
        header('Location: index.php?page=cursos');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $name = trim($_POST['name'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    $id = $_POST['id'] ?? null;

    if ($action === 'delete') {
        if (!$id) {
            flash('error', 'Curso invalido.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM cursos WHERE id = ?');
            $stmt->execute([$id]);
            flash('success', 'Curso eliminado.');
        }
        header('Location: index.php?page=cursos');
        exit;
    }

    if ($name === '') {
        flash('error', 'Nome do curso é obrigatório.');
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE cursos SET nome = ?, ativo = ? WHERE id = ?');
            $stmt->execute([$name, $active, $id]);
            flash('success', 'Curso atualizado.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO cursos (nome, ativo) VALUES (?, ?)');
            $stmt->execute([$name, $active]);
            flash('success', 'Curso criado.');
        }
    }
    header('Location: index.php?page=cursos');
    exit;
}

$courses = $pdo->query('SELECT * FROM cursos ORDER BY nome')->fetchAll();
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="page-title h4 mb-0">Cursos</div>
    <span class="text-muted">Gestão de oferta formativa</span>
</div>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadowed h-100">
            <div class="card-body">
                <h5 class="card-title"><?php echo $editingCourse ? 'Editar Curso' : 'Novo Curso'; ?></h5>
                <form method="post" class="d-grid gap-3">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo e($editingCourse['id'] ?? ''); ?>">
                    <?php if ($editingCourse): ?>
                        <div>
                            <label class="form-label">ID</label>
                            <input type="text" class="form-control" value="<?php echo e($editingCourse['id']); ?>" readonly>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" name="name" placeholder="Engenharia Informática" value="<?php echo e($editingCourse['nome'] ?? ''); ?>" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="active" id="active" <?php echo !$editingCourse || $editingCourse['ativo'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="active">Ativo</label>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Guardar</button>
                    <?php if ($editingCourse): ?>
                        <a class="btn btn-secondary w-100" href="index.php?page=cursos">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadowed h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="card-title mb-0">Lista de Cursos</h5>
                    <span class="text-muted small">Total: <?php echo count($courses); ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead><tr><th>ID</th><th>Nome</th><th>Estado</th><th>Criado</th><th>Acoes</th></tr></thead>
                        <tbody>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td><?php echo e($c['id']); ?></td>
                                <td><?php echo e($c['nome']); ?></td>
                                <td>
                                    <?php if ($c['ativo']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($c['criado_em']); ?></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="index.php?page=cursos&edit_id=<?php echo e($c['id']); ?>">Editar</a>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo e($c['id']); ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Eliminar este curso?');">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
