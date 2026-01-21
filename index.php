<?php
session_start();

// Lê a mensagem de sucesso do report (se existir) e remove da sessão
$incidentMessage = null;
if (isset($_SESSION['incident_success'])) {
    $incidentMessage = $_SESSION['incident_success'];
    unset($_SESSION['incident_success']);
}

// PROCESSAMENTO DO FORMULÁRIO DE INCIDENT (exemplo simplificado)
if (isset($_POST['incident_submit'])) {
    // ... (código de inserção no banco, etc.)
    $_SESSION['incident_success'] = "Relatório enviado com sucesso. Obrigado por informar!";
    header("Location: index-tabler-modern.php");
    exit;
}

// Carrega config do banco
include_once 'admin/config.php';

// Consulta links do menu
$sql = "SELECT id, titulo, descricao, url, icone, cor, tamanho, parent_id, target_blank, ordem, status FROM menu_links WHERE status='ativo' ORDER BY ordem ASC";
$stmt = $pdo->query($sql);
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Função para mapear ícones para SVG
 */
function getIconSvg($icone) {
    $iconMap = [
        'ti ti-currency-real' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 6h-4a3 3 0 0 0 0 6h1a3 3 0 0 1 0 6h-4"/><path d="M17 4v2"/><path d="M17 18v2"/><path d="M3 12h3"/><path d="M5 9l-2 3 2 3"/></svg>',
        'ti ti-device-desktop' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="1"/><path d="M7 20h10"/><path d="M9 16v4"/><path d="M15 16v4"/></svg>',
        'ti ti-device-laptop' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 19h18"/><rect x="5" y="6" width="14" height="10" rx="1"/></svg>',
        'ti ti-link' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><path d="M11 13h2"/></svg>',
        'ti ti-mail' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/></svg>',
        'ti ti-message' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'ti ti-refresh' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>',
        'ti ti-settings' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>',
        'ti ti-shield' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'ti ti-tool' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
        'ti ti-truck' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16,8 20,8 23,11 23,16 16,16 16,8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
        'ti ti-user' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'ti ti-users' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m22 21-2-2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'ti ti-currency-dollar' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2" /><path d="M12 3v3m0 12v3" /></svg>',
        'ti ti-credit-card' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-credit-card"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M3 10l18 0" /><path d="M7 15l.01 0" /><path d="M11 15l2 0" /></svg>',
        'fa fa-film' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 512 512" fill="currentColor"><path d="M0 96C0 60.7 28.7 32 64 32H448c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM48 368v32c0 8.8 7.2 16 16 16H96c8.8 0 16-7.2 16-16V368c0-8.8-7.2-16-16-16H64c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V368c0-8.8-7.2-16-16-16H416zM48 240v32c0 8.8 7.2 16 16 16H96c8.8 0 16-7.2 16-16V240c0-8.8-7.2-16-16-16H64c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V240c0-8.8-7.2-16-16-16H416zM48 112v32c0 8.8 7.2 16 16 16H96c8.8 0 16-7.2 16-16V112c0-8.8-7.2-16-16-16H64c-8.8 0-16 7.2-16 16zM416 96c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V112c0-8.8-7.2-16-16-16H416zM160 128v64c0 17.7 14.3 32 32 32H320c17.7 0 32-14.3 32-32V128c0-17.7-14.3-32-32-32H192c-17.7 0-32 14.3-32 32zm32 160c-17.7 0-32 14.3-32 32v64c0 17.7 14.3 32 32 32H320c17.7 0 32-14.3 32-32V320c0-17.7-14.3-32-32-32H192z"/></svg>',
    
        'ti ti-arrow-left' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M5 12l6 6" /><path d="M5 12l6 -6" /></svg>',
        'ti ti-edit' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>',
        'ti ti-eye' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>',
        'ti ti-info-circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-info-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>',
        'ti ti-device-floppy' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-device-floppy"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>',

        'ti ti-palette' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-palette"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21a9 9 0 0 1 0 -18c4.97 0 9 3.582 9 8c0 1.06 -.474 2.078 -1.318 2.828c-.844 .75 -1.989 1.172 -3.182 1.172h-2.5a2 2 0 0 0 -1 3.75a1.3 1.3 0 0 1 -1 2.25" /><path d="M8.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M16.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /></svg>',
        'ti ti-layout' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-layout-grid"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M14 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M4 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M14 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /></svg>',
        'ti ti-icons' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-icons"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6.5 6.5m-3.5 0a3.5 3.5 0 1 0 7 0a3.5 3.5 0 1 0 -7 0" /><path d="M2.5 21h8l-4 -7z" /><path d="M14 3l7 7" /><path d="M14 10l7 -7" /><path d="M14 14h7v7h-7z" /></svg>',
        'ti ti-external-link' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-external-link"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" /><path d="M11 13l9 -9" /><path d="M15 4h5v5" /></svg>',
        'ti ti-world' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-world"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M3.6 9h16.8" /><path d="M3.6 15h16.8" /><path d="M11.5 3a17 17 0 0 0 0 18" /><path d="M12.5 3a17 17 0 0 1 0 18" /></svg>',
        'ti ti-home' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-home"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>',

        'ti ti-tag' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-tag"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M3 6v5.172a2 2 0 0 0 .586 1.414l7.71 7.71a2.41 2.41 0 0 0 3.408 0l5.592 -5.592a2.41 2.41 0 0 0 0 -3.408l-7.71 -7.71a2 2 0 0 0 -1.414 -.586h-5.172a3 3 0 0 0 -3 3z" /></svg>',
        'ti ti-file-text' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-text"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 9l1 0" /><path d="M9 13l6 0" /><path d="M9 17l6 0" /></svg>',

        'ti ti-search' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-search"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>',
        'ti ti-plus' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>',
        'ti ti-minus' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-minus"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /></svg>',
        'ti ti-check' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>',
        'ti ti-x' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-x"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>',
        'ti ti-menu' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-menu"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 8l16 0" /><path d="M4 16l16 0" /></svg>',

        'ti ti-star' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-star"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" /></svg>',
        'ti ti-heart' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-heart"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572" /></svg>',
        'ti ti-bookmark' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-bookmark"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 7v14l-6 -4l-6 4v-14a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4z" /></svg>',
        'ti ti-download' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-download"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>',
        'ti ti-upload' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-upload"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 9l5 -5l5 5" /><path d="M12 4l0 12" /></svg>',

        'ti ti-folder' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-folder"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2" /></svg>',
        'ti ti-file' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /></svg>',
        'ti ti-trash' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>',
        'ti ti-lock' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-lock"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z" /><path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M8 11v-4a4 4 0 1 1 8 0v4" /></svg>',
        'ti ti-key' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-key"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" /><path d="M15 9h.01" /></svg>',

        'ti ti-phone' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-phone"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /></svg>',
        'ti ti-bell' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-bell"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" /><path d="M9 17v1a3 3 0 0 0 6 0v-1" /></svg>',

        'ti ti-camera' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-camera"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 7h1a2 2 0 0 0 2 -2a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1a2 2 0 0 0 2 2h1a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2" /><path d="M9 13a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg>',
        'ti ti-video' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-video"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z" /><path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /></svg>',
        'ti ti-music' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-music"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M13 17a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M9 17v-13h10v13" /><path d="M9 8h10" /></svg>',
        'ti ti-photo' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-photo"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01" /><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z" /><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5" /><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3" /></svg>',

        'ti ti-arrow-right' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-right"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M13 18l6 -6" /><path d="M13 6l6 6" /></svg>',
        'ti ti-arrow-up' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-narrow-up"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M16 9l-4 -4" /><path d="M8 9l4 -4" /></svg>',
        'ti ti-arrow-down' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-down"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M18 13l-6 6" /><path d="M6 13l6 6" /></svg>',
        'ti ti-adjustments' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-adjustments"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 10a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M6 4v4" /><path d="M6 12v8" /><path d="M10 16a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M12 4v10" /><path d="M12 18v2" /><path d="M16 7a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M18 4v1" /><path d="M18 9v11" /></svg>',

        'ti ti-calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /><path d="M11 15h1" /><path d="M12 15v3" /></svg>',
        'ti ti-clock' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-clock"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 7v5l3 3" /></svg>',
        'ti ti-sun' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-sun"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" /></svg>',
        'ti ti-moon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-moon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" /></svg>',
        'ti ti-wifi' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-wifi"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 18l.01 0" /><path d="M9.172 15.172a4 4 0 0 1 5.656 0" /><path d="M6.343 12.343a8 8 0 0 1 11.314 0" /><path d="M3.515 9.515c4.686 -4.687 12.284 -4.687 17 0" /></svg>',
        'ti ti-battery' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-battery"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 7h11a2 2 0 0 1 2 2v.5a.5 .5 0 0 0 .5 .5a.5 .5 0 0 1 .5 .5v3a.5 .5 0 0 1 -.5 .5a.5 .5 0 0 0 -.5 .5v.5a2 2 0 0 1 -2 2h-11a2 2 0 0 1 -2 -2v-6a2 2 0 0 1 2 -2" /></svg>',
        'ti ti-device-mobile' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-device-mobile"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 5a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-14z" /><path d="M11 4h2" /><path d="M12 17v.01" /></svg>',
        'ti ti-device-tablet' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-device-tablet"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v16a1 1 0 0 1 -1 1h-12a1 1 0 0 1 -1 -1v-16z" /><path d="M11 17a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /></svg>',

        'ti ti-building' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-building"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M9 8l1 0" /><path d="M9 12l1 0" /><path d="M9 16l1 0" /><path d="M14 8l1 0" /><path d="M14 12l1 0" /><path d="M14 16l1 0" /><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" /></svg>',
        'ti ti-chart-bar' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chart-bar"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M9 8m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M15 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M4 20l14 0" /></svg>',
        'ti ti-presentation' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-presentation"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 4l18 0" /><path d="M4 4v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-10" /><path d="M12 16l0 4" /><path d="M9 20l6 0" /><path d="M8 12l3 -3l2 2l3 -3" /></svg>',
        'ti ti-briefcase' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-briefcase"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2" /><path d="M12 12l0 .01" /><path d="M3 13a20 20 0 0 0 18 0" /></svg>',
        'ti ti-car' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-car"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M5 17h-2v-6l2 -5h9l4 5h1a2 2 0 0 1 2 2v4h-2m-4 0h-6m-6 -6h15m-6 0v-5" /></svg>',
        'ti ti-plane' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plane"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 10h4a2 2 0 0 1 0 4h-4l-4 7h-3l2 -7h-4l-2 2h-3l2 -4l-2 -4h3l2 2h4l-2 -7h3z" /></svg>',
        'ti ti-book' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-book"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0" /><path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0" /><path d="M3 6l0 13" /><path d="M12 6l0 13" /><path d="M21 6l0 13" /></svg>',
        'ti ti-school' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-school"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M22 9l-10 -4l-10 4l10 4l10 -4v6" /><path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4" /></svg>',
        'ti ti-certificate' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-certificate"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 15m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5" /><path d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -1 1.73" /><path d="M6 9l12 0" /><path d="M6 12l3 0" /><path d="M6 15l2 0" /></svg>',
        'ti ti-trophy' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trophy"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 21l8 0" /><path d="M12 17l0 4" /><path d="M7 4l10 0" /><path d="M17 4v8a5 5 0 0 1 -10 0v-8" /><path d="M5 9m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M19 9m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /></svg>',

        'ti ti-cloud' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-cloud"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6.657 18c-2.572 0 -4.657 -2.007 -4.657 -4.483c0 -2.475 2.085 -4.482 4.657 -4.482c.393 -1.762 1.794 -3.2 3.675 -3.773c1.88 -.572 3.956 -.193 5.444 1c1.488 1.19 2.162 3.007 1.77 4.769h.99c1.913 0 3.464 1.56 3.464 3.486c0 1.927 -1.551 3.487 -3.465 3.487h-11.878" /></svg>',
        'ti ti-shopping-cart' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-cart"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>',
        'ti ti-building-estate' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-building-estate"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21h18" /><path d="M19 21v-4" /><path d="M19 17a2 2 0 0 0 2 -2v-2a2 2 0 1 0 -4 0v2a2 2 0 0 0 2 2z" /><path d="M14 21v-14a3 3 0 0 0 -3 -3h-4a3 3 0 0 0 -3 3v14" /><path d="M9 17v4" /><path d="M8 13h2" /><path d="M8 9h2" /></svg>',

        'ti ti-database' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-database"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0" /><path d="M4 6v6a8 3 0 0 0 16 0v-6" /><path d="M4 12v6a8 3 0 0 0 16 0v-6" /></svg>',
        'ti ti-server' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-server"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 4m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M3 12m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M7 8l0 .01" /><path d="M7 16l0 .01" /><path d="M11 8l2 0" /><path d="M11 16l2 0" /></svg>',
        'ti ti-filter' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-filter"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-4.414 4.414v7l-6 2v-9l-4.48 -4.928a2 2 0 0 1 -.52 -1.345v-2.227z" /></svg>',
        'ti ti-volume' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-volume"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8a5 5 0 0 1 0 8" /><path d="M17.7 5a9 9 0 0 1 0 14" /><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /></svg>',
        'ti ti-share' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-share"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M18 6m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M18 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M8.7 10.7l6.6 -3.4" /><path d="M8.7 13.3l6.6 3.4" /></svg>',
        'ti ti-list' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-list"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l11 0" /><path d="M9 12l11 0" /><path d="M9 18l11 0" /><path d="M5 6l0 .01" /><path d="M5 12l0 .01" /><path d="M5 18l0 .01" /></svg>',
        'ti ti-map' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-map"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7l6 -3l6 3l6 -3v13l-6 3l-6 -3l-6 3v-13" /><path d="M9 4v13" /><path d="M15 7v13" /></svg>',

        'ti ti-brush' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-brush"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21v-4a4 4 0 1 1 4 4h-4" /><path d="M21 3a16 16 0 0 0 -12.8 10.2" /><path d="M21 3a16 16 0 0 1 -10.2 12.8" /><path d="M10.6 9a9 9 0 0 1 4.4 4.4" /></svg>',
        'ti ti-bulb' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-bulb"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12h1m8 -9v1m8 8h1m-15.4 -6.4l.7 .7m12.1 -.7l-.7 .7" /><path d="M9 16a5 5 0 1 1 6 0a3.5 3.5 0 0 0 -1 3a2 2 0 0 1 -4 0a3.5 3.5 0 0 0 -1 -3" /><path d="M9.7 17l4.6 0" /></svg>',
        'ti ti-calculator' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calculator"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 3m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" /><path d="M8 7m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v1a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z" /><path d="M8 14l0 .01" /><path d="M12 14l0 .01" /><path d="M16 14l0 .01" /><path d="M8 17l0 .01" /><path d="M12 17l0 .01" /><path d="M16 17l0 .01" /></svg>',
        'ti ti-clipboard' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-clipboard"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" /><path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /></svg>',
        'ti ti-cloud-rain' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-cloud-rain"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 18a4.6 4.4 0 0 1 0 -9a5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7" /><path d="M11 13v2m0 3v2m4 -5v2m0 3v2" /></svg>',
        'ti ti-dashboard' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-layout-dashboard"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-6a1 1 0 0 1 1 -1" /><path d="M5 16h4a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-2a1 1 0 0 1 1 -1" /><path d="M15 12h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-6a1 1 0 0 1 1 -1" /><path d="M15 4h4a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-2a1 1 0 0 1 1 -1" /></svg>',

        'ti ti-flame' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-flame"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12c2 -2.96 0 -7 -1 -8c0 3.038 -1.773 4.741 -3 6c-1.226 1.26 -2 3.24 -2 5a6 6 0 1 0 12 0c0 -1.532 -1.056 -3.94 -2 -5c-1.786 3 -3.5 1 -4 2z" /></svg>',
        'ti ti-grid-dots' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-grid-dots"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M19 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M5 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M19 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M5 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M19 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /></svg>',
        'ti ti-hammer' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-hammer"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.414 10l-7.383 7.418a2.091 2.091 0 0 0 0 2.967a2.11 2.11 0 0 0 2.976 0l7.407 -7.385" /><path d="M18.121 15.293l2.586 -2.586a1 1 0 0 0 0 -1.414l-7.586 -7.586a1 1 0 0 0 -1.414 0l-2.586 2.586a1 1 0 0 0 0 1.414l7.586 7.586a1 1 0 0 0 1.414 0z" /></svg>',
        'ti ti-hierarchy' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-hierarchy"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M5 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M19 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M6.5 17.5l5.5 -4.5l5.5 4.5" /><path d="M12 7l0 6" /></svg>',
        'ti ti-sitemap' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-sitemap"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 15m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /><path d="M15 15m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /><path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /><path d="M6 15v-1a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v1" /><path d="M12 9l0 3" /></svg>',
        'ti ti-home-2' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-home-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M10 12h4v4h-4z" /></svg>',
        'ti ti-hospital' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-medical-cross"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 3a1 1 0 0 1 1 1v4.535l3.928 -2.267a1 1 0 0 1 1.366 .366l1 1.732a1 1 0 0 1 -.366 1.366l-3.927 2.268l3.927 2.269a1 1 0 0 1 .366 1.366l-1 1.732a1 1 0 0 1 -1.366 .366l-3.928 -2.269v4.536a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1v-4.536l-3.928 2.268a1 1 0 0 1 -1.366 -.366l-1 -1.732a1 1 0 0 1 .366 -1.366l3.927 -2.268l-3.927 -2.268a1 1 0 0 1 -.366 -1.366l1 -1.732a1 1 0 0 1 1.366 -.366l3.928 2.267v-4.535a1 1 0 0 1 1 -1z" /></svg>',
        'ti ti-location' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-location"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5" /></svg>',
        'ti ti-notebook' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-notebook"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h11a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-11a1 1 0 0 1 -1 -1v-14a1 1 0 0 1 1 -1m3 0v18" /><path d="M13 8l2 0" /><path d="M13 12l2 0" /></svg>',
        'ti ti-office' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-building"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M9 8l1 0" /><path d="M9 12l1 0" /><path d="M9 16l1 0" /><path d="M14 8l1 0" /><path d="M14 12l1 0" /><path d="M14 16l1 0" /><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" /></svg>',
        'ti ti-plug' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plug-connected"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 12l5 5l-1.5 1.5a3.536 3.536 0 1 1 -5 -5l1.5 -1.5z" /><path d="M17 12l-5 -5l1.5 -1.5a3.536 3.536 0 1 1 5 5l-1.5 1.5z" /><path d="M3 21l2.5 -2.5" /><path d="M18.5 5.5l2.5 -2.5" /><path d="M10 11l-2 2" /><path d="M13 14l-2 2" /></svg>',
        'ti ti-printer' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-printer"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" /><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" /><path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z" /></svg>',
        'ti ti-report' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-report"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h5.697" /><path d="M18 14v4h4" /><path d="M18 11v-4a2 2 0 0 0 -2 -2h-2" /><path d="M8 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /><path d="M18 18m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M8 11h4" /><path d="M8 15h3" /></svg>',
        'ti ti-snowflake' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-snowflake"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 4l2 1l2 -1" /><path d="M12 2v6.5l3 1.72" /><path d="M17.928 6.268l.134 2.232l1.866 1.232" /><path d="M20.66 7.5l-5.629 3.25l.01 3.458" /><path d="M19.928 14.268l-1.866 1.232l-.134 2.232" /><path d="M20.66 16.5l-5.629 -3.25l-2.99 1.738" /><path d="M14 20l-2 -1l-2 1" /><path d="M12 22v-6.5l-3 -1.72" /><path d="M6.072 17.732l-.134 -2.232l-1.866 -1.232" /><path d="M3.34 16.5l5.629 -3.25l-.01 -3.458" /><path d="M4.072 9.732l1.866 -1.232l.134 -2.232" /><path d="M3.34 7.5l5.629 3.25l2.99 -1.738" /></svg>',
        'ti ti-sort-ascending' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-sort-ascending"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6l7 0" /><path d="M4 12l7 0" /><path d="M4 18l9 0" /><path d="M15 9l3 -3l3 3" /><path d="M18 6l0 12" /></svg>',
        'ti ti-toggle-left' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-toggle-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 12m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M2 6m0 6a6 6 0 0 1 6 -6h8a6 6 0 0 1 6 6v0a6 6 0 0 1 -6 6h-8a6 6 0 0 1 -6 -6z" /></svg>',
        'ti ti-umbrella' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-umbrella-closed"> <path stroke="none" d="M0 0h24v24H0z" fill="none" /> <path d="M9 16l3 -13l3 13z" /> <path d="M12 16v3c0 2.667 4 2.667 4 0" /> </svg>',
        'ti ti-usb' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-usb"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M12 17v-11.5" /><path d="M7 10v3l5 3" /><path d="M12 14.5l5 -2v-2.5" /><path d="M16 10h2v-2h-2z" /><path d="M7 9m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M10 5.5h4l-2 -2.5z" /></svg>',
        'ti ti-wrench' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-tool"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 10h3v-3l-3.5 -3.5a6 6 0 0 1 8 8l6 6a2 2 0 0 1 -3 3l-6 -6a6 6 0 0 1 -8 -8l3.5 3.5" /></svg>',

        'ti ti-chat' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-message"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 9h8" /><path d="M8 13h6" /><path d="M18 4a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-5l-5 3v-3h-2a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12z" /></svg>',
        'ti ti-bluetooth' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-wifi"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 18l.01 0" /><path d="M9.172 15.172a4 4 0 0 1 5.656 0" /><path d="M6.343 12.343a8 8 0 0 1 11.314 0" /><path d="M3.515 9.515c4.686 -4.687 12.284 -4.687 17 0" /></svg>',
];
    
    // Usar SVG se disponível, senão usar ícone padrão
    if (isset($iconMap[$icone])) {
        return $iconMap[$icone];
    } else {
        return '<i class="' . $icone . '"></i>';
    }
}

