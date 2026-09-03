<?php
require __DIR__ . '/config/auth.php';
$usuario = exigirLoginPagina();
$paginaAtiva = 'kits';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro de Kits — Controle de Kits</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css?v=5">
</head>
<body>

<?php include __DIR__ . '/config/navbar.php'; ?>

<main class="container">
  <h1 class="titulo-pagina">Cadastro de Kits</h1>
  <p class="subtitulo-pagina">Adicione os kits anunciados para que apareçam na tela de vendas.</p>

  <div id="alerta" class="alerta" role="alert"></div>

  <div class="card">
    <form id="form-kit" autocomplete="off">
      <div class="linha-campos">
        <div class="grupo" style="flex: 2">
          <label for="nome">Nome do Kit</label>
          <input type="text" id="nome" name="nome" placeholder="Ex.: Kit Festa Completo 50 pessoas" autofocus required>
        </div>
        <div class="grupo">
          <label for="sku">Referência / SKU <span class="dica">— único</span></label>
          <input type="text" id="sku" name="sku" placeholder="Ex.: KIT-FESTA-50" style="text-transform: uppercase" required>
        </div>
      </div>

      <div class="linha-campos">
        <div class="grupo">
          <label for="preco">Preço (R$)</label>
          <input type="text" id="preco" name="preco" placeholder="0,00" inputmode="decimal" required>
        </div>
        <div class="grupo">
          <label for="data_anuncio">Data de criação do anúncio</label>
          <input type="date" id="data_anuncio" name="data_anuncio" required>
        </div>
      </div>

      <div class="grupo">
        <label>Locais do Anúncio <span class="dica">— marque todos onde o kit está anunciado</span></label>
        <div class="chips" id="chips-locais">
          <label class="chip"><input type="checkbox" name="locais[]" value="OLX"><span>OLX</span></label>
          <label class="chip"><input type="checkbox" name="locais[]" value="Whatsapp"><span>Whatsapp</span></label>
          <label class="chip"><input type="checkbox" name="locais[]" value="Ebay"><span>Ebay</span></label>
          <label class="chip"><input type="checkbox" name="locais[]" value="Facebook"><span>Facebook</span></label>
          <label class="chip"><input type="checkbox" name="locais[]" value="Instagram"><span>Instagram</span></label>
        </div>
      </div>

      <button type="submit" id="btn-salvar" class="btn btn-primario">+ Cadastrar Kit</button>
    </form>
  </div>

  <p class="nota-rodape">Para ver e gerenciar os kits cadastrados, acesse <a href="anuncios.php">Kits à Venda</a>.</p>
</main>

<script>
const $ = (id) => document.getElementById(id);

const form   = $('form-kit');
const btn    = $('btn-salvar');
const alerta = $('alerta');

const brl = (v) => Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

function avisar(tipo, mensagem) {
  alerta.className = 'alerta visivel ' + tipo;
  alerta.textContent = (tipo === 'sucesso' ? '✓ ' : '⚠ ') + mensagem;
  clearTimeout(avisar.timer);
  avisar.timer = setTimeout(() => { alerta.className = 'alerta'; }, 6000);
}

// Máscara simples de moeda: mantém dígitos e vírgula
$('preco').addEventListener('input', (e) => {
  e.target.value = e.target.value.replace(/[^\d.,]/g, '');
});

// Data do anúncio começa em hoje
$('data_anuncio').value = new Date().toISOString().slice(0, 10);

// ---- Cadastrar --------------------------------------------------------
form.addEventListener('submit', async (e) => {
  e.preventDefault();

  if (!form.querySelector('input[name="locais[]"]:checked')) {
    avisar('falha', 'Selecione pelo menos um local de anúncio.');
    return;
  }

  btn.disabled = true;

  const corpo = new FormData(form);
  corpo.append('acao', 'criar_kit');

  try {
    const resp = await fetch('api.php', { method: 'POST', body: corpo });
    if (resp.status === 401) { window.location.href = 'login.php'; return; }
    const dados = await resp.json();

    if (dados.ok) {
      avisar('sucesso', `Kit ${dados.sku} cadastrado com sucesso!`);
      form.reset();
      $('data_anuncio').value = new Date().toISOString().slice(0, 10);
      $('nome').focus();
    } else {
      avisar('falha', dados.erro);
    }
  } catch {
    avisar('falha', 'Falha de conexão com o servidor.');
  } finally {
    btn.disabled = false;
  }
});
</script>

</body>
</html>
