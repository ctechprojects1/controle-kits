-- =====================================================================
-- MIGRAÇÃO v3 — usuários e login. Rode APENAS se o banco já existe.
-- Banco novo? Use direto o banco.sql atualizado.
-- =====================================================================

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

-- Vendas passam a registrar quem vendeu
ALTER TABLE vendas
  ADD COLUMN usuario_id INT UNSIGNED NULL AFTER kit_id,
  ADD CONSTRAINT fk_vendas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id);
