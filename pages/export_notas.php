<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
$pdo = get_pdo();
$user = current_user();

$sheetId = (int)($_GET['sheet_id'] ?? 0);
$targetStudentId = (int)($_GET['aluno_id'] ?? 0);
$title = 'Exportação de Notas';
$rows = [];
$meta = [];
$backUrl = 'index.php?page=notas';
$logoPath = __DIR__ . '/../uploads/photos/ipca.pt.png';
$logoUrl = file_exists($logoPath) ? 'uploads/photos/ipca.pt.png' : null;

if ($sheetId > 0) {
    // Staff/manager can export full sheet by sheet identifier.
    if (!in_array($user['perfil'], [ROLE_STAFF, ROLE_MANAGER], true)) {
        http_response_code(403);
        echo 'Acesso negado.';
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT gs.id, gs.ano_letivo, gs.epoca, c.nome AS course_name, u.codigo, u.nome AS uc_name
         FROM pautas gs
         JOIN cursos c ON gs.curso_id = c.id
         JOIN unidades_curriculares u ON gs.uc_id = u.id
         WHERE gs.id = ? LIMIT 1'
    );
    $stmt->execute([$sheetId]);
    $sheet = $stmt->fetch();

    if (!$sheet) {
        http_response_code(404);
        echo 'Pauta não encontrada.';
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT s.nome AS student_name, s.email, ge.nota, ge.atualizado_em
         FROM pauta_registos ge
         JOIN utilizadores s ON ge.aluno_id = s.id
         WHERE ge.pauta_id = ?
         ORDER BY s.nome ASC'
    );
    $stmt->execute([$sheetId]);
    $rows = $stmt->fetchAll();

    $title = 'Notas da Pauta';
    $backUrl = 'index.php?page=pautas&sheet=' . $sheetId;
    $meta = [
        'Curso' => $sheet['course_name'],
        'Disciplina' => $sheet['codigo'] . ' - ' . $sheet['uc_name'],
        'Ano letivo' => $sheet['ano_letivo'],
        'Época' => $sheet['epoca'],
    ];
} else {
    // Student export defaults to own records unless elevated role targets aluno_id.
    $studentId = $targetStudentId > 0 ? $targetStudentId : (int)$user['id'];

    if ($user['perfil'] === ROLE_STUDENT && $studentId !== (int)$user['id']) {
        http_response_code(403);
        echo 'Acesso negado.';
        exit;
    }

    if (!in_array($user['perfil'], [ROLE_STUDENT, ROLE_STAFF, ROLE_MANAGER], true)) {
        http_response_code(403);
        echo 'Acesso negado.';
        exit;
    }

    $stmt = $pdo->prepare('SELECT nome, email FROM utilizadores WHERE id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();

    if (!$student) {
        http_response_code(404);
        echo 'Aluno não encontrado.';
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT pr.nota, pr.atualizado_em, p.ano_letivo, p.epoca, c.nome AS course_name, u.codigo, u.nome AS uc_name
         FROM pauta_registos pr
         JOIN pautas p ON pr.pauta_id = p.id
         JOIN cursos c ON p.curso_id = c.id
         JOIN unidades_curriculares u ON p.uc_id = u.id
         WHERE pr.aluno_id = ?
         ORDER BY p.ano_letivo DESC, p.criado_em DESC, u.nome ASC'
    );
    $stmt->execute([$studentId]);
    $rows = $stmt->fetchAll();

    $title = 'Notas do Aluno';
    if (in_array($user['perfil'], [ROLE_STAFF, ROLE_MANAGER], true)) {
        $backUrl = 'index.php?page=validar_fichas';
    }
    $meta = [
        'Aluno' => $student['nome'],
        'Email' => $student['email'],
    ];
}
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($title); ?></title>
    <style>
        body { font-family: 'Space Grotesk', Arial, sans-serif; margin: 24px; color: #0f172a; }
        .doc { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 2px solid #1e3a8a; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand img { height: 56px; width: auto; object-fit: contain; }
        .brand-text h1 { margin: 0; font-size: 24px; }
        .brand-text p { margin: 4px 0 0; color: #334155; font-size: 13px; }
        .meta { margin-bottom: 16px; display: grid; grid-template-columns: 220px 1fr; gap: 6px 12px; }
        .meta p { margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 9px; text-align: left; }
        th { background: #dbeafe; color: #0f172a; }
        .actions { margin-bottom: 14px; display: flex; gap: 8px; }
        .btn { border: 1px solid #334155; padding: 8px 10px; text-decoration: none; color: #0f172a; border-radius: 6px; }
        .generated-at { margin-top: 12px; font-size: 12px; color: #475569; text-align: right; }
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
                <h1><?php echo e($title); ?></h1>
                <p><?php echo e(APP_NAME); ?></p>
            </div>
        </div>
    </div>

    <div class="meta">
        <?php foreach ($meta as $label => $value): ?>
            <p><strong><?php echo e($label); ?>:</strong> <?php echo e((string)$value); ?></p>
        <?php endforeach; ?>
    </div>

    <table>
        <thead>
        <?php if ($sheetId > 0): ?>
            <tr>
                <th>Aluno</th>
                <th>Email</th>
                <th>Nota</th>
                <th>Atualizada</th>
            </tr>
        <?php else: ?>
            <tr>
                <th>Curso</th>
                <th>Disciplina</th>
                <th>Ano letivo</th>
                <th>Época</th>
                <th>Nota</th>
                <th>Atualizada</th>
            </tr>
        <?php endif; ?>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="6">Sem registos.</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <?php if ($sheetId > 0): ?>
                    <tr>
                        <td><?php echo e($row['student_name']); ?></td>
                        <td><?php echo e($row['email']); ?></td>
                        <td><?php echo $row['nota'] !== null && $row['nota'] !== '' ? e($row['nota']) : 'Por lançar'; ?></td>
                        <td><?php echo e($row['atualizado_em'] ?: '-'); ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td><?php echo e($row['course_name']); ?></td>
                        <td><?php echo e($row['codigo'] . ' - ' . $row['uc_name']); ?></td>
                        <td><?php echo e($row['ano_letivo']); ?></td>
                        <td><?php echo e($row['epoca']); ?></td>
                        <td><?php echo $row['nota'] !== null && $row['nota'] !== '' ? e($row['nota']) : 'Por lançar'; ?></td>
                        <td><?php echo e($row['atualizado_em'] ?: '-'); ?></td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="generated-at">Gerado em: <?php echo e(date('d/m/Y H:i')); ?></div>
    </div>
</body>
</html>
