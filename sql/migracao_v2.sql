-- =====================================================================
-- MIGRAÇÃO v2 — rode APENAS se o banco já foi criado com a versão
-- anterior do banco.sql. Banco novo? Use direto o banco.sql atualizado.
--
-- Mudanças: local_anuncio aceita vários locais (lista separada por
-- vírgula) e novo campo data_anuncio (data de criação do anúncio).
-- =====================================================================

ALTER TABLE kits
  MODIFY local_anuncio VARCHAR(255) NOT NULL DEFAULT 'Mercado Livre',
  ADD COLUMN data_anuncio DATE NULL AFTER local_anuncio;

-- Kits antigos assumem a data de cadastro como data do anúncio
UPDATE kits SET data_anuncio = DATE(criado_em) WHERE data_anuncio IS NULL;
