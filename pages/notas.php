<?php
require_role([ROLE_STUDENT]);
require_once __DIR__ . '/../includes/db.php';

$pdo = get_pdo();
$user = current_user();

$stmt = $pdo->prepare(
    'SELECT pr.nota, pr.atualizado_em, p.ano_letivo, p.epoca, c.nome AS course_name, u.codigo, u.nome AS uc_name
     FROM pauta_registos pr
     JOIN pautas p ON pr.pauta_id = p.id
     JOIN cursos c ON p.curso_id = c.id
     JOIN unidades_curriculares u ON p.uc_id = u.id
     WHERE pr.aluno_id = ?
     ORDER BY p.ano_letivo DESC, p.criado_em DESC, u.nome ASC'
);
$stmt->execute([$user['id']]);
$grades = $stmt->fetchAll();

$publishedCount = 0;
$sumGrades = 0.0;
foreach ($grades as $gradeRow) {
    if ($gradeRow['nota'] !== null && $gradeRow['nota'] !== '') {
        $publishedCount++;
        $sumGrades += (float)$gradeRow['nota'];
    }
}

$average = $publishedCount > 0 ? number_format($sumGrades / $publishedCount, 1) : null;
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="h4 mb-0">As Minhas Notas</h2>
    <a class="btn btn-outline-primary" href="index.php?page=export_notas" target="_blank">Exportar PDF (Notas)</a>
</div>
<div class="row g-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Resumo</h5>
                <p class="mb-2"><strong>Total de registos:</strong> <?php echo e((string)count($grades)); ?></p>
                <p class="mb-2"><strong>Notas lançadas:</strong> <?php echo e((string)$publishedCount); ?></p>
                <p class="mb-0"><strong>Média:</strong> <?php echo $average !== null ? e($average . ' valores') : 'Sem notas publicadas'; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Histórico</h5>
                <?php if (!$grades): ?>
                    <div class="alert alert-info mb-0">Ainda não existem pautas associadas ao seu utilizador.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Curso</th>
                                    <th>UC</th>
                                    <th>Ano letivo</th>
                                    <th>Época</th>
                                    <th>Nota</th>
                                    <th>Atualizada</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($grades as $gradeRow): ?>
                                <tr>
                                    <td><?php echo e($gradeRow['course_name']); ?></td>
                                    <td><?php echo e($gradeRow['codigo'] . ' - ' . $gradeRow['uc_name']); ?></td>
                                    <td><?php echo e($gradeRow['ano_letivo']); ?></td>
                                    <td><?php echo e($gradeRow['epoca']); ?></td>
                                    <td><?php echo $gradeRow['nota'] !== null && $gradeRow['nota'] !== '' ? e($gradeRow['nota']) : 'Por lançar'; ?></td>
                                    <td><?php echo e($gradeRow['atualizado_em'] ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>