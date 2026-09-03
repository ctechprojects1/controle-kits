<?php
require __DIR__ . '/config/auth.php';
if (usuarioLogado()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Entrar — Controle de Kits</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css?v=5">
</head>
<body>

<main class="login-tela">
  <div class="card login-card">
    <?php
    $logoArquivo = null;
    foreach (['logo.png', 'logo.svg', 'logo.webp', 'logo.jpg'] as $l) {
        if (file_exists(__DIR__ . '/assets/' . $l)) { $logoArquivo = 'assets/' . $l; break; }
    }
    ?>
    <?php if ($logoArquivo): ?>
      <img src="<?= $logoArquivo ?>" alt="Logo" class="login-logo-img">
    <?php else: ?>
      <div class="login-logo">📦</div>
    <?php endif; ?>
    <h1 class="login-titulo">Controle de Kits - Flash</h1>
    <p class="login-subtitulo">Entre com seu usuário para continuar</p>

    <div id="alerta" class="alerta" role="alert"></div>

    <form id="form-login" autocomplete="off">
      <div class="grupo">
        <label for="usuario">Usuário</label>
        <input type="text" id="usuario" name="usuario" class="campo-grande"
               placeholder="seu.usuario" autofocus required autocapitalize="none">
      </div>
      <div class="grupo">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" class="campo-grande"
               placeholder="••••••••" required>
      </div>
      <button type="submit" id="btn-entrar" class="btn btn-primario btn-bloco">Entrar</button>
    </form>
  </div>
</main>

<script>
const form   = document.getElementById('form-login');
const btn    = document.getElementById('btn-entrar');
const alerta = document.getElementById('alerta');

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  btn.disabled = true;
  btn.textContent = 'Entrando…';

  const corpo = new FormData(form);
  corpo.append('acao', 'login');

  try {
    const resp = await fetch('api.php', { method: 'POST', body: corpo });
    const dados = await resp.json();

    if (dados.ok) {
      window.location.href = 'index.php';
      return;
    }
    alerta.className = 'alerta visivel falha';
    alerta.textContent = '⚠ ' + dados.erro;
    document.getElementById('senha').select();
  } catch {
    alerta.className = 'alerta visivel falha';
    alerta.textContent = '⚠ Falha de conexão com o servidor.';
  }
  btn.disabled = false;
  btn.textContent = 'Entrar';
});
</script>

</body>
</html>
