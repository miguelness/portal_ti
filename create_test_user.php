<?php
require_once 'admin/config.php';

try {
    echo "=== CRIANDO USUÁRIO DE TESTE ===\n\n";
    
    // Verificar se já existe um usuário de teste
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['teste.admin']);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "✓ Usuário de teste já existe (ID: {$existingUser['id']})\n";
        $userId = $existingUser['id'];
    } else {
        // Criar usuário de teste
        $userData = [
            'username' => 'teste.admin',
            'nome' => 'Usuário Teste',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'role' => 'admin'
        ];
        
        $sql = "INSERT INTO users (username, nome, password, role) VALUES (:username, :nome, :password, :role)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($userData)) {
            $userId = $pdo->lastInsertId();
            echo "✓ Usuário de teste criado (ID: $userId)\n";
        } else {
            throw new Exception("Erro ao criar usuário: " . implode(', ', $stmt->errorInfo()));
        }
    }
    
    // Verificar se existe o acesso "Super Administrador"
    $stmt = $pdo->prepare("SELECT * FROM accesses WHERE access_name = ?");
    $stmt->execute(['Super Administrador']);
    $access = $stmt->fetch();
    
    if (!$access) {
        // Criar o acesso se não existir
        $stmt = $pdo->prepare("INSERT INTO accesses (access_name, description) VALUES (?, ?)");
        $stmt->execute(['Super Administrador', 'Acesso total ao sistema']);
        $accessId = $pdo->lastInsertId();
        echo "✓ Acesso 'Super Administrador' criado (ID: $accessId)\n";
    } else {
        $accessId = $access['id'];
        echo "✓ Acesso 'Super Administrador' já existe (ID: $accessId)\n";
    }
    
    // Verificar se o usuário já tem o acesso
    $stmt = $pdo->prepare("SELECT * FROM user_access WHERE user_id = ? AND access_id = ?");
    $stmt->execute([$userId, $accessId]);
    $userAccess = $stmt->fetch();
    
    if (!$userAccess) {
        // Dar acesso ao usuário
        $stmt = $pdo->prepare("INSERT INTO user_access (user_id, access_id) VALUES (?, ?)");
        $stmt->execute([$userId, $accessId]);
        echo "✓ Acesso concedido ao usuário\n";
    } else {
        echo "✓ Usuário já possui o acesso\n";
    }
    
    echo "\n=== CREDENCIAIS DE TESTE ===\n";
    echo "Username: teste.admin\n";
    echo "Senha: 123456\n";
    echo "\nAcesse: http://localhost:8000/admin/login.php\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>