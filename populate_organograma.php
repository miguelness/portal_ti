<?php
/**
 * Script para popular a tabela organograma com dados iniciais
 * Baseado no arquivo organogramas.md
 */

require_once 'admin/config.php';

try {
    // Limpar tabela antes de popular (opcional)
    $pdo->exec("DELETE FROM organograma");
    $pdo->exec("ALTER TABLE organograma AUTO_INCREMENT = 1");
    
    // Dados do organograma estruturados hierarquicamente
    $organograma_data = [
        // Compras
        ['nome' => 'Sergio Cerqueira', 'cargo' => 'Gerente de Compras', 'departamento' => 'Compras', 'tipo_contrato' => 'CLT', 'parent_id' => null, 'nivel_hierarquico' => 1, 'ordem_exibicao' => 1],
        ['nome' => 'Sandra Silva', 'cargo' => 'Coordenadora de Compras', 'departamento' => 'Compras', 'tipo_contrato' => 'CLT', 'parent_id' => 1, 'nivel_hierarquico' => 2, 'ordem_exibicao' => 1],
        ['nome' => 'Fabricia Silveira', 'cargo' => 'Assistente de Compras Jr.', 'departamento' => 'Compras', 'tipo_contrato' => 'CLT', 'parent_id' => 2, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 1],
        ['nome' => 'Davi Cocentino', 'cargo' => 'Jovem Aprendiz', 'departamento' => 'Compras', 'tipo_contrato' => 'Aprendiz', 'parent_id' => 2, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 2],
        
        // Controladoria
        ['nome' => 'Betuel Lopes', 'cargo' => 'Controller', 'departamento' => 'Controladoria', 'tipo_contrato' => 'PJ', 'parent_id' => null, 'nivel_hierarquico' => 1, 'ordem_exibicao' => 2],
        ['nome' => 'Orlando Esau', 'cargo' => 'Contador', 'departamento' => 'Controladoria', 'tipo_contrato' => 'PJ', 'parent_id' => 5, 'nivel_hierarquico' => 2, 'ordem_exibicao' => 1],
        ['nome' => 'Edileuza Queiroz', 'cargo' => 'Analista Fiscal Sr.', 'departamento' => 'Controladoria', 'tipo_contrato' => 'CLT', 'parent_id' => 6, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 1],
        ['nome' => 'Tailila Santos', 'cargo' => 'Assistente Fiscal', 'departamento' => 'Controladoria', 'tipo_contrato' => 'CLT', 'parent_id' => 7, 'nivel_hierarquico' => 4, 'ordem_exibicao' => 1],
        ['nome' => 'Gabriel Pedrosa', 'cargo' => 'Analista Contábil Pl.', 'departamento' => 'Controladoria', 'tipo_contrato' => 'CLT', 'parent_id' => 6, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 2],
        ['nome' => 'Fernando Custódio', 'cargo' => 'Analista Contábil Jr.', 'departamento' => 'Controladoria', 'tipo_contrato' => 'CLT', 'parent_id' => 6, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 3],
        
        // Recursos Humanos
        ['nome' => 'Danielle Ness', 'cargo' => 'Diretoria', 'departamento' => 'Recursos Humanos', 'tipo_contrato' => 'CLT', 'parent_id' => null, 'nivel_hierarquico' => 1, 'ordem_exibicao' => 3],
        ['nome' => 'Josefina Camargo', 'cargo' => 'Gerente de Gestão de Pessoas', 'departamento' => 'Recursos Humanos', 'tipo_contrato' => 'CLT', 'parent_id' => 11, 'nivel_hierarquico' => 2, 'ordem_exibicao' => 1],
        ['nome' => 'Jefferson Cândido', 'cargo' => 'Manutenção Predial', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'CLT', 'parent_id' => 12, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 1],
        ['nome' => 'Celso Santana', 'cargo' => 'Oficial de Manutenção Predial', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'CLT', 'parent_id' => 13, 'nivel_hierarquico' => 4, 'ordem_exibicao' => 1],
        ['nome' => 'Gabriela Ludovico', 'cargo' => 'Assistente de R.H. Sr.', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'CLT', 'parent_id' => 12, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 2],
        ['nome' => 'Beatriz Sousa', 'cargo' => 'Auxiliar de R.H.', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'CLT', 'parent_id' => 15, 'nivel_hierarquico' => 4, 'ordem_exibicao' => 1],
        ['nome' => 'Patrícia Aparecida', 'cargo' => 'Recepcionista', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'CLT', 'parent_id' => 12, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 3],
        ['nome' => 'Vanessa Sena', 'cargo' => 'Assistente de R.H. Jr.', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'CLT', 'parent_id' => 12, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 4],
        ['nome' => 'Nicole Nascimento', 'cargo' => 'Jovem Aprendiz', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Aprendiz', 'parent_id' => 12, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 5],
        
        // T.I.
        ['nome' => 'André Mor', 'cargo' => 'Gerente de T.I.', 'departamento' => 'T.I.', 'tipo_contrato' => 'PJ', 'parent_id' => 5, 'nivel_hierarquico' => 2, 'ordem_exibicao' => 2],
        ['nome' => 'Luiz Rogério', 'cargo' => 'Coordenador de Infra T.I.', 'departamento' => 'T.I.', 'tipo_contrato' => 'PJ', 'parent_id' => 20, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 1],
        ['nome' => 'Jorge Domingos', 'cargo' => 'Supervisor de Infra', 'departamento' => 'T.I.', 'tipo_contrato' => 'CLT', 'parent_id' => 21, 'nivel_hierarquico' => 4, 'ordem_exibicao' => 1],
        ['nome' => 'Rafael Akiyama', 'cargo' => 'Assistente de Infra Pl.', 'departamento' => 'T.I.', 'tipo_contrato' => 'CLT', 'parent_id' => 22, 'nivel_hierarquico' => 5, 'ordem_exibicao' => 1],
        ['nome' => 'Matheus Ramires', 'cargo' => 'Programador', 'departamento' => 'T.I.', 'tipo_contrato' => 'CLT', 'parent_id' => 21, 'nivel_hierarquico' => 4, 'ordem_exibicao' => 2],
        ['nome' => 'Miguel Ness', 'cargo' => 'Analista de T.I. Pl.', 'departamento' => 'T.I.', 'tipo_contrato' => 'CLT', 'parent_id' => 20, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 2],
        
        // Financeiro
        ['nome' => 'Marcos Soares', 'cargo' => 'Gerente Financeiro', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 5, 'nivel_hierarquico' => 2, 'ordem_exibicao' => 3],
        ['nome' => 'Vanessa Zanatta', 'cargo' => 'Coordenadora Financeiro', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 26, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 1],
        ['nome' => 'Katia Souza', 'cargo' => 'Analista de Crédito Sr.', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 27, 'nivel_hierarquico' => 4, 'ordem_exibicao' => 1],
        ['nome' => 'Ana Paula da Silva', 'cargo' => 'Analista Financeiro Jr.', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 27, 'nivel_hierarquico' => 4, 'ordem_exibicao' => 2],
        ['nome' => 'Iara Menezes', 'cargo' => 'Analista Financeiro Pl.', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 27, 'nivel_hierarquico' => 4, 'ordem_exibicao' => 3],
        ['nome' => 'Diego Souza', 'cargo' => 'Assistente Financeiro', 'departamento' => 'Financeiro', 'tipo_contrato' => 'PJ', 'parent_id' => 27, 'nivel_hierarquico' => 4, 'ordem_exibicao' => 4],
        ['nome' => 'Daiana Carvalho', 'cargo' => 'Assistente de Cobrança Pl.', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 27, 'nivel_hierarquico' => 4, 'ordem_exibicao' => 5],
        ['nome' => 'Luami Oliveira', 'cargo' => 'Assistente Financeiro', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 27, 'nivel_hierarquico' => 4, 'ordem_exibicao' => 6],
        ['nome' => 'Bianca Espindola', 'cargo' => 'Assistente Financeiro Marketplace', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 27, 'nivel_hierarquico' => 4, 'ordem_exibicao' => 7],
        
        // Marketing
        ['nome' => 'Luiz Fiorinni', 'cargo' => 'Diretor de Marketing', 'departamento' => 'Marketing', 'tipo_contrato' => 'PJ', 'parent_id' => null, 'nivel_hierarquico' => 1, 'ordem_exibicao' => 4],
        ['nome' => 'Viviane Tamborim', 'cargo' => 'Gerente de Marketing (Comunicação)', 'departamento' => 'Marketing', 'tipo_contrato' => 'PJ', 'parent_id' => 35, 'nivel_hierarquico' => 2, 'ordem_exibicao' => 1],
        ['nome' => 'Thais Lizandra', 'cargo' => 'Analista de Marketing Pl.', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 36, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 1],
        ['nome' => 'Anderson Souto', 'cargo' => 'Designer Sr.', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 36, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 2],
        ['nome' => 'Beatriz Rodrigues', 'cargo' => 'Assistente de MKT Jr.', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 36, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 3],
        ['nome' => 'Diego Allegue', 'cargo' => 'Designer Pleno', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 36, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 4],
        ['nome' => 'Fernanda Couto', 'cargo' => 'Designer Jr.', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 36, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 5],
        ['nome' => 'Marcelo Martins', 'cargo' => 'Gerente de Produto e Importação', 'departamento' => 'Marketing', 'tipo_contrato' => 'PJ', 'parent_id' => 35, 'nivel_hierarquico' => 2, 'ordem_exibicao' => 2],
        ['nome' => 'Kelly Moura', 'cargo' => 'Analista de Importação Sr.', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 42, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 1],
        ['nome' => 'Sandra Maria', 'cargo' => 'Analista de Importação', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 42, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 2],
        ['nome' => 'Paulo Mendes', 'cargo' => 'Assistente de Importação', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 42, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 3],
        ['nome' => 'Eduardo Carvalho', 'cargo' => 'Gerente E-commerce', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 35, 'nivel_hierarquico' => 2, 'ordem_exibicao' => 3],
        
        // Comercial
        ['nome' => 'Gilson Pestana', 'cargo' => 'Diretor Nacional de Vendas', 'departamento' => 'Comercial', 'tipo_contrato' => 'CLT', 'parent_id' => null, 'nivel_hierarquico' => 1, 'ordem_exibicao' => 5],
        ['nome' => 'Vinicius Avila', 'cargo' => 'Gerente de Vendas', 'departamento' => 'Comercial', 'tipo_contrato' => 'PJ', 'parent_id' => 47, 'nivel_hierarquico' => 2, 'ordem_exibicao' => 1],
        ['nome' => 'William Vicentin', 'cargo' => 'Coordenador de Vendas', 'departamento' => 'Comercial', 'tipo_contrato' => 'PJ', 'parent_id' => 48, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 1],
        ['nome' => 'Ronny Ramos', 'cargo' => 'Key Account', 'departamento' => 'Comercial', 'tipo_contrato' => 'PJ', 'parent_id' => 48, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 2],
        ['nome' => 'Janaina Laura', 'cargo' => 'Coordenador de Vendas', 'departamento' => 'Comercial', 'tipo_contrato' => 'CLT', 'parent_id' => 47, 'nivel_hierarquico' => 2, 'ordem_exibicao' => 2],
        ['nome' => 'Marcelo Fadel', 'cargo' => 'Analista Adm. Comercial Sr.', 'departamento' => 'Comercial', 'tipo_contrato' => 'CLT', 'parent_id' => 47, 'nivel_hierarquico' => 2, 'ordem_exibicao' => 3],
        ['nome' => 'Yasmin Oliveira', 'cargo' => 'Assistente ADM Pleno', 'departamento' => 'Comercial', 'tipo_contrato' => 'CLT', 'parent_id' => 52, 'nivel_hierarquico' => 3, 'ordem_exibicao' => 1],
        ['nome' => 'Amanda Canobre', 'cargo' => 'Analista de Inteligência de Mercado', 'departamento' => 'Comercial', 'tipo_contrato' => 'CLT', 'parent_id' => 47, 'nivel_hierarquico' => 2, 'ordem_exibicao' => 4],
        ['nome' => 'Paula Ribeiro', 'cargo' => 'Analista Adm. Comercial Jr.', 'departamento' => 'Comercial', 'tipo_contrato' => 'CLT', 'parent_id' => 47, 'nivel_hierarquico' => 2, 'ordem_exibicao' => 5]
    ];
    
    // Preparar statement para inserção
    $stmt = $pdo->prepare("
        INSERT INTO organograma (nome, cargo, departamento, tipo_contrato, parent_id, nivel_hierarquico, ordem_exibicao) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $inserted = 0;
    foreach ($organograma_data as $pessoa) {
        $stmt->execute([
            $pessoa['nome'],
            $pessoa['cargo'],
            $pessoa['departamento'],
            $pessoa['tipo_contrato'],
            $pessoa['parent_id'],
            $pessoa['nivel_hierarquico'],
            $pessoa['ordem_exibicao']
        ]);
        $inserted++;
    }
    
    echo "<h2>✅ Dados inseridos com sucesso!</h2>";
    echo "<p><strong>$inserted</strong> registros inseridos na tabela organograma.</p>";
    
    // Mostrar estatísticas
    $stats = $pdo->query("
        SELECT 
            departamento,
            COUNT(*) as total,
            COUNT(CASE WHEN tipo_contrato = 'CLT' THEN 1 END) as clt,
            COUNT(CASE WHEN tipo_contrato = 'PJ' THEN 1 END) as pj,
            COUNT(CASE WHEN tipo_contrato = 'Aprendiz' THEN 1 END) as aprendiz
        FROM organograma 
        GROUP BY departamento 
        ORDER BY departamento
    ");
    
    echo "<h3>Estatísticas por Departamento:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0; width: 100%;'>";
    echo "<tr style='background: #f5f5f5;'><th>Departamento</th><th>Total</th><th>CLT</th><th>PJ</th><th>Aprendiz</th></tr>";
    
    $total_geral = 0;
    while ($row = $stats->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['departamento']) . "</td>";
        echo "<td><strong>" . $row['total'] . "</strong></td>";
        echo "<td>" . $row['clt'] . "</td>";
        echo "<td>" . $row['pj'] . "</td>";
        echo "<td>" . $row['aprendiz'] . "</td>";
        echo "</tr>";
        $total_geral += $row['total'];
    }
    echo "<tr style='background: #e9ecef; font-weight: bold;'>";
    echo "<td>TOTAL GERAL</td>";
    echo "<td>$total_geral</td>";
    echo "<td colspan='3'>-</td>";
    echo "</tr>";
    echo "</table>";
    
    echo "<h3>Próximos passos:</h3>";
    echo "<ul>";
    echo "<li>✅ Tabela criada e populada</li>";
    echo "<li>⏳ Criar interface administrativa</li>";
    echo "<li>⏳ Implementar organograma interativo</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Erro ao inserir dados:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
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