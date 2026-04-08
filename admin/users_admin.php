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
            // Força a aprovação MESMO sem confirmar e-mail e converte email_verified para 1 autometicamente
            $stmtCheck = $pdo->prepare('SELECT email_verified, approved FROM users WHERE id = ? LIMIT 1');
            $stmtCheck->execute([$id]);
            $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                throw new Exception('Usuário não encontrado.');
            }
            if ((int)($row['approved'] ?? 0) === 1 && (int)($row['email_verified'] ?? 0) === 1) {
                $toastMsg = 'Usuário já estava aprovado e verificado.';
                $toastType = 'success';
            } else {
                $stmtA = $pdo->prepare('UPDATE users SET approved = 1, email_verified = 1 WHERE id = ?');
                $stmtA->execute([$id]);
                
                // Marcar notificações relacionadas como lidas
                try {
                    $stmtClear = $pdo->prepare("UPDATE persistent_notifications SET is_read = 1 WHERE user_id = ? AND type = 'registration_approval'");
                    $stmtClear->execute([$id]);
                } catch (Exception $eClear) { }

                $toastMsg = 'Usuário aprovado verificado com sucesso.';
                $toastType = 'success';
            }
        } elseif ($action === 'resend_verification') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID inválido.');
            $stmtV = $pdo->prepare('SELECT username, nome, email, email_verified, verification_token FROM users WHERE id = :id LIMIT 1');
            $stmtV->execute([':id' => $id]);
            $usr = $stmtV->fetch(PDO::FETCH_ASSOC);
            if (!$usr) throw new Exception('Usuário não encontrado.');
            if (empty($usr['email'])) throw new Exception('Usuário não possui e-mail cadastrado.');
            if ((int)$usr['email_verified'] === 1) throw new Exception('E-mail já verificado.');
            
            require_once __DIR__ . '/../utils/smtp_mail.php';
            
            // Gera um novo token
            $token = bin2hex(random_bytes(16));
            $pdo->prepare('UPDATE users SET verification_token = :t WHERE id = :id')->execute([':t' => $token, ':id' => $id]);
            
            $verifyLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/portal/verify_email.php?token=" . urlencode($token) . "&u=" . $id;
            
            if (sendVerificationEmail($usr['email'], $usr['nome'] ?: $usr['username'], $verifyLink)) {
                $toastMsg = 'E-mail de confirmação reenviado para ' . $usr['email'];
                $toastType = 'success';
            } else {
                throw new Exception('Falha ao enviar e-mail. Verifique o log do SMTP.');
            }
        } elseif ($action === 'send_password_reset') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID inválido.');
            $stmtP = $pdo->prepare('SELECT username, nome, email FROM users WHERE id = :id LIMIT 1');
            $stmtP->execute([':id' => $id]);
            $usr = $stmtP->fetch(PDO::FETCH_ASSOC);
            if (!$usr) throw new Exception('Usuário não encontrado.');
            if (empty($usr['email'])) throw new Exception('E-mail não cadastrado para enviar senha.');
            
            require_once __DIR__ . '/../utils/smtp_mail.php';
            
            // Gera senha forte de 8 caracters
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
            $novaSenha = substr(str_shuffle($chars), 0, 8);
            $hashed = password_hash($novaSenha, PASSWORD_DEFAULT);
            
            $pdo->prepare('UPDATE users SET password = :p WHERE id = :id')->execute([':p' => $hashed, ':id' => $id]);
            
            if (sendPasswordResetEmail($usr['email'], $usr['nome'] ?: $usr['username'], $novaSenha)) {
                $toastMsg = 'Nova senha gerada e enviada por e-mail para o usuário!';
                $toastType = 'success';
            } else {
                throw new Exception('Banco atualizado mas e-mail falhou. Nova senha: ' . $novaSenha);
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
                <div class="dropdown">
                  <button class="btn btn-white btn-sm dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                    <i class="ti ti-settings me-1"></i> Ações
                  </button>
                  <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="#" data-id="<?= (int)$usr['id'] ?>" data-username="<?= htmlspecialchars($usr['username']) ?>" data-email="<?= htmlspecialchars($usr['email'] ?? '') ?>" data-bs-toggle="modal" data-bs-target="#modalEditarUsuario">
                      <i class="ti ti-edit me-2"></i> Editar Dados Básicos
                    </a>
                    
                    <div class="dropdown-divider"></div>
                    
                    <?php if ((int)($usr['approved'] ?? 0) !== 1 || (int)($usr['email_verified'] ?? 0) !== 1): ?>
                        <form method="post" action="" style="display:inline;" onsubmit="return confirm('Deseja aprovar e verificar o acesso deste usuário?');">
                          <input type="hidden" name="action" value="approve">
                          <input type="hidden" name="id" value="<?= (int)$usr['id'] ?>">
                          <button type="submit" class="dropdown-item text-success">
                            <i class="ti ti-checks me-2"></i> Aprovar e Verificar Imediatamente
                          </button>
                        </form>
                    <?php endif; ?>

                    <?php if (!empty($usr['email'])): ?>
                        <?php if ((int)($usr['email_verified'] ?? 0) !== 1): ?>
                            <form method="post" action="" style="display:inline;">
                              <input type="hidden" name="action" value="resend_verification">
                              <input type="hidden" name="id" value="<?= (int)$usr['id'] ?>">
                              <button type="submit" class="dropdown-item text-primary" title="Enviar novamente o e-mail de confirmação para acesso do usuário">
                                <i class="ti ti-mail-forward me-2"></i> Reenviar Confirmação de E-mail
                              </button>
                            </form>
                        <?php endif; ?>
                        
                        <form method="post" action="" style="display:inline;" onsubmit="return confirm('ATENÇÃO: Isso irá alterar a senha do usuário e enviar uma nova chave por e-mail para ele. Continuar?');">
                          <input type="hidden" name="action" value="send_password_reset">
                          <input type="hidden" name="id" value="<?= (int)$usr['id'] ?>">
                          <button type="submit" class="dropdown-item text-orange">
                            <i class="ti ti-key me-2"></i> Gerar e Enviar Nova Senha
                          </button>
                        </form>
                    <?php endif; ?>

                    <div class="dropdown-divider"></div>
                    
                    <a class="dropdown-item text-danger" href="user_excluir.php?id=<?= (int)$usr['id'] ?>" onclick="return confirm('Tem absoluta certeza que deseja excluir esse usuário?');">
                      <i class="ti ti-trash me-2"></i> Excluir Usuário
                    </a>
                  </div>
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
