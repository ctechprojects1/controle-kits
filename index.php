<?php
require __DIR__ . '/config/auth.php';
$usuario = exigirLoginPagina();
$paginaAtiva = 'vendas';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vendas — Controle de Kits</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css?v=5">
</head>
<body>

<?php include __DIR__ . '/config/navbar.php'; ?>

<main class="container estreito">
  <h1 class="titulo-pagina">Registrar venda</h1>
  <p class="subtitulo-pagina">Bipe ou digite o SKU, informe a nota e finalize. Tudo pelo teclado.</p>

  <div id="alerta" class="alerta" role="alert"></div>

  <div class="card">
    <form id="form-venda" autocomplete="off">
      <div class="grupo">
        <label for="sku">SKU do Kit <span class="dica">— Enter ou Tab para buscar</span></label>
        <input type="text" id="sku" name="sku" class="campo-grande"
               placeholder="Ex.: KIT-FESTA-50" autofocus required>
        <div id="kit-preview" class="kit-preview">
          <div class="icone">📦</div>
          <div>
            <div class="nome" id="kit-nome"></div>
            <div class="detalhe" id="kit-detalhe"></div>
          </div>
          <div class="preco" id="kit-preco"></div>
        </div>
      </div>

      <div class="linha-campos">
        <div class="grupo">
          <label for="preco">Preço da venda (R$) <span class="dica">— ajuste se necessário</span></label>
          <input type="text" id="preco" name="preco" class="campo-grande"
                 placeholder="0,00" inputmode="decimal">
        </div>
        <div class="grupo">
          <label for="nota_fiscal">Número da Nota Fiscal</label>
          <input type="text" id="nota_fiscal" name="nota_fiscal" class="campo-grande"
                 placeholder="000000" inputmode="numeric" required>
        </div>
        <div class="grupo">
          <label for="serie_nf">Série</label>
          <input type="text" id="serie_nf" name="serie_nf" class="campo-grande"
                 placeholder="1" inputmode="numeric" required>
        </div>
      </div>

      <button type="submit" id="btn-finalizar" class="btn btn-venda">
        ✓ Finalizar Venda
      </button>
    </form>
  </div>

  <div class="card tabela-card">
    <div class="cabecalho cabecalho-flex">
      <span>Últimas vendas</span>
      <a href="historico.php" class="link-cabecalho">Ver histórico completo →</a>
    </div>
    <div id="lista-vendas"><div class="tabela-vazia">Carregando…</div></div>
  </div>
</main>

<script>
const $ = (id) => document.getElementById(id);

const form       = $('form-venda');
const campoSku   = $('sku');
const campoPreco = $('preco');
const campoNf    = $('nota_fiscal');
const campoSer   = $('serie_nf');
const btn        = $('btn-finalizar');
const alerta     = $('alerta');
const preview    = $('kit-preview');

const EH_ADMIN = <?= ehAdmin() ? 'true' : 'false' ?>;

const brl = (v) => Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

function avisar(tipo, mensagem) {
  alerta.className = 'alerta visivel ' + tipo;
  alerta.textContent = (tipo === 'sucesso' ? '✓ ' : '⚠ ') + mensagem;
  clearTimeout(avisar.timer);
  avisar.timer = setTimeout(() => { alerta.className = 'alerta'; }, 6000);
}

function limparPreview() {
  preview.classList.remove('visivel');
}

// ---- Busca do kit ao sair do campo SKU (Tab) ou Enter -----------------
let skuValido = null;