/**
 * Monta a árvore (pais/filhos)
 */
function buildTree(array $elements, $parentId = 0) {
    $branch = [];
    foreach ($elements as $element) {
        $elParent = $element['parent_id'] ?: 0;
        if ($elParent == $parentId) {
            $children = buildTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[] = $element;
        }
    }
    return $branch;
}
$menuTree = buildTree($links, 0);

// Consulta últimas 2 notícias do Portal
$sqlNews = "SELECT * FROM noticias WHERE categoria = 'Portal' AND status = 'ativo' ORDER BY data_publicacao DESC LIMIT 2";
$stmtNews = $pdo->query($sqlNews);
$noticias = $stmtNews->fetchAll(PDO::FETCH_ASSOC);

// Consulta alertas ativos (limitando a até três alertas)
$sqlAlerts = "SELECT * FROM alerts WHERE status = 'ativo' ORDER BY display_order ASC, created_at DESC LIMIT 3";
$stmtAlerts = $pdo->query($sqlAlerts);
$alerts = $stmtAlerts->fetchAll(PDO::FETCH_ASSOC);

// Consulta últimas 2 notícias da categoria Maxtrade
$sqlMaxtrade = "SELECT * FROM noticias 
                WHERE categoria = 'Maxtrade' AND status = 'ativo' 
                ORDER BY data_publicacao DESC LIMIT 2";
