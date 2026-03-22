<?php
$user = current_user();

// Prepara os cartões por perfil para manter o HTML limpo.
$cards = [];

if ($user['perfil'] === ROLE_MANAGER) {
    // Manager dashboard shortcuts.
    $cards[] = [
        'title' => 'Gestão Pedagógica',
        'text' => 'Gerir cursos, UCs e planos de estudo.',
        'links' => [
            ['href' => 'index.php?page=cursos', 'label' => 'Gerir Cursos', 'class' => 'btn btn-primary'],
            ['href' => 'index.php?page=unidades', 'label' => 'Gerir UCs', 'class' => 'btn btn-outline-secondary mt-2'],
            ['href' => 'index.php?page=plano', 'label' => 'Plano', 'class' => 'btn btn-outline-secondary mt-2'],
        ],
    ];

    $cards[] = [
        'title' => 'Fichas de Aluno',
        'text' => 'Validar fichas submetidas e registar observacoes.',
        'links' => [
            ['href' => 'index.php?page=validar_fichas', 'label' => 'Validar Fichas', 'class' => 'btn btn-primary'],
        ],
    ];

    $cards[] = [
        'title' => 'Pedidos de Utilizador',
        'text' => 'Aprovar pedidos de novos acessos.',
        'links' => [
            ['href' => 'index.php?page=validar_utilizadores', 'label' => 'Validar Pedidos', 'class' => 'btn btn-primary'],
        ],
    ];
}

if ($user['perfil'] === ROLE_STUDENT) {
    // Student dashboard shortcuts.
    $cards[] = [
        'title' => 'Ficha de Aluno',
        'text' => 'Preencher dados e submeter para validacao.',
        'links' => [
            ['href' => 'index.php?page=ficha_aluno', 'label' => 'Editar Ficha', 'class' => 'btn btn-primary'],
        ],
    ];

    $cards[] = [
        'title' => 'Matricula / Inscricao',
        'text' => 'Criar e acompanhar pedido de matricula.',
        'links' => [
            ['href' => 'index.php?page=pedido_matricula', 'label' => 'Novo Pedido', 'class' => 'btn btn-primary'],
        ],
    ];

    $cards[] = [
        'title' => 'Notas',
        'text' => 'Consultar classificacoes lancadas nas pautas.',
        'links' => [
            ['href' => 'index.php?page=notas', 'label' => 'Ver Notas', 'class' => 'btn btn-primary'],
        ],
    ];
}

if ($user['perfil'] === ROLE_STAFF) {
    // Staff dashboard shortcuts.
    $cards[] = [
        'title' => 'Pedidos de Matricula',
        'text' => 'Validar ou rejeitar pedidos pendentes.',
        'links' => [
            ['href' => 'index.php?page=validar_matriculas', 'label' => 'Listar Pedidos', 'class' => 'btn btn-primary'],
        ],
    ];

    $cards[] = [
        'title' => 'Pautas de Avaliacao',
        'text' => 'Criar pautas, registar e editar notas.',
        'links' => [
            ['href' => 'index.php?page=pautas', 'label' => 'Gerir Pautas', 'class' => 'btn btn-primary'],
        ],
    ];
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="h4 mb-3">Ola, <?php echo e($user['nome']); ?>!</h1>
    </div>
</div>

<div class="row g-3">
    <?php foreach ($cards as $card): ?>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?php echo e($card['title']); ?></h5>
                    <p class="card-text"><?php echo e($card['text']); ?></p>
                    <?php foreach ($card['links'] as $link): ?>
                        <a href="<?php echo e($link['href']); ?>" class="<?php echo e($link['class']); ?>"><?php echo e($link['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
