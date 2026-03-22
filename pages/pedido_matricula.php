<?php
require_role([ROLE_STUDENT]);
require_once __DIR__ . '/../includes/db.php';
$pdo = get_pdo();
$user = current_user();
$courses = $pdo->query('SELECT * FROM cursos WHERE ativo = 1 ORDER BY nome')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Students can open a new request only when no pending request exists.
    $course_id = (int)($_POST['course_id'] ?? 0);
    if (!$course_id) {
        flash('error', 'Selecione um curso.');
    } else {
        // prevent multiple pending
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM pedidos_matricula WHERE utilizador_id = ? AND estado = "pendente"');
        $stmt->execute([$user['id']]);
        if ($stmt->fetchColumn() > 0) {
            flash('error', 'Já tem um pedido pendente.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO pedidos_matricula (utilizador_id, curso_id, estado) VALUES (?, ?, "pendente")');
            $stmt->execute([$user['id'], $course_id]);
            flash('success', 'Pedido criado.');
        }
    }
    header('Location: index.php?page=pedido_matricula');
    exit;
}

$stmt = $pdo->prepare('SELECT er.*, c.nome AS course_name, u.nome AS decided_by_name FROM pedidos_matricula er JOIN cursos c ON er.curso_id = c.id LEFT JOIN utilizadores u ON er.decidido_por = u.id WHERE er.utilizador_id = ? ORDER BY er.criado_em DESC');
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll();
?>
<h2 class="h4 mb-3">Pedido de Matrícula / Inscrição</h2>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Novo Pedido</h5>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Curso</label>
                        <select name="course_id" class="form-select" required>
                            <option value="">-- selecione --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo e($c['id']); ?>"><?php echo e($c['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">Submeter Pedido</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <table class="table table-striped">
            <thead><tr><th>Curso</th><th>Estado</th><th>Observações</th><th>Data decisão</th><th>Criado</th><th>PDF</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?php echo e($r['course_name']); ?></td>
                    <td><?php echo e($r['estado']); ?></td>
                    <td><?php echo e($r['observacoes']); ?></td>
                    <td><?php echo e($r['decidido_em']); ?></td>
                    <td><?php echo e($r['criado_em']); ?></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="index.php?page=export_matricula&request_id=<?php echo e($r['id']); ?>" target="_blank">PDF</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
