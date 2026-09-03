<?php
require __DIR__ . '/config/auth.php';
$usuario = exigirLoginPagina();
$paginaAtiva = 'anuncios';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kits à Venda — Controle de Kits</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css?v=5">
</head>
<body>

<?php include __DIR__ . '/config/navbar.php'; ?>

<main class="container">
  <h1 class="titulo-pagina">Kits à Venda</h1>
  <p class="subtitulo-pagina">Acompanhe os kits ativos, há quanto tempo estão anunciados e o desempenho de venda.</p>

  <div id="alerta" class="alerta" role="alert"></div>

  <div class="stats">
    <div class="stat-card">
      <span class="stat-valor" id="stat-kits">—</span>
      <span class="stat-rotulo">Kits ativos</span>
    </div>
    <div class="stat-card">
      <span class="stat-valor" id="stat-vendas">—</span>
      <span class="stat-rotulo">Vendas registradas</span>
    </div>
    <div class="stat-card">
      <span class="stat-valor" id="stat-media">—</span>
      <span class="stat-rotulo">Média p/ vender</span>
    </div>
  </div>

  <div class="card tabela-card">
    <div class="cabecalho cabecalho-flex">
      <span>Kits ativos em venda</span>
      <input type="text" id="busca" class="campo-busca" placeholder="🔍 Buscar por nome, SKU ou local…">
    </div>
    <div id="lista-kits"><div class="tabela-vazia">Carregando…</div></div>
  </div>
</main>

<script>
const $ = (id) => document.getElementById(id);
const alerta = $('alerta');
const brl = (v) => Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

let todosKits = [];

function avisar(tipo, mensagem) {
  alerta.className = 'alerta visivel ' + tipo;
  alerta.textContent = (tipo === 'sucesso' ? '✓ ' : '⚠ ') + mensagem;
  clearTimeout(avisar.timer);
  avisar.timer = setTimeout(() => { alerta.className = 'alerta'; }, 6000);
}

async function carregarKits() {
  try {
    const resp = await fetch('api.php?acao=kits');
    if (resp.status === 401) { window.location.href = 'login.php'; return; }
    const dados = await resp.json();
    todosKits = dados.ok ? dados.kits : [];
    atualizarStats();
    renderizar();
  } catch {
    $('lista-kits').innerHTML = '<div class="tabela-vazia">Não foi possível carregar os kits.</div>';
  }
}

function atualizarStats() {
  $('stat-kits').textContent = todosKits.length;
  $('stat-vendas').textContent = todosKits.reduce((s, k) => s + Number(k.total_vendas), 0);

  const medias = todosKits.filter((k) => k.media_dias_venda !== null).map((k) => Number(k.media_dias_venda));
  $('stat-media').textContent = medias.length
    ? '~' + Math.round(medias.reduce((s, m) => s + m, 0) / medias.length) + ' dias'
    : '—';
}

function renderizar() {
  const termo = $('busca').value.trim().toLowerCase();
  const kits = termo
    ? todosKits.filter((k) =>
        k.nome.toLowerCase().includes(termo) ||
        k.sku.toLowerCase().includes(termo) ||
        (k.local_anuncio || '').toLowerCase().includes(termo))
    : todosKits;

  if (!kits.length) {
    $('lista-kits').innerHTML = `<div class="tabela-vazia">${termo ? 'Nenhum kit encontrado para "' + termo + '".' : 'Nenhum kit cadastrado ainda.'}</div>`;
    return;
  }

  const linhas = kits.map((k) => {
    const locais = (k.local_anuncio || '').split(',')
      .map((l) => l.trim()).filter(Boolean)
      .map((l) => `<span class="badge badge-local">${l}</span>`).join(' ');

    const dataBr = k.data_anuncio ? k.data_anuncio.split('-').reverse().join('/') : '—';

    const dias = k.dias_anuncio === null ? '—'
      : k.dias_anuncio == 0 ? 'hoje'
      : `${k.dias_anuncio} dia${k.dias_anuncio == 1 ? '' : 's'}`;

    const media = k.media_dias_venda === null ? '—'
      : k.media_dias_venda == 0 ? 'no dia'
      : `~${k.media_dias_venda} dia${k.media_dias_venda == 1 ? '' : 's'}`;

    return `<tr>
      <td><span class="badge">${k.sku}</span></td>
      <td>${k.nome}</td>
      <td class="monetario">${brl(k.preco)}</td>
      <td>${locais}</td>
      <td>${dataBr}</td>
      <td><span class="badge badge-dias">${dias}</span></td>
      <td style="text-align:center">${k.total_vendas}</td>
      <td>${media}</td>
      <td style="text-align:right">
        <button class="btn btn-perigo" onclick="excluirKit(${k.id}, '${k.sku}')">Excluir</button>
      </td>
    </tr>`;
  }).join('');

  $('lista-kits').innerHTML = `<div style="overflow-x:auto"><table>
    <thead><tr>
      <th>SKU</th><th>Nome</th><th>Preço</th><th>Anúncios</th>
      <th>Anunciado em</th><th>Dias à venda</th><th>Vendas</th><th>Média p/ vender</th><th></th>
    </tr></thead>
    <tbody>${linhas}</tbody>
  </table></div>`;
}

$('busca').addEventListener('input', renderizar);

async function excluirKit(id, sku) {
  if (!confirm(`Excluir o kit ${sku}? Ele deixará de aparecer na tela de vendas (o histórico de vendas é mantido).`)) return;

  const corpo = new FormData();
  corpo.append('acao', 'excluir_kit');
  corpo.append('id', id);

  const resp = await fetch('api.php', { method: 'POST', body: corpo });
  if (resp.status === 401) { window.location.href = 'login.php'; return; }
  const dados = await resp.json();

  if (dados.ok) {
    avisar('sucesso', `Kit ${sku} excluído.`);
    carregarKits();
  } else {
    avisar('falha', dados.erro);
  }
}

carregarKits();
</script>

</body>
</html>
