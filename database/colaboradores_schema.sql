-- Script de criação das tabelas para o sistema de colaboradores
-- Grupo Barão - Portal TI

-- Tabela de colaboradores
CREATE TABLE IF NOT EXISTS colaboradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ramal VARCHAR(10) DEFAULT NULL,
    nome VARCHAR(255) NOT NULL,
    empresa VARCHAR(255) NOT NULL,
    setor VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    telefone VARCHAR(255) DEFAULT NULL,
    teams VARCHAR(255) DEFAULT NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    observacoes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices BTREE para otimização
    INDEX idx_ramal (ramal) USING BTREE,
    INDEX idx_nome (nome) USING BTREE,
    INDEX idx_empresa (empresa) USING BTREE,
    INDEX idx_setor (setor) USING BTREE,
    INDEX idx_email (email) USING BTREE,
    INDEX idx_status (status) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1207;

-- Tabela de contatos adicionais (para múltiplos contatos por colaborador)
CREATE TABLE IF NOT EXISTS colaborador_contatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    colaborador_id INT NOT NULL,
    tipo_contato ENUM('telefone', 'celular', 'email', 'teams', 'whatsapp', 'outro') NOT NULL,
    valor VARCHAR(255) NOT NULL,
    descricao VARCHAR(100) DEFAULT NULL,
    principal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Chave estrangeira
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    
    -- Índices
    INDEX idx_colaborador_id (colaborador_id),
    INDEX idx_tipo_contato (tipo_contato),
    INDEX idx_principal (principal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de histórico de alterações (auditoria)
CREATE TABLE IF NOT EXISTS colaboradores_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    colaborador_id INT NOT NULL,
    acao ENUM('criado', 'atualizado', 'excluido') NOT NULL,
    dados_anteriores JSON DEFAULT NULL,
    dados_novos JSON DEFAULT NULL,
    usuario_id INT DEFAULT NULL,
    usuario_nome VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Chave estrangeira
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    
    -- Índices
    INDEX idx_colaborador_id (colaborador_id),
    INDEX idx_acao (acao),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserção de dados de exemplo
INSERT INTO colaboradores (ramal, nome, empresa, setor, email, telefone, teams, status) VALUES
('229', 'ADAILSON SILVA', 'Alfaness Log', 'RECEBIMENTO/ARMAZENAGEM', 'adailson.silva@alfanesslog.com.br', NULL, NULL, 'ativo'),
('2025', 'ALEF SILVA', 'Alfaness Log', 'ADMINISTRATIVO', 'alef.silva@alfanesslog.com.br', NULL, 'Abrir no app', 'ativo'),
('2021', 'ALESSANDRA GASPAR', 'Grupo Barão', 'TELEVENDAS/ DAÍ (VENDAS INTERNAS)', 'alessandra.gaspar@baraodistribuidor.com.br', NULL, 'Abrir no app', 'ativo'),
('2105', 'ALINE PINHEIRO', 'Grupo Barão', 'T.MANIA (MKT)', 'aline.pinheiro@toymania.com.br', NULL, 'Abrir no app', 'ativo'),
('233', 'ALISSON PAZ', 'Alfaness Log', 'COMPRAS', 'alisson.paz@alfanesslog.com.br', NULL, NULL, 'ativo'),
('2053', 'AMANDA CANOBRE', 'Grupo Barão', 'COMERCIAL', 'amanda.canobre@baraodistribuidor.com.br', '(11) 98369-3461', 'Abrir no app', 'ativo'),
('2000', 'ANA CAROLINE SAVIO', 'Grupo Barão', 'SAC', 'ana.savio@toymania.com.br', '(11) 3305-1002', 'Abrir no app', 'ativo'),
('2086', 'ANA PAULA', 'Grupo Barão', 'FINANCEIRO - CONTAS A PAGAR', 'ana.paula@baraodistribuidor.com.br', NULL, 'Abrir no app', 'ativo'),
('2378', 'ANDERSON AMORIM', 'Grupo Barão', 'COMPRAS', 'anderson.amorim@baraodistribuidor.com.br', NULL, 'Abrir no app', 'ativo');

-- Inserção de contatos adicionais de exemplo
INSERT INTO colaborador_contatos (colaborador_id, tipo_contato, valor, descricao, principal) VALUES
(6, 'telefone', '(11) 98369-3461', 'Celular corporativo', TRUE),
(7, 'telefone', '(11) 3305-1002', 'Telefone direto', TRUE);