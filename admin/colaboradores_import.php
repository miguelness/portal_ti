<?php
require_once 'config.php';
require_once 'check_access.php';

$pageTitle = 'Importar Colaboradores';

// Função para verificar acessos
if (!function_exists('hasAccess')) {
    function hasAccess($access, $user_accesses) {
        if (in_array('Super Administrador', $user_accesses)) {
            return true;
        }
        return in_array($access, $user_accesses);
    }
}

// Verificar se o usuário tem acesso
$user_id = $_SESSION['user_id'] ?? 0;
$sql = "SELECT a.access_name FROM user_access ua JOIN accesses a ON ua.access_id = a.id WHERE ua.user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$user_accesses = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!hasAccess('Colaboradores', $user_accesses) && !hasAccess('Gestão de Colaboradores', $user_accesses) && !hasAccess('Super Administrador', $user_accesses)) {
    header('Location: index.php');
    exit;
}

$extraCSS = '
<style>
    .upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 0.5rem;
        padding: 3rem 2rem;
        text-align: center;
        transition: all 0.3s ease;
        background: #f8fafc;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    
    .upload-area:hover {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    
    .upload-area.dragover {
        border-color: #3b82f6;
        background: #dbeafe;
        transform: scale(1.02);
    }
    
    .upload-icon {
        font-size: 3rem;
        color: #6b7280;
        margin-bottom: 1rem;
    }
    
    .upload-area:hover .upload-icon {
        color: #3b82f6;
    }
    
    .file-input {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 10;
    }
    
    .preview-table {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
    }
    
    .step-indicator {
        display: flex;
        justify-content: center;
        margin-bottom: 2rem;
    }
    
    .step {
        display: flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        background: #f1f5f9;
        color: #64748b;
        margin: 0 0.5rem;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .step.active {
        background: #3b82f6;
        color: white;
    }
    
    .step.completed {
        background: #10b981;
        color: white;
    }
    
    .step-number {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .progress-bar-custom {
        height: 0.5rem;
        background: #e5e7eb;
        border-radius: 0.25rem;
        overflow: hidden;
        margin: 1rem 0;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
        border-radius: 0.25rem;
        transition: width 0.3s ease;
        width: 0%;
    }
    
    .mapping-container {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin: 1rem 0;
    }
    
    .mapping-row {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        padding: 0.75rem;
        background: white;
        border-radius: 0.375rem;
        border: 1px solid #e5e7eb;
    }
    
    .mapping-label {
        flex: 1;
        font-weight: 500;
        color: #374151;
    }
    
    .mapping-arrow {
        margin: 0 1rem;
        color: #6b7280;
    }
    
    .mapping-select {
        flex: 1;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.5rem;
    }
    
    .btn-import {
        background: linear-gradient(135deg, #10b981, #059669);
        border: none;
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
    }
    
    .btn-import:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        color: white;
    }
    
    .btn-import:disabled {
        background: #9ca3af;
        transform: none;
        box-shadow: none;
        cursor: not-allowed;
    }
    
    .alert-custom {
        border-radius: 0.5rem;
        border: none;
        padding: 1rem 1.5rem;
        margin: 1rem 0;
    }
    
    .alert-success-custom {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        color: #065f46;
        border-left: 4px solid #10b981;
    }
    
    .alert-error-custom {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        color: #991b1b;
        border-left: 4px solid #ef4444;
    }
    
    .alert-warning-custom {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: #92400e;
        border-left: 4px solid #f59e0b;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin: 1rem 0;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1.5rem;
        text-align: center;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #6b7280;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .hidden {
        display: none !important;
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>';

$extraJS = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const fileInput = document.getElementById("excelFile");
    const uploadArea = document.getElementById("uploadArea");
    const step1 = document.getElementById("step1");
    const step2 = document.getElementById("step2");
    const step3 = document.getElementById("step3");
    const progressFill = document.querySelector(".progress-fill");
    
    let currentStep = 1;
    let uploadedData = null;
    
    // Drag and drop functionality
    uploadArea.addEventListener("dragover", function(e) {
        e.preventDefault();
        uploadArea.classList.add("dragover");
    });
    
    uploadArea.addEventListener("dragleave", function(e) {
        e.preventDefault();
        uploadArea.classList.remove("dragover");
    });
    
    uploadArea.addEventListener("drop", function(e) {
        e.preventDefault();
        uploadArea.classList.remove("dragover");
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFileUpload(files[0]);
        }
    });
    
    fileInput.addEventListener("change", function(e) {
        if (e.target.files.length > 0) {
            handleFileUpload(e.target.files[0]);
        }
    });
    
    function handleFileUpload(file) {
        if (!file.name.match(/\.(xlsx|xls)$/)) {
            showAlert("Por favor, selecione um arquivo Excel (.xlsx ou .xls)", "error");
            return;
        }
        
        const formData = new FormData();
        formData.append("excel_file", file);
        formData.append("action", "analyze");
        
        showLoading("Analisando arquivo...");
        
        fetch("colaboradores_import.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                uploadedData = data;
                showStep2(data);
            } else {
                showAlert(data.message || "Erro ao processar arquivo", "error");
            }
        })
        .catch(error => {
            hideLoading();
            showAlert("Erro ao enviar arquivo: " + error.message, "error");
        });
    }
    
    function showStep2(data) {
        updateStep(2);
        step1.classList.add("hidden");
        step2.classList.remove("hidden");
        step2.classList.add("fade-in");
        
        // Mostrar estatísticas
        document.getElementById("totalRows").textContent = data.total_rows;
        document.getElementById("validRows").textContent = data.valid_rows;
        document.getElementById("invalidRows").textContent = data.invalid_rows;
        
        // Mostrar preview dos dados
        const previewTable = document.getElementById("previewTable");
        let tableHTML = `
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nome</th>
                        <th>Empresa</th>
                        <th>Setor</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Ramal</th>
                        <th>Teams</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.preview.forEach(row => {
            const statusClass = row.valid ? "text-success" : "text-danger";
            const statusIcon = row.valid ? "✓" : "✗";
            tableHTML += `
                <tr class="${row.valid ? "" : "table-warning"}">
                    <td>${row.nome || ""}</td>
                    <td>${row.empresa || ""}</td>
                    <td>${row.setor || ""}</td>
                    <td>${row.email || ""}</td>
                    <td>${row.telefone || ""}</td>
                    <td>${row.ramal || ""}</td>
                    <td>${row.teams || ""}</td>
                    <td class="${statusClass}">${statusIcon}</td>
                </tr>
            `;
        });
        
        tableHTML += "</tbody></table>";
        previewTable.innerHTML = tableHTML;
        
        // Mostrar erros se houver
        if (data.errors && data.errors.length > 0) {
            const errorsDiv = document.getElementById("errorsDiv");
            let errorsHTML = "<h6>Erros encontrados:</h6><ul>";
            data.errors.forEach(error => {
                errorsHTML += `<li>${error}</li>`;
            });
            errorsHTML += "</ul>";
            errorsDiv.innerHTML = errorsHTML;
            errorsDiv.classList.remove("hidden");
        }
    }
    
    function confirmImport() {
        if (!uploadedData) {
            showAlert("Nenhum arquivo foi processado", "error");
            return;
        }
        
        showLoading("Importando dados...");
        
        const formData = new FormData();
        formData.append("action", "import");
        formData.append("data", JSON.stringify(uploadedData));
        
        fetch("colaboradores_import.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showStep3(data);
            } else {
                showAlert(data.message || "Erro ao importar dados", "error");
            }
        })
        .catch(error => {
            hideLoading();
            showAlert("Erro na importação: " + error.message, "error");
        });
    }
    
    function showStep3(data) {
        updateStep(3);
        step2.classList.add("hidden");
        step3.classList.remove("hidden");
        step3.classList.add("fade-in");
        
        document.getElementById("importedCount").textContent = data.imported;
        document.getElementById("skippedCount").textContent = data.skipped;
        document.getElementById("errorCount").textContent = data.errors;
    }
    
    function updateStep(stepNumber) {
        currentStep = stepNumber;
        progressFill.style.width = (stepNumber * 33.33) + "%";
        
        // Atualizar indicadores de step
        for (let i = 1; i <= 3; i++) {
            const stepEl = document.querySelector(`.step:nth-child(${i})`);
            stepEl.classList.remove("active", "completed");
            
            if (i < stepNumber) {
                stepEl.classList.add("completed");
            } else if (i === stepNumber) {
                stepEl.classList.add("active");
            }
        }
    }
    
    function showAlert(message, type) {
        const alertDiv = document.createElement("div");
        alertDiv.className = `alert alert-${type}-custom alert-custom`;
        alertDiv.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="ti ti-${type === "success" ? "check" : type === "error" ? "x" : "alert-triangle"} me-2"></i>
                ${message}
            </div>
        `;
        
        const container = document.querySelector(".container-xl");
        container.insertBefore(alertDiv, container.firstChild);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
    
    function showLoading(message) {
        const loadingDiv = document.createElement("div");
        loadingDiv.id = "loadingOverlay";
        loadingDiv.innerHTML = `
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; padding: 2rem; border-radius: 0.5rem; text-align: center;">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <div>${message}</div>
                </div>
            </div>
        `;
        document.body.appendChild(loadingDiv);
    }
    
    function hideLoading() {
        const loadingDiv = document.getElementById("loadingOverlay");
        if (loadingDiv) {
            loadingDiv.remove();
        }
    }
    
    // Expor funções globalmente
    window.confirmImport = confirmImport;
    window.resetImport = function() {
        location.reload();
    };
});
</script>';

