<?php
// users_admin.php — agora usando o mesmo layout/menubar de links_admin.php
$requiredAccess = 'Gestão de Usuários';
require_once 'check_access.php';
require_once __DIR__ . '/../utils/users_schema.php';
ensureVerificationColumns($pdo);

// Processa ações de criação/edição neste próprio arquivo (POST)
$toastMsg = '';
$toastType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            if ($username === '' || $password === '') {
                throw new Exception('Informe usuário e senha.');
            }
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmtC = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:u, :e, :p)");
            $stmtC->execute([':u' => $username, ':e' => $email, ':p' => $hashed]);
            $toastMsg = 'Usuário criado com sucesso!';
            $toastType = 'success';
        } elseif ($action === 'update') {
            $id       = (int)($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            if ($id <= 0 || $username === '') {
                throw new Exception('Dados inválidos para atualização.');
            }
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmtU = $pdo->prepare("UPDATE users SET username = :u, email = :e, password = :p WHERE id = :id");
                $stmtU->execute([':u' => $username, ':e' => $email, ':p' => $hashed, ':id' => $id]);
            } else {
                $stmtU = $pdo->prepare("UPDATE users SET username = :u, email = :e WHERE id = :id");
                $stmtU->execute([':u' => $username, ':e' => $email, ':id' => $id]);
            }
            $toastMsg = 'Usuário atualizado com sucesso!';
            $toastType = 'success';
        } elseif ($action === 'approve') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID inválido para aprovação.');
            }
            // Garante que só aprova se e-mail estiver verificado
            $stmtCheck = $pdo->prepare('SELECT email_verified, approved FROM users WHERE id = ? LIMIT 1');
            $stmtCheck->execute([$id]);
            $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                throw new Exception('Usuário não encontrado.');
            }
            if ((int)($row['email_verified'] ?? 0) !== 1) {
                throw new Exception('Usuário ainda não confirmou o e-mail.');
            }
            if ((int)($row['approved'] ?? 0) === 1) {
                $toastMsg = 'Usuário já estava aprovado.';
                $toastType = 'success';
            } else {
                $stmtA = $pdo->prepare('UPDATE users SET approved = 1 WHERE id = ?');
                $stmtA->execute([$id]);
                $toastMsg = 'Usuário aprovado.';
                $toastType = 'success';
            }
        }
    } catch (Throwable $e) {
        $toastMsg = 'Erro: ' . $e->getMessage();
        $toastType = 'danger';
    }
}

// Consulta todos os usuários
$stmt = $pdo->query("SELECT * FROM users ORDER BY username ASC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Métricas
$totalUsuarios = count($usuarios);
$usuariosComEmail = 0;
foreach ($usuarios as $u) { if (!empty($u['email'])) { $usuariosComEmail++; } }
$usuariosSemEmail = $totalUsuarios - $usuariosComEmail;

ob_start();
?>

<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <div class="page-pretitle">Administração</div>
        <h2 class="page-title d-flex align-items-center">
          <i class="ti ti-users me-2"></i>
          Usuários
        </h2>
      </div>
      <div class="col-auto ms-auto d-print-none">
        <div class="btn-list">
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoUsuario">
            <i class="ti ti-user-plus me-1"></i>
            Novo Usuário
          </button>
          <a href="accesses_admin.php" class="btn btn-outline-primary">
            <i class="ti ti-lock-access me-1"></i>
            Acessos
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="row row-deck row-cards mb-3">
      <div class="col-sm-6 col-lg-4">
        <div class="card card-sm">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-auto"><span class="badge bg-primary p-2"><i class="ti ti-users"></i></span></div>
              <div class="col"><div class="fw-bold">Usuários</div><div class="text-muted small">Total cadastrados</div></div>
              <div class="col-auto"><div class="h2 mb-0"><?= $totalUsuarios ?></div></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-4">
        <div class="card card-sm"><div class="card-body">
          <div class="row align-items-center">
            <div class="col-auto"><span class="badge bg-success p-2"><i class="ti ti-mail"></i></span></div>
            <div class="col"><div class="fw-bold">Com e-mail</div><div class="text-muted small">Usuários com e-mail</div></div>
            <div class="col-auto"><div class="h2 mb-0"><?= $usuariosComEmail ?></div></div>
          </div>
        </div></div>
      </div>
      <div class="col-sm-6 col-lg-4">
        <div class="card card-sm"><div class="card-body">
          <div class="row align-items-center">
            <div class="col-auto"><span class="badge bg-secondary p-2"><i class="ti ti-mail-off"></i></span></div>
            <div class="col"><div class="fw-bold">Sem e-mail</div><div class="text-muted small">Usuários sem e-mail</div></div>
            <div class="col-auto"><div class="h2 mb-0"><?= $usuariosSemEmail ?></div></div>
          </div>
        </div></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header align-items-center">
        <h3 class="card-title d-flex align-items-center">
          <i class="ti ti-list-details me-2"></i>
          Lista de Usuários
        </h3>
        <div class="ms-auto d-flex flex-column">
          <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" id="quickFilter" class="form-control" placeholder="Filtrar por nome, username ou e-mail">
          </div>
          <div id="usersLengthContainer" class="mt-2"></div>
        </div>
      </div>
      <div class="table-responsive">
        <table id="tabelaUsuarios" class="table table-vcenter card-table">
          <thead>
            <tr>
              <th>Usuário</th>
              <th>Username</th>
              <th>Email</th>
              <th>Verificado</th>
              <th>Aprovado</th>
              <th>Senha</th>
              <th class="w-1">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $usr): ?>
            <?php
              $nome = $usr['username'];
              $initials = strtoupper(substr($nome, 0, 1));
              $colors = ['#206bc4','#2fb344','#d63939','#fab005','#17a2b8','#5e60ce'];
              $color = $colors[$usr['id'] % count($colors)];
            ?>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <span class="avatar me-2" style="background-color: <?= $color ?>; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600; color:#fff;">
                    <?= $initials ?>
                  </span>
                  <div>
                    <div class="fw-bold"><?= htmlspecialchars($usr['username']) ?></div>
                    <div class="text-muted small">ID #<?= (int)$usr['id'] ?></div>
                  </div>
                </div>
              </td>
              <td class="text-muted"><?= htmlspecialchars($usr['username']) ?></td>
              <td>
                <?php if (!empty($usr['email'])): ?>
                  <a href="mailto:<?= htmlspecialchars($usr['email']) ?>" class="text-reset">
                    <?= htmlspecialchars($usr['email']) ?>
                  </a>
                <?php else: ?>
                  <span class="text-secondary">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ((int)($usr['email_verified'] ?? 0) === 1): ?>
                  <span class="badge bg-success">Sim</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Não</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ((int)($usr['approved'] ?? 0) === 1): ?>
                  <span class="badge bg-success">Sim</span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark">Pendente</span>
                <?php endif; ?>
              </td>
              <td class="text-muted"><span class="badge bg-secondary">Protegida</span></td>
              <td>
                <div class="btn-list flex-nowrap">
                  <button type="button" class="btn btn-white btn-sm btn-edit"
                          title="Editar usuário"
                          data-id="<?= (int)$usr['id'] ?>"
                          data-username="<?= htmlspecialchars($usr['username']) ?>"
                          data-email="<?= htmlspecialchars($usr['email'] ?? '') ?>"
                          data-bs-toggle="modal" data-bs-target="#modalEditarUsuario">
                    <i class="ti ti-edit"></i>
                  </button>
                  <a href="user_excluir.php?id=<?= (int)$usr['id'] ?>" class="btn btn-white btn-sm text-danger" title="Excluir usuário" onclick="return confirm('Excluir este usuário?');">
                    <i class="ti ti-trash"></i>
                  </a>
                  <?php if ((int)($usr['approved'] ?? 0) !== 1): ?>
                    <?php if ((int)($usr['email_verified'] ?? 0) === 1): ?>
                      <form method="post" action="" class="d-inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" value="<?= (int)$usr['id'] ?>">
                        <button type="submit" class="btn btn-white btn-sm text-success" title="Aprovar">
                          <i class="ti ti-checks"></i>
                        </button>
                      </form>
                    <?php else: ?>
                      <button type="button" class="btn btn-white btn-sm" title="Aguardando verificação de e-mail" disabled>
                        <i class="ti ti-mail-question"></i>
                      </button>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (!empty($toastMsg)): ?>
      <div class="alert alert-<?= $toastType ?> mt-3" role="alert">
        <?= htmlspecialchars($toastMsg) ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal: Novo Usuário -->
