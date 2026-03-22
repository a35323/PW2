<?php
require_role([ROLE_MANAGER]);
require_once __DIR__ . '/../includes/db.php';

$pdo = get_pdo();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manager approval creates account and closes the request atomically.
    $id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $obs = trim($_POST['observations'] ?? '');

    if (!$id || !in_array($action, ['approve', 'reject'], true)) {
        flash('error', 'Pedido invalido.');
    } elseif ($action === 'approve') {
        $stmt = $pdo->prepare('SELECT * FROM pedidos_utilizador WHERE id = ? AND estado = "pendente" LIMIT 1');
        $stmt->execute([$id]);
        $request = $stmt->fetch();

        if (!$request) {
            flash('error', 'Pedido nao encontrado.');
        } else {
            try {
                // Transaction keeps user creation and request decision consistent.
                $pdo->beginTransaction();

                $insert = $pdo->prepare('INSERT INTO utilizadores (nome, email, hash_senha, perfil) VALUES (?, ?, ?, ?)');
                $insert->execute([
                    $request['nome'],
                    $request['email'],
                    $request['hash_senha'],
                    $request['perfil_sugerido'],
                ]);

                $update = $pdo->prepare('UPDATE pedidos_utilizador SET estado = "aprovado", observacoes = ?, decidido_por = ?, decidido_em = NOW() WHERE id = ?');
                $update->execute([$obs ?: null, $user['id'], $id]);

                $pdo->commit();
                flash('success', 'Utilizador aprovado e criado.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                flash('error', 'Nao foi possivel criar o utilizador.');
            }
        }
    } else {
        $stmt = $pdo->prepare('UPDATE pedidos_utilizador SET estado = "rejeitado", observacoes = ?, decidido_por = ?, decidido_em = NOW() WHERE id = ?');
        $stmt->execute([$obs ?: null, $user['id'], $id]);
        flash('success', 'Pedido rejeitado.');
    }

    header('Location: index.php?page=validar_utilizadores');
    exit;
}

$requests = $pdo->query('SELECT pr.*, u.nome AS decided_name FROM pedidos_utilizador pr LEFT JOIN utilizadores u ON pr.decidido_por = u.id ORDER BY FIELD(pr.estado,"pendente","aprovado","rejeitado"), pr.criado_em DESC')->fetchAll();
?>
<h2 class="h4 mb-3">Pedidos de Utilizador</h2>
<table class="table table-striped align-middle">
    <thead><tr><th>Nome</th><th>Email</th><th>Perfil sugerido</th><th>Estado</th><th>Observacoes</th><th>Decisao</th><th>Acoes</th></tr></thead>
    <tbody>
    <?php foreach ($requests as $r): ?>
        <tr>
            <td><?php echo e($r['nome']); ?></td>
            <td><?php echo e($r['email']); ?></td>
            <td><?php echo e($r['perfil_sugerido']); ?></td>
            <td><?php echo e($r['estado']); ?></td>
            <td><?php echo e($r['observacoes']); ?></td>
            <td><?php echo e($r['decided_name']); ?><br><small><?php echo e($r['decidido_em']); ?></small></td>
            <td>
                <?php if ($r['estado'] === 'pendente'): ?>
                    <form method="post" class="d-flex flex-column gap-2">
                        <input type="hidden" name="request_id" value="<?php echo e($r['id']); ?>">
                        <textarea name="observations" class="form-control" placeholder="Observacoes"></textarea>
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
