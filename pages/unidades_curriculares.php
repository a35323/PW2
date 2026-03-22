<?php
require_role([ROLE_MANAGER]);
require_once __DIR__ . '/../includes/db.php';
$pdo = get_pdo();

$editId = (int)($_GET['edit_id'] ?? 0);
$editingUc = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM unidades_curriculares WHERE id = ? LIMIT 1');
    $stmt->execute([$editId]);
    $editingUc = $stmt->fetch();
    if (!$editingUc) {
        flash('error', 'UC nao encontrada.');
        header('Location: index.php?page=unidades');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Single endpoint for create/update/delete UC actions.
    $action = $_POST['action'] ?? 'save';
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $ects = (int)($_POST['ects'] ?? 0);
    $id = $_POST['id'] ?? null;

    if ($action === 'delete') {
        if (!$id) {
            flash('error', 'UC invalida.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM unidades_curriculares WHERE id = ?');
            $stmt->execute([$id]);
            flash('success', 'UC eliminada.');
        }
        header('Location: index.php?page=unidades');
        exit;
    }

    if ($code === '' || $name === '') {
        flash('error', 'Código e nome são obrigatórios.');
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE unidades_curriculares SET codigo = ?, nome = ?, ects = ? WHERE id = ?');
            $stmt->execute([$code, $name, $ects, $id]);
            flash('success', 'UC atualizada.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO unidades_curriculares (codigo, nome, ects) VALUES (?, ?, ?)');
            $stmt->execute([$code, $name, $ects]);
            flash('success', 'UC criada.');
        }
    }
    header('Location: index.php?page=unidades');
    exit;
}

$ucs = $pdo->query('SELECT * FROM unidades_curriculares ORDER BY codigo')->fetchAll();
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="page-title h4 mb-0">Unidades Curriculares</div>
    <span class="text-muted">Gestão de UCs e créditos</span>
</div>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadowed h-100">
            <div class="card-body">
                <h5 class="card-title"><?php echo $editingUc ? 'Editar UC' : 'Nova UC'; ?></h5>
                <form method="post" class="d-grid gap-3">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo e($editingUc['id'] ?? ''); ?>">
                    <?php if ($editingUc): ?>
                        <div>
                            <label class="form-label">ID</label>
                            <input type="text" class="form-control" value="<?php echo e($editingUc['id']); ?>" readonly>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="form-label">Código</label>
                        <input type="text" class="form-control" name="code" placeholder="INF101" value="<?php echo e($editingUc['codigo'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" name="name" placeholder="Algoritmos e Estruturas de Dados" value="<?php echo e($editingUc['nome'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label class="form-label">ECTS (opcional)</label>
                        <input type="number" min="0" class="form-control" name="ects" placeholder="6" value="<?php echo e($editingUc['ects'] ?? ''); ?>">
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Guardar</button>
                    <?php if ($editingUc): ?>
                        <a class="btn btn-secondary w-100" href="index.php?page=unidades">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadowed h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="card-title mb-0">Lista de UCs</h5>
                    <span class="text-muted small">Total: <?php echo count($ucs); ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead><tr><th>ID</th><th>Código</th><th>Nome</th><th>ECTS</th><th>Criado</th><th>Acoes</th></tr></thead>
                        <tbody>
                        <?php foreach ($ucs as $uc): ?>
                            <tr>
                                <td><?php echo e($uc['id']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo e($uc['codigo']); ?></span></td>
                                <td><?php echo e($uc['nome']); ?></td>
                                <td><?php echo e($uc['ects']); ?></td>
                                <td><?php echo e($uc['criado_em']); ?></td>
                                <td>
                                    <div class="btn-group gap-2" role="group">
                                        <a class="btn btn-sm btn-outline-primary" href="index.php?page=unidades&edit_id=<?php echo e($uc['id']); ?>">Editar</a>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo e($uc['id']); ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Eliminar esta UC?');">Eliminar</button>
                                        </form>
                                    </div>
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
