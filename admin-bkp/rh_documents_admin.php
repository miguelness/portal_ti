<?php
// admin/rh_documents_admin.php
// Gestão de Menu
// Feeds
// Reports
// Gestão de Usuários
// Documentos RH
// Documentos Liderança
// Acessos
// Super Administrador
$requiredAccess = 'Documentos RH';
require_once 'check_access.php';  // esse arquivo fará a verificação e encerra se não tiver acesso

$pageTitle = 'Gerenciar Documentos RH';

// Incluir o layout do admin
ob_start();

// Supondo que o ID do usuário esteja armazenado na sessão (ex.: $_SESSION['user_id'])
$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    // Redireciona para login se não houver usuário logado
    header('Location: index.php');
    exit;
}

// Verifica se o usuário possui o acesso "Documentos RH"
$sql = "SELECT COUNT(*) FROM user_access ua
        INNER JOIN accesses a ON ua.access_id = a.id
        WHERE ua.user_id = :user_id AND a.access_name = 'Documentos RH'";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$temAcesso = ($stmt->fetchColumn() > 0);

if (!$temAcesso) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Você não tem permissão para acessar essa área.</div></div>";
    exit;
}

// Consulta todos os documentos, ordenando pela data de upload (mais recentes primeiro)
$sql = "SELECT * FROM rh_documents ORDER BY upload_date DESC";
$stmt = $pdo->query($sql);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page header -->
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">
          Gerenciar Documentos RH
        </h2>
      </div>
      <!-- Page title actions -->
      <div class="col-auto ms-auto d-print-none">
        <div class="btn-list">
          <a href="rh_document_add.php" class="btn btn-primary d-none d-sm-inline-block">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="m12 5l0 14" /><path d="m5 12l14 0" /></svg>
            Novo Documento
          </a>
          <a href="rh_document_add.php" class="btn btn-primary d-sm-none btn-icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="m12 5l0 14" /><path d="m5 12l14 0" /></svg>
          </a>
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
            <h3 class="card-title">Lista de Documentos</h3>
          </div>
          <div class="table-responsive">
            <table id="tabelaDocuments" class="table table-vcenter card-table">
              <thead>
                <tr>
                  <th class="text-center" style="width:5%;">ID</th>
                  <th>Título</th>
                  <th>Descrição</th>
                  <th class="text-center">Tipo</th>
                  <th class="text-center">Upload</th>
                  <th class="text-center" style="width:15%;">Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($documents) > 0): ?>
                  <?php foreach ($documents as $doc): ?>
                    <tr>
                      <td class="text-center text-secondary"><?php echo $doc['id']; ?></td>
                      <td>
                        <div class="d-flex py-1 align-items-center">
                          <span class="avatar me-2" style="background-image: url(https://ui-avatars.com/api/?name=<?php echo urlencode(substr($doc['title'], 0, 2)); ?>&background=random)"></span>
                          <div class="flex-fill">
                            <div class="font-weight-medium"><?php echo htmlspecialchars($doc['title']); ?></div>
                          </div>
                        </div>
                      </td>
                      <td class="text-wrap" style="max-width: 300px;"><?php echo htmlspecialchars($doc['description']); ?></td>
                      <td class="text-center">
                        <?php echo ($doc['leadership_only'] == 1)
                            ? '<span class="badge bg-warning">Liderança</span>'
                            : '<span class="badge bg-blue">RH</span>'; ?>
                      </td>
                      <td class="text-center text-secondary"><?php echo date('d/m/Y H:i', strtotime($doc['upload_date'])); ?></td>
                      <td class="text-center">
                        <div class="btn-list flex-nowrap">
                          <a href="rh_document_edit.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="m7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="m20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="m16 5l3 3" /></svg>
                          </a>
                          <a href="rh_document_delete.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-danger" title="Excluir"
                             onclick="return confirm('Tem certeza que deseja excluir este documento?');">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="m4 7l16 0" /><path d="m10 11l0 6" /><path d="m14 11l0 6" /><path d="m5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="m9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      <div class="empty">
                        <div class="empty-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /></svg>
                        </div>
                        <p class="empty-title">Nenhum documento encontrado</p>
                        <p class="empty-subtitle text-muted">Não há documentos RH cadastrados no momento.</p>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$extraJS = "
$(document).ready(function() {
  $('#tabelaDocuments').DataTable({
    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json' },
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
    order: [[ 0, 'desc' ]],
    columnDefs: [
      { orderable: false, targets: [5] }
    ]
  });
});
";

$content = ob_get_clean();
require_once 'admin_layout.php';
?>
