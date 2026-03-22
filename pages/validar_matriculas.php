<?php
require_role([ROLE_STAFF]);
require_once __DIR__ . '/../includes/db.php';
$pdo = get_pdo();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Staff decision updates request state and audit fields.
    $id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $obs = trim($_POST['observations'] ?? '');
    if (!$id || !in_array($action, ['approve', 'reject'], true)) {
        flash('error', 'Pedido inválido.');
    } else {
        $status = $action === 'approve' ? 'aprovado' : 'rejeitado';
        $stmt = $pdo->prepare('UPDATE pedidos_matricula SET estado = ?, observacoes = ?, decidido_por = ?, decidido_em = NOW() WHERE id = ?');
        $stmt->execute([$status, $obs ?: null, $user['id'], $id]);
        flash('success', 'Pedido ' . ($action === 'approve' ? 'aprovado' : 'rejeitado') . '.');
    }
    header('Location: index.php?page=validar_matriculas');
    exit;
}

$requests = $pdo->query('SELECT er.*, s.nome AS student_name, s.email, c.nome AS course_name, u.nome AS decided_name FROM pedidos_matricula er JOIN utilizadores s ON er.utilizador_id = s.id JOIN cursos c ON er.curso_id = c.id LEFT JOIN utilizadores u ON er.decidido_por = u.id ORDER BY FIELD(er.estado,"pendente","aprovado","rejeitado"), er.criado_em DESC')->fetchAll();
?>
<h2 class="h4 mb-3">Pedidos de Matrícula / Inscrição</h2>
<table class="table table-striped align-middle">
    <thead><tr><th>Aluno</th><th>Curso</th><th>Estado</th><th>Observações</th><th>Decisão</th><th>PDF</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($requests as $r): ?>
        <tr>
            <td><?php echo e($r['student_name']); ?><br><small><?php echo e($r['email']); ?></small></td>
            <td><?php echo e($r['course_name']); ?></td>
            <td><?php echo e($r['estado']); ?></td>
            <td><?php echo e($r['observacoes']); ?></td>
            <td><?php echo e($r['decided_name']); ?><br><small><?php echo e($r['decidido_em']); ?></small></td>
            <td><a class="btn btn-sm btn-outline-primary" href="index.php?page=export_matricula&request_id=<?php echo e($r['id']); ?>" target="_blank">PDF</a></td>
            <td>
                <?php if ($r['estado'] === 'pendente'): ?>
                    <form method="post" class="d-flex flex-column gap-2">
                        <input type="hidden" name="request_id" value="<?php echo e($r['id']); ?>">
                        <textarea name="observations" class="form-control" placeholder="Observações"></textarea>
                        <div class="btn-group">
                            <button class="btn btn-success" name="action" value="approve" type="submit">Aprovar</button>
                            <button class="btn btn-danger" name="action" value="reject" type="submit">Rejeitar</button>
                        </div>
                    </form>
                <?php else: ?>
                    <span class="text-muted">Fechado</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
