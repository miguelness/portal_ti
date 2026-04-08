<?php
/**
 * API para Gestão de Notificações Administrativas
 */
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Apenas usuários logados no admin podem acessar
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_accesses = $_SESSION['acessos'] ?? [];
$is_super = in_array('Super Administrador', $user_accesses);

$action = $_GET['action'] ?? '';

try {
    if ($action === 'list_unread') {
        // Busca notificações que o usuário tem permissão para ver
        $query = "SELECT * FROM persistent_notifications WHERE is_read = 0 ";
        $params = [];
        
        if (!$is_super) {
            $placeholders = [];
            foreach ($user_accesses as $i => $access) {
                $placeholders[] = ":access$i";
                $params[":access$i"] = $access;
            }
            if (!empty($placeholders)) {
                $query .= " AND (required_access IS NULL OR required_access IN (" . implode(',', $placeholders) . "))";
            } else {
                $query .= " AND required_access IS NULL";
            }
        }
        
        $query .= " ORDER BY created_at DESC LIMIT 10";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $notifications]);
        exit;
    } 
    
    if ($action === 'mark_read') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception('ID inválido');
        
        $stmt = $pdo->prepare("UPDATE persistent_notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
