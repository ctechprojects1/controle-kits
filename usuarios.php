<?php
require __DIR__ . '/config/auth.php';
$usuario = exigirAdminPagina();
$paginaAtiva = 'usuarios';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Usuários — Controle de Kits</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css?v=5">
</head>
<body>

<?php include __DIR__ . '/config/navbar.php'; ?>

<main class="container">
  <h1 class="titulo-pagina">Usuários</h1>
  <p class="subtitulo-pagina">Crie e gerencie os acessos ao sistema. Administradores gerenciam tudo; operadores registram vendas.</p>

  <div id="alerta" class="alerta" role="alert"></div>

  <div class="card">
    <form id="form-usuario" autocomplete="off">
      <div class="linha-campos">
        <div class="grupo" style="flex: 2">
          <label for="nome">Nome completo</label>
          <input type="text" id="nome" name="nome" placeholder="Ex.: Maria Silva" autofocus required>
        </div>
        <div class="grupo">
          <label for="usuario">Usuário <span class="dica">— para login</span></label>
          <input type="text" id="usuario" name="usuario" placeholder="ex.: maria" autocapitalize="none" required>
        </div>
      </div>

      <div class="linha-campos">
        <div class="grupo">
          <label for="senha">Senha <span class="dica">— mínimo 6 caracteres</span></label>
          <input type="password" id="senha" name="senha" placeholder="••••••••" required>
        </div>
        <div class="grupo">
          <label>Perfil de acesso</label>
          <div class="chips">
            <label class="chip"><input type="radio" name="perfil" value="operador" checked><span>Operador</span></label>
            <label class="chip"><input type="radio" name="perfil" value="admin"><span>Administrador</span></label>
          </div>
        </div>
      </div>

      <button type="submit" id="btn-salvar" class="btn btn-primario">+ Criar Usuário</button>
    </form>
  </div>

  <div class="card tabela-card">
    <div class="cabecalho">Usuários do sistema</div>
    <div id="lista-usuarios"><div class="tabela-vazia">Carregando…</div></div>
  </div>
</main>

<script>
const $ = (id) => document.getElementById(id);

const form   = $('form-usuario');
const btn    = $('btn-salvar');
const alerta = $('alerta');

function avisar(tipo, mensagem) {
  alerta.className = 'alerta visivel ' + tipo;
  alerta.textContent = (tipo === 'sucesso' ? '✓ ' : '⚠ ') + mensagem;
  clearTimeout(avisar.timer);
  avisar.timer = setTimeout(() => { alerta.className = 'alerta'; }, 6000);
}

async function chamarApi(corpo) {
  const resp = await fetch('api.php', { method: 'POST', body: corpo });
  if (resp.status === 401) { window.location.href = 'login.php'; throw new Error('sessão'); }
  return resp.json();
}

// ---- Criar usuário ----------------------------------------------------
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  btn.disabled = true;

  const corpo = new FormData(form);
  corpo.append('acao', 'criar_usuario');

  try {
    const dados = await chamarApi(corpo);
    if (dados.ok) {
      avisar('sucesso', `Usuário "${dados.usuario}" criado com sucesso!`);
      form.reset();
      $('nome').focus();
      carregarUsuarios();
    } else {
      avisar('falha', dados.erro);
    }
  } catch {}
  btn.disabled = false;
});

// ---- Listar -----------------------------------------------------------
async function carregarUsuarios() {
  try {
    const resp = await fetch('api.php?acao=usuarios');
    if (resp.status === 401) { window.location.href = 'login.php'; return; }
    const dados = await resp.json();
    const usuarios = dados.ok ? dados.usuarios : [];

    if (!usuarios.length) {
      $('lista-usuarios').innerHTML = '<div class="tabela-vazia">Nenhum usuário cadastrado.</div>';
      return;
    }

    const linhas = usuarios.map((u) => {
      const perfil = u.perfil === 'admin'
        ? '<span class="badge badge-admin">Administrador</span>'
        : '<span class="badge">Operador</span>';
      const status = u.ativo == 1
        ? '<span class="badge badge-dias">Ativo</span>'
        : '<span class="badge badge-inativo">Inativo</span>';

      return `<tr>
        <td><strong>${u.nome}</strong></td>
        <td>${u.usuario}</td>
        <td>${perfil}</td>
        <td>${status}</td>
        <td style="text-align:center">${u.total_vendas}</td>
        <td style="text-align:right; white-space:nowrap">
          <button class="btn btn-suave" onclick="redefinirSenha(${u.id}, '${u.usuario}')">Redefinir senha</button>
          <button class="btn btn-perigo" onclick="alternarUsuario(${u.id}, '${u.usuario}', ${u.ativo})">
            ${u.ativo == 1 ? 'Desativar' : 'Reativar'}
          </button>
        </td>
      </tr>`;
    }).join('');

    $('lista-usuarios').innerHTML = `<div style="overflow-x:auto"><table>
      <thead><tr>
        <th>Nome</th><th>Usuário</th><th>Perfil</th><th>Status</th><th>Vendas</th><th></th>
      </tr></thead>
      <tbody>${linhas}</tbody>
    </table></div>`;
  } catch {
    $('lista-usuarios').innerHTML = '<div class="tabela-vazia">Não foi possível carregar os usuários.</div>';
  }
}

// ---- Ações ------------------------------------------------------------
async function redefinirSenha(id, login) {
  const senha = prompt(`Nova senha para "${login}" (mínimo 6 caracteres):`);
  if (senha === null) return;

  const corpo = new FormData();
  corpo.append('acao', 'redefinir_senha');
  corpo.append('id', id);
  corpo.append('senha', senha);

  const dados = await chamarApi(corpo);
  dados.ok ? avisar('sucesso', `Senha de "${login}" redefinida.`) : avisar('falha', dados.erro);
}

async function alternarUsuario(id, login, ativo) {
  const acao = ativo == 1 ? 'desativar' : 'reativar';
  if (!confirm(`Deseja ${acao} o usuário "${login}"?`)) return;

  const corpo = new FormData();
  corpo.append('acao', 'alternar_usuario');
  corpo.append('id', id);

  const dados = await chamarApi(corpo);
  if (dados.ok) {
    avisar('sucesso', `Usuário "${login}" ${ativo == 1 ? 'desativado' : 'reativado'}.`);
    carregarUsuarios();
  } else {
    avisar('falha', dados.erro);
  }
}

carregarUsuarios();
</script>

</body>
</html>
