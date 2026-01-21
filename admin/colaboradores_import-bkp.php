<?php
require_once 'config.php';
require_once 'check_access.php';

// Verificar permissões específicas para importação de colaboradores
$hasAccess = in_array('Super Administrador', $user_accesses) || in_array('Gestão de Colaboradores', $user_accesses);

if (!$hasAccess) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Importar Colaboradores';

// CSS adicional para esta página
$extraCSS = '
<style>
    .upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        background-color: #f8f9fa;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .upload-area:hover {
        border-color: #0d6efd;
        background-color: #e7f3ff;
    }
    
    .upload-area.dragover {
        border-color: #0d6efd;
        background-color: #e7f3ff;
    }
    
    .file-info {
        display: none;
        margin-top: 20px;
    }
    
    .preview-table {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .alert-warning {
        border-left: 4px solid #ffc107;
    }
    
    .alert-danger {
        border-left: 4px solid #dc3545;
    }
    
    .alert-success {
        border-left: 4px solid #198754;
    }
</style>
';

ob_start(); ?>
<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-file-import me-2"></i>Importar Colaboradores
                </h2>
                <div class="text-muted mt-1">Importação em massa de colaboradores via planilha Excel</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="colaboradores.php" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <div class="row row-deck row-cards">
            <div class="col-12">
                
                <!-- Alertas de Aviso -->
                <div class="alert alert-warning" role="alert">
                    <h5><i class="ti ti-alert-triangle me-2"></i>Atenção!</h5>
                    <ul class="mb-0">
                        <li><strong>Todos os dados atuais de colaboradores serão removidos</strong> antes da importação</li>
                        <li>Certifique-se de que a planilha está no formato correto (Excel .xlsx)</li>
                        <li>A planilha deve conter as colunas: NOME, RAMAIL, EMAIL, TELEFONE, LINK TEAMS</li>
                        <li>Faça backup dos dados atuais antes de prosseguir</li>
                    </ul>
                </div>
                
                <!-- Formulário de Upload -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="ti ti-upload me-2"></i>Selecionar Planilha</h5>
                    </div>
                    <div class="card-body">
                        <form id="importForm" enctype="multipart/form-data">
                            <div class="upload-area" id="uploadArea">
                                <i class="ti ti-cloud-upload" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                                <h5>Clique aqui ou arraste a planilha</h5>
                                <p class="text-muted">Formatos aceitos: .xlsx (Excel)</p>
                                <input type="file" id="fileInput" name="excel_file" accept=".xlsx" style="display: none;">
                            </div>
                            
                            <div class="file-info" id="fileInfo">
                                <div class="alert alert-info">
                                    <h6><i class="ti ti-file-spreadsheet me-2"></i>Arquivo Selecionado:</h6>
                                    <p id="fileName" class="mb-0"></p>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <button type="button" id="previewBtn" class="btn btn-info" style="display: none;">
                                    <i class="ti ti-eye me-2"></i>Visualizar Dados
                                </button>
                                <button type="button" id="importBtn" class="btn btn-danger" style="display: none;">
                                    <i class="ti ti-database me-2"></i>Importar Dados
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Preview dos Dados -->
                <div id="previewSection" style="display: none;">
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><i class="ti ti-table me-2"></i>Preview dos Dados</h5>
                        </div>
                        <div class="card-body">
                            <div id="previewContent"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Resultado da Importação -->
                <div id="resultSection" style="display: none;">
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><i class="ti ti-check me-2"></i>Resultado da Importação</h5>
                        </div>
                        <div class="card-body">
                            <div id="resultContent"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ti ti-alert-triangle me-2"></i>Confirmar Importação</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Esta ação irá:</strong></p>
                <ul>
                    <li>Remover TODOS os colaboradores existentes</li>
                    <li>Importar <span id="recordCount">0</span> novos registros</li>
                </ul>
                <p class="text-danger"><strong>Esta ação não pode ser desfeita!</strong></p>
                <p>Tem certeza que deseja continuar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="confirmImport" class="btn btn-danger">
                    <i class="ti ti-database me-2"></i>Sim, Importar
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// JavaScript adicional para esta página
$extraJS = '
<script>
    $(document).ready(function() {
        let selectedFile = null;
        let previewData = null;
        
        // Upload area click
        $("#uploadArea").click(function() {
            $("#fileInput").click();
        });
        
        // Drag and drop
        $("#uploadArea").on("dragover", function(e) {
            e.preventDefault();
            $(this).addClass("dragover");
        });
        
        $("#uploadArea").on("dragleave", function(e) {
            e.preventDefault();
            $(this).removeClass("dragover");
        });
        
        $("#uploadArea").on("drop", function(e) {
            e.preventDefault();
            $(this).removeClass("dragover");
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        });
        
        // File input change
        $("#fileInput").change(function() {
            if (this.files.length > 0) {
                handleFileSelect(this.files[0]);
            }
        });
        
        function handleFileSelect(file) {
            if (!file.name.toLowerCase().endsWith(".xlsx")) {
                alert("Por favor, selecione um arquivo Excel (.xlsx)");
                return;
            }
            
            selectedFile = file;
            $("#fileName").text(file.name + " (" + formatFileSize(file.size) + ")");
            $("#fileInfo").show();
            $("#previewBtn").show();
            $("#importBtn").hide();
            $("#previewSection").hide();
            $("#resultSection").hide();
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return "0 Bytes";
            const k = 1024;
            const sizes = ["Bytes", "KB", "MB", "GB"];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
        }
        
        // Preview button
        $("#previewBtn").click(function() {
            if (!selectedFile) return;
            
            const formData = new FormData();
            formData.append("excel_file", selectedFile);
            formData.append("action", "preview");
            
            $(this).prop("disabled", true).html("<i class=\"ti ti-loader\"></i> Carregando...");
            
            $.ajax({
                url: "colaboradores_import_process.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        previewData = data.data;
                        showPreview(data.data, data.sheets, data.total);
                        $("#importBtn").show();
                    } else {
                        alert("Erro: " + data.message);
                    }
                },
                error: function() {
                    alert("Erro ao processar arquivo");
                },
                complete: function() {
                    $("#previewBtn").prop("disabled", false).html("<i class=\"ti ti-eye me-2\"></i>Visualizar Dados");
                }
            });
        });
        
        function showPreview(data, sheets, total) {
            let html = "<div class=\"alert alert-info\">";
            html += "<h6><i class=\"ti ti-info-circle me-2\"></i>Resumo da Importação</h6>";
            html += "<strong>Total de registros encontrados: " + total + "</strong> (excluindo cabeçalhos)<br>";
            
            if (sheets && sheets.length > 0) {
                html += "<strong>Abas processadas:</strong><br>";
                sheets.forEach(function(sheet) {
                    html += "• <strong>" + sheet.name + "</strong>: " + sheet.rows + " registros<br>";
                });
            }
            html += "</div>";
            
            html += "<div class=\"preview-table\">";
            html += "<table class=\"table table-striped table-sm\">";
            html += "<thead class=\"table-dark\">";
            html += "<tr>";
            if (data.length > 0) {
                data[0].forEach(function(header) {
                    html += "<th>" + header + "</th>";
                });
            }
            html += "</tr>";
            html += "</thead>";
            html += "<tbody>";
            
            for (let i = 1; i < Math.min(11, data.length); i++) {
                html += "<tr>";
                data[i].forEach(function(cell) {
                    html += "<td>" + (cell || "") + "</td>";
                });
                html += "</tr>";
            }
            
            if (data.length > 11) {
                html += "<tr><td colspan=\"" + data[0].length + "\" class=\"text-center text-muted\">... e mais " + (data.length - 11) + " registros</td></tr>";
            }
            
            html += "</tbody>";
            html += "</table>";
            html += "</div>";
            
            $("#previewContent").html(html);
            $("#previewSection").show();
        }
        
        // Import button
        $("#importBtn").click(function() {
            if (!previewData) return;
            
            // Usar o total correto que foi calculado no backend
            const totalRecords = $(".alert-info").text().match(/Total de registros encontrados: (\\d+)/);
            const count = totalRecords ? totalRecords[1] : (previewData.length - 1);
            $("#recordCount").text(count);
            $("#confirmModal").modal("show");
        });
        
        // Confirm import
        $("#confirmImport").click(function() {
            if (!selectedFile) return;
            
            const formData = new FormData();
            formData.append("excel_file", selectedFile);
            formData.append("action", "import");
            
            $(this).prop("disabled", true).html("<i class=\"ti ti-loader\"></i> Importando...");
            $("#confirmModal").modal("hide");
            
            $.ajax({
                url: "colaboradores_import_process.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    const data = JSON.parse(response);
                    showResult(data);
                },
                error: function() {
                    showResult({success: false, message: "Erro na comunicação com o servidor"});
                },
                complete: function() {
                    $("#confirmImport").prop("disabled", false).html("<i class=\"ti ti-database me-2\"></i>Sim, Importar");
                }
            });
        });
        
        function showResult(data) {
            let html = "";
            if (data.success) {
                html = "<div class=\"alert alert-success\">";
                html += "<h5><i class=\"ti ti-check me-2\"></i>Importação Concluída!</h5>";
                html += "<p>Total de registros importados: <strong>" + data.imported + "</strong></p>";
                if (data.errors && data.errors.length > 0) {
                    html += "<p>Registros com erro: <strong>" + data.errors.length + "</strong></p>";
                    html += "<details><summary>Ver erros</summary><ul>";
                    data.errors.forEach(function(error) {
                        html += "<li>" + error + "</li>";
                    });
                    html += "</ul></details>";
                }
                html += "</div>";
                
                html += "<div class=\"mt-3\">";
                html += "<a href=\"colaboradores.php\" class=\"btn btn-primary\"><i class=\"ti ti-users me-2\"></i>Ver Colaboradores</a>";
                html += "</div>";
            } else {
                html = "<div class=\"alert alert-danger\">";
                html += "<h5><i class=\"ti ti-x me-2\"></i>Erro na Importação</h5>";
                html += "<p>" + data.message + "</p>";
                html += "</div>";
            }
            
            $("#resultContent").html(html);
            $("#resultSection").show();
        }
    });
</script>
';

// Incluir o layout padrão
include 'admin_layout.php';
?>