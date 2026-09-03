<?php
/* Navbar compartilhada — espera $usuario (sessão) e $paginaAtiva.
   Logo: suba sua imagem como assets/logo.png (ou .svg/.webp/.jpg)
   que ela substitui o ícone padrão automaticamente. */
$logoArquivo = null;
foreach (['logo.png', 'logo.svg', 'logo.webp', 'logo.jpg'] as $l) {
    if (file_exists(__DIR__ . '/../assets/' . $l)) { $logoArquivo = 'assets/' . $l; break; }
}
?>
<header class="navbar">
  <div class="logo <?= $logoArquivo ? 'com-imagem' : '' ?>">
    <?php if ($logoArquivo): ?><img src="<?= $logoArquivo ?>?v=<?= filemtime(__DIR__ . '/../' . $logoArquivo) ?>" alt="Logo" class="logo-img"><?php endif; ?>
    Controle de Kits - Flash
  </div>
  <nav>
    <a href="index.php" class="<?= $paginaAtiva === 'vendas' ? 'ativa' : '' ?>">Vendas</a>
    <a href="historico.php" class="<?= $paginaAtiva === 'historico' ? 'ativa' : '' ?>">Histórico</a>
    <a href="anuncios.php" class="<?= $paginaAtiva === 'anuncios' ? 'ativa' : '' ?>">Kits à Venda</a>
    <a href="kits.php" class="<?= $paginaAtiva === 'kits' ? 'ativa' : '' ?>">Cadastro de Kits</a>
    <?php if ($usuario['perfil'] === 'admin'): ?>
      <a href="usuarios.php" class="<?= $paginaAtiva === 'usuarios' ? 'ativa' : '' ?>">Usuários</a>
    <?php endif; ?>
  </nav>
  <div class="usuario-menu">
    <div class="usuario-info">
      <span class="usuario-nome"><?= htmlspecialchars($usuario['nome']) ?></span>
      <span class="usuario-perfil"><?= $usuario['perfil'] === 'admin' ? 'Administrador' : 'Operador' ?></span>
    </div>
    <a href="sair.php" class="btn-sair" title="Sair do sistema">Sair</a>
  </div>
</header>
