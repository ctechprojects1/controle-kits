<?php
require __DIR__ . '/config/auth.php';
$usuario = exigirLoginPagina();
$paginaAtiva = 'historico';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Histórico de Vendas — Controle de Kits</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css?v=5">
</head>
<body>

<?php include __DIR__ . '/config/navbar.php'; ?>

<main class="container">
  <h1 class="titulo-pagina">Histórico de Vendas</h1>
  <p class="subtitulo-pagina">Todas as vendas registradas, com filtros por período e busca.</p>

  <div id="alerta" class="alerta" role="alert"></div>

  <div class="stats stats-4">
    <div class="stat-card">
      <span class="stat-valor" id="stat-qtd">—</span>
      <span class="stat-rotulo">Vendas</span>
    </div>
    <div class="stat-card">
      <span class="stat-valor" id="stat-total">—</span>
      <span class="stat-rotulo">Faturamento</span>
    </div>
    <div class="stat-card">
      <span class="stat-valor" id="stat-ticket">—</span>
      <span class="stat-rotulo">Ticket médio</span>
    </div>
    <div class="stat-card">
      <span class="stat-valor" id="stat-tempo">—</span>
      <span class="stat-rotulo">Tempo médio p/ vender</span>
    </div>
  </div>

  <div class="card filtros-card">
    <div class="filtros">
      <div class="grupo">
        <label for="busca">Buscar</label>
        <input type="text" id="busca" placeholder="Kit, SKU ou nota fiscal…">
      </div>
      <div class="grupo">
        <label for="data_ini">De</label>
        <input type="date" id="data_ini">
      </div>
      <div class="grupo">
        <label for="data_fim">Até</label>
        <input type="date" id="data_fim">
      </div>
      <div class="grupo grupo-botoes">
        <button class="btn btn-suave" id="btn-limpar">Limpar filtros</button>
      </div>
    </div>
    <div class="atalhos-periodo">
      <button class="btn btn-atalho" data-dias="0">Hoje</button>
      <button class="btn btn-atalho" data-dias="7">Últimos 7 dias</button>
      <button class="btn btn-atalho" data-dias="30">Últimos 30 dias</button>
      <button class="btn btn-atalho" data-mes="1">Este mês</button>
    </div>
  </div>

  <div class="card tabela-card">
    <div class="cabecalho">Vendas <span class="dica" id="contagem"></span></div>
    <div id="lista-vendas"><div class="tabela-vazia">Carregando…</div></div>
  </div>
</main>

<script>
const $ = (id) => document.getElementById(id);
const alerta = $('alerta');
const brl = (v) => Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const EH_ADMIN = <?= ehAdmin() ? 'true' : 'false' ?>;

function avisar(tipo, mensagem) {
  alerta.className = 'alerta visivel ' + tipo;
  alerta.textContent = (tipo === 'sucesso' ? '✓ ' : '⚠ ') + mensagem;
  clearTimeout(avisar.timer);
  avisar.timer = setTimeout(() => { alerta.className = 'alerta'; }, 6000);
}

// ---- Carregar com filtros ---------------------------------------------
let timerBusca = null;

async function carregarVendas() {
  const params = new URLSearchParams({ acao: 'vendas', limite: 500 });
  if ($('busca').value.trim()) params.set('busca', $('busca').value.trim());
  if ($('data_ini').value) params.set('data_ini', $('data_ini').value);
  if ($('data_fim').value) params.set('data_fim', $('data_fim').value);

  try {
    const resp = await fetch('api.php?' + params.toString());
    if (resp.status === 401) { window.location.href = 'login.php'; return; }
    const dados = await resp.json();
    const vendas = dados.ok ? dados.vendas : [];
    atualizarStats(vendas);
    renderizar(vendas);
  } catch {
    $('lista-vendas').innerHTML = '<div class="tabela-vazia">Não foi possível carregar as vendas.</div>';
  }
}

