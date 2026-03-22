<?php
require_role([ROLE_MANAGER]);
require_once __DIR__ . '/../includes/db.php';
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create course-UC association in the curriculum plan.
    $course_id = (int)($_POST['course_id'] ?? 0);
    $uc_id = (int)($_POST['uc_id'] ?? 0);
    $year = (int)($_POST['year'] ?? 1);
    $semester = (int)($_POST['semester'] ?? 1);

    if (!$course_id || !$uc_id) {
        flash('error', 'Selecione curso e UC.');
    } else {
        // prevent duplicates
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM plano_curso WHERE curso_id = ? AND uc_id = ? AND ano = ? AND semestre = ?');
        $stmt->execute([$course_id, $uc_id, $year, $semester]);
        if ($stmt->fetchColumn() > 0) {
            flash('error', 'Associação duplicada.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO plano_curso (curso_id, uc_id, ano, semestre) VALUES (?, ?, ?, ?)');
            $stmt->execute([$course_id, $uc_id, $year, $semester]);
            flash('success', 'Plano atualizado.');
        }
    }
    header('Location: index.php?page=plano');
    exit;
}

if (isset($_GET['delete'])) {
    // Remove one plan association by identifier.
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM plano_curso WHERE id = ?');
    $stmt->execute([$id]);
    flash('success', 'Associação removida.');
    header('Location: index.php?page=plano');
    exit;
}

$courses = $pdo->query('SELECT * FROM cursos WHERE ativo = 1 ORDER BY nome')->fetchAll();
$ucs = $pdo->query('SELECT * FROM unidades_curriculares ORDER BY codigo')->fetchAll();
$plan = $pdo->query('SELECT cp.id, c.nome AS course, u.codigo, u.nome AS uc_name, cp.ano, cp.semestre FROM plano_curso cp JOIN cursos c ON cp.curso_id = c.id JOIN unidades_curriculares u ON cp.uc_id = u.id ORDER BY c.nome, cp.ano, cp.semestre')->fetchAll();
?>
<h2 class="h4 mb-3">Plano de Estudos</h2>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Associar UC a Curso</h5>
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
                    <div class="mb-3">
                        <label class="form-label">Unidade Curricular</label>
                        <select name="uc_id" class="form-select" required>
                            <option value="">-- selecione --</option>
                            <?php foreach ($ucs as $uc): ?>
                                <option value="<?php echo e($uc['id']); ?>"><?php echo e($uc['codigo'] . ' - ' . $uc['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ano</label>
                        <input type="number" min="1" max="5" name="year" class="form-control" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Semestre</label>
                        <input type="number" min="1" max="2" name="semester" class="form-control" value="1" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Guardar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <table class="table table-striped">
            <thead><tr><th>Curso</th><th>UC</th><th>Ano</th><th>Semestre</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($plan as $row): ?>
                <tr>
                    <td><?php echo e($row['course']); ?></td>
                    <td><?php echo e($row['codigo'] . ' - ' . $row['uc_name']); ?></td>
                    <td><?php echo e($row['ano']); ?></td>
                    <td><?php echo e($row['semestre']); ?></td>
                    <td><a class="btn btn-sm btn-outline-danger" href="index.php?page=plano&delete=<?php echo e($row['id']); ?>" onclick="return confirm('Remover associação?');">Remover</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
