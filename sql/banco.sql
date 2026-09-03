-- =====================================================================
-- CONTROLE DE KITS — Script de criação do banco de dados
-- Compatível com MySQL 5.7+ / MariaDB 10.3+ (HostGator)
--
-- NA HOSTGATOR: crie o banco e o usuário pelo cPanel (MySQL Databases)
-- e depois importe SOMENTE as tabelas abaixo pelo phpMyAdmin.
-- (O CREATE DATABASE local é apenas para testes no Laragon.)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS controle_kits
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE controle_kits;

-- ---------------------------------------------------------------------
-- Tabela de Kits (catálogo de anúncios)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kits (
  id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  nome          VARCHAR(150)     NOT NULL,
  sku           VARCHAR(60)      NOT NULL,
  preco         DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
  -- lista separada por vírgula, ex.: 'Mercado Livre, Shopee'
  local_anuncio VARCHAR(255)     NOT NULL DEFAULT 'Mercado Livre',
  data_anuncio  DATE             NULL,
  ativo         TINYINT(1)       NOT NULL DEFAULT 1,
  criado_em     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kits_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabela de Usuários (acesso ao sistema)
-- perfil: 'admin' gerencia usuários e kits; 'operador' registra vendas.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id         INT UNSIGNED             NOT NULL AUTO_INCREMENT,
  nome       VARCHAR(100)             NOT NULL,
  usuario    VARCHAR(50)              NOT NULL,
  senha_hash VARCHAR(255)             NOT NULL,
  perfil     ENUM('admin','operador') NOT NULL DEFAULT 'operador',
  ativo      TINYINT(1)               NOT NULL DEFAULT 1,
  criado_em  TIMESTAMP                NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuários iniciais — TROQUE AS SENHAS após o primeiro login!
-- admin1 / admin123   |   admin2 / admin123   |   operador / oper123
INSERT INTO usuarios (nome, usuario, senha_hash, perfil) VALUES
  ('Administrador 1', 'admin1',   '$2y$10$BX4dJSoUyz3MBcMUQe/pxuzQ.W/EBng5d.PcSYr3IWA9/FO8G0ViK', 'admin'),
  ('Administrador 2', 'admin2',   '$2y$10$BX4dJSoUyz3MBcMUQe/pxuzQ.W/EBng5d.PcSYr3IWA9/FO8G0ViK', 'admin'),
  ('Operador',        'operador', '$2y$10$.Z0RnsIwGC89k7gHRJuuyuWZNucBQ7FRzv4.cEJDvRZfoed9PXjIC', 'operador');

-- ---------------------------------------------------------------------
-- Tabela de Vendas (registro operacional)
-- preco_venda guarda o preço do kit NO MOMENTO da venda (histórico
-- não muda se o preço do kit for alterado depois).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS vendas (
  id           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  kit_id       INT UNSIGNED   NOT NULL,
  usuario_id   INT UNSIGNED   NULL,
  nota_fiscal  VARCHAR(20)    NOT NULL,
  serie_nf     VARCHAR(10)    NOT NULL,
  preco_venda  DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  data_venda   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_vendas_kit (kit_id),
  KEY idx_vendas_data (data_venda),
  UNIQUE KEY uq_vendas_nf (nota_fiscal, serie_nf),
  CONSTRAINT fk_vendas_kit FOREIGN KEY (kit_id) REFERENCES kits (id),
  CONSTRAINT fk_vendas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kits de exemplo (opcional — remova em produção)
INSERT INTO kits (nome, sku, preco, local_anuncio, data_anuncio) VALUES
  ('Kit Festa Completo 50 pessoas', 'KIT-FESTA-50', 289.90, 'Mercado Livre, Shopee', '2026-07-15'),
  ('Kit Aniversário Infantil',      'KIT-ANIV-INF', 159.90, 'Shopee',                '2026-08-01'),
  ('Kit Decoração Casamento',       'KIT-CASA-01',  449.00, 'Loja Física',           '2026-08-20');
