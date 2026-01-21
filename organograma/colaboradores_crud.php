<?php
require_once 'config.php';

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $nome = $_POST['nome'] ?? '';
            $cargo = $_POST['cargo'] ?? '';
            $departamento = $_POST['departamento'] ?? '';
            $empresa = $_POST['empresa'] ?? '';
            $ramal = $_POST['ramal'] ?? '';
            $telefone = $_POST['telefone'] ?? '';
            $email = $_POST['email'] ?? '';
            $teams = $_POST['teams'] ?? '';
            $tipo_contrato = $_POST['tipo_contrato'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $observacoes = $_POST['observacoes'] ?? '';
        $data_admissao = $_POST['data_admissao'] ?? null;
        $parent_id = $_POST['parent_id'] ?? null;
        $ordem_exibicao = $_POST['ordem_exibicao'] ?? 0;
        $nivel_hierarquico = $_POST['nivel_hierarquico'] ?? 1;
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        // Processar upload de foto
        $foto_nome = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $foto_nome = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $foto_nome;
                
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                    echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload da foto']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Formato de arquivo não permitido. Use JPG, PNG ou GIF.']);
                exit;
            }
        }
            
            $sql = "INSERT INTO colaboradores (nome, cargo, departamento, empresa, ramal, telefone, email, teams, tipo_contrato, descricao, observacoes, data_admissao, parent_id, ordem_exibicao, nivel_hierarquico, foto, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
             $stmt = $conn->prepare($sql);
             $stmt->bind_param("ssssssssssssiiiss", $nome, $cargo, $departamento, $empresa, $ramal, $telefone, $email, $teams, $tipo_contrato, $descricao, $observacoes, $data_admissao, $parent_id, $ordem_exibicao, $nivel_hierarquico, $foto_nome, $ativo);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Colaborador criado com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao criar colaborador: ' . $conn->error]);
            }
            exit;
            
        case 'update':
            $id = $_POST['id'] ?? 0;
            $nome = $_POST['nome'] ?? '';
            $cargo = $_POST['cargo'] ?? '';
            $departamento = $_POST['departamento'] ?? '';
            $empresa = $_POST['empresa'] ?? '';
            $ramal = $_POST['ramal'] ?? '';
            $telefone = $_POST['telefone'] ?? '';
            $email = $_POST['email'] ?? '';
            $teams = $_POST['teams'] ?? '';
            $tipo_contrato = $_POST['tipo_contrato'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $observacoes = $_POST['observacoes'] ?? '';
         $data_admissao = $_POST['data_admissao'] ?? null;
         $parent_id = $_POST['parent_id'] ?? null;
         $ordem_exibicao = $_POST['ordem_exibicao'] ?? 0;
         $nivel_hierarquico = $_POST['nivel_hierarquico'] ?? 1;
         $ativo = isset($_POST['ativo']) ? 1 : 0;
         
         // Processar upload de foto na edição
         $foto_update = "";
         $foto_params = "";
         if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
             $upload_dir = 'uploads/';
             if (!is_dir($upload_dir)) {
                 mkdir($upload_dir, 0777, true);
             }
             
             $file_extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
             $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
             
             if (in_array($file_extension, $allowed_extensions)) {
                 $foto_nome = uniqid() . '.' . $file_extension;
                 $upload_path = $upload_dir . $foto_nome;
                 
                 if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                     $foto_update = ", foto=?";
                     $foto_params = "s";
                 } else {
                     echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload da foto']);
                     exit;
                 }
             } else {
                 echo json_encode(['success' => false, 'message' => 'Formato de arquivo não permitido. Use JPG, PNG ou GIF.']);
                 exit;
             }
         }
         
         $sql = "UPDATE colaboradores SET nome=?, cargo=?, departamento=?, empresa=?, ramal=?, telefone=?, email=?, teams=?, tipo_contrato=?, descricao=?, observacoes=?, data_admissao=?, parent_id=?, ordem_exibicao=?, nivel_hierarquico=?, ativo=?" . $foto_update . " WHERE id=?";
         $stmt = $conn->prepare($sql);
         
         if ($foto_update) {
             $stmt->bind_param("ssssssssssssiiis" . $foto_params . "i", $nome, $cargo, $departamento, $empresa, $ramal, $telefone, $email, $teams, $tipo_contrato, $descricao, $observacoes, $data_admissao, $parent_id, $ordem_exibicao, $nivel_hierarquico, $ativo, $foto_nome, $id);
         } else {
             $stmt->bind_param("ssssssssssssiiisi", $nome, $cargo, $departamento, $empresa, $ramal, $telefone, $email, $teams, $tipo_contrato, $descricao, $observacoes, $data_admissao, $parent_id, $ordem_exibicao, $nivel_hierarquico, $ativo, $id);
         }
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Colaborador atualizado com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar colaborador: ' . $conn->error]);
            }
            exit;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            $sql = "DELETE FROM colaboradores WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Colaborador excluído com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir colaborador: ' . $conn->error]);
            }
            exit;
            
        case 'bulk_delete':
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids)) {
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $sql = "DELETE FROM colaboradores WHERE id IN ($placeholders)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => count($ids) . ' colaborador(es) excluído(s) com sucesso!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao excluir colaboradores: ' . $conn->error]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Nenhum colaborador selecionado']);
            }
            exit;
            
        case 'bulk_activate':
            $ids = $_POST['ids'] ?? [];
            $status = $_POST['status'] ?? 1;
            if (!empty($ids)) {
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $sql = "UPDATE colaboradores SET ativo = ? WHERE id IN ($placeholders)";
                $stmt = $conn->prepare($sql);
                $types = 'i' . str_repeat('i', count($ids));
                $stmt->bind_param($types, $status, ...$ids);
                
                if ($stmt->execute()) {
                    $action_text = $status ? 'ativado(s)' : 'desativado(s)';
                    echo json_encode(['success' => true, 'message' => count($ids) . ' colaborador(es) ' . $action_text . ' com sucesso!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar colaboradores: ' . $conn->error]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Nenhum colaborador selecionado']);
            }
            exit;
            
        case 'get':
            $id = $_POST['id'] ?? 0;
            $sql = "SELECT * FROM colaboradores WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Colaborador não encontrado']);
            }
            exit;
    }
}

