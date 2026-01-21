<?php
// Gestão de Menu
// Feeds
// Reports
// Gestão de Usuários
// Documentos RH
// Documentos Liderança
// Acessos
// Super Administrador
$requiredAccess = 'Reports';
require_once 'check_access.php';  // esse arquivo fará a verificação e encerra se não tiver acesso

$pageTitle = 'Reports de Instabilidade';

// Incluir o layout do admin
ob_start();

// Consulta os reports de instabilidade, ordenando pela data de relatório (mais recentes primeiro)
$sql = "SELECT * FROM incidents_reports ORDER BY report_date DESC";
$stmt = $pdo->query($sql);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- Page header -->
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">
          Reports de Instabilidade
        </h2>
      </div>
      <!-- Page title actions -->
      <div class="col-auto ms-auto d-print-none">
        <!-- Exemplo: botão para limpar reports, se desejar -->
        <!-- <a href="limpar_reports.php" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja limpar todos os reports?');">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="m7 7l10 10m0 -10l-10 10" /></svg>
          Limpar Reports
        </a> -->
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
            <h3 class="card-title">Lista de Reports</h3>
          </div>
          <div class="table-responsive">
            <table id="tabelaReports" class="table table-vcenter card-table">
              <thead>
                <tr>
                  <th class="text-center">ID</th>
                  <th>Reportado Por</th>
                  <th>Local</th>
                  <th>Tipo de Problema</th>
                  <th>Nível</th>
                  <th>Descrição</th>
                  <th>Data/Hora</th>
                  <th class="text-center">Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($reports as $report): ?>
                <tr>
                  <td class="text-center text-secondary"><?php echo $report['id']; ?></td>
                  <td>
                    <div class="d-flex py-1 align-items-center">
                      <span class="avatar me-2" style="background-image: url(https://ui-avatars.com/api/?name=<?php echo urlencode($report['reported_by']); ?>&background=random)"></span>
                      <div class="flex-fill">
                        <div class="font-weight-medium"><?php echo htmlspecialchars($report['reported_by']); ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($report['location']); ?></td>
                  <td>
                    <span class="badge bg-blue-lt"><?php echo htmlspecialchars($report['type_of_issue']); ?></span>
                  </td>
                  <td>
                    <?php 
                    $severityClass = 'bg-secondary';
                    switch(strtolower($report['severity_level'])) {
                      case 'baixo':
                      case 'low':
                        $severityClass = 'bg-green';
                        break;
                      case 'médio':
                      case 'medium':
                        $severityClass = 'bg-yellow';
                        break;
                      case 'alto':
                      case 'high':
                        $severityClass = 'bg-red';
                        break;
                    }
                    ?>
                    <span class="badge <?php echo $severityClass; ?>"><?php echo htmlspecialchars($report['severity_level']); ?></span>
                  </td>
                  <td class="text-wrap" style="max-width: 200px;"><?php echo htmlspecialchars($report['description']); ?></td>
                  <td class="text-secondary"><?php echo date('d/m/Y H:i', strtotime($report['report_date'])); ?></td>
                  <td class="text-center">
                    <a href="report_excluir.php?id=<?php echo $report['id']; ?>" 
                       class="btn btn-sm btn-outline-danger" 
                       title="Excluir Report"
                       onclick="return confirm('Tem certeza que deseja excluir este report?');">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="m4 7l16 0" /><path d="m10 11l0 6" /><path d="m14 11l0 6" /><path d="m5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="m9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($reports) === 0): ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">
                    <div class="empty">
                      <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /></svg>
                      </div>
                      <p class="empty-title">Nenhum report encontrado</p>
                      <p class="empty-subtitle text-muted">Não há reports de instabilidade registrados no momento.</p>
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
  $('#tabelaReports').DataTable({
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
    },
    pageLength: 10,
    lengthMenu: [ [10, 25, 50, -1], [10, 25, 50, 'Todos'] ],
    order: [[ 0, 'desc' ]],
    columnDefs: [
      { orderable: false, targets: [7] }
    ]
  });
});
";

$content = ob_get_clean();
require_once 'admin_layout.php';
?>
