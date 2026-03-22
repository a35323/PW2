<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
$pdo = get_pdo();
$user = current_user();

$requestId = (int)($_GET['request_id'] ?? 0);
$studentId = (int)($_GET['aluno_id'] ?? 0);
$logoPath = __DIR__ . '/../uploads/photos/ipca.pt.png';
$logoUrl = file_exists($logoPath) ? 'uploads/photos/ipca.pt.png' : null;

$backUrl = 'index.php?page=pedido_matricula';
if (in_array($user['perfil'], [ROLE_STAFF, ROLE_MANAGER], true)) {
    $backUrl = 'index.php?page=validar_fichas';
}

$params = [];
// Build one query that supports request_id or aluno_id export inputs.
$sql =
    'SELECT er.*, c.nome AS course_name, s.nome AS student_name, s.email AS student_email, u.nome AS decided_by_name, sp.caminho_foto AS student_photo
     FROM pedidos_matricula er
     JOIN cursos c ON er.curso_id = c.id
     JOIN utilizadores s ON er.utilizador_id = s.id
     LEFT JOIN fichas_aluno sp ON sp.utilizador_id = s.id
     LEFT JOIN utilizadores u ON er.decidido_por = u.id
     WHERE 1 = 1';

if ($requestId > 0) {
    $sql .= ' AND er.id = ?';
    $params[] = $requestId;
} elseif ($studentId > 0) {
    $sql .= ' AND er.utilizador_id = ?';
    $params[] = $studentId;
} elseif ($user['perfil'] === ROLE_STUDENT) {
    $sql .= ' AND er.utilizador_id = ?';
    $params[] = $user['id'];
} else {
    http_response_code(400);
    echo 'Pedido inválido.';
    exit;
}

if ($user['perfil'] === ROLE_STUDENT) {
    // Students are always restricted to their own enrollment request.
    $sql .= ' AND er.utilizador_id = ?';
    $params[] = $user['id'];
} elseif (!in_array($user['perfil'], [ROLE_STAFF, ROLE_MANAGER], true)) {
    http_response_code(403);
    echo 'Acesso negado.';
    exit;
}

$stmt = $pdo->prepare($sql . ' ORDER BY er.criado_em DESC LIMIT 1');
$stmt->execute($params);
$request = $stmt->fetch();

if (!$request) {
    http_response_code(404);
    echo 'Pedido não encontrado.';
    exit;
}
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exportação de Matrícula</title>
    <style>
        body { font-family: 'Space Grotesk', Arial, sans-serif; margin: 24px; color: #0f172a; }
        .doc { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 2px solid #1e3a8a; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand img { height: 56px; width: auto; object-fit: contain; }
        .brand-text h1 { margin: 0; font-size: 24px; }
        .brand-text p { margin: 4px 0 0; color: #334155; font-size: 13px; }
        .grid { display: grid; grid-template-columns: 220px 1fr; gap: 8px 12px; max-width: 900px; }
        .label { font-weight: 700; }
        .student-photo-wrap { margin-top: 16px; }
        .student-photo { max-width: 180px; max-height: 220px; border: 1px solid #94a3b8; border-radius: 8px; object-fit: cover; }
        .actions { margin-bottom: 14px; display: flex; gap: 8px; }
        .btn { border: 1px solid #334155; padding: 8px 10px; text-decoration: none; color: #0f172a; border-radius: 6px; }
        .generated-at { margin-top: 14px; font-size: 12px; color: #475569; text-align: right; }
        @media print {
            .actions { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="doc">
    <div class="actions">
        <!-- Browser print dialog is used to generate the final PDF. -->
        <button class="btn" onclick="window.print()">Imprimir / Guardar PDF</button>
        <a class="btn" href="<?php echo e($backUrl); ?>">Voltar</a>
    </div>

    <div class="header">
        <div class="brand">
            <?php if ($logoUrl): ?>
                <img src="<?php echo e($logoUrl); ?>" alt="IPCA">
            <?php endif; ?>
            <div class="brand-text">
                <h1>Comprovativo de Matrícula</h1>
                <p><?php echo e(APP_NAME); ?></p>
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="label">Aluno</div><div><?php echo e($request['student_name']); ?></div>
        <div class="label">Email</div><div><?php echo e($request['student_email']); ?></div>
        <div class="label">Curso</div><div><?php echo e($request['course_name']); ?></div>
        <div class="label">Estado</div><div><?php echo e($request['estado']); ?></div>
        <div class="label">Observações</div><div><?php echo e($request['observacoes'] ?: '-'); ?></div>
        <div class="label">Decidido por</div><div><?php echo e($request['decided_by_name'] ?: '-'); ?></div>
        <div class="label">Data decisão</div><div><?php echo e($request['decidido_em'] ?: '-'); ?></div>
        <div class="label">Data pedido</div><div><?php echo e($request['criado_em']); ?></div>
    </div>

    <?php if (!empty($request['student_photo'])): ?>
        <div class="student-photo-wrap">
            <div class="label" style="margin-bottom: 6px;">Fotografia do Aluno</div>
            <img src="<?php echo e($request['student_photo']); ?>" alt="Fotografia do aluno" class="student-photo">
        </div>
    <?php endif; ?>

    <div class="generated-at">Gerado em: <?php echo e(date('d/m/Y H:i')); ?></div>
    </div>
</body>
</html>
