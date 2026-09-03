<?php
/**
 * api.php — Endpoints JSON do sistema.
 *
 * GET  api.php?acao=kits                 → lista todos os kits ativos
 * GET  api.php?acao=kit&sku=XXX          → busca um kit pelo SKU
 * GET  api.php?acao=vendas&limite=15     → últimas vendas
 * POST acao=criar_kit                    → cadastra um kit
 * POST acao=excluir_kit                  → desativa um kit
 * POST acao=venda                        → registra uma venda
 */

declare(strict_types=1);

require __DIR__ . '/config/conexao.php';
require __DIR__ . '/config/auth.php';

header('Content-Type: application/json; charset=utf-8');

function responder(array $dados, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

function erro(string $mensagem, int $status = 400): void
{
    responder(['ok' => false, 'erro' => $mensagem], $status);
}

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

// Controle de acesso: 'login' é público; gestão de usuários é só admin.
$ACOES_ADMIN = ['usuarios', 'criar_usuario', 'redefinir_senha', 'alternar_usuario', 'excluir_venda'];

if ($acao !== 'login' && !usuarioLogado()) {
    erro('Sessão expirada. Faça login novamente.', 401);
}
if (in_array($acao, $ACOES_ADMIN, true) && !ehAdmin()) {
    erro('Acesso restrito a administradores.', 403);
}

try {
    $pdo = conectar();

    switch ($acao) {

        // ------------------------------------------------------- login
        case 'login':
            $login = trim($_POST['usuario'] ?? '');
            $senha = $_POST['senha'] ?? '';
            if ($login === '' || $senha === '') {
                erro('Informe usuário e senha.');
            }

            $stmt = $pdo->prepare(
                "SELECT id, nome, usuario, senha_hash, perfil
                   FROM usuarios WHERE usuario = ? AND ativo = 1"
            );
            $stmt->execute([$login]);
            $u = $stmt->fetch();

            if (!$u || !password_verify($senha, $u['senha_hash'])) {
                erro('Usuário ou senha inválidos.', 401);
            }

            session_regenerate_id(true);
            $_SESSION['usuario'] = [
                'id'     => (int)$u['id'],
                'nome'   => $u['nome'],
                'perfil' => $u['perfil'],
            ];
            responder(['ok' => true, 'usuario' => $_SESSION['usuario']]);

        // ------------------------------------------------ listar usuários
        case 'usuarios':
            $usuarios = $pdo->query(
                "SELECT u.id, u.nome, u.usuario, u.perfil, u.ativo, u.criado_em,
                        COUNT(v.id) AS total_vendas
                   FROM usuarios u
                   LEFT JOIN vendas v ON v.usuario_id = u.id
                  GROUP BY u.id
                  ORDER BY u.perfil, u.nome"
            )->fetchAll();
            responder(['ok' => true, 'usuarios' => $usuarios]);

        // ------------------------------------------------ criar usuário
        case 'criar_usuario':
            $nome   = trim($_POST['nome'] ?? '');
            $login  = strtolower(trim($_POST['usuario'] ?? ''));
            $senha  = $_POST['senha'] ?? '';
            $perfil = $_POST['perfil'] ?? 'operador';

            if ($nome === '' || $login === '' || $senha === '') {
                erro('Preencha nome, usuário e senha.');
            }
            if (!preg_match('/^[a-z0-9._-]{3,50}$/', $login)) {
                erro('Usuário deve ter 3+ caracteres: letras, números, ponto, hífen.');
            }
            if (strlen($senha) < 6) {
                erro('A senha deve ter pelo menos 6 caracteres.');
            }
            if (!in_array($perfil, ['admin', 'operador'], true)) {
                erro('Perfil inválido.');
            }

            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $stmt->execute([$login]);
            if ($stmt->fetch()) {
                erro("Já existe um usuário \"$login\".", 409);
            }

            $stmt = $pdo->prepare(
                "INSERT INTO usuarios (nome, usuario, senha_hash, perfil) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$nome, $login, password_hash($senha, PASSWORD_DEFAULT), $perfil]);
            responder(['ok' => true, 'usuario' => $login]);

        // ---------------------------------------------- redefinir senha
        case 'redefinir_senha':
            $id    = (int)($_POST['id'] ?? 0);
            $senha = $_POST['senha'] ?? '';
            if ($id <= 0 || strlen($senha) < 6) {
                erro('A nova senha deve ter pelo menos 6 caracteres.');
            }
            $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?");
            $stmt->execute([password_hash($senha, PASSWORD_DEFAULT), $id]);
            responder(['ok' => true]);

        // ------------------------------------- ativar/desativar usuário
        case 'alternar_usuario':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                erro('Usuário inválido.');
            }
            if ($id === (int)usuarioLogado()['id']) {
                erro('Você não pode desativar o próprio usuário.');
            }
            $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 1 - ativo WHERE id = ?");
            $stmt->execute([$id]);
            responder(['ok' => true]);

        // ---------------------------------------------------- listar kits
        case 'kits':
            $kits = $pdo->query(
                "SELECT k.id, k.nome, k.sku, k.preco, k.local_anuncio,
                        k.data_anuncio,
                        DATEDIFF(CURDATE(), k.data_anuncio) AS dias_anuncio,
                        COUNT(v.id) AS total_vendas,
                        ROUND(AVG(DATEDIFF(DATE(v.data_venda), k.data_anuncio))) AS media_dias_venda,
                        k.criado_em
                   FROM kits k
                   LEFT JOIN vendas v ON v.kit_id = k.id
                  WHERE k.ativo = 1
                  GROUP BY k.id
                  ORDER BY k.nome"
            )->fetchAll();
            responder(['ok' => true, 'kits' => $kits]);

        // ------------------------------------------------ buscar por SKU
        case 'kit':
            $sku = trim($_GET['sku'] ?? '');
            if ($sku === '') {
                erro('Informe o SKU.');
            }
            $stmt = $pdo->prepare(
                "SELECT id, nome, sku, preco, local_anuncio, data_anuncio,
                        DATEDIFF(CURDATE(), data_anuncio) AS dias_anuncio
                   FROM kits WHERE sku = ? AND ativo = 1"
            );
            $stmt->execute([$sku]);
            $kit = $stmt->fetch();
            if (!$kit) {
                erro("SKU \"$sku\" não encontrado.", 404);
            }
            responder(['ok' => true, 'kit' => $kit]);

        // ------------------------------------------------- criar kit
        case 'criar_kit':
            $nome  = trim($_POST['nome'] ?? '');
            $sku   = strtoupper(trim($_POST['sku'] ?? ''));
            $preco = str_replace(',', '.', str_replace('.', '', trim($_POST['preco'] ?? '')));

            // locais chega como array (checkboxes "locais[]")
            $locais = $_POST['locais'] ?? [];
            if (is_string($locais)) {
                $locais = [$locais];
            }
            $locais = array_values(array_filter(array_map('trim', $locais)));

            $dataAnuncio = trim($_POST['data_anuncio'] ?? '');
            if ($dataAnuncio === '') {
                $dataAnuncio = date('Y-m-d');
            }
            $dt = DateTime::createFromFormat('Y-m-d', $dataAnuncio);
            if (!$dt || $dt->format('Y-m-d') !== $dataAnuncio) {
                erro('Data do anúncio inválida.');
            }

            if ($nome === '' || $sku === '') {
                erro('Preencha todos os campos obrigatórios.');
            }
            if (!$locais) {
                erro('Selecione pelo menos um local de anúncio.');
            }
            if (!is_numeric($preco) || (float)$preco < 0) {
                erro('Preço inválido.');
            }

            $stmt = $pdo->prepare("SELECT id FROM kits WHERE sku = ?");
            $stmt->execute([$sku]);
            if ($stmt->fetch()) {
                erro("Já existe um kit com o SKU \"$sku\".", 409);
            }

            $stmt = $pdo->prepare(
                "INSERT INTO kits (nome, sku, preco, local_anuncio, data_anuncio)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$nome, $sku, (float)$preco, implode(', ', $locais), $dataAnuncio]);
            responder(['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'sku' => $sku]);

        // ----------------------------------------------- excluir (desativar) kit
        case 'excluir_kit':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                erro('Kit inválido.');
            }
            $stmt = $pdo->prepare("UPDATE kits SET ativo = 0 WHERE id = ?");
            $stmt->execute([$id]);
            responder(['ok' => true]);

        // ------------------------------------------------ registrar venda
        case 'venda':
            $sku   = trim($_POST['sku'] ?? '');
            $nf    = trim($_POST['nota_fiscal'] ?? '');
            $serie = trim($_POST['serie_nf'] ?? '');

            if ($sku === '' || $nf === '' || $serie === '') {
                erro('Preencha SKU, Nota Fiscal e Série.');
            }

            $stmt = $pdo->prepare(
                "SELECT id, nome, preco FROM kits WHERE sku = ? AND ativo = 1"
            );
            $stmt->execute([$sku]);
            $kit = $stmt->fetch();
            if (!$kit) {
                erro("SKU \"$sku\" não encontrado. Confira o código ou cadastre o kit.", 404);
            }

            $stmt = $pdo->prepare(
                "SELECT id FROM vendas WHERE nota_fiscal = ? AND serie_nf = ?"
            );
            $stmt->execute([$nf, $serie]);
            if ($stmt->fetch()) {
                erro("A NF $nf (série $serie) já foi registrada.", 409);
            }

            // Preço editável na venda: se informado, substitui o preço do kit
            $precoVenda = (float)$kit['preco'];
            $precoInformado = trim($_POST['preco'] ?? '');
            if ($precoInformado !== '') {
                $p = str_replace(',', '.', str_replace('.', '', $precoInformado));
                if (!is_numeric($p) || (float)$p < 0) {
                    erro('Preço da venda inválido.');
                }
                $precoVenda = (float)$p;
            }

            $stmt = $pdo->prepare(
                "INSERT INTO vendas (kit_id, usuario_id, nota_fiscal, serie_nf, preco_venda)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$kit['id'], usuarioLogado()['id'], $nf, $serie, $precoVenda]);

            responder([
                'ok'    => true,
                'venda' => [
                    'id'    => (int)$pdo->lastInsertId(),
                    'kit'   => $kit['nome'],
                    'preco' => $precoVenda,
                ],
            ]);

        // ------------------------------------- excluir venda (só admin)
        case 'excluir_venda':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                erro('Venda inválida.');
            }
            $stmt = $pdo->prepare("DELETE FROM vendas WHERE id = ?");
            $stmt->execute([$id]);
            responder(['ok' => true]);

        // ------------------- vendas (com filtros de busca e período)
        case 'vendas':
            $limite = min(1000, max(1, (int)($_GET['limite'] ?? 15)));

            $cond = [];
            $par  = [];

            $busca = trim($_GET['busca'] ?? '');
            if ($busca !== '') {
                $cond[] = '(k.sku LIKE ? OR k.nome LIKE ? OR v.nota_fiscal LIKE ?)';
                $like = "%$busca%";
                array_push($par, $like, $like, $like);
            }
            $dataIni = trim($_GET['data_ini'] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataIni)) {
                $cond[] = 'DATE(v.data_venda) >= ?';
                $par[] = $dataIni;
            }
            $dataFim = trim($_GET['data_fim'] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim)) {
                $cond[] = 'DATE(v.data_venda) <= ?';
                $par[] = $dataFim;
            }

            $where = $cond ? 'WHERE ' . implode(' AND ', $cond) : '';

            $stmt = $pdo->prepare(
                "SELECT v.id, v.nota_fiscal, v.serie_nf, v.preco_venda, v.data_venda,
                        k.nome AS kit_nome, k.sku,
                        DATEDIFF(DATE(v.data_venda), k.data_anuncio) AS dias_para_vender,
                        u.nome AS vendedor
                   FROM vendas v
                   JOIN kits k ON k.id = v.kit_id
                   LEFT JOIN usuarios u ON u.id = v.usuario_id
                  $where
                  ORDER BY v.id DESC
                  LIMIT $limite"
            );
            $stmt->execute($par);
            responder(['ok' => true, 'vendas' => $stmt->fetchAll()]);

        default:
            erro('Ação desconhecida.', 404);
    }
} catch (PDOException $e) {
    erro('Erro no banco de dados: ' . $e->getMessage(), 500);
}
