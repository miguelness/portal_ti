<?php
/* admin/rh_document_add.php - Adicionar Documento RH */
$requiredAccesses = ['Documentos RH'];
require_once 'check_access.php';

// Como estamos na pasta admin e a pasta uploads_rh está na raiz, o diretório de upload é "../uploads_rh/"
$upload_dir = "../uploads_rh/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $leadership_only = isset($_POST['leadership_only']) ? 1 : 0;

    // Processa o arquivo: gera um nome único e guarda somente o nome no BD
    if (!empty($_FILES['document']['name'])) {
        $ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('doc_', true) . '.' . $ext;
        $target_path = $upload_dir . $newFileName;
        
        if (move_uploaded_file($_FILES['document']['tmp_name'], $target_path)) {
            // Armazena somente o nome do arquivo (sem caminho)
            $file_name = $newFileName;
        } else {
            $error = "Erro no upload do arquivo.";
        }
    } else {
        $error = "Selecione um arquivo para upload.";
    }

    if (!isset($error)) {
        $sql = "INSERT INTO rh_documents (title, description, file_path, leadership_only)
                VALUES (:title, :description, :file_path, :leadership_only)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':file_path' => $file_name,  // apenas o nome do arquivo
            ':leadership_only' => $leadership_only
        ]);
        header("Location: rh_documents_admin.php");
        exit;
    }
}

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$pageTitle = 'Adicionar Documento RH';
ob_start();
?>
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <div class="page-pretitle">
          Documentos RH
        </div>
        <h2 class="page-title"><?= $pageTitle ?></h2>
      </div>
      <div class="col-auto ms-auto d-print-none">
        <div class="btn-list">
          <a href="rh_documents_admin.php" class="btn btn-secondary d-none d-sm-inline-block">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M9 11l-4 4l4 4m-4 -4h11a4 4 0 0 0 0 -8h-1"/>
            </svg>
            Voltar
          </a>
          <a href="rh_documents_admin.php" class="btn btn-secondary d-sm-none btn-icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M9 11l-4 4l4 4m-4 -4h11a4 4 0 0 0 0 -8h-1"/>
            </svg>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="row row-deck row-cards">
      <div class="col-12">
        <?php if (isset($error)): ?>
          <div class="alert alert-danger alert-dismissible" role="alert">
            <div class="d-flex">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <circle cx="12" cy="12" r="9"/>
                  <line x1="12" y1="8" x2="12" y2="12"/>
                  <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
              </div>
              <div>
                <?= e($error) ?>
              </div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
          </div>
        <?php endif; ?>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                <line x1="9" y1="9" x2="10" y2="9"/>
                <line x1="9" y1="13" x2="15" y2="13"/>
                <line x1="9" y1="17" x2="15" y2="17"/>
              </svg>
              Novo Documento
            </h3>
          </div>
          <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
              <div class="row">
                <div class="col-lg-8">
                  <div class="mb-3">
                    <label class="form-label">Título do Documento</label>
                    <input type="text" name="title" class="form-control" placeholder="Digite o título do documento" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Descreva o conteúdo do documento"></textarea>
                  </div>
                </div>
                <div class="col-lg-4">
                  <div class="mb-3">
                    <label class="form-label">Arquivo</label>
                    <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,image/*" required>
                    <div class="form-hint">
                      Formatos aceitos: PDF, DOC, DOCX, XLS, XLSX, imagens
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-check">
                      <input type="checkbox" name="leadership_only" class="form-check-input">
                      <span class="form-check-label">Exclusivo para Liderança</span>
                    </label>
                  </div>
                </div>
              </div>
              <div class="card-footer bg-transparent mt-auto">
                <div class="btn-list justify-content-end">
                  <a href="rh_documents_admin.php" class="btn">
                    Cancelar
                  </a>
                  <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                      <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                      <path d="M5 12l5 5l10 -10"/>
                    </svg>
                    Salvar Documento
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
require_once 'admin_layout.php';
?>
