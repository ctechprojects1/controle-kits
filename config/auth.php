<?php
/**
 * auth.php — Sessão e controle de acesso.
 * Perfis: 'admin' (gerencia usuários) e 'operador' (registra vendas).
 */

declare(strict_types=1);

session_name('CONTROLE_KITS');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function usuarioLogado(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function ehAdmin(): bool
{
    return (usuarioLogado()['perfil'] ?? '') === 'admin';
}

/** Para páginas: redireciona ao login se não autenticado. */
function exigirLoginPagina(): array
{
    $usuario = usuarioLogado();
    if (!$usuario) {
        header('Location: login.php');
        exit;
    }
    return $usuario;
}

/** Para páginas restritas a administradores. */
function exigirAdminPagina(): array
{
    $usuario = exigirLoginPagina();
    if ($usuario['perfil'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
    return $usuario;
}
