<?php
/* admin/rh_document_edit.php - Editar Documento RH */
$requiredAccesses = ['Documentos RH'];
require_once 'check_access.php';

// Verifica se o ID foi passado
if (!isset($_GET['id'])) {
    header("Location: rh_documents_admin.php");
    exit;
}

$id = $_GET['id'];

// Recupera os dados do documento
$sql = "SELECT * FROM rh_documents WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    header("Location: rh_documents_admin.php");
    exit;
}

$upload_dir = "../uploads_rh/"; // Diretório de uploads (na raiz)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $leadership_only = isset($_POST['leadership_only']) ? 1 : 0;
    // Mantém o nome do arquivo atual
    $file_name = $document['file_path'];

    // Se um novo arquivo for enviado, gera nome único, move o arquivo e exclui o antigo
    if (!empty($_FILES['document']['name'])) {
        $ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('doc_', true) . '.' . $ext;
        $target_path = $upload_dir . $newFileName;
        if (move_uploaded_file($_FILES['document']['tmp_name'], $target_path)) {
            // Remove o arquivo antigo, se existir
            if (!empty($document['file_path']) && file_exists($upload_dir . $document['file_path'])) {
                unlink($upload_dir . $document['file_path']);
            }
            $file_name = $newFileName;
            $success = "Arquivo substituído com sucesso!";
        } else {
            $error = "Erro no upload do novo arquivo.";
        }
    }

    if (!isset($error)) {
        // Atualiza o registro no banco
        $sqlUpdate = "UPDATE rh_documents 
                      SET title = :title, description = :description, file_path = :file_path, leadership_only = :leadership_only 
                      WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
          ':title' => $title,
          ':description' => $description,
          ':file_path' => $file_name,
          ':leadership_only' => $leadership_only,
          ':id' => $id
        ]);

        if (!isset($success)) {
            $success = "Documento atualizado com sucesso!";
        }
        
        // Recarrega os dados atualizados
        $stmt->execute();
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Função para determinar o tipo de arquivo e ícone
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'pdf':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-red" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><line x1="9" y1="9" x2="10" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>';
        case 'doc':
        case 'docx':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-blue" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><line x1="9" y1="9" x2="10" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>';
        case 'xls':
        case 'xlsx':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-green" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><line x1="9" y1="9" x2="10" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'webp':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-purple" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15l-5 -5L5 21"/></svg>';
        default:
            return '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>';
    }
}

function isImage($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}

$pageTitle = 'Editar Documento RH';
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

        <?php if (isset($success)): ?>
          <div class="alert alert-success alert-dismissible" role="alert">
            <div class="d-flex">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <path d="M5 12l5 5l10 -10"/>
                </svg>
              </div>
              <div>
                <?= e($success) ?>
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
                <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/>
                <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/>
                <path d="M16 5l3 3"/>
              </svg>
              Editar Documento
            </h3>
          </div>
          <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
              <div class="row">
                <div class="col-lg-8">
                  <div class="mb-3">
                    <label class="form-label">Título do Documento</label>
                    <input type="text" name="title" class="form-control" placeholder="Digite o título do documento" value="<?= e($document['title']) ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Descreva o conteúdo do documento"><?= e($document['description']) ?></textarea>
                  </div>
                  <div class="mb-3">
                    <label class="form-check">
                      <input type="checkbox" name="leadership_only" class="form-check-input" <?= ($document['leadership_only'] == 1) ? 'checked' : '' ?>>
                      <span class="form-check-label">Exclusivo para Liderança</span>
                    </label>
                  </div>
                </div>
                <div class="col-lg-4">
                  <div class="mb-3">
                    <label class="form-label">Arquivo Atual</label>
                    <?php if ($document['file_path']): ?>
                      <div class="card card-sm">
                        <div class="card-body d-flex align-items-center">
                          <span class="avatar me-3">
                            <?= getFileIcon($document['file_path']) ?>
                          </span>
                          <div class="flex-fill">
                            <div class="font-weight-medium"><?= e(pathinfo($document['file_path'], PATHINFO_FILENAME)) ?></div>
                            <div class="text-muted"><?= strtoupper(pathinfo($document['file_path'], PATHINFO_EXTENSION)) ?></div>
                          </div>
                          <div class="ms-auto">
                            <a href="<?= "../uploads_rh/" . e($document['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                              <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                                <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                              </svg>
                            </a>
                          </div>
                        </div>
                      </div>
                      
                      <?php if (isImage($document['file_path'])): ?>
                        <div class="mt-2">
                          <img src="<?= "../uploads_rh/" . e($document['file_path']) ?>" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 150px;">
                        </div>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Substituir Arquivo <span class="text-muted">(opcional)</span></label>
                    <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,image/*">
                    <div class="form-hint">
                      Deixe em branco para manter o arquivo atual. Formatos aceitos: PDF, DOC, DOCX, XLS, XLSX, imagens
                    </div>
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
                    Salvar Alterações
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
