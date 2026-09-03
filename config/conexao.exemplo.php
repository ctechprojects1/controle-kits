<?php
/**
 * conexao.exemplo.php — Modelo de conexão com o banco.
 * Copie este arquivo para conexao.php e preencha com as credenciais
 * do ambiente (o conexao.php real NÃO é versionado no git).
 */

define('DB_HOST',    'localhost');
define('DB_NOME',    'suaconta_kits');
define('DB_USUARIO', 'suaconta_kits');
define('DB_SENHA',   'sua_senha_aqui');

function conectar(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NOME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USUARIO, DB_SENHA, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}
