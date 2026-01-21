<?php
/**
 * Script para criar a tabela do organograma
 * Executa uma única vez para configurar a estrutura do banco
 */

require_once 'admin/config.php';

try {
    // SQL para criar a tabela organograma
    $sql = "CREATE TABLE IF NOT EXISTS organograma (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        cargo VARCHAR(255) NOT NULL,
        departamento VARCHAR(255) NOT NULL,
        tipo_contrato ENUM('CLT', 'PJ', 'Aprendiz', 'Terceirizado') DEFAULT 'CLT',
        parent_id INT NULL,
        nivel_hierarquico INT NOT NULL DEFAULT 1,
        ordem_exibicao INT DEFAULT 0,
        email VARCHAR(255) NULL,
        telefone VARCHAR(20) NULL,
        foto VARCHAR(255) NULL,
        descricao TEXT NULL,
        ativo TINYINT(1) DEFAULT 1,
        data_admissao DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_parent_id (parent_id),
        INDEX idx_departamento (departamento),
        INDEX idx_nivel (nivel_hierarquico),
        INDEX idx_ativo (ativo),
        
        FOREIGN KEY (parent_id) REFERENCES organograma(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    
    echo "<h2>✅ Tabela 'organograma' criada com sucesso!</h2>";
    
    // Verificar se a tabela foi criada
    $check = $pdo->query("SHOW TABLES LIKE 'organograma'");
    if ($check->rowCount() > 0) {
        echo "<p>✅ Tabela confirmada no banco de dados.</p>";
        
        // Mostrar estrutura da tabela
        echo "<h3>Estrutura da tabela:</h3>";
        $structure = $pdo->query("DESCRIBE organograma");
        echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background: #f5f5f5;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Próximos passos:</h3>";
        echo "<ul>";
        echo "<li>✅ Tabela criada com estrutura hierárquica</li>";
        echo "<li>⏳ Implementar interface administrativa</li>";
        echo "<li>⏳ Criar organograma interativo público</li>";
        echo "<li>⏳ Importar dados do organogramas.md</li>";
        echo "</ul>";
        
    } else {
        echo "<p>❌ Erro: Tabela não foi encontrada após criação.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Erro ao criar tabela:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Verificar se o erro é de tabela já existente
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "<p>ℹ️ A tabela já existe. Verificando estrutura...</p>";
        
        try {
            $structure = $pdo->query("DESCRIBE organograma");
            echo "<h3>Estrutura atual da tabela:</h3>";
            echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
            echo "<tr style='background: #f5f5f5;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            
            while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Default'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } catch (Exception $e2) {
            echo "<p>Erro ao verificar estrutura: " . htmlspecialchars($e2->getMessage()) . "</p>";
        }
    }
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
}

h2, h3 {
    color: #333;
}

table {
    width: 100%;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

th, td {
    padding: 12px;
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background: #f8f9fa;
    font-weight: bold;
}

ul {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

li {
    margin: 10px 0;
    padding: 5px 0;
}
</style>