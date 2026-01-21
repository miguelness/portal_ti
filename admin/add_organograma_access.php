<?php
/**
 * Script para adicionar a permissão 'Organograma' na tabela de acessos
 */

require_once 'config.php';

try {
    // Verificar se a permissão já existe
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM accesses WHERE access_name = ?");
    $check_stmt->execute(['Organograma']);
    $exists = $check_stmt->fetchColumn();
    
    if ($exists > 0) {
        echo "✓ Permissão 'Organograma' já existe na tabela de acessos.<br>";
    } else {
        // Inserir a nova permissão
        $insert_stmt = $pdo->prepare("INSERT INTO accesses (access_name) VALUES (?)");
        $insert_stmt->execute(['Organograma']);
        echo "✓ Permissão 'Organograma' adicionada com sucesso à tabela de acessos.<br>";
    }
    
    // Verificar se o Super Administrador já tem essa permissão
    $super_admin_check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM user_accesses ua 
        JOIN accesses a ON ua.access_id = a.id 
        JOIN users u ON ua.user_id = u.id 
        WHERE a.access_name = 'Organograma' AND u.username = 'admin'
    ");
    $super_admin_check->execute();
    $super_admin_has_access = $super_admin_check->fetchColumn();
    
    if ($super_admin_has_access > 0) {
        echo "✓ Super Administrador já possui acesso ao Organograma.<br>";
    } else {
        // Dar permissão ao Super Administrador
        $admin_user = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
        $admin_user->execute();
        $admin_id = $admin_user->fetchColumn();
        
        $organograma_access = $pdo->prepare("SELECT id FROM accesses WHERE access_name = 'Organograma'");
        $organograma_access->execute();
        $access_id = $organograma_access->fetchColumn();
        
        if ($admin_id && $access_id) {
            $grant_access = $pdo->prepare("INSERT INTO user_accesses (user_id, access_id) VALUES (?, ?)");
            $grant_access->execute([$admin_id, $access_id]);
            echo "✓ Permissão 'Organograma' concedida ao Super Administrador.<br>";
        }
    }
    
    echo "<br><strong>Configuração de permissões concluída!</strong><br>";
    echo "<a href='organograma_admin.php'>Acessar Administração do Organograma</a><br>";
    echo "<a href='accesses_admin.php'>Gerenciar Acessos de Usuários</a>";
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>