ob_start();
?>

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-file-import me-2"></i>
                    Importar Colaboradores
                </h2>
                <div class="text-muted mt-1">Importe dados de colaboradores a partir de planilha Excel</div>
            </div>
            <div class="col-auto">
                <a href="colaboradores.php" class="btn btn-outline-primary">
                    <i class="ti ti-arrow-left me-1"></i>
                    Voltar para Colaboradores
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <!-- Indicador de progresso -->
        <div class="step-indicator">
            <div class="step active">
                <div class="step-number">1</div>
                Selecionar Arquivo
            </div>
            <div class="step">
                <div class="step-number">2</div>
                Visualizar Dados
            </div>
            <div class="step">
                <div class="step-number">3</div>
                Confirmar Importação
            </div>
        </div>
        
        <div class="progress-bar-custom">
            <div class="progress-fill"></div>
        </div>

        <!-- Step 1: Upload do arquivo -->
        <div id="step1" class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-upload me-2"></i>
                    Selecionar Arquivo Excel
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-8">
                        <div id="uploadArea" class="upload-area">
                            <input type="file" id="excelFile" name="excel_file" accept=".xlsx,.xls" class="file-input">
                            <div class="upload-icon">
                                <i class="ti ti-cloud-upload"></i>
                            </div>
                            <h4>Arraste o arquivo aqui ou clique para selecionar</h4>
                            <p class="text-muted">Formatos aceitos: .xlsx, .xls (máximo 10MB)</p>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h4 class="card-title">
                                    <i class="ti ti-info-circle me-2"></i>
                                    Formato Esperado
                                </h4>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">A planilha deve conter as seguintes colunas:</p>
                                <ul class="list-unstyled">
                                    <li><i class="ti ti-check text-success me-2"></i><strong>NOME</strong> (obrigatório)</li>
                                    <li><i class="ti ti-check text-success me-2"></i><strong>EMPRESA</strong> (obrigatório)</li>
                                    <li><i class="ti ti-check text-success me-2"></i><strong>SETOR</strong> (obrigatório)</li>
                                    <li><i class="ti ti-minus text-muted me-2"></i><strong>EMAIL</strong> (opcional)</li>
                                    <li><i class="ti ti-minus text-muted me-2"></i><strong>TELEFONE</strong> (opcional)</li>
                                    <li><i class="ti ti-minus text-muted me-2"></i><strong>RAMAIL</strong> (opcional)</li>
                                    <li><i class="ti ti-minus text-muted me-2"></i><strong>TEAMS</strong> (opcional)</li>
                                </ul>
                                <div class="alert alert-info">
                                    <i class="ti ti-lightbulb me-2"></i>
                                    <strong>Dica:</strong> A primeira linha deve conter os cabeçalhos das colunas.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Preview dos dados -->
        <div id="step2" class="hidden">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="ti ti-eye me-2"></i>
                        Visualização dos Dados
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Estatísticas -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number text-primary" id="totalRows">0</div>
                            <div class="stat-label">Total de Registros</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number text-success" id="validRows">0</div>
                            <div class="stat-label">Registros Válidos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number text-warning" id="invalidRows">0</div>
                            <div class="stat-label">Registros com Problemas</div>
                        </div>
                    </div>

                    <!-- Erros (se houver) -->
                    <div id="errorsDiv" class="alert alert-warning-custom hidden"></div>

                    <!-- Preview da tabela -->
                    <div class="preview-table" id="previewTable">
                        <!-- Conteúdo será preenchido via JavaScript -->
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary" onclick="resetImport()">
                            <i class="ti ti-arrow-left me-1"></i>
                            Voltar
                        </button>
                        <button type="button" class="btn btn-import" onclick="confirmImport()">
                            <i class="ti ti-check me-1"></i>
                            Confirmar Importação
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Resultado da importação -->
        <div id="step3" class="hidden">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="ti ti-check-circle me-2 text-success"></i>
                        Importação Concluída
                    </h3>
                </div>
                <div class="card-body text-center">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number text-success" id="importedCount">0</div>
                            <div class="stat-label">Registros Importados</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number text-warning" id="skippedCount">0</div>
                            <div class="stat-label">Registros Ignorados</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number text-danger" id="errorCount">0</div>
                            <div class="stat-label">Erros</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="colaboradores.php" class="btn btn-primary me-2">
                            <i class="ti ti-users me-1"></i>
                            Ver Colaboradores
                        </a>
                        <button type="button" class="btn btn-outline-primary" onclick="resetImport()">
                            <i class="ti ti-refresh me-1"></i>
                            Nova Importação
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'analyze':
                    echo json_encode(analyzeExcelFile());
                    exit;
                    
                case 'import':
                    echo json_encode(importData());
                    exit;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