async function buscarKit() {
  const sku = campoSku.value.trim();
  limparPreview();
  skuValido = null;
  if (!sku) return;

  try {
    const resp = await fetch('api.php?acao=kit&sku=' + encodeURIComponent(sku));
    if (resp.status === 401) { window.location.href = 'login.php'; return; }
    const dados = await resp.json();
    if (!dados.ok) {
      avisar('falha', dados.erro);
      campoSku.select();
      return;
    }
    skuValido = dados.kit.sku;
    const dias = dados.kit.dias_anuncio === null ? ''
      : ' · ' + (dados.kit.dias_anuncio == 0 ? 'anunciado hoje'
        : dados.kit.dias_anuncio + ' dia' + (dados.kit.dias_anuncio == 1 ? '' : 's') + ' à venda');
    $('kit-nome').textContent = dados.kit.nome;
    $('kit-detalhe').textContent = dados.kit.sku + ' · ' + dados.kit.local_anuncio + dias;
    $('kit-preco').textContent = brl(dados.kit.preco);
    campoPreco.value = Number(dados.kit.preco).toFixed(2).replace('.', ',');
    preview.classList.add('visivel');
  } catch {
    avisar('falha', 'Falha de conexão com o servidor.');
  }
}

campoSku.addEventListener('change', buscarKit);
campoSku.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    buscarKit().then(() => { if (skuValido) campoNf.focus(); });
  }
});

// Só dígitos, ponto e vírgula no preço
campoPreco.addEventListener('input', (e) => {
  e.target.value = e.target.value.replace(/[^\d.,]/g, '');
});

// Enter percorre: Preço → NF → Série → envia o formulário
campoPreco.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') { e.preventDefault(); campoNf.focus(); }
});
campoNf.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') { e.preventDefault(); campoSer.focus(); }
});

// ---- Envio da venda ---------------------------------------------------
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  btn.disabled = true;
  btn.textContent = 'Registrando…';

  const corpo = new FormData();
  corpo.append('acao', 'venda');
  corpo.append('sku', campoSku.value.trim());
  corpo.append('preco', campoPreco.value.trim());
  corpo.append('nota_fiscal', campoNf.value.trim());
  corpo.append('serie_nf', campoSer.value.trim());

  try {
    const resp = await fetch('api.php', { method: 'POST', body: corpo });
    if (resp.status === 401) { window.location.href = 'login.php'; return; }
    const dados = await resp.json();

    if (dados.ok) {
      avisar('sucesso', `Venda registrada: ${dados.venda.kit} — ${brl(dados.venda.preco)} (NF ${campoNf.value.trim()})`);
      form.reset();
      limparPreview();
      skuValido = null;
      carregarVendas();
    } else {
      avisar('falha', dados.erro);
    }
  } catch {
    avisar('falha', 'Falha de conexão com o servidor.');
  } finally {
    btn.disabled = false;
    btn.textContent = '✓ Finalizar Venda';
    campoSku.focus();
  }
});

// ---- Últimas vendas ---------------------------------------------------
async function carregarVendas() {
  try {
    const resp = await fetch('api.php?acao=vendas&limite=15');
    if (resp.status === 401) { window.location.href = 'login.php'; return; }
    const dados = await resp.json();
    const vendas = dados.ok ? dados.vendas : [];

    if (!vendas.length) {
      $('lista-vendas').innerHTML = '<div class="tabela-vazia">Nenhuma venda registrada ainda.</div>';
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
        <td><span class="badge">${v.sku}</span></td>
        <td>${v.kit_nome}</td>
        <td>${v.nota_fiscal} / ${v.serie_nf}</td>
        <td class="monetario">${brl(v.preco_venda)}</td>
        <td><span class="badge badge-dias">${tempo}</span></td>
        <td>${v.vendedor || '—'}</td>
        <td>${data.toLocaleDateString('pt-BR')} ${data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}</td>
        ${excluir}
      </tr>`;
    }).join('');

    const thExcluir = EH_ADMIN ? '<th></th>' : '';
    $('lista-vendas').innerHTML = `<div style="overflow-x:auto"><table>
      <thead><tr><th>SKU</th><th>Kit</th><th>NF / Série</th><th>Valor</th><th>Tempo p/ vender</th><th>Vendedor</th><th>Data</th>${thExcluir}</tr></thead>
      <tbody>${linhas}</tbody>
    </table></div>`;
  } catch {
    $('lista-vendas').innerHTML = '<div class="tabela-vazia">Não foi possível carregar as vendas.</div>';
  }
}

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
