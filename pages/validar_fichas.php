<?php
require_role([ROLE_MANAGER]);
require_once __DIR__ . '/../includes/db.php';
$pdo = get_pdo();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['profile_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $obs = trim($_POST['observations'] ?? '');
    if (!$id || !in_array($action, ['approve', 'reject'], true)) {
        flash('error', 'Pedido inválido.');
    } else {
        $status = $action === 'approve' ? 'aprovada' : 'rejeitada';
        $stmt = $pdo->prepare('UPDATE fichas_aluno SET estado = ?, observacoes = ?, validado_por = ?, validado_em = NOW() WHERE id = ?');
        $stmt->execute([$status, $obs ?: null, $user['id'], $id]);
        flash('success', 'Ficha ' . ($action === 'approve' ? 'aprovada' : 'rejeitada') . '.');
    }
    header('Location: index.php?page=validar_fichas');
    exit;
}

$profiles = $pdo->query('SELECT sp.*, u.id AS student_id, u.nome AS student_name, u.email, c.nome AS course_name FROM fichas_aluno sp JOIN utilizadores u ON sp.utilizador_id = u.id LEFT JOIN cursos c ON sp.curso_id = c.id ORDER BY sp.estado ASC, sp.atualizado_em DESC')->fetchAll();
?>
<h2 class="h4 mb-3">Validação de Fichas de Aluno</h2>
<table class="table table-striped">
    <thead><tr><th>Aluno</th><th>Curso</th><th>Estado</th><th>Observações</th><th>Fotografia</th><th>PDF</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($profiles as $p): ?>
        <tr>
            <td><?php echo e($p['student_name']); ?><br><small><?php echo e($p['email']); ?></small></td>
            <td><?php echo e($p['course_name']); ?></td>
            <td><?php echo e($p['estado']); ?></td>
            <td><?php echo e($p['observacoes']); ?></td>
            <td><?php if ($p['caminho_foto']): ?><a href="<?php echo e($p['caminho_foto']); ?>" target="_blank"><img src="<?php echo e($p['caminho_foto']); ?>" alt="Fotografia" class="img-thumbnail" style="max-height: 130px; max-width: 130px;"></a><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
            <td>
                <div class="d-flex flex-column gap-2">
                    <a class="btn btn-sm btn-outline-primary" href="index.php?page=export_notas&aluno_id=<?php echo e($p['student_id']); ?>" target="_blank">Notas</a>
                    <a class="btn btn-sm btn-outline-secondary" href="index.php?page=export_matricula&aluno_id=<?php echo e($p['student_id']); ?>" target="_blank">Matrícula</a>
                </div>
            </td>
            <td>
                <?php if ($p['estado'] === 'submetida'): ?>
                    <form method="post" class="d-flex flex-column gap-2">
                        <input type="hidden" name="profile_id" value="<?php echo e($p['id']); ?>">
                        <textarea name="observations" class="form-control" placeholder="Observações"></textarea>
                        <div class="btn-group">
                            <button class="btn btn-success" name="action" value="approve" type="submit">Aprovar</button>
                            <button class="btn btn-danger" name="action" value="reject" type="submit">Rejeitar</button>
                        </div>
                    </form>
                <?php else: ?>
                    <span class="text-muted">Sem ações</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
