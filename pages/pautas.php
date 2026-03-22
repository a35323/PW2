<?php
require_role([ROLE_STAFF]);
require_once __DIR__ . '/../includes/db.php';
$pdo = get_pdo();
$user = current_user();

$courses = $pdo->query('SELECT * FROM cursos WHERE ativo = 1 ORDER BY nome')->fetchAll();
$coursesById = [];
$planByCourse = [];
foreach ($courses as $c) {
    // Preload per-course UC plan to validate sheet creation server-side.
    $coursesById[$c['id']] = $c;
    $stmt = $pdo->prepare('SELECT u.id, u.codigo, u.nome FROM plano_curso cp JOIN unidades_curriculares u ON cp.uc_id = u.id WHERE cp.curso_id = ? ORDER BY cp.ano, cp.semestre');
    $stmt->execute([$c['id']]);
    $planByCourse[$c['id']] = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_sheet') {
    // Create a grading sheet only for UCs that belong to selected course plan.
    $course_id = (int)($_POST['course_id'] ?? 0);
    $uc_id = (int)($_POST['uc_id'] ?? 0);
    $academic_year = trim($_POST['academic_year'] ?? '');
    $season = trim($_POST['season'] ?? 'Normal');

    $allowedUcIds = array_column($planByCourse[$course_id] ?? [], 'id');

    if (!$course_id || !$uc_id || $academic_year === '' || !in_array($season, SHEET_SEASONS, true)) {
        flash('error', 'Preencha todos os campos para criar a pauta.');
    } elseif (!in_array($uc_id, $allowedUcIds, true)) {
        flash('error', 'UC não pertence ao plano do curso selecionado.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO pautas (curso_id, uc_id, ano_letivo, epoca, criado_por) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$course_id, $uc_id, $academic_year, $season, $user['id']]);
        $sheetId = (int)$pdo->lastInsertId();

        // Alunos elegíveis: matrícula aprovada para o curso selecionado
        $stmt = $pdo->prepare('SELECT utilizador_id FROM pedidos_matricula WHERE curso_id = ? AND estado = "aprovado"');
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll();
        $insert = $pdo->prepare('INSERT INTO pauta_registos (pauta_id, aluno_id) VALUES (?, ?)');
        foreach ($students as $s) {
            $insert->execute([$sheetId, $s['utilizador_id']]);
        }
        flash('success', 'Pauta criada com ' . count($students) . ' alunos elegíveis.');
    }
    if (ob_get_level()) {
        ob_clean();
    }
    header('Location: index.php?page=pautas');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_grades') {
    // Validate each grade (0-20) before persisting updates.
    $sheetId = (int)($_POST['sheet_id'] ?? 0);
    $grades = $_POST['grade'] ?? [];
    $stmt = $pdo->prepare('UPDATE pauta_registos SET nota = ?, atualizado_em = NOW() WHERE id = ?');
    $invalid = 0;
    foreach ($grades as $entryId => $grade) {
        $gradeVal = trim((string)$grade);
        if ($gradeVal !== '' && (!is_numeric($gradeVal) || $gradeVal < 0 || $gradeVal > 20)) {
            $invalid++;
            continue;
        }
        $stmt->execute([$gradeVal === '' ? null : $gradeVal, $entryId]);
    }
    if ($invalid > 0) {
        flash('error', 'Algumas notas foram ignoradas por estarem fora do intervalo 0-20.');
    } else {
        flash('success', 'Notas atualizadas.');
    }
    if (ob_get_level()) {
        ob_clean();
    }
    header('Location: index.php?page=pautas&sheet=' . $sheetId);
    exit;
}

$sheetId = isset($_GET['sheet']) ? (int)$_GET['sheet'] : null;
$editingSheet = null;
$entries = [];
if ($sheetId) {
    $stmt = $pdo->prepare('SELECT gs.*, u.nome AS uc_name, u.codigo, c.nome AS course_name FROM pautas gs JOIN unidades_curriculares u ON gs.uc_id = u.id JOIN cursos c ON gs.curso_id = c.id WHERE gs.id = ?');
    $stmt->execute([$sheetId]);
    $editingSheet = $stmt->fetch();
    if ($editingSheet) {
        $stmt = $pdo->prepare('SELECT ge.*, s.nome AS student_name, s.email FROM pauta_registos ge JOIN utilizadores s ON ge.aluno_id = s.id WHERE ge.pauta_id = ?');
        $stmt->execute([$sheetId]);
        $entries = $stmt->fetchAll();
    }
}

$searchTerm = trim($_GET['search'] ?? '');
$searchField = $_GET['search_field'] ?? 'all';
$allowedSearchFields = ['all', 'course', 'year', 'discipline'];
if (!in_array($searchField, $allowedSearchFields, true)) {
    $searchField = 'all';
}

if ($searchTerm !== '') {
    $searchLike = '%' . $searchTerm . '%';
    $baseQuery =
        'SELECT gs.id, c.nome AS course_name, u.codigo, u.nome AS uc_name, gs.ano_letivo, gs.epoca, gs.criado_em
         FROM pautas gs
         JOIN cursos c ON gs.curso_id = c.id
         JOIN unidades_curriculares u ON gs.uc_id = u.id';

    if ($searchField === 'course') {
        $stmt = $pdo->prepare($baseQuery . ' WHERE c.nome LIKE ? ORDER BY gs.criado_em DESC');
        $stmt->execute([$searchLike]);
    } elseif ($searchField === 'year') {
        $stmt = $pdo->prepare($baseQuery . ' WHERE gs.ano_letivo LIKE ? ORDER BY gs.criado_em DESC');
        $stmt->execute([$searchLike]);
    } elseif ($searchField === 'discipline') {
        $stmt = $pdo->prepare($baseQuery . ' WHERE u.nome LIKE ? OR u.codigo LIKE ? ORDER BY gs.criado_em DESC');
        $stmt->execute([$searchLike, $searchLike]);
    } else {
        $stmt = $pdo->prepare($baseQuery . ' WHERE c.nome LIKE ? OR gs.ano_letivo LIKE ? OR u.nome LIKE ? OR u.codigo LIKE ? ORDER BY gs.criado_em DESC');
        $stmt->execute([$searchLike, $searchLike, $searchLike, $searchLike]);
    }

    $sheets = $stmt->fetchAll();
} else {
    $sheets = $pdo->query('SELECT gs.id, c.nome AS course_name, u.codigo, u.nome AS uc_name, gs.ano_letivo, gs.epoca, gs.criado_em FROM pautas gs JOIN cursos c ON gs.curso_id = c.id JOIN unidades_curriculares u ON gs.uc_id = u.id ORDER BY gs.criado_em DESC')->fetchAll();
}
?>
<div class="row g-3 justify-content-center">
    <div style="width: 1040px;">
        <div class="d-flex flex-wrap gap-3 justify-content-center">
            <div class="card" style="width: 400px;">
            <div class="card-body">
                <h5 class="card-title">Criar Pauta</h5>
                <form method="post">
                    <input type="hidden" name="action" value="create_sheet">
                    <div class="mb-3">
                        <label class="form-label">Curso</label>
                        <select name="course_id" class="form-select" required>
                            <option value="">-- selecione --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo e($c['id']); ?>"><?php echo e($c['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">UC</label>
                        <select name="uc_id" class="form-select" required>
                            <option value="">-- selecione --</option>
                            <?php foreach ($planByCourse as $cid => $ucs): ?>
                                <optgroup label="<?php echo e($coursesById[$cid]['nome'] ?? ''); ?>">
                                    <?php foreach ($ucs as $uc): ?>
                                        <option value="<?php echo e($uc['id']); ?>"><?php echo e($uc['codigo'] . ' - ' . $uc['nome']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ano letivo</label>
                        <input type="text" name="academic_year" class="form-control" placeholder="2024/2025" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Época</label>
                        <select name="season" class="form-select">
                            <?php foreach (SHEET_SEASONS as $s): ?>
                                <option value="<?php echo e($s); ?>"><?php echo e($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">Criar</button>
                </form>
            </div>
            </div>
            <div class="card" style="width: 500px;">
                <div class="card-body">
                    <h5 class="card-title">Pautas</h5>
                    <form method="get" class="mb-3 d-flex gap-2">
                        <input type="hidden" name="page" value="pautas">
                        <?php if ($sheetId): ?>
                            <input type="hidden" name="sheet" value="<?php echo e($sheetId); ?>">
                        <?php endif; ?>
                        <select name="search_field" class="form-select" style="max-width: 170px;">
                            <option value="all" <?php echo $searchField === 'all' ? 'selected' : ''; ?>>Tudo</option>
                            <option value="course" <?php echo $searchField === 'course' ? 'selected' : ''; ?>>Curso</option>
                            <option value="year" <?php echo $searchField === 'year' ? 'selected' : ''; ?>>Ano letivo</option>
                            <option value="discipline" <?php echo $searchField === 'discipline' ? 'selected' : ''; ?>>Disciplina</option>
                        </select>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Introduza o termo"
                            value="<?php echo e($searchTerm); ?>"
                        >
                        <button type="submit" class="btn btn-outline-primary">Pesquisar</button>
                        <?php if ($searchTerm !== ''): ?>
                            <a href="index.php?page=pautas<?php echo $sheetId ? '&sheet=' . e((string)$sheetId) : ''; ?>" class="btn btn-outline-secondary">Limpar</a>
                        <?php endif; ?>
                    </form>
                    <ul class="list-group list-group-flush<?php echo count($sheets) > 4 ? ' overflow-auto' : ''; ?>"<?php echo count($sheets) > 4 ? ' style="max-height: 350px;"' : ''; ?>>
                        <?php if (!$sheets): ?>
                            <li class="list-group-item text-muted">Sem pautas encontradas.</li>
                        <?php else: ?>
                            <?php foreach ($sheets as $s): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo e($s['course_name']); ?></strong><br>
                                        <?php echo e($s['codigo']); ?> - <?php echo e($s['uc_name']); ?><br>
                                        <small><?php echo e($s['ano_letivo'] . ' - ' . $s['epoca']); ?></small>
                                    </div>
                                    <a class="btn btn-sm btn-outline-primary" href="index.php?page=pautas&sheet=<?php echo e($s['id']); ?>">Editar</a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div style="width: 930px;">
        <?php if ($editingSheet): ?>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="card-title mb-0">Editar Pauta</h5>
                        <a class="btn btn-sm btn-outline-primary" href="index.php?page=export_notas&sheet_id=<?php echo e($editingSheet['id']); ?>" target="_blank">Exportar PDF (Notas)</a>
                    </div>
                    <p><strong><?php echo e($editingSheet['course_name']); ?></strong> - <?php echo e($editingSheet['codigo']); ?> <?php echo e($editingSheet['uc_name']); ?><br>
                        <?php echo e($editingSheet['ano_letivo']); ?> | <?php echo e($editingSheet['epoca']); ?></p>
                    <form method="post">
                        <input type="hidden" name="action" value="save_grades">
                        <input type="hidden" name="sheet_id" value="<?php echo e($editingSheet['id']); ?>">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Aluno</th><th>Curso</th><th>Email</th><th>Nota</th></tr></thead>
                            <tbody>
                            <?php foreach ($entries as $e): ?>
                                <tr>
                                    <td><?php echo e($e['student_name']); ?></td>
                                    <td><?php echo e($editingSheet['course_name']); ?></td>
                                    <td><?php echo e($e['email']); ?></td>
                                    <td><input type="text" name="grade[<?php echo e($e['id']); ?>]" class="form-control" value="<?php echo e($e['nota']); ?>" placeholder="0-20"></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button class="btn btn-primary" type="submit">Guardar Notas</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Selecione uma pauta para editar.</div>
        <?php endif; ?>
    </div>
</div>