function analyzeExcelFile() {
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erro no upload do arquivo'];
    }
    
    $file = $_FILES['excel_file'];
    $allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipo de arquivo não permitido'];
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB
        return ['success' => false, 'message' => 'Arquivo muito grande (máximo 10MB)'];
    }
    
    require_once '../vendor/autoload.php';
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        
        if ($highestRow < 2) {
            return ['success' => false, 'message' => 'Arquivo deve conter pelo menos uma linha de dados além do cabeçalho'];
        }
        
        // Mapear colunas
        $columnMap = [];
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $header = strtoupper(trim($worksheet->getCell($col . '1')->getValue()));
            $columnMap[$header] = $col;
        }
        
        // Verificar colunas obrigatórias
        $requiredColumns = ['NOME', 'EMPRESA', 'SETOR'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $required) {
            if (!isset($columnMap[$required])) {
                $missingColumns[] = $required;
            }
        }
        
        if (!empty($missingColumns)) {
            return ['success' => false, 'message' => 'Colunas obrigatórias não encontradas: ' . implode(', ', $missingColumns)];
        }
        
        // Processar dados
        $data = [];
        $validRows = 0;
        $invalidRows = 0;
        $errors = [];
        
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [
                'nome' => trim($worksheet->getCell(($columnMap['NOME'] ?? 'A') . $row)->getValue()),
                'empresa' => trim($worksheet->getCell(($columnMap['EMPRESA'] ?? 'B') . $row)->getValue()),
                'setor' => trim($worksheet->getCell(($columnMap['SETOR'] ?? 'C') . $row)->getValue()),
                'email' => trim($worksheet->getCell(($columnMap['EMAIL'] ?? 'D') . $row)->getValue()),
                'telefone' => trim($worksheet->getCell(($columnMap['TELEFONE'] ?? 'E') . $row)->getValue()),
                'ramal' => trim($worksheet->getCell(($columnMap['RAMAIL'] ?? 'F') . $row)->getValue()),
                'teams' => trim($worksheet->getCell(($columnMap['TEAMS'] ?? 'G') . $row)->getValue()),
                'row_number' => $row,
                'valid' => true,
                'errors' => []
            ];
            
            // Validações
            if (empty($rowData['nome'])) {
                $rowData['valid'] = false;
                $rowData['errors'][] = 'Nome é obrigatório';
            }
            
            if (empty($rowData['empresa'])) {
                $rowData['valid'] = false;
                $rowData['errors'][] = 'Empresa é obrigatória';
            }
            
            if (empty($rowData['setor'])) {
                $rowData['valid'] = false;
                $rowData['errors'][] = 'Setor é obrigatório';
            }
            
            if (!empty($rowData['email']) && !filter_var($rowData['email'], FILTER_VALIDATE_EMAIL)) {
                $rowData['valid'] = false;
                $rowData['errors'][] = 'Email inválido';
            }
            
            if ($rowData['valid']) {
                $validRows++;
            } else {
                $invalidRows++;
                $errors[] = "Linha $row: " . implode(', ', $rowData['errors']);
            }
            
            $data[] = $rowData;
        }
        
        // Salvar dados na sessão para importação posterior
        $_SESSION['import_data'] = $data;
        
        return [
            'success' => true,
            'total_rows' => count($data),
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'preview' => array_slice($data, 0, 10), // Primeiros 10 registros para preview
            'errors' => array_slice($errors, 0, 20), // Primeiros 20 erros
            'data' => $data
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erro ao processar arquivo: ' . $e->getMessage()];
    }
}