// Buscar colaboradores para listagem
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(10, min(100, intval($_GET['limit'] ?? 10))); // Permite entre 10 e 100 itens
$offset = ($page - 1) * $limit;

$where_clause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where_clause = "WHERE nome LIKE ? OR email LIKE ? OR cargo LIKE ? OR departamento LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
    $types = 'ssss';
}

// Contar total de registros
$count_sql = "SELECT COUNT(*) as total FROM colaboradores $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_result = $count_stmt->get_result();
} else {
    $total_result = $conn->query($count_sql);
}
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Buscar registros da página atual
$sql = "SELECT * FROM colaboradores $where_clause ORDER BY nome LIMIT $limit OFFSET $offset";
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Gestão de Colaboradores</title>
    <!-- CSS files -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons@latest/icons-sprite.svg" rel="stylesheet"/>
    <style>
        .avatar-sm {
            width: 2rem;
            height: 2rem;
        }
        .table-responsive {
            min-height: 400px;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-active {
            background-color: #2fb344;
        }
        .status-inactive {
            background-color: #d63939;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="page-wrapper">
            <!-- Page header -->
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <h2 class="page-title">
                                Gestão de Colaboradores
                            </h2>
                        </div>
                        <!-- Page title actions -->
                        <div class="col-auto">
                            <div class="d-flex align-items-center">
                                <label class="form-label me-2 mb-0">Itens por página:</label>
                                <select class="form-select form-select-sm" id="items-per-page" style="width: auto;" onchange="changeItemsPerPage()">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-colaborador">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <line x1="12" y1="5" x2="12" y2="19"/>
                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                    Novo Colaborador
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Page body -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="row row-deck row-cards">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Lista de Colaboradores</h3>
                                    <div class="card-actions">
                                        <form method="GET" class="d-flex" id="search-form">
                                            <input type="search" name="search" id="search-input" class="form-control me-2" placeholder="Buscar colaboradores..." value="<?= htmlspecialchars($search) ?>">
                                            <button type="submit" class="btn btn-outline-primary">Buscar</button>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Ações em lote -->
                                <div class="card-body border-bottom py-3">
                                    <div class="d-flex">
                                        <div class="text-muted">
                                            <span id="selected-count">0</span> colaborador(es) selecionado(s)
                                        </div>
                                        <div class="ms-auto">
                                            <div class="btn-group" id="bulk-actions" style="display: none;">
                                                <button type="button" class="btn btn-sm btn-success" onclick="bulkActivate(1)">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M5 12l5 5l10 -10"/>
                                                    </svg>
                                                    Ativar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning" onclick="bulkActivate(0)">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M18 6l-12 12"/>
                                                        <path d="M6 6l12 12"/>
                                                    </svg>
                                                    Desativar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="bulkDelete()">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M4 7l16 0"/>
                                                        <path d="M10 11l0 6"/>
                                                        <path d="M14 11l0 6"/>
                                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                                                    </svg>
                                                    Excluir
                                                </button>
                                                <button type="button" class="btn btn-sm btn-info" onclick="exportSelected()">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                                        <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                                        <path d="M12 17v-6"/>
                                                        <path d="M9.5 14.5l2.5 2.5l2.5 -2.5"/>
                                                    </svg>
                                                    Exportar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead>
                                            <tr>
                                                <th class="w-1">
                                                    <input class="form-check-input m-0 align-middle" type="checkbox" id="select-all">
                                                </th>
                                                <th class="w-1">Foto</th>
                                                <th>Colaborador</th>
                                                <th>Cargo</th>
                                                <th>Departamento</th>
                                                <th>Empresa</th>
                                                <th>Contato</th>
                                                <th>Status</th>
                                                <th class="w-1">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($result && $result->num_rows > 0): ?>
                                                <?php while ($row = $result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <input class="form-check-input m-0 align-middle row-checkbox" type="checkbox" value="<?= $row['id'] ?>">
                                                        </td>
                                                        <td>
                                                            <span class="avatar avatar-md" style="background-image: url(<?= !empty($row['foto']) ? 'uploads/' . $row['foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($row['nome']) . '&background=random' ?>)"></span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex py-1 align-items-center">
                                                                <div class="flex-fill">
                                                                    <div class="font-weight-medium"><?= htmlspecialchars($row['nome']) ?></div>
                                                                    <div class="text-muted"><?= htmlspecialchars($row['email']) ?></div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?= htmlspecialchars($row['cargo']) ?></td>
                                                        <td><?= htmlspecialchars($row['departamento']) ?></td>
                                                        <td><?= htmlspecialchars($row['empresa']) ?></td>
                                                        <td>
                                                            <div><?= htmlspecialchars($row['telefone']) ?></div>
                                                            <div class="text-muted">Ramal: <?= htmlspecialchars($row['ramal']) ?></div>
                                                        </td>
                                                        <td>
                                                            <span class="status-dot <?= $row['ativo'] ? 'status-active' : 'status-inactive' ?>"></span>
                                                            <?= $row['ativo'] ? 'Ativo' : 'Inativo' ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-list flex-nowrap">
                                                                <button class="btn btn-sm btn-outline-primary" onclick="editColaborador(<?= $row['id'] ?>)">
                                                                    Editar
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteColaborador(<?= $row['id'] ?>)">
                                                                    Excluir
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center py-4">
                                                        <div class="empty">
                                                            <div class="empty-icon">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                                    <circle cx="12" cy="7" r="4"/>
                                                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                                                </svg>
                                                            </div>
                                                            <p class="empty-title">Nenhum colaborador encontrado</p>
                                                            <p class="empty-subtitle text-muted">
                                                                Tente ajustar sua pesquisa ou adicionar um novo colaborador.
                                                            </p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Paginação -->
                                <?php if ($total_pages > 1): ?>
                                    <div class="card-footer d-flex align-items-center">
                                        <p class="m-0 text-muted">Mostrando <span><?= min($offset + 1, $total_records) ?></span> a <span><?= min($offset + $limit, $total_records) ?></span> de <span><?= $total_records ?></span> registros</p>
                                        <ul class="pagination m-0 ms-auto">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>&limit=<?= $limit ?>">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <polyline points="15,6 9,12 15,18"/>
                                                        </svg>
                                                        anterior
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>&limit=<?= $limit ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>&limit=<?= $limit ?>">
                                                        próxima
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <polyline points="9,6 15,12 9,18"/>
                                                        </svg>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Colaborador -->
    <div class="modal modal-blur fade" id="modal-colaborador" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Novo Colaborador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="form-colaborador">
                    <div class="modal-body">
                        <input type="hidden" id="colaborador-id" name="id">
                        
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label class="form-label required">Nome</label>
                                    <input type="text" class="form-control" name="nome" id="nome" required>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo" checked>
                                        <label class="form-check-label" for="ativo">Ativo</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Cargo</label>
                                    <input type="text" class="form-control" name="cargo" id="cargo">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Departamento</label>
                                    <input type="text" class="form-control" name="departamento" id="departamento">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Empresa</label>
                                    <select class="form-select" name="empresa" id="empresa">
                                        <option value="">Selecione...</option>
                                        <option value="Grupo Barão">Grupo Barão</option>
                                        <option value="Barão">Barão</option>
                                        <option value="Toymania">Toymania</option>
                                        <option value="Alfaness">Alfaness</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Contrato</label>
                                    <select class="form-select" name="tipo_contrato" id="tipo_contrato">
                                        <option value="">Selecione...</option>
                                        <option value="CLT">CLT</option>
                                        <option value="PJ">PJ</option>
                                        <option value="Aprendiz">Aprendiz</option>
                                        <option value="Terceirizado">Terceirizado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label">Ramal</label>
                                    <input type="text" class="form-control" name="ramal" id="ramal" maxlength="10">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label">Telefone</label>
                                    <input type="text" class="form-control" name="telefone" id="telefone">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label">Data de Admissão</label>
                                    <input type="date" class="form-control" name="data_admissao" id="data_admissao">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="email">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Teams</label>
                                    <input type="text" class="form-control" name="teams" id="teams">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label">Nível Hierárquico</label>
                                    <input type="number" class="form-control" name="nivel_hierarquico" id="nivel_hierarquico" value="1" min="1">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label">Ordem de Exibição</label>
                                    <input type="number" class="form-control" name="ordem_exibicao" id="ordem_exibicao" value="0" min="0">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label">Superior Direto</label>
                                    <select class="form-select" name="parent_id" id="parent_id">
                                        <option value="">Nenhum</option>
                                        <?php
                                        $parent_sql = "SELECT id, nome FROM colaboradores WHERE ativo = 1 ORDER BY nome";
                                        $parent_result = $conn->query($parent_sql);
                                        if ($parent_result) {
                                            while ($parent_row = $parent_result->fetch_assoc()) {
                                                echo "<option value='{$parent_row['id']}'>{$parent_row['nome']}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" name="descricao" id="descricao" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                                     <label class="form-label">Observações</label>
                                     <textarea class="form-control" name="observacoes" id="observacoes" rows="3"></textarea>
                                 </div>
                                 <div class="mb-3">
                                     <label class="form-label">Foto</label>
                                     <input type="file" class="form-control" name="foto" id="foto" accept="image/*">
                                     <div class="mt-2" id="foto-preview" style="display: none;">
                                         <img id="foto-img" src="" alt="Preview" style="max-width: 100px; max-height: 100px; border-radius: 4px;">
                                         <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeFoto()">Remover</button>
                                     </div>
                                 </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary ms-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 12l5 5l10 -10"/>
                            </svg>
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
    
    <script>
        // Variáveis globais
        let selectedIds = [];
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            updateBulkActions();
            
            // Event listeners
            document.getElementById('select-all').addEventListener('change', toggleSelectAll);
            document.querySelectorAll('.row-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateSelection);
            });
            
            // Form submit
            document.getElementById('form-colaborador').addEventListener('submit', handleFormSubmit);
        });
        
        // Seleção em lote
        function toggleSelectAll() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelection();
        }
        
        function updateSelection() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
            
            document.getElementById('selected-count').textContent = selectedIds.length;
            updateBulkActions();
            
            // Atualizar estado do select-all
            const allCheckboxes = document.querySelectorAll('.row-checkbox');
            const selectAll = document.getElementById('select-all');
            selectAll.checked = selectedIds.length === allCheckboxes.length;
            selectAll.indeterminate = selectedIds.length > 0 && selectedIds.length < allCheckboxes.length;
        }
        
        function updateBulkActions() {
            const bulkActions = document.getElementById('bulk-actions');
            bulkActions.style.display = selectedIds.length > 0 ? 'block' : 'none';
        }
        
        // Ações em lote
        function bulkActivate(status) {
            if (selectedIds.length === 0) {
                alert('Selecione pelo menos um colaborador');
                return;
            }
            
            const action = status ? 'ativar' : 'desativar';
            if (!confirm(`Deseja ${action} ${selectedIds.length} colaborador(es) selecionado(s)?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'bulk_activate');
            formData.append('status', status);
            selectedIds.forEach(id => formData.append('ids[]', id));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar solicitação');
            });
        }
        
        function bulkDelete() {
            if (selectedIds.length === 0) {
                alert('Selecione pelo menos um colaborador');
                return;
            }
            
            if (!confirm(`Deseja excluir ${selectedIds.length} colaborador(es) selecionado(s)? Esta ação não pode ser desfeita.`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'bulk_delete');
            selectedIds.forEach(id => formData.append('ids[]', id));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar solicitação');
            });
        }
        
        function exportSelected() {
            if (selectedIds.length === 0) {
                alert('Selecione pelo menos um colaborador');
                return;
            }
            
            // Criar CSV dos dados selecionados
            const rows = [];
            const headers = ['ID', 'Nome', 'Cargo', 'Departamento', 'Empresa', 'Email', 'Telefone', 'Ramal', 'Status'];
            rows.push(headers.join(','));
            
            selectedIds.forEach(id => {
                const row = document.querySelector(`input[value="${id}"]`).closest('tr');
                const cells = row.querySelectorAll('td');
                const rowData = [
                    id,
                    cells[1].querySelector('.font-weight-medium').textContent.trim(),
                    cells[2].textContent.trim(),
                    cells[3].textContent.trim(),
                    cells[4].textContent.trim(),
                    cells[1].querySelector('.text-muted').textContent.trim(),
                    cells[5].querySelector('div:first-child').textContent.trim(),
                    cells[5].querySelector('.text-muted').textContent.replace('Ramal: ', '').trim(),
                    cells[6].textContent.trim()
                ];
                rows.push(rowData.map(cell => `"${cell}"`).join(','));
            });
            
            const csvContent = rows.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'colaboradores_selecionados.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // CRUD Operations
        function editColaborador(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateForm(data.data);
                    document.getElementById('modal-title').textContent = 'Editar Colaborador';
                    new bootstrap.Modal(document.getElementById('modal-colaborador')).show();
                } else {
                    alert('Erro ao carregar dados: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao carregar dados');
            });
        }
        
        function deleteColaborador(id) {
            if (!confirm('Deseja excluir este colaborador? Esta ação não pode ser desfeita.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao excluir colaborador');
            });
        }
        
        function populateForm(data) {
            document.getElementById('colaborador-id').value = data.id;
            document.getElementById('nome').value = data.nome || '';
            document.getElementById('cargo').value = data.cargo || '';
            document.getElementById('departamento').value = data.departamento || '';
            document.getElementById('empresa').value = data.empresa || '';
            document.getElementById('ramal').value = data.ramal || '';
            document.getElementById('telefone').value = data.telefone || '';
            document.getElementById('email').value = data.email || '';
            document.getElementById('teams').value = data.teams || '';
            document.getElementById('tipo_contrato').value = data.tipo_contrato || '';
            document.getElementById('descricao').value = data.descricao || '';
            document.getElementById('observacoes').value = data.observacoes || '';
            document.getElementById('data_admissao').value = data.data_admissao || '';
            document.getElementById('parent_id').value = data.parent_id || '';
            document.getElementById('ordem_exibicao').value = data.ordem_exibicao || 0;
            document.getElementById('nivel_hierarquico').value = data.nivel_hierarquico || 1;
            document.getElementById('ativo').checked = data.ativo == 1;
            
            // Exibir foto atual se existir
            if (data.foto) {
                document.getElementById('foto-img').src = 'uploads/' + data.foto;
                document.getElementById('foto-preview').style.display = 'block';
            }
        }
        
        function clearForm() {
            document.getElementById('form-colaborador').reset();
            document.getElementById('colaborador-id').value = '';
            document.getElementById('ativo').checked = true;
            document.getElementById('modal-title').textContent = 'Novo Colaborador';
            document.getElementById('foto-preview').style.display = 'none';
            document.getElementById('foto-img').src = '';
        }
        
        function handleFormSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const id = formData.get('id');
            formData.append('action', id ? 'update' : 'create');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    bootstrap.Modal.getInstance(document.getElementById('modal-colaborador')).hide();
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar colaborador');
            });
        }
        
        // Event listener para limpar form quando modal é fechado
        document.getElementById('modal-colaborador').addEventListener('hidden.bs.modal', clearForm);
        
        // Preview da foto
        document.getElementById('foto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('foto-img').src = e.target.result;
                    document.getElementById('foto-preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Função para remover foto
        function removeFoto() {
            document.getElementById('foto').value = '';
            document.getElementById('foto-preview').style.display = 'none';
            document.getElementById('foto-img').src = '';
        }
        
        // Gerenciamento de itens por página
        function changeItemsPerPage() {
            const select = document.getElementById('items-per-page');
            const itemsPerPage = select.value;
            
            // Salvar no localStorage
            localStorage.setItem('colaboradores_items_per_page', itemsPerPage);
            
            // Recarregar a página com o novo limite
            const url = new URL(window.location);
            url.searchParams.set('limit', itemsPerPage);
            url.searchParams.set('page', '1'); // Voltar para a primeira página
            window.location.href = url.toString();
        }
        
        // Carregar configuração salva ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            const savedItemsPerPage = localStorage.getItem('colaboradores_items_per_page');
            if (savedItemsPerPage) {
                const select = document.getElementById('items-per-page');
                select.value = savedItemsPerPage;
                
                // Se a URL não tem o parâmetro limit, adicionar
                const url = new URL(window.location);
                if (!url.searchParams.has('limit')) {
                    url.searchParams.set('limit', savedItemsPerPage);
                    window.history.replaceState({}, '', url.toString());
                }
            }
            
            // Inicializar busca dinâmica
            initDynamicSearch();
        });
        
        // Variáveis para busca dinâmica
        let searchTimeout;
        let currentPage = 1;
        let currentLimit = 10;
        
        // Inicializar busca dinâmica
        function initDynamicSearch() {
            const searchInput = document.getElementById('search-input');
            const searchForm = document.getElementById('search-form');
            const itemsPerPageSelect = document.getElementById('items-per-page');
            
            if (searchForm) {
                // Prevenir comportamento padrão do formulário
                searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    currentPage = 1;
                    performSearch();
                });
            }
            
            if (searchInput) {
                // Capturar digitação no campo de busca
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        currentPage = 1; // Resetar para primeira página
                        performSearch();
                    }, 300); // Debounce de 300ms
                });
                
                // Realizar busca inicial se houver valor no campo
                if (searchInput.value.trim()) {
                    performSearch();
                }
            }
            
            // Atualizar limite quando mudado
            if (itemsPerPageSelect) {
                currentLimit = parseInt(itemsPerPageSelect.value) || 10;
            }
        }
        
        // Realizar busca AJAX
        function performSearch(page = 1) {
            const searchInput = document.getElementById('search-input');
            const searchTerm = searchInput ? searchInput.value.trim() : '';
            const itemsPerPageSelect = document.getElementById('items-per-page');
            const limit = itemsPerPageSelect ? parseInt(itemsPerPageSelect.value) : currentLimit;
            
            // Mostrar loading
            showLoading();
            
            fetch('ajax_search.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    search: searchTerm,
                    page: page,
                    limit: limit
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateTable(data.colaboradores);
                    updatePagination(data.pagination, searchTerm);
                } else {
                    console.error('Erro na busca:', data.error);
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
            })
            .finally(() => {
                hideLoading();
            });
        }
        
        // Mostrar loading
        function showLoading() {
            const tableBody = document.querySelector('tbody');
            if (tableBody) {
                tableBody.innerHTML = '<tr><td colspan="12" class="text-center"><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</td></tr>';
            }
        }
        
        // Esconder loading
        function hideLoading() {
            // O loading será substituído pelos resultados
        }
        
        // Atualizar tabela com resultados
        function updateTable(colaboradores) {
            const tableBody = document.querySelector('tbody');
            if (!tableBody) return;
            
            if (colaboradores.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <div class="empty">
                                <div class="empty-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <circle cx="12" cy="7" r="4"/>
                                        <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                    </svg>
                                </div>
                                <p class="empty-title">Nenhum colaborador encontrado</p>
                                <p class="empty-subtitle text-muted">
                                    Tente ajustar sua pesquisa ou adicionar um novo colaborador.
                                </p>
                            </div>
                        </td>
                    </tr>`;
                return;
            }
            
            let html = '';
            colaboradores.forEach(colaborador => {
                const fotoUrl = colaborador.foto ? 
                    `uploads/${colaborador.foto}` : 
                    `https://ui-avatars.com/api/?name=${encodeURIComponent(colaborador.nome || 'User')}&background=random`;
                
                const statusClass = colaborador.ativo == 1 ? 'status-active' : 'status-inactive';
                const statusText = colaborador.ativo == 1 ? 'Ativo' : 'Inativo';
                
                html += `
                    <tr>
                        <td>
                            <input class="form-check-input m-0 align-middle row-checkbox" type="checkbox" value="${colaborador.id}">
                        </td>
                        <td>
                            <span class="avatar avatar-md" style="background-image: url(${fotoUrl})"></span>
                        </td>
                        <td>
                            <div class="d-flex py-1 align-items-center">
                                <div class="flex-fill">
                                    <div class="font-weight-medium">${colaborador.nome || ''}</div>
                                    <div class="text-muted">${colaborador.email || ''}</div>
                                </div>
                            </div>
                        </td>
                        <td>${colaborador.cargo || ''}</td>
                        <td>${colaborador.departamento || ''}</td>
                        <td>${colaborador.empresa || ''}</td>
                        <td>
                            <div>${colaborador.telefone || ''}</div>
                            <div class="text-muted">Ramal: ${colaborador.ramal || ''}</div>
                        </td>
                        <td>
                            <span class="status-dot ${statusClass}"></span>
                            ${statusText}
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <button class="btn btn-sm btn-outline-primary" onclick="editColaborador(${colaborador.id})">
                                    Editar
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteColaborador(${colaborador.id})">
                                    Excluir
                                </button>
                            </div>
                        </td>
                    </tr>`;
            });
            
            tableBody.innerHTML = html;
        }
        
        // Atualizar paginação
        function updatePagination(pagination, searchTerm) {
            const paginationContainer = document.querySelector('.pagination');
            const recordsInfo = document.querySelector('.card-footer p');
            
            if (recordsInfo) {
                const start = Math.min(pagination.offset + 1, pagination.total_records);
                const end = Math.min(pagination.offset + pagination.limit, pagination.total_records);
                recordsInfo.innerHTML = `Mostrando <span>${start}</span> a <span>${end}</span> de <span>${pagination.total_records}</span> registros`;
            }
            
            if (!paginationContainer) return;
            
            let html = '';
            
            // Botão anterior
            if (pagination.current_page > 1) {
                html += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="performSearch(${pagination.current_page - 1}); return false;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <polyline points="15,6 9,12 15,18"/>
                            </svg>
                            anterior
                        </a>
                    </li>
                `;
            }
            
            // Páginas numeradas
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                const activeClass = i === pagination.current_page ? 'active' : '';
                html += `
                    <li class="page-item ${activeClass}">
                        <a class="page-link" href="#" onclick="performSearch(${i}); return false;">${i}</a>
                    </li>
                `;
            }
            
            // Botão próxima
            if (pagination.current_page < pagination.total_pages) {
                html += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="performSearch(${pagination.current_page + 1}); return false;">
                            próxima
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <polyline points="9,6 15,12 9,18"/>
                            </svg>
                        </a>
                    </li>
                `;
            }
            
            paginationContainer.innerHTML = html;
        }
    </script>
</body>
</html>