$stmtMaxtrade = $pdo->query($sqlMaxtrade);
$maxtradeNews = $stmtMaxtrade->fetchAll(PDO::FETCH_ASSOC);

// Paleta de cores do Tabler (sincronizada com link_editar.php)
$tablerColors = [
    '#206bc4', // Azul
    '#2fb344', // Verde
    '#f59f00', // Amarelo
    '#d63384', // Rosa
    '#ae3ec9', // Roxo
    '#17a2b8', // Ciano
    '#fd7e14', // Laranja
    '#e64980', // Pink
    '#6c757d', // Cinza
    '#198754', // Verde Escuro
    '#dc3545', // Vermelho
    '#0dcaf0', // Azul Claro
    '#ffc107', // Amarelo Dourado
    '#6f42c1', // Índigo
    '#20c997', // Teal
    '#fd7e14', // Laranja Escuro
    '#e83e8c', // Rosa Escuro
    '#6610f2', // Violeta
    '#0d6efd', // Azul Primário
    '#198754'  // Sucesso
];

// Tamanhos disponíveis (sincronizado com link_editar.php)
$availableSizes = [
    'col-12' => 'Largura Total',
    'col-lg-6 col-md-6' => 'Metade',
    'col-lg-4 col-md-6' => 'Um Terço',
    'col-lg-3 col-md-6' => 'Um Quarto',
    'col-lg-2 col-md-4' => 'Um Sexto'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>..::Grupo Barão::..</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php
    /* ---- METADADOS DINÂMICOS ---- */
    $metaTitle       = 'Portal TI do Grupo Barão';
    $metaDescription = 'Acesso rápido a sistemas internos, notícias e alertas de TI do Grupo Barão.';
    $metaImage = 'https://www.ti.grupobarao.com.br/portal/assets/img/social/portal-og8.jpg';
    $metaUrl         = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    ?>
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($metaImage) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($metaUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Grupo Barão">
    
    <!-- Twitter Card fallback -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($metaImage) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    <!-- Tabler Icons - Versão mais recente -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons@2.44.0/tabler-icons.min.css">
    <!-- Fallback para ícones Tabler -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    
    <!-- Fallback para ícones Tabler (caso CDN não funcione) -->
    <style>
        /* Ícones Tabler essenciais definidos localmente com SVG */
        .ti {
            font-style: normal !important;
            font-weight: normal !important;
            font-variant: normal !important;
            text-transform: none !important;
            line-height: 1 !important;
            -webkit-font-smoothing: antialiased !important;
            -moz-osx-font-smoothing: grayscale !important;
            display: inline-block !important;
            width: 1em;
            height: 1em;
            vertical-align: -0.125em;
        }
        
        .ti::before {
            content: "";
            display: inline-block;
            width: 1em;
            height: 1em;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }
        
        /* Ícones SVG inline para os mais usados */
        .ti-apps::before { 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Crect x='3' y='3' width='7' height='7'/%3E%3Crect x='14' y='3' width='7' height='7'/%3E%3Crect x='14' y='14' width='7' height='7'/%3E%3Crect x='3' y='14' width='7' height='7'/%3E%3C/svg%3E");
        }
        .ti-home::before { 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'/%3E%3Cpolyline points='9,22 9,12 15,12 15,22'/%3E%3C/svg%3E");
        }
        .ti-user::before { 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'/%3E%3Ccircle cx='12' cy='7' r='4'/%3E%3C/svg%3E");
        }
        .ti-users::before { 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'/%3E%3Ccircle cx='9' cy='7' r='4'/%3E%3Cpath d='m22 21-2-2'/%3E%3Cpath d='M16 3.13a4 4 0 0 1 0 7.75'/%3E%3C/svg%3E");
        }
        .ti-settings::before { 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z'/%3E%3Ccircle cx='12' cy='12' r='3'/%3E%3C/svg%3E");
        }
        .ti-news::before { 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2'/%3E%3Cpath d='M18 14h-8'/%3E%3Cpath d='M15 18h-5'/%3E%3Cpath d='M10 6h8v4h-8V6Z'/%3E%3C/svg%3E");
        }
        .ti-sun::before { 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Ccircle cx='12' cy='12' r='4'/%3E%3Cpath d='m12 2v2'/%3E%3Cpath d='m12 20v2'/%3E%3Cpath d='m4.93 4.93 1.41 1.41'/%3E%3Cpath d='m17.66 17.66 1.41 1.41'/%3E%3Cpath d='M2 12h2'/%3E%3Cpath d='M20 12h2'/%3E%3Cpath d='m6.34 17.66-1.41 1.41'/%3E%3Cpath d='m19.07 4.93-1.41 1.41'/%3E%3C/svg%3E");
        }
        .ti-moon::before { 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z'/%3E%3C/svg%3E");
        }
        .ti-bell::before { 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9'/%3E%3Cpath d='m13.73 21a2 2 0 0 1-3.46 0'/%3E%3C/svg%3E");
        }
        .ti-chevron-right::before { 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='m9 18 6-6-6-6'/%3E%3C/svg%3E");
        }
        .ti-download::before { 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'/%3E%3Cpolyline points='7,10 12,15 17,10'/%3E%3Cline x1='12' x2='12' y1='15' y2='3'/%3E%3C/svg%3E");
        }
        .ti-link::before { 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71'/%3E%3Cpath d='M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71'/%3E%3C/svg%3E");
        }
        
        /* Forçar exibição dos ícones em todas as áreas */
        .card-icon .ti,
        .modal-title .ti,
        .submenu-icon .ti {
            display: inline-block !important;
            font-style: normal !important;
            font-family: "tabler-icons" !important;
            font-weight: normal !important;
            font-variant: normal !important;
            text-transform: none !important;
            line-height: 1 !important;
            -webkit-font-smoothing: antialiased !important;
            -moz-osx-font-smoothing: grayscale !important;
        }
        
        /* Garantir que ícones sejam visíveis mesmo se a fonte não carregar */
        .card-icon i {
            min-width: 24px !important;
            min-height: 24px !important;
            text-align: center !important;
            line-height: 24px !important;
        }
        
        /* Fallback para ícones que não carregam */
        .card-icon i:empty::before {
            content: "📱" !important;
            font-size: 24px !important;
            display: inline-block !important;
        }
    </style>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --tblr-font-sans-serif: 'Inter', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }

        :root {
            --bg-primary: #ffffff;
            --bg-secondary: rgba(255, 255, 255, 0.95);
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: rgba(0, 0, 0, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.1);
            /* Fundo com camadas que conectam topo, meio e rodapé */
            --parallax-bg:
                radial-gradient(1200px 520px at 10% 5%, rgba(32,107,196,.18), transparent 60%),
                radial-gradient(1000px 480px at 90% 100%, rgba(111,66,193,.18), transparent 60%),
                linear-gradient(180deg, rgba(32,107,196,.06) 0%, rgba(255,255,255,0) 32%, rgba(111,66,193,.06) 64%, rgba(255,255,255,0) 100%),
                linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        [data-theme="dark"] {
            --bg-primary: #1e293b;
            --bg-secondary: rgba(30, 41, 59, 0.95);
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.3);
            /* Versão escura com camadas mais intensas */
            --parallax-bg:
                radial-gradient(1200px 520px at 10% 5%, rgba(32,107,196,.24), transparent 60%),
                radial-gradient(1000px 480px at 90% 100%, rgba(111,66,193,.24), transparent 60%),
                linear-gradient(180deg, rgba(32,107,196,.10) 0%, rgba(0,0,0,0) 35%, rgba(111,66,193,.10) 65%, rgba(0,0,0,0) 100%),
                linear-gradient(135deg, #1e293b 0%, #334155 100%);
        }

        body {
            min-height: 100vh;
            font-family: var(--tblr-font-sans-serif);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .main-container {
            background: var(--bg-secondary);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px var(--shadow-color);
            margin: 120px auto 2rem auto;
            max-width: 1400px;
            overflow: hidden;
            position: relative;
            z-index: 1;
            border: 1px solid var(--border-color);
        }

        .header-section {
            background: linear-gradient(135deg, #206bc4 0%, #1a5490 100%);
            color: white;
            padding: 1rem 2rem;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            backdrop-filter: saturate(120%) blur(10px);
            transition: all 0.3s ease;
            box-shadow: 0 12px 30px rgba(32, 107, 196, 0.25);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom-left-radius: 24px;
            border-bottom-right-radius: 24px;
        }

        .header-section.scrolled {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: saturate(120%) blur(10px);
            padding: 0.65rem 2rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .header-section.scrolled .logo-container img {
            height: 40px;
            filter: brightness(0) saturate(100%);
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        /* Onda suave sob o header para transição moderna */
        .header-section::after {
            content: '';
            position: absolute;
            bottom: -18px;
            left: 0;
            right: 0;
            height: 48px;
            background: radial-gradient(60% 42px at 50% 0%, rgba(32,107,196,.18) 0%, rgba(32,107,196,0) 70%);
            filter: blur(8px);
            pointer-events: none;
        }

        .logo-container {
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
        }

        .logo-container img {
            max-width: 140px;
            height: 60px;
            filter: brightness(0) invert(1);
            transition: all 0.3s ease;
        }

        .theme-toggle {
            display: flex;
            align-items: center;
            position: relative;
        }

        #themeToggle {
            border: 1px solid rgba(255, 255, 255, 0.85);
            background: linear-gradient(180deg, rgba(255,255,255,.75) 0%, rgba(255,255,255,.55) 100%);
            color: #1e293b;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 1.2rem;
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 24px rgba(32, 107, 196, 0.25);
            border-radius: 14px;
        }

        #themeToggle:hover {
            border-color: rgba(255, 255, 255, 0.9);
            transform: translateY(-1px);
            box-shadow: 0 16px 32px rgba(32, 107, 196, 0.35);
        }

        #themeToggle:active {
            transform: scale(0.95);
        }

        [data-theme="dark"] #themeToggle {
            border: 1px solid rgba(255, 255, 255, 0.85);
            background: linear-gradient(180deg, rgba(255,255,255,.35) 0%, rgba(255,255,255,.25) 100%);
            color: #fbbf24;
        }

        [data-theme="dark"] #themeToggle:hover {
            border: 1px solid rgba(255, 255, 255, 0.96);
            background: rgba(255, 255, 255, 0.76);
            color: #f59e0b;
        }

        /* Animação suave para transição de ícones */
        #themeIcon {
            transition: all 0.3s ease;
        }

        /* Melhorias nos botões de fechar dos modais */
        .modal-header-dynamic .btn-close {
            background: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 48px !important;
            height: 100% !important;
            min-height: 48px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.3s ease !important;
            opacity: 1 !important;
            color: white !important;
            border-radius: 0 !important;
            position: relative !important;
        }

        .modal-header-dynamic .btn-close:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            opacity: 1 !important;
        }

        .modal-header-dynamic .btn-close:focus {
            box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.25) !important;
            outline: none !important;
        }

        /* Garantir que o X seja visível e bem dimensionado */
        .modal-header-dynamic .btn-close::before {
            content: "×" !important;
            font-size: 1.8rem !important;
            line-height: 1 !important;
            color: white !important;
            font-weight: bold !important;
        }

        /* Estilo específico para modal de imagem */
        #imagePreviewModal .btn-close {
            background: rgba(0, 0, 0, 0.5);
            color: white;
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1060;
        }

        #imagePreviewModal .btn-close:hover {
            background: rgba(0, 0, 0, 0.7);
        }

        .header-section.scrolled .logo-container img {
            max-width: 100px;
        }

        .parallax-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 140%;
            background: var(--parallax-bg);
            z-index: -1;
            will-change: transform;
        }

        /* Textura leve no fundo para profundidade */
        .parallax-bg::after {
            content: '';
            position: fixed;
            inset: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.06"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.06"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.06"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.06"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.06"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        /* Halo sutil ao redor do container principal */
        .main-container::before {
            content: '';
            position: absolute;
            top: -18px;
            left: 24px;
            right: 24px;
            height: 24px;
            border-radius: 24px;
            background: radial-gradient(50% 18px at 50% 100%, rgba(32,107,196,.14) 0%, rgba(32,107,196,0) 100%);
            filter: blur(8px);
            pointer-events: none;
        }

        /* Rodapé padrão – Grupo Barão */
        .site-footer {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            box-shadow: 0 10px 24px var(--shadow-color);
            padding: 1rem;
            text-align: center;
            color: var(--text-secondary);
            max-width: 1400px;
            margin: 2rem auto 1.5rem;
        }
        .site-footer .copyright { font-size: .9rem; }

        /* Modo escuro: header scrolled mais translúcido */
        [data-theme="dark"] .header-section.scrolled {
            background: rgba(17, 24, 39, 0.85);
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.35);
        }

        /* Modo escuro: manter logo branco durante a rolagem */
        [data-theme="dark"] .header-section.scrolled .logo-container img {
            filter: brightness(0) invert(1);
        }



        .dashboard-grid {
            padding: 2rem;
            gap: 1.5rem;
        }

        .dashboard-card {
            background: var(--bg-primary);
            border-radius: 16px;
            box-shadow: 0 4px 20px var(--shadow-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--border-color);
            overflow: hidden;
            position: relative;
            cursor: pointer;
            height: 140px;
            color: var(--text-primary);
            min-height: 140px;
            max-height: 140px;
            display: flex;
            align-items: center;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-color, #206bc4);
            transition: height 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .dashboard-card:hover::before {
            height: 8px;
        }

        .card-body {
            padding: 1rem;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            gap: 0.6rem;
            box-sizing: border-box;
        }

        .card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            max-width: 56px;
            max-height: 56px;
            margin: 0 auto;
            border-radius: 12px;
            background: rgba(var(--card-color-rgb, 32, 107, 196), 0.1);
            flex-shrink: 0;
            position: relative;
            transition: all 0.3s ease;
        }

        .card-icon i {
            font-size: 1.8rem !important;
            color: var(--card-color, #206bc4) !important;
            display: flex !important;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            transition: all 0.3s ease;
            opacity: 1 !important;
            margin: 0;
        }

        .card-icon svg {
            width: 32px !important;
            height: 32px !important;
            color: var(--card-color, #206bc4) !important;
            stroke: currentColor;
            fill: none;
            transition: all 0.3s ease;
            opacity: 1 !important;
            margin: 0 auto;
            display: block;
        }

        .card-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            line-height: 1.2;
            text-align: center;
            min-height: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .card-description {
            font-size: 0.82rem;
            color: var(--text-secondary);
            line-height: 1.3;
            margin: 0;
            text-align: center;
            opacity: 0.8;
            max-height: 2.6rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            flex: 0 0 auto;
        }

        /* Garantir que os ícones sejam visíveis em ambos os temas */
        [data-theme="dark"] .card-icon {
            background: rgba(var(--card-color-rgb, 32, 107, 196), 0.2);
            border: 1px solid rgba(var(--card-color-rgb, 32, 107, 196), 0.3);
        }

        [data-theme="dark"] .card-icon i {
            color: var(--card-color, #206bc4) !important;
            filter: brightness(1.2);
        }

        [data-theme="dark"] .card-icon svg {
            color: var(--card-color, #206bc4) !important;
            filter: brightness(1.2);
        }

        /* Hover effects para melhor interação */
        .dashboard-card:hover .card-icon {
            background: rgba(var(--card-color-rgb, 32, 107, 196), 0.2);
            transform: scale(1.05);
        }

        .dashboard-card:hover .card-icon i {
            color: var(--card-color, #206bc4) !important;
            transform: scale(1.1);
        }

        .dashboard-card:hover .card-icon svg {
            color: var(--card-color, #206bc4) !important;
            transform: scale(1.1);
        }

        [data-theme="dark"] .dashboard-card:hover .card-icon {
            background: rgba(var(--card-color-rgb, 32, 107, 196), 0.3);
            border-color: rgba(var(--card-color-rgb, 32, 107, 196), 0.5);
        }

        /* Melhorar o layout dos cartões */
        .dashboard-card {
            background: var(--bg-primary);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px var(--shadow-color);
            transition: all 0.3s ease;
            cursor: pointer;
            height: 160px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--card-color, #206bc4);
            opacity: 0.8;
        }

        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px var(--shadow-color);
        }

        .card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            height: 100%;
            position: relative;
        }

        /* Cores específicas para modo escuro - melhoradas */
        [data-theme="dark"] .dashboard-card {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        [data-theme="dark"] .card-title {
            color: #ffffff !important;
        }

        [data-theme="dark"] .card-description {
            color: #e2e8f0 !important;
            opacity: 0.9;
        }

        .news-section {
            background: var(--bg-primary);
            padding: 2rem;
            border-top: 1px solid var(--border-color);
            border: 1px solid var(--border-color);
        }

        .news-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 2rem;
        }

        .news-card {
            background: var(--bg-primary);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px var(--shadow-color);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .news-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .news-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid var(--border-color);
        }

        .news-content {
            padding: 1.25rem 1.25rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex: 1 1 auto;
            min-height: 180px;
        }

        .news-date {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
        }

        .news-excerpt {
            color: var(--text-primary);
            line-height: 1.5;
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 3.6em; /* garante altura mínima para 3 linhas */
        }

        /* Cores específicas para notícias no modo escuro */
        [data-theme="dark"] .news-title {
            color: #ffffff !important;
        }

        [data-theme="dark"] .news-date {
            color: #e2e8f0 !important;
        }

        [data-theme="dark"] .news-excerpt {
            color: #ffffff !important;
        }

        [data-theme="dark"] .news-image {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-news {
            background: linear-gradient(135deg, #206bc4 0%, #1a5490 100%);
            border: none;
            border-radius: 8px;
            height: 42px; /* altura padronizada */
            padding: 0 16px; /* padding horizontal apenas */
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%; /* largura consistente */
            margin-top: auto; /* fixa no rodapé do card */
        }

        .btn-news:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(32, 107, 196, 0.3);
        }

        /* Subtítulo das colunas de notícia */
        .news-subtitle {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .news-subtitle-badge {
            display: inline-block;
            padding: 0.35rem 0.9rem;
            border-radius: 999px;
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
        }

        /* Modo escuro: títulos de subtítulo (Notícias e Atualizações Maxtrade) em branco */
        [data-theme="dark"] .news-subtitle {
            color: #ffffff !important;
        }
        [data-theme="dark"] .news-subtitle-badge {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.12) !important;
            border-color: rgba(255, 255, 255, 0.35) !important;
        }

        .news-category {
            border-radius: 16px;
            padding: 1.25rem 1rem 1rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 6px 18px var(--shadow-color);
        }

        .news-category-portal {
            background: linear-gradient(180deg, rgba(32,107,196,.10) 0%, rgba(32,107,196,.04) 100%);
            border-color: rgba(32,107,196,.25);
        }

        .news-category-maxtrade {
            background: linear-gradient(180deg, rgba(111,66,193,.12) 0%, rgba(111,66,193,.05) 100%);
            border-color: rgba(111,66,193,.25);
        }

        .news-category-portal .news-subtitle-badge {
            color: #206bc4;
            background: rgba(32,107,196,.12);
            border-color: rgba(32,107,196,.35);
        }

        .news-category-maxtrade .news-subtitle-badge {
            color: #6f42c1;
            background: rgba(111,66,193,.12);
            border-color: rgba(111,66,193,.35);
        }

        /* Modo escuro: badges em branco nas duas categorias */
        [data-theme="dark"] .news-category-portal .news-subtitle-badge,
        [data-theme="dark"] .news-category-maxtrade .news-subtitle-badge {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.12) !important;
            border-color: rgba(255, 255, 255, 0.35) !important;
        }

        /* Dark mode adjustments */
        [data-theme="dark"] .news-category-portal {
            background: linear-gradient(180deg, rgba(32,107,196,.20) 0%, #1f2937 100%);
            border-color: rgba(32,107,196,.38);
        }
        [data-theme="dark"] .news-category-maxtrade {
            background: linear-gradient(180deg, rgba(111,66,193,.22) 0%, #1f2937 100%);
            border-color: rgba(111,66,193,.38);
        }

        /* Cores por categoria (Portal e Maxtrade) mantendo identidade visual */
        .news-card-portal {
            background: linear-gradient(180deg, rgba(32, 107, 196, 0.08) 0%, var(--bg-primary) 35%);
            border-color: rgba(32, 107, 196, 0.25);
        }

        .news-card-portal .news-date {
            color: #206bc4;
            font-weight: 600;
        }

        .news-card-portal .btn-news {
            background: linear-gradient(135deg, #206bc4 0%, #1a5490 100%);
        }

        .news-card-maxtrade {
            background: linear-gradient(180deg, rgba(111, 66, 193, 0.10) 0%, var(--bg-primary) 35%);
            border-color: rgba(111, 66, 193, 0.25);
        }

        .news-card-maxtrade .news-date {
            color: #6f42c1;
            font-weight: 600;
        }

        .news-card-maxtrade .btn-news {
            background: linear-gradient(135deg, #6f42c1 0%, #4b2a82 100%);
        }

        /* Dark mode adjustments for category styles */
        [data-theme="dark"] .news-card-portal {
            background: linear-gradient(180deg, rgba(32, 107, 196, 0.18) 0%, #1f2937 35%);
            border-color: rgba(32, 107, 196, 0.35);
        }

        [data-theme="dark"] .news-card-maxtrade {
            background: linear-gradient(180deg, rgba(111, 66, 193, 0.20) 0%, #1f2937 35%);
            border-color: rgba(111, 66, 193, 0.35);
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 60px var(--shadow-color);
            background: var(--bg-primary);
        }

        .modal-header {
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 1.5rem 2rem;
            border-bottom: none;
        }

        /* Estilo padrão para modais sem cor dinâmica */
        .modal-header:not(.modal-header-dynamic) {
            background: linear-gradient(135deg, #206bc4 0%, #1a5490 100%);
        }

        /* Garante que o estilo inline dos modais dinâmicos tenha prioridade */
        .modal-header-dynamic {
            /* Remove o background padrão mas permite que o JavaScript aplique o estilo */
            min-height: 60px !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }

        /* Permite cores específicas para elementos do cabeçalho dinâmico */
        .modal-header-dynamic .modal-title {
            /* Cor será definida inline conforme necessário */
            flex: 1 !important;
            padding: 1rem !important;
            margin: 0 !important;
            display: flex !important;
            align-items: center !important;
            font-weight: 600 !important;
            font-size: 1.25rem !important;
            line-height: 1.2 !important;
        }

        /* Ícone dentro do título do modal */
        .modal-header-dynamic .modal-title i {
            font-size: 2.5rem !important;
            margin-right: 0.75rem !important;
            margin-left: 0 !important;
            padding: 0 !important;
            flex: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 3rem !important;
            height: 3rem !important;
            line-height: 1 !important;
        }



        .modal-body {
            padding: 2rem;
        }

        .submenu-card {
            background: var(--bg-primary);
            border-radius: 12px;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-primary);
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1rem;
        }

        .submenu-card:hover {
            border-color: var(--card-color, #206bc4);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--shadow-color);
            color: var(--text-primary);
            text-decoration: none;
        }

        .submenu-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--card-color, #206bc4);
        }

        .submenu-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .submenu-desc {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin: 0;
        }

        /* Dark mode specific styles for modal cards */
        [data-theme="dark"] .submenu-card {
            background: #334155;
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .submenu-card:hover {
            background: #475569;
            border-color: var(--card-color, #206bc4);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }

        /* Icon fixes and fallbacks */
        .ti {
            display: inline-block;
            width: 1em;
            height: 1em;
            vertical-align: middle;
            font-style: normal;
            font-weight: normal;
            font-variant: normal;
            text-transform: none;
            line-height: 1;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Ícones dos Cards */
        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            position: relative;
        }
        
        .card-icon i {
            font-size: 2rem;
            color: white;
            line-height: 1;
        }
        

        
        /* Ícones em modais */
        .modal-title i {
            font-size: 1.5rem !important;
            margin-right: 0.5rem !important;
            color: inherit !important;
            font-style: normal !important;
        }
        
        /* Ícones em submenus */
        .submenu-icon i {
            font-size: 1.25rem !important;
            margin-right: 0.5rem !important;
            color: inherit !important;
            font-style: normal !important;
        }

        .modal-header .ti {
            color: white !important;
        }

        .submenu-icon .ti {
            color: var(--card-color, #206bc4) !important;
        }

        /* Dark mode modal fixes */
        [data-theme="dark"] .modal-content {
            background: var(--bg-primary);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .modal-body {
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        [data-theme="dark"] .modal-footer {
            background: var(--bg-primary);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .modal-footer .btn-secondary {
            background: #475569;
            border-color: #475569;
            color: white;
        }

        [data-theme="dark"] .modal-footer .btn-secondary:hover {
            background: #64748b;
            border-color: #64748b;
        }

        /* Alert Styles */
        .alert-item {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .alert-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .alert-img:hover {
            transform: scale(1.05);
        }

        .alert-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .alert-message {
            color: #475569;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        /* Forçar exibição correta dos ícones Tabler */
        .ti {
            font-style: normal !important;
            font-weight: normal !important;
            font-variant: normal !important;
            text-transform: none !important;
            line-height: 1 !important;
            -webkit-font-smoothing: antialiased !important;
            -moz-osx-font-smoothing: grayscale !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            width: 1em;
            height: 1em;
            vertical-align: -0.125em;
        }

        /* Garantir que os ícones SVG sejam exibidos corretamente */
        .ti::before {
            content: "";
            display: inline-block;
            width: 1em;
            height: 1em;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }

        /* Forçar exibição dos ícones em todas as áreas */
        .card-icon .ti,
        .modal-title .ti,
        .submenu-icon .ti {
            display: inline-flex !important;
            font-style: normal !important;
            align-items: center;
            justify-content: center;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                margin: 1rem;
                border-radius: 16px;
            }

            .header-title {
                font-size: 2rem;
            }

            .dashboard-grid {
                padding: 1rem;
                gap: 1rem;
            }

            .dashboard-card {
                height: 140px;
            }

            .card-body {
                padding: 1rem;
            }

            .card-icon {
                width: 50px;
                height: 50px;
                margin-bottom: 0.75rem;
            }

            .card-icon i {
                font-size: 1.5rem !important;
            }

            .card-title {
                font-size: 1rem;
                min-height: 2rem;
            }

            .card-description {
                font-size: 0.8rem;
            }

            .news-section {
                padding: 1rem;
            }
        }

        @media (max-width: 576px) {
            .dashboard-card {
                height: 120px;
            }

            .card-icon {
                width: 40px;
                height: 40px;
                margin-bottom: 0.5rem;
            }

            .card-icon i {
                font-size: 1.3rem !important;
            }

            .card-title {
                font-size: 0.9rem;
                min-height: 1.8rem;
            }

            .card-description {
                font-size: 0.75rem;
                max-height: 2.4rem;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dashboard-card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .dashboard-card:nth-child(1) { animation-delay: 0.1s; }
        .dashboard-card:nth-child(2) { animation-delay: 0.2s; }
        .dashboard-card:nth-child(3) { animation-delay: 0.3s; }
        .dashboard-card:nth-child(4) { animation-delay: 0.4s; }
        .dashboard-card:nth-child(5) { animation-delay: 0.5s; }
        .dashboard-card:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <!-- Parallax Background -->
    <div class="parallax-bg"></div>

    <!-- Fixed Header -->
    <div class="header-section" id="header">
        <div class="logo-container">
            <img src="assets/img/avatars/logo-cores.png" alt="Logo Grupo Barão">
        </div>
        <div class="theme-toggle">
            <button id="themeToggle" class="btn btn-outline-light" title="Alternar tema">
                <i class="ti ti-sun" id="themeIcon"></i>
            </button>
        </div>
    </div>

    <!-- Inclui o arquivo com os modais (report_modal.php) -->
    <?php include 'report_modal.php'; ?>

    <div class="main-container">

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <div class="row g-3">
                <?php
                $modalCount = 1;
                $colorIndex = 0;
                foreach ($menuTree as $parentLink):
                    $titulo = $parentLink['titulo'];
                    $descricao = $parentLink['descricao'];
                    $url = $parentLink['url'];
                    $cor = $parentLink['cor'] ?: $tablerColors[$colorIndex % count($tablerColors)];
                    // Garantir que o ícone seja aplicado corretamente
                    $icone = !empty($parentLink['icone']) ? trim($parentLink['icone']) : 'ti ti-apps';
                    

                    $target = $parentLink['target_blank'] ? "_blank" : "_self";
                    $hasChildren = !empty($parentLink['children']);
                    // Usa o tamanho configurado no banco ou padrão
                    $sizeClass = $parentLink['tamanho'] ?: 'col-lg-3 col-md-6';
                    $colorIndex++;
                ?>
                <div class="<?php echo $sizeClass; ?>">
                    <?php if ($hasChildren || empty($url)):
                        $modalID = "modalLink{$modalCount}";
                        $modalCount++;
                    ?>
                        <div class="dashboard-card" 
                             style="--card-color: <?php echo $cor; ?>; --card-color-rgb: <?php 
                                $hex = str_replace('#', '', $cor);
                                $r = hexdec(substr($hex, 0, 2));
                                $g = hexdec(substr($hex, 2, 2));
                                $b = hexdec(substr($hex, 4, 2));
                                echo "$r, $g, $b";
                             ?>;"
                             data-bs-toggle="modal" 
                             data-bs-target="#<?php echo $modalID; ?>">
                            <div class="card-body">
                                <div class="card-icon">
                                    <?php echo getIconSvg($icone); ?>
                                </div>
                                <h3 class="card-title"><?php echo htmlspecialchars($titulo); ?></h3>
                                <p class="card-description"><?php echo htmlspecialchars($descricao); ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo $url; ?>" target="<?php echo $target; ?>" style="text-decoration: none;">
                            <div class="dashboard-card" style="--card-color: <?php echo $cor; ?>; --card-color-rgb: <?php 
                                $hex = str_replace('#', '', $cor);
                                $r = hexdec(substr($hex, 0, 2));
                                $g = hexdec(substr($hex, 2, 2));
                                $b = hexdec(substr($hex, 4, 2));
                                echo "$r, $g, $b";
                             ?>;">
                                <div class="card-body">
                                    <div class="card-icon">
                                        <?php echo getIconSvg($icone); ?>
                                    </div>
                                    <h3 class="card-title"><?php echo htmlspecialchars($titulo); ?></h3>
                                    <p class="card-description"><?php echo htmlspecialchars($descricao); ?></p>
                                </div>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- News Section -->
        <div class="news-section">
            <h2 class="news-title">Atualizações</h2>
            <div class="row g-4">
                <!-- Coluna Esquerda: Portal (2 notícias) -->
                <div class="col-lg-6">
                    <div class="news-category news-category-portal">
                        <div class="news-subtitle"><span class="news-subtitle-badge">Blog do TI</span></div>
                        <div class="row g-4">
                        <?php if (!empty($noticias)): ?>
                            <?php foreach ($noticias as $noticia): ?>
                                <div class="col-md-6">
                                    <div class="news-card news-card-portal">
                                        <?php if (!empty($noticia['imagem'])): ?>
                                            <img src="<?php echo htmlspecialchars($noticia['imagem']); ?>"
                                                 alt="Imagem da Notícia"
                                                 class="news-image">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                                <i class="ti ti-news" style="font-size: 3rem; color: #64748b;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="news-content">
                                            <div class="news-date">
                                                <?php echo date('d/m/Y', strtotime($noticia['data_publicacao'])); ?>
                                            </div>
                                            <h5 class="card-title"><?php echo htmlspecialchars($noticia['titulo']); ?></h5>
                                            <p class="news-excerpt">
                                                <?php echo mb_strimwidth(strip_tags($noticia['conteudo'] ?? ''), 0, 100, '...'); ?>
                                            </p>
                                            <a href="blog_post.php?id=<?php echo $noticia['id']; ?>&from=index"
                                               class="btn btn-primary btn-news"
                                               target="_blank">
                                                Continuar Lendo
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-muted text-center">Nenhuma notícia do Portal disponível no momento.</p>
                            </div>
                        <?php endif; ?>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="blog.php" class="btn btn-primary btn-sm">Ver mais do Portal</a>
                        </div>
                    </div>
                </div>

                <!-- Coluna Direita: Maxtrade (2 notícias) -->
                <div class="col-lg-6">
                    <div class="news-category news-category-maxtrade">
                        <div class="news-subtitle"><span class="news-subtitle-badge">Atualizações Maxtrade</span></div>
                        <div class="row g-4">
                        <?php if (!empty($maxtradeNews)): ?>
                            <?php foreach ($maxtradeNews as $news): ?>
                                <div class="col-md-6">
                                    <div class="news-card news-card-maxtrade">
                                        <?php if (!empty($news['imagem'])): ?>
                                            <img src="<?php echo htmlspecialchars($news['imagem']); ?>"
                                                 alt="Imagem da Notícia"
                                                 class="news-image">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                                <i class="ti ti-news" style="font-size: 3rem; color: #64748b;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="news-content">
                                            <div class="news-date">
                                                <?php echo date('d/m/Y', strtotime($news['data_publicacao'])); ?>
                                            </div>
                                            <h5 class="card-title"><?php echo htmlspecialchars($news['titulo']); ?></h5>
                                            <p class="news-excerpt">
                                                <?php echo mb_strimwidth(strip_tags($news['conteudo'] ?? ''), 0, 100, '...'); ?>
                                            </p>
                                            <a href="blog_post.php?id=<?php echo $news['id']; ?>&from=index"
                                               class="btn btn-primary btn-news"
                                               target="_blank">
                                                Continuar Lendo
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-muted text-center">Nenhuma atualização do Maxtrade disponível no momento.</p>
                            </div>
                        <?php endif; ?>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="blog-maxtrade.php" class="btn btn-outline-primary btn-sm">Ver mais do Maxtrade</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals for Submenus -->
    <?php
    $modalCount = 1;
    $colorIndex = 0;
    foreach ($menuTree as $parentLink):
        if (!empty($parentLink['children'])):
            $modalID = "modalLink{$modalCount}";
            $modalCount++;
            $cor = $parentLink['cor'] ?: $tablerColors[$colorIndex % count($tablerColors)];
            $colorIndex++;
    ?>
    <div class="modal fade" id="<?php echo $modalID; ?>" tabindex="-1" aria-hidden="true" data-modal-color="<?php echo $cor; ?>">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header modal-header-dynamic" style="background-color: <?php echo $cor; ?>; color: white;">
                    <h5 class="modal-title" style="color: white;">
                        <span class="me-2" style="color: white;"><?php echo getIconSvg($parentLink['icone'] ?: 'ti ti-apps'); ?></span>
                        <?php echo htmlspecialchars($parentLink['titulo']); ?>
                        <?php if (!empty($parentLink['descricao'])): ?>
                            - <?php echo htmlspecialchars($parentLink['descricao']); ?>
                        <?php endif; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <?php foreach ($parentLink['children'] as $child):
                            $childTitulo = $child['titulo'];
                            $childDescricao = $child['descricao'];
                            $childURL = $child['url'];
                            $childCor = $child['cor'] ?: $cor;
                            $childIcone = $child['icone'] ?: 'ti ti-link';
                            $childTarget = $child['target_blank'] ? "_blank" : "_self";
                            // Usa o tamanho configurado no banco ou padrão para submenus
                            $childTamanho = $child['tamanho'] ?: 'col-lg-3 col-md-6';
                        ?>
                        <div class="<?php echo $childTamanho; ?>">
                            <?php if (!empty($childURL)): ?>
                                <a href="<?php echo $childURL; ?>" 
                                   target="<?php echo $childTarget; ?>" 
                                   class="submenu-card"
                                   style="--card-color: <?php echo $childCor; ?>;">
                                    <div>
                                        <div class="submenu-icon">
                                            <?php echo getIconSvg($childIcone); ?>
                                        </div>
                                        <div class="submenu-title"><?php echo htmlspecialchars($childTitulo); ?></div>
                                        <div class="submenu-desc"><?php echo htmlspecialchars($childDescricao); ?></div>
                                    </div>
                                </a>
                            <?php else: ?>
                                <div class="submenu-card" style="--card-color: <?php echo $childCor; ?>;">
                                    <div>
                                        <div class="submenu-icon">
                                            <?php echo getIconSvg($childIcone); ?>
                                        </div>
                                        <div class="submenu-title"><?php echo htmlspecialchars($childTitulo); ?></div>
                                        <div class="submenu-desc"><?php echo htmlspecialchars($childDescricao); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
        endif;
    endforeach;
    ?>

    <!-- Alerts Modal -->
    <?php if (!empty($alerts)): ?>
    <div class="modal fade" id="alertsModal" tabindex="-1" aria-labelledby="alertsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title w-100 text-center" id="alertsModalLabel">
                        <i class="ti ti-bell me-2"></i>
                        Alertas Importantes
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <!-- Removidas notícias do Maxtrade; modal exibe apenas alertas -->

                    <?php foreach ($alerts as $alert): ?>
                        <div class="alert-item d-flex align-items-start">
                            <?php if (!empty($alert['image'])): ?>
                                <img src="uploads_alertas/<?php echo htmlspecialchars($alert['image']); ?>"
                                     alt="ilustração"
                                     class="alert-img me-3"
                                     onclick="openImageModal('uploads_alertas/<?php echo htmlspecialchars($alert['image']); ?>')">
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <h4 class="alert-title"><?php echo htmlspecialchars($alert['title']); ?></h4>
                                <div class="alert-message"><?php echo $alert['message']; ?></div>
                                <?php if (!empty($alert['file_path'])): ?>
                                    <a href="uploads_alertas/<?php echo htmlspecialchars($alert['file_path']); ?>" 
                                       download 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-download me-1"></i>
                                        Baixar anexo
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="previewImage" src="" class="img-fluid rounded shadow" alt="imagem ampliada">
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabler JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Auto-show alerts modal if there are alerts
            <?php if (!empty($alerts)): ?>
            var alertsModal = new bootstrap.Modal(document.getElementById('alertsModal'));
            alertsModal.show();
            <?php endif; ?>

            // Show success modal if incident message exists
            <?php if (!empty($incidentMessage)): ?>
            var successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();

            document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
                    backdrop.remove();
                });
            });
            <?php endif; ?>

            // Parallax and header scroll effects
            const parallaxBg = document.querySelector('.parallax-bg');
            const header = document.getElementById('header');
            let ticking = false;

            function updateParallax() {
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;
                
                // Parallax effect
                parallaxBg.style.transform = `translateY(${rate}px)`;
                
                // Header scroll effect
                if (scrolled > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
                
                ticking = false;
            }

            function requestTick() {
                if (!ticking) {
                    requestAnimationFrame(updateParallax);
                    ticking = true;
                }
            }

            window.addEventListener('scroll', requestTick);

            // Dark mode functionality
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const body = document.body;

            // Check for saved theme preference or default to light mode
            const currentTheme = localStorage.getItem('theme') || 'light';
            body.setAttribute('data-theme', currentTheme);
            updateThemeIcon(currentTheme);

            themeToggle.addEventListener('click', function() {
                const currentTheme = body.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                body.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
                
                // Atualiza modais abertos quando o tema muda
                updateOpenModalsColors();
            });

            // Função para atualizar cores dos modais abertos quando o tema muda
            function updateOpenModalsColors() {
                const openModals = document.querySelectorAll('.modal.show');
                openModals.forEach(modal => {
                    const modalColor = modal.getAttribute('data-modal-color');
                    if (modalColor && modalColor !== 'null' && modalColor !== '') {
                        const modalHeader = modal.querySelector('.modal-header-dynamic');
                        if (modalHeader) {
                            const adaptiveTextColor = getAdaptiveTextColor(modalColor);
                            
                            // Atualiza a cor do texto
                            modalHeader.style.setProperty('color', adaptiveTextColor, 'important');
                            
                            // Atualiza elementos filhos
                            const modalTitle = modalHeader.querySelector('.modal-title');
                            const modalIcons = modalHeader.querySelectorAll('i');
                            const closeButton = modalHeader.querySelector('.btn-close');
                            
                            if (modalTitle) modalTitle.style.setProperty('color', adaptiveTextColor, 'important');
                            modalIcons.forEach(icon => icon.style.setProperty('color', adaptiveTextColor, 'important'));
                            if (closeButton) closeButton.style.setProperty('color', adaptiveTextColor, 'important');
                        }
                    }
                });
            }

            function updateThemeIcon(theme) {
                if (theme === 'dark') {
                    themeIcon.className = 'ti ti-moon';
                } else {
                    themeIcon.className = 'ti ti-sun';
                }
            }

            // Função para converter cor RGB para luminância
            function getLuminance(r, g, b) {
                const [rs, gs, bs] = [r, g, b].map(c => {
                    c = c / 255;
                    return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
                });
                return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
            }

            // Função para calcular contraste entre duas cores
            function getContrastRatio(color1, color2) {
                const lum1 = getLuminance(color1.r, color1.g, color1.b);
                const lum2 = getLuminance(color2.r, color2.g, color2.b);
                const brightest = Math.max(lum1, lum2);
                const darkest = Math.min(lum1, lum2);
                return (brightest + 0.05) / (darkest + 0.05);
            }

            // Função para extrair valores RGB de uma string de cor
            function parseColor(colorStr) {
                if (colorStr.startsWith('rgb(')) {
                    const values = colorStr.match(/\d+/g);
                    return {
                        r: parseInt(values[0]),
                        g: parseInt(values[1]),
                        b: parseInt(values[2])
                    };
                } else if (colorStr.startsWith('#')) {
                    const hex = colorStr.slice(1);
                    return {
                        r: parseInt(hex.slice(0, 2), 16),
                        g: parseInt(hex.slice(2, 4), 16),
                        b: parseInt(hex.slice(4, 6), 16)
                    };
                }
                return { r: 0, g: 0, b: 0 };
            }

            // Função para determinar a cor do texto baseada no tema e contraste
            function getAdaptiveTextColor(backgroundColor) {
                // Sempre retorna branco para o texto do modal
                return 'white';
            }

            // Modal color update functionality
            document.addEventListener('show.bs.modal', function (event) {
                const modal = event.target;
                
                // Obtém a cor do modal do atributo data-modal-color
                const modalColor = modal.getAttribute('data-modal-color');
                
                if (modalColor && modalColor !== 'null' && modalColor !== '') {
                    const modalHeader = modal.querySelector('.modal-header-dynamic');
                    if (modalHeader) {
                        
                        // Remove qualquer estilo anterior
                        modalHeader.style.removeProperty('background');
                        modalHeader.style.removeProperty('background-image');
                        modalHeader.style.removeProperty('background-color');
                        modalHeader.style.removeProperty('color');
                        
                        // Aplica a cor como fundo do modal
                        modalHeader.style.setProperty('background-color', modalColor, 'important');
                        
                        // Determina automaticamente a cor do texto baseada no tema e contraste
                        const adaptiveTextColor = getAdaptiveTextColor(modalColor);
                        
                        // Aplica a cor adaptiva ao texto
                        modalHeader.style.setProperty('color', adaptiveTextColor, 'important');
                        
                        // Garante que os ícones e textos tenham a cor adaptiva
                        const modalTitle = modalHeader.querySelector('.modal-title');
                        const modalIcons = modalHeader.querySelectorAll('i');
                        const closeButton = modalHeader.querySelector('.btn-close');
                        
                        if (modalTitle) modalTitle.style.setProperty('color', adaptiveTextColor, 'important');
                        modalIcons.forEach(icon => icon.style.setProperty('color', adaptiveTextColor, 'important'));
                        if (closeButton) closeButton.style.setProperty('color', adaptiveTextColor, 'important');
                    }
                }
            });
        });

        function openImageModal(imageSrc) {
            const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
            document.getElementById('previewImage').src = imageSrc;
            modal.show();
        }

        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
    <footer class="site-footer d-print-none">
        <div class="container-xl text-center">
            <small class="copyright">© <?php echo date('Y'); ?> Grupo Barão • Todos os direitos reservados</small>
        </div>
    </footer>
</body>
</html>