function importData() {
    global $pdo;
    
    if (!isset($_SESSION['import_data'])) {
        return ['success' => false, 'message' => 'Dados não encontrados. Faça o upload novamente.'];
    }
    
    $data = $_SESSION['import_data'];
    $imported = 0;
    $skipped = 0;
    $errors = 0;
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO colaboradores (nome, empresa, setor, email, telefone, ramal, teams, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo')
            ON DUPLICATE KEY UPDATE 
                empresa = VALUES(empresa),
                setor = VALUES(setor),
                email = VALUES(email),
                telefone = VALUES(telefone),
                ramal = VALUES(ramal),
                teams = VALUES(teams),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        foreach ($data as $row) {
            if (!$row['valid']) {
                $skipped++;
                continue;
            }
            
            try {
                $stmt->execute([
                    $row['nome'],
                    $row['empresa'],
                    $row['setor'],
                    $row['email'] ?: null,
                    $row['telefone'] ?: null,
                    $row['ramal'] ?: null,
                    $row['teams'] ?: null
                ]);
                $imported++;
            } catch (Exception $e) {
                $errors++;
            }
        }
        
        $pdo->commit();
        
        // Limpar dados da sessão
        unset($_SESSION['import_data']);
        
        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Erro na importação: ' . $e->getMessage()];
    }
}

include 'admin_layout.php';
?>