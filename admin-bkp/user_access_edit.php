<?php
// admin/user_access_edit.php
// Gestão de Menu
// Feeds
// Reports
// Gestão de Usuários
// Documentos RH
// Documentos Liderança
// Acessos
// Super Administrador
$requiredAccess = 'Acessos';
include 'check_access.php';  // esse arquivo fará a verificação e encerra se não tiver acesso
include 'menu.php'; // Caso queira manter o menu superior do seu painel

if (!isset($_GET['id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ID do usuário não especificado.</div></div>";
    exit;
}
$user_id = $_GET['id'];

// Recupera dados do usuário
$sql = "SELECT * FROM users WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Usuário não encontrado.</div></div>";
    exit;
}

// Recupera todos os acessos disponíveis
$sqlAll = "SELECT * FROM accesses ORDER BY access_name ASC";
$stmtAll = $pdo->query($sqlAll);
$all_accesses = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

// Recupera os acessos atuais do usuário
$sqlUserAccess = "SELECT access_id FROM user_access WHERE user_id = :user_id";
$stmtUserAccess = $pdo->prepare($sqlUserAccess);
$stmtUserAccess->execute([':user_id' => $user_id]);
$current_accesses = $stmtUserAccess->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Remove os acessos atuais do usuário
    $sqlDelete = "DELETE FROM user_access WHERE user_id = :user_id";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute([':user_id' => $user_id]);

    // Insere os novos acessos (se houver)
    if (isset($_POST['accesses']) && is_array($_POST['accesses'])) {
        $sqlInsert = "INSERT INTO user_access (user_id, access_id) VALUES (:user_id, :access_id)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        foreach ($_POST['accesses'] as $access_id) {
            $stmtInsert->execute([':user_id' => $user_id, ':access_id' => $access_id]);
        }
    }
    header("Location: accesses_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Acessos - <?php echo htmlspecialchars($user['username']); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .container { margin-top: 40px; max-width: 600px; }
    .page-title { text-align: center; margin-bottom: 30px; font-weight: bold; }
  </style>
</head>
<body>
<div class="container">
  <h1 class="page-title">Editar Acessos para <?php echo htmlspecialchars($user['username']); ?></h1>
  <form method="POST">
    <div class="mb-3">
      <?php foreach ($all_accesses as $access): 
          $checked = in_array($access['id'], $current_accesses) ? 'checked' : '';
      ?>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="accesses[]" value="<?php echo $access['id']; ?>" id="access_<?php echo $access['id']; ?>" <?php echo $checked; ?>>
          <label class="form-check-label" for="access_<?php echo $access['id']; ?>">
            <?php echo htmlspecialchars($access['access_name']); ?>
            <small>(<?php echo htmlspecialchars($access['description']); ?>)</small>
          </label>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-success">Salvar Alterações</button>
    <a href="accesses_admin.php" class="btn btn-secondary">Voltar</a>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
