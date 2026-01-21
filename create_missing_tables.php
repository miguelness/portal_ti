<?php
require_once 'admin/config.php';

try {
    echo "=== Criando Tabelas Faltantes ===\n";
    
    // Criar tabela colaborador_contatos
    $sql_contatos = "
    CREATE TABLE IF NOT EXISTS colaborador_contatos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        colaborador_id INT NOT NULL,
        tipo_contato VARCHAR(50) NOT NULL,
        valor VARCHAR(255) NOT NULL,
        descricao VARCHAR(255),
        principal BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql_contatos);
    echo "✓ Tabela 'colaborador_contatos' criada\n";
    
    // Criar tabela colaboradores_historico
    $sql_historico = "
    CREATE TABLE IF NOT EXISTS colaboradores_historico (
        id INT AUTO_INCREMENT PRIMARY KEY,
        colaborador_id INT NOT NULL,
        acao VARCHAR(50) NOT NULL,
        dados_anteriores JSON,
        dados_novos JSON,
        usuario_id INT,
        usuario_nome VARCHAR(255),
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql_historico);
    echo "✓ Tabela 'colaboradores_historico' criada\n";
    
    echo "\n=== Verificando Tabelas Criadas ===\n";
    
    // Verificar se as tabelas foram criadas
    $tables = ['colaborador_contatos', 'colaboradores_historico'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            echo "✓ Tabela '$table' existe agora\n";
        } else {
            echo "✗ Erro ao criar tabela '$table'\n";
        }
    }
    
    echo "\n✅ Processo concluído!\n";
    
} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
}
?>