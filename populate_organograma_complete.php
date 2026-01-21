<?php
// Script para popular a tabela organograma com dados completos do organogramas.md
require_once 'admin/config.php';

try {
    // Limpar tabela existente
    $pdo->exec("DELETE FROM organograma");
    $pdo->exec("ALTER TABLE organograma AUTO_INCREMENT = 1");
    
    echo "Tabela organograma limpa.\n";
    
    // Dados organizacionais completos baseados no organogramas.md
    $organograma_data = [
        // COMPRAS
        ['nome' => 'Sergio Cerqueira', 'cargo' => 'Gerente de Compras', 'departamento' => 'Compras', 'tipo_contrato' => 'CLT', 'parent_id' => null, 'nivel_hierarquico' => 1],
        ['nome' => 'Sandra Silva', 'cargo' => 'Coordenadora de Compras', 'departamento' => 'Compras', 'tipo_contrato' => 'CLT', 'parent_id' => 1, 'nivel_hierarquico' => 2],
        ['nome' => 'Fabricia Silveira', 'cargo' => 'Assistente de Compras Jr.', 'departamento' => 'Compras', 'tipo_contrato' => 'CLT', 'parent_id' => 2, 'nivel_hierarquico' => 3],
        ['nome' => 'Davi Cocentino', 'cargo' => 'Jovem Aprendiz', 'departamento' => 'Compras', 'tipo_contrato' => 'Aprendiz', 'parent_id' => 2, 'nivel_hierarquico' => 3],
        
        // CONTROLADORIA
        ['nome' => 'Betuel Lopes', 'cargo' => 'Controller', 'departamento' => 'Controladoria', 'tipo_contrato' => 'PJ', 'parent_id' => null, 'nivel_hierarquico' => 1],
        ['nome' => 'Orlando Esau', 'cargo' => 'Contador', 'departamento' => 'Controladoria', 'tipo_contrato' => 'PJ', 'parent_id' => 5, 'nivel_hierarquico' => 2],
        ['nome' => 'Edileuza Queiroz', 'cargo' => 'Analista Fiscal Sr.', 'departamento' => 'Controladoria', 'tipo_contrato' => 'CLT', 'parent_id' => 6, 'nivel_hierarquico' => 3],
        ['nome' => 'Tailila Santos', 'cargo' => 'Assistente Fiscal', 'departamento' => 'Controladoria', 'tipo_contrato' => 'CLT', 'parent_id' => 7, 'nivel_hierarquico' => 4],
        ['nome' => 'Gabriel Pedrosa', 'cargo' => 'Analista Contábil Pl.', 'departamento' => 'Controladoria', 'tipo_contrato' => 'CLT', 'parent_id' => 6, 'nivel_hierarquico' => 3],
        ['nome' => 'Fernando Custódio', 'cargo' => 'Analista Contábil Jr.', 'departamento' => 'Controladoria', 'tipo_contrato' => 'CLT', 'parent_id' => 6, 'nivel_hierarquico' => 3],
        
        // RECURSOS HUMANOS
        ['nome' => 'Danielle Ness', 'cargo' => 'Diretoria', 'departamento' => 'Recursos Humanos', 'tipo_contrato' => 'CLT', 'parent_id' => null, 'nivel_hierarquico' => 1],
        ['nome' => 'Josefina Camargo', 'cargo' => 'Gerente de Gestão de Pessoas', 'departamento' => 'Recursos Humanos', 'tipo_contrato' => 'CLT', 'parent_id' => 11, 'nivel_hierarquico' => 2],
        
        // RH - Barão
        ['nome' => 'Jefferson Cândido', 'cargo' => 'Manutenção Predial', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'CLT', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Celso Santana', 'cargo' => 'Oficial de Manutenção Predial', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'CLT', 'parent_id' => 13, 'nivel_hierarquico' => 4],
        ['nome' => 'Gabriela Ludovico', 'cargo' => 'Assistente de R.H. Sr.', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'CLT', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Beatriz Sousa', 'cargo' => 'Auxiliar de R.H.', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'CLT', 'parent_id' => 15, 'nivel_hierarquico' => 4],
        ['nome' => 'Patrícia Aparecida', 'cargo' => 'Recepcionista', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'CLT', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Auxiliar de Limpeza 1', 'cargo' => 'Auxiliar de Limpeza', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Auxiliar de Limpeza 2', 'cargo' => 'Auxiliar de Limpeza', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Porteiro 1', 'cargo' => 'Portaria', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Porteiro 2', 'cargo' => 'Portaria', 'departamento' => 'Recursos Humanos - Barão', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        
        // RH - Alfaness
        ['nome' => 'Vanessa Sena', 'cargo' => 'Assistente de R.H. Jr.', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'CLT', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Nicole Nascimento', 'cargo' => 'Jovem Aprendiz', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Aprendiz', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Auxiliar de Limpeza Alfaness 1', 'cargo' => 'Auxiliar de Limpeza', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Auxiliar de Limpeza Alfaness 2', 'cargo' => 'Auxiliar de Limpeza', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Auxiliar de Limpeza Alfaness 3', 'cargo' => 'Auxiliar de Limpeza', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Auxiliar de Limpeza Alfaness 4', 'cargo' => 'Auxiliar de Limpeza', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Porteiro Alfaness 1', 'cargo' => 'Portaria', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Porteiro Alfaness 2', 'cargo' => 'Portaria', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Porteiro Alfaness 3', 'cargo' => 'Portaria', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Porteiro Alfaness 4', 'cargo' => 'Portaria', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Refeitório Alfaness 1', 'cargo' => 'Refeitório', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Refeitório Alfaness 2', 'cargo' => 'Refeitório', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Refeitório Alfaness 3', 'cargo' => 'Refeitório', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        ['nome' => 'Refeitório Alfaness 4', 'cargo' => 'Refeitório', 'departamento' => 'Recursos Humanos - Alfaness', 'tipo_contrato' => 'Terceirizado', 'parent_id' => 12, 'nivel_hierarquico' => 3],
        
        // T.I.
        ['nome' => 'André Mor', 'cargo' => 'Gerente de T.I.', 'departamento' => 'T.I.', 'tipo_contrato' => 'PJ', 'parent_id' => 5, 'nivel_hierarquico' => 2], // Parent: Betuel Lopes
        ['nome' => 'Luiz Rogério', 'cargo' => 'Coordenador de Infra T.I.', 'departamento' => 'T.I.', 'tipo_contrato' => 'PJ', 'parent_id' => 35, 'nivel_hierarquico' => 3],
        ['nome' => 'Jorge Domingos', 'cargo' => 'Supervisor de Infra', 'departamento' => 'T.I.', 'tipo_contrato' => 'CLT', 'parent_id' => 36, 'nivel_hierarquico' => 4],
        ['nome' => 'Rafael Akiyama', 'cargo' => 'Assistente de Infra Pl.', 'departamento' => 'T.I.', 'tipo_contrato' => 'CLT', 'parent_id' => 37, 'nivel_hierarquico' => 5],
        ['nome' => 'Matheus Ramires', 'cargo' => 'Programador', 'departamento' => 'T.I.', 'tipo_contrato' => 'CLT', 'parent_id' => 36, 'nivel_hierarquico' => 4],
        ['nome' => 'Miguel Ness', 'cargo' => 'Analista de T.I. Pl.', 'departamento' => 'T.I.', 'tipo_contrato' => 'CLT', 'parent_id' => 35, 'nivel_hierarquico' => 3],
        
        // FINANCEIRO
        ['nome' => 'Marcos Soares', 'cargo' => 'Gerente Financeiro', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 5, 'nivel_hierarquico' => 2], // Parent: Betuel Lopes
        ['nome' => 'Vanessa Zanatta', 'cargo' => 'Coordenadora Financeiro', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 41, 'nivel_hierarquico' => 3],
        ['nome' => 'Katia Souza', 'cargo' => 'Analista de Crédito Sr.', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 42, 'nivel_hierarquico' => 4],
        ['nome' => 'Ana Paula da Silva', 'cargo' => 'Analista Financeiro Jr.', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 42, 'nivel_hierarquico' => 4],
        ['nome' => 'Iara Menezes', 'cargo' => 'Analista Financeiro Pl.', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 42, 'nivel_hierarquico' => 4],
        ['nome' => 'Diego Souza', 'cargo' => 'Assistente Financeiro', 'departamento' => 'Financeiro', 'tipo_contrato' => 'PJ', 'parent_id' => 42, 'nivel_hierarquico' => 4],
        ['nome' => 'Daiana Carvalho', 'cargo' => 'Assistente de Cobrança Pl.', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 42, 'nivel_hierarquico' => 4],
        ['nome' => 'Luami Oliveira', 'cargo' => 'Assistente Financeiro', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 42, 'nivel_hierarquico' => 4],
        ['nome' => 'Bianca Espindola', 'cargo' => 'Assistente Financeiro Marketplace', 'departamento' => 'Financeiro', 'tipo_contrato' => 'CLT', 'parent_id' => 42, 'nivel_hierarquico' => 4],
        
        // MARKETING
        ['nome' => 'Luiz Fiorinni', 'cargo' => 'Diretor de Marketing', 'departamento' => 'Marketing', 'tipo_contrato' => 'PJ', 'parent_id' => null, 'nivel_hierarquico' => 1],
        ['nome' => 'Viviane Tamborim', 'cargo' => 'Gerente de Marketing (Comunicação)', 'departamento' => 'Marketing', 'tipo_contrato' => 'PJ', 'parent_id' => 50, 'nivel_hierarquico' => 2],
        ['nome' => 'Thais Lizandra', 'cargo' => 'Analista de Marketing Pl.', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 51, 'nivel_hierarquico' => 3],
        ['nome' => 'Anderson Souto', 'cargo' => 'Designer Sr.', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 51, 'nivel_hierarquico' => 3],
        ['nome' => 'Beatriz Rodrigues', 'cargo' => 'Assistente de MKT Jr.', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 51, 'nivel_hierarquico' => 3],
        ['nome' => 'Diego Allegue', 'cargo' => 'Designer Pleno', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 51, 'nivel_hierarquico' => 3],
        ['nome' => 'Fernanda Couto', 'cargo' => 'Designer Jr.', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 51, 'nivel_hierarquico' => 3],
        ['nome' => 'Marcelo Martins', 'cargo' => 'Gerente de Produto e Importação', 'departamento' => 'Marketing', 'tipo_contrato' => 'PJ', 'parent_id' => 50, 'nivel_hierarquico' => 2],
        ['nome' => 'Kelly Moura', 'cargo' => 'Analista de Importação Sr.', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 56, 'nivel_hierarquico' => 3],
        ['nome' => 'Sandra Maria', 'cargo' => 'Analista de Importação', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 56, 'nivel_hierarquico' => 3],
        ['nome' => 'Paulo Mendes', 'cargo' => 'Assistente de Importação', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 56, 'nivel_hierarquico' => 3],
        ['nome' => 'Eduardo Carvalho', 'cargo' => 'Gerente E-commerce', 'departamento' => 'Marketing', 'tipo_contrato' => 'CLT', 'parent_id' => 50, 'nivel_hierarquico' => 2],
        
        // COMERCIAL
        ['nome' => 'Gilson Pestana', 'cargo' => 'Diretor Nacional de Vendas', 'departamento' => 'Comercial', 'tipo_contrato' => 'CLT', 'parent_id' => null, 'nivel_hierarquico' => 1],
        ['nome' => 'Vinicius Avila', 'cargo' => 'Gerente de Vendas', 'departamento' => 'Comercial', 'tipo_contrato' => 'PJ', 'parent_id' => 61, 'nivel_hierarquico' => 2],
        ['nome' => 'William Vicentin', 'cargo' => 'Coordenador de Vendas', 'departamento' => 'Comercial', 'tipo_contrato' => 'PJ', 'parent_id' => 62, 'nivel_hierarquico' => 3],
        ['nome' => 'Ronny Ramos', 'cargo' => 'Key Account', 'departamento' => 'Comercial', 'tipo_contrato' => 'PJ', 'parent_id' => 62, 'nivel_hierarquico' => 3],
        ['nome' => 'Vendedor/Representante 1', 'cargo' => 'Vendedores/Representantes', 'departamento' => 'Comercial', 'tipo_contrato' => 'PJ', 'parent_id' => 62, 'nivel_hierarquico' => 3],
        ['nome' => 'Janaina Laura', 'cargo' => 'Coordenador de Vendas', 'departamento' => 'Comercial', 'tipo_contrato' => 'CLT', 'parent_id' => 61, 'nivel_hierarquico' => 2],
        ['nome' => 'Marcelo Fadel', 'cargo' => 'Analista Adm. Comercial Sr.', 'departamento' => 'Comercial', 'tipo_contrato' => 'CLT', 'parent_id' => 61, 'nivel_hierarquico' => 2],
        ['nome' => 'Yasmin Oliveira', 'cargo' => 'Assistente ADM Pleno', 'departamento' => 'Comercial', 'tipo_contrato' => 'CLT', 'parent_id' => 67, 'nivel_hierarquico' => 3],
        ['nome' => 'Amanda Canobre', 'cargo' => 'Analista de Inteligência de Mercado', 'departamento' => 'Comercial', 'tipo_contrato' => 'CLT', 'parent_id' => 61, 'nivel_hierarquico' => 2],
        ['nome' => 'Paula Ribeiro', 'cargo' => 'Analista Adm. Comercial Jr.', 'departamento' => 'Comercial', 'tipo_contrato' => 'CLT', 'parent_id' => 61, 'nivel_hierarquico' => 2],
        
        // LOJA / E-COMMERCE (OPERAÇÃO)
        ['nome' => 'Nathalia Ferreira', 'cargo' => 'Coordenadora de Marketplace', 'departamento' => 'Loja / E-commerce (Operação)', 'tipo_contrato' => 'CLT', 'parent_id' => 60, 'nivel_hierarquico' => 3], // Parent: Eduardo Carvalho
        ['nome' => 'Thiago Costa', 'cargo' => 'Supervisor de Loja', 'departamento' => 'Loja / E-commerce (Operação)', 'tipo_contrato' => 'CLT', 'parent_id' => 71, 'nivel_hierarquico' => 4],
        ['nome' => 'Jacilene da Silva', 'cargo' => 'Operadora de Caixa', 'departamento' => 'Loja / E-commerce (Operação)', 'tipo_contrato' => 'CLT', 'parent_id' => 72, 'nivel_hierarquico' => 5],
        ['nome' => 'Evanei da Silva', 'cargo' => 'Atendente III', 'departamento' => 'Loja / E-commerce (Operação)', 'tipo_contrato' => 'CLT', 'parent_id' => 72, 'nivel_hierarquico' => 5],
        
        // ADM. VENDAS
        ['nome' => 'Rafael Bispo', 'cargo' => 'Coordenador ADM. Vendas', 'departamento' => 'ADM. Vendas', 'tipo_contrato' => 'CLT', 'parent_id' => 1, 'nivel_hierarquico' => 2], // Parent: Sergio Cerqueira
        ['nome' => 'Edilaine Evangelista', 'cargo' => 'Analista ADM. Vendas Jr.', 'departamento' => 'ADM. Vendas', 'tipo_contrato' => 'CLT', 'parent_id' => 75, 'nivel_hierarquico' => 3],
        ['nome' => 'Humberto Freitas', 'cargo' => 'Assistente ADM. Vendas', 'departamento' => 'ADM. Vendas', 'tipo_contrato' => 'CLT', 'parent_id' => 75, 'nivel_hierarquico' => 3],
        ['nome' => 'Tauane Kapasa', 'cargo' => 'Assistente ADM. Vendas Jr.', 'departamento' => 'ADM. Vendas', 'tipo_contrato' => 'CLT', 'parent_id' => 75, 'nivel_hierarquico' => 3],
        
        // CLIENTES — GRANDES CONTAS (MERCHANDISING)
        ['nome' => 'Luiz Carlos', 'cargo' => 'Supervisor Merchandising', 'departamento' => 'Clientes — Grandes Contas (Merchandising)', 'tipo_contrato' => 'CLT', 'parent_id' => null, 'nivel_hierarquico' => 1],
        ['nome' => 'Eraldo Nunes', 'cargo' => 'Trainee Supervisor Merchandising', 'departamento' => 'Clientes — Grandes Contas (Merchandising)', 'tipo_contrato' => 'CLT', 'parent_id' => 79, 'nivel_hierarquico' => 2],
        ['nome' => 'Tiago Evangelista', 'cargo' => 'Trainee Supervisor Merchandising', 'departamento' => 'Clientes — Grandes Contas (Merchandising)', 'tipo_contrato' => 'CLT', 'parent_id' => 79, 'nivel_hierarquico' => 2],
        ['nome' => 'Thiago de Almeida', 'cargo' => 'Repositor Sr.', 'departamento' => 'Clientes — Grandes Contas (Merchandising)', 'tipo_contrato' => 'CLT', 'parent_id' => 79, 'nivel_hierarquico' => 2],
        ['nome' => 'Lucas Alves', 'cargo' => 'Repositor Pl.', 'departamento' => 'Clientes — Grandes Contas (Merchandising)', 'tipo_contrato' => 'CLT', 'parent_id' => 79, 'nivel_hierarquico' => 2],
        ['nome' => 'Matheus Macedo', 'cargo' => 'Repositor Jr.', 'departamento' => 'Clientes — Grandes Contas (Merchandising)', 'tipo_contrato' => 'CLT', 'parent_id' => 79, 'nivel_hierarquico' => 2],
        ['nome' => 'Lucas Alves 2', 'cargo' => 'Repositor Jr.', 'departamento' => 'Clientes — Grandes Contas (Merchandising)', 'tipo_contrato' => 'CLT', 'parent_id' => 79, 'nivel_hierarquico' => 2],
        ['nome' => 'Ronaldo dos Reis', 'cargo' => 'Repositor Jr.', 'departamento' => 'Clientes — Grandes Contas (Merchandising)', 'tipo_contrato' => 'CLT', 'parent_id' => 79, 'nivel_hierarquico' => 2],
        ['nome' => 'Elisangela Santos', 'cargo' => 'Repositor Jr.', 'departamento' => 'Clientes — Grandes Contas (Merchandising)', 'tipo_contrato' => 'CLT', 'parent_id' => 79, 'nivel_hierarquico' => 2],
        ['nome' => 'Sonia Moraes', 'cargo' => 'Repositor Jr.', 'departamento' => 'Clientes — Grandes Contas (Merchandising)', 'tipo_contrato' => 'CLT', 'parent_id' => 79, 'nivel_hierarquico' => 2],
        
        // MARKETING — TOYMANIA (E-COMMERCE)
        ['nome' => 'Natalia Ferreira', 'cargo' => 'Coordenadora de E-commerce', 'departamento' => 'Marketing — Toymania (E-commerce)', 'tipo_contrato' => 'CLT', 'parent_id' => 60, 'nivel_hierarquico' => 3], // Parent: Eduardo Carvalho
        ['nome' => 'Aline Reis', 'cargo' => 'Analista de Marketing Digital', 'departamento' => 'Marketing — Toymania (E-commerce)', 'tipo_contrato' => 'CLT', 'parent_id' => 88, 'nivel_hierarquico' => 4],
        ['nome' => 'Emerson Luiz', 'cargo' => 'Designer Jr.', 'departamento' => 'Marketing — Toymania (E-commerce)', 'tipo_contrato' => 'CLT', 'parent_id' => 88, 'nivel_hierarquico' => 4],
        ['nome' => 'Camila Navas', 'cargo' => 'Assistente de Marketplace Sr.', 'departamento' => 'Marketing — Toymania (E-commerce)', 'tipo_contrato' => 'CLT', 'parent_id' => 88, 'nivel_hierarquico' => 4],
        ['nome' => 'Natalia Lima', 'cargo' => 'Assistente de Marketing Pl.', 'departamento' => 'Marketing — Toymania (E-commerce)', 'tipo_contrato' => 'CLT', 'parent_id' => 88, 'nivel_hierarquico' => 4],
        ['nome' => 'Kauany Barbosa', 'cargo' => 'Assistente de Marketplace Jr.', 'departamento' => 'Marketing — Toymania (E-commerce)', 'tipo_contrato' => 'CLT', 'parent_id' => 88, 'nivel_hierarquico' => 4],
        ['nome' => 'Najara Almeida', 'cargo' => 'Assistente de Marketing Jr.', 'departamento' => 'Marketing — Toymania (E-commerce)', 'tipo_contrato' => 'CLT', 'parent_id' => 88, 'nivel_hierarquico' => 4],
        ['nome' => 'Anderson Amorim', 'cargo' => 'Assistente Comercial', 'departamento' => 'Marketing — Toymania (E-commerce)', 'tipo_contrato' => 'CLT', 'parent_id' => 88, 'nivel_hierarquico' => 4],
    ];
    
    // Preparar statement para inserção
    $stmt = $pdo->prepare("
        INSERT INTO organograma (nome, cargo, departamento, tipo_contrato, parent_id, nivel_hierarquico, ativo) 
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    
    $count = 0;
    foreach ($organograma_data as $pessoa) {
        $stmt->execute([
            $pessoa['nome'],
            $pessoa['cargo'],
            $pessoa['departamento'],
            $pessoa['tipo_contrato'],
            $pessoa['parent_id'],
            $pessoa['nivel_hierarquico']
        ]);
        $count++;
    }
    
    echo "Inseridos $count registros na tabela organograma.\n";
    
    // Verificar resultado
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM organograma");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total de registros na tabela: " . $total['total'] . "\n";
    
    // Mostrar departamentos únicos
    $stmt = $pdo->query("SELECT DISTINCT departamento FROM organograma ORDER BY departamento");
    $departamentos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "\nDepartamentos criados:\n";
    foreach ($departamentos as $dept) {
        echo "- $dept\n";
    }
    
    echo "\nPopulação da tabela organograma concluída com sucesso!\n";
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>