function atualizarStats(vendas) {
  const total = vendas.reduce((s, v) => s + Number(v.preco_venda), 0);
  $('stat-qtd').textContent = vendas.length;
  $('stat-total').textContent = brl(total);
  $('stat-ticket').textContent = vendas.length ? brl(total / vendas.length) : '—';

  const tempos = vendas.filter((v) => v.dias_para_vender !== null).map((v) => Number(v.dias_para_vender));
  $('stat-tempo').textContent = tempos.length
    ? '~' + Math.round(tempos.reduce((s, t) => s + t, 0) / tempos.length) + ' dias'
    : '—';
}

function renderizar(vendas) {
  $('contagem').textContent = vendas.length ? `— ${vendas.length} registro${vendas.length == 1 ? '' : 's'}` : '';

  if (!vendas.length) {
    $('lista-vendas').innerHTML = '<div class="tabela-vazia">Nenhuma venda encontrada com esses filtros.</div>';
    return;
  }

  const linhas = vendas.map((v) => {
    const data = new Date(v.data_venda.replace(' ', 'T'));
    const tempo = v.dias_para_vender === null ? '—'
      : v.dias_para_vender == 0 ? 'no dia'
      : `${v.dias_para_vender} dia${v.dias_para_vender == 1 ? '' : 's'}`;
    const excluir = EH_ADMIN
      ? `<td style="text-align:right"><button class="btn btn-perigo" onclick="excluirVenda(${v.id}, '${v.nota_fiscal}/${v.serie_nf}')">Excluir</button></td>`
      : '';
    return `<tr>
      <td>${data.toLocaleDateString('pt-BR')}<br><span class="dica">${data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}</span></td>
      <td><span class="badge">${v.sku}</span></td>
      <td>${v.kit_nome}</td>
      <td>${v.nota_fiscal} / ${v.serie_nf}</td>
      <td class="monetario">${brl(v.preco_venda)}</td>
      <td><span class="badge badge-dias">${tempo}</span></td>
      <td>${v.vendedor || '—'}</td>
      ${excluir}
    </tr>`;
  }).join('');

  const thExcluir = EH_ADMIN ? '<th></th>' : '';
  $('lista-vendas').innerHTML = `<div style="overflow-x:auto"><table>
    <thead><tr>
      <th>Data</th><th>SKU</th><th>Kit</th><th>NF / Série</th><th>Valor</th>
      <th>Tempo p/ vender</th><th>Vendedor</th>${thExcluir}
    </tr></thead>
    <tbody>${linhas}</tbody>
  </table></div>`;
}

// ---- Filtros ----------------------------------------------------------
$('busca').addEventListener('input', () => {
  clearTimeout(timerBusca);
  timerBusca = setTimeout(carregarVendas, 350);
});
$('data_ini').addEventListener('change', carregarVendas);
$('data_fim').addEventListener('change', carregarVendas);

$('btn-limpar').addEventListener('click', () => {
  $('busca').value = '';
  $('data_ini').value = '';
  $('data_fim').value = '';
  carregarVendas();
});

const hojeISO = () => new Date().toISOString().slice(0, 10);

document.querySelectorAll('.btn-atalho').forEach((b) => {
  b.addEventListener('click', () => {
    const hoje = new Date();
    if (b.dataset.mes) {
      $('data_ini').value = hojeISO().slice(0, 8) + '01';
    } else {
      const ini = new Date(hoje);
      ini.setDate(hoje.getDate() - Number(b.dataset.dias));
      $('data_ini').value = ini.toISOString().slice(0, 10);
    }
    $('data_fim').value = hojeISO();
    carregarVendas();
  });
});

// ---- Excluir venda (só admin) -----------------------------------------
async function excluirVenda(id, nf) {
  if (!confirm(`Excluir a venda da NF ${nf}? Essa ação não pode ser desfeita e libera a NF para novo registro.`)) return;

  const corpo = new FormData();
  corpo.append('acao', 'excluir_venda');
  corpo.append('id', id);

  const resp = await fetch('api.php', { method: 'POST', body: corpo });
  if (resp.status === 401) { window.location.href = 'login.php'; return; }
  const dados = await resp.json();

  if (dados.ok) {
    avisar('sucesso', `Venda da NF ${nf} excluída.`);
    carregarVendas();
  } else {
    avisar('falha', dados.erro);
  }
}

carregarVendas();
</script>

</body>
</html>