<div class="modal fade" id="modalNovoUsuario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <form class="modal-content" method="post">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-user-plus me-2"></i>Novo Usuário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="mb-3">
          <label class="form-label required">Usuário</label>
          <input class="form-control" name="username" required placeholder="ex: jdoe">
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" name="email" placeholder="ex: jdoe@empresa.com">
        </div>
        <div class="mb-3">
          <label class="form-label required">Senha</label>
          <input type="password" class="form-control" name="password" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Salvar</button>
        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Editar Usuário -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <form class="modal-content" method="post">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-edit me-2"></i>Editar Usuário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="">
        <div class="mb-3">
          <label class="form-label required">Usuário</label>
          <input class="form-control" name="username" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" name="email">
        </div>
        <div class="mb-3">
          <label class="form-label">Nova Senha</label>
          <input type="password" class="form-control" name="password">
          <small class="form-hint">Deixe em branco para manter a senha atual.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Salvar</button>
        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Inicializa DataTable com paginação e limitador iniciando em 10
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
      const dt = $('#tabelaUsuarios').DataTable({
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
        },
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
        order: [],
        ordering: false,
        responsive: true,
        // remove o filtro padrão (f) para manter apenas o campo de cima
        dom: '<"row"<"col-sm-12 col-md-6"l>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
      });

      // Move o seletor de quantidade para abaixo do campo de pesquisa
      const dtWrapper = $('#tabelaUsuarios_wrapper');
      const lengthEl = dtWrapper.find('.dataTables_length');
      const lengthTarget = document.getElementById('usersLengthContainer');
      if (lengthEl.length && lengthTarget) {
        lengthTarget.innerHTML = '';
        lengthTarget.appendChild(lengthEl[0]);
        // Ajustes visuais
        lengthEl.addClass('d-inline-block');
        lengthEl.find('label').addClass('mb-0 text-muted');
        lengthEl.find('select').addClass('form-select form-select-sm ms-2');
        // Remove a primeira linha gerada pelo DataTables (fica vazia após mover)
        const firstRow = dtWrapper.children('.row').first();
        if (firstRow.find('.dataTables_length').length === 0) {
          firstRow.remove();
        }
      }

      // Filtro rápido usando o search do DataTables
      const filterInput = document.getElementById('quickFilter');
      filterInput?.addEventListener('input', function() {
        dt.search(this.value).draw();
      });
    }

    // Preenche modal de edição
    const editModal = document.getElementById('modalEditarUsuario');
    editModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const id = button.getAttribute('data-id');
      const username = button.getAttribute('data-username');
      const email = button.getAttribute('data-email');
      editModal.querySelector('input[name="id"]').value = id;
      editModal.querySelector('input[name="username"]').value = username || '';
      editModal.querySelector('input[name="email"]').value = email || '';
      editModal.querySelector('input[name="password"]').value = '';
    });
  });
</script>

<?php
$content = ob_get_clean();
require_once 'admin_layout.php';
