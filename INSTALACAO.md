# Controle de Kits — Guia de Instalação na HostGator

Sistema de controle interno de anúncios e vendas de kits.
Stack: **PHP 8+ / MySQL / HTML+CSS+JS puro** (100% compatível com hospedagem compartilhada cPanel).

## Estrutura de arquivos

```
controle-kits/
├── index.php          → Tela de Vendas (página inicial, exige login)
├── kits.php           → Cadastro de Kits (exige login)
├── usuarios.php       → Administração de usuários (só admin)
├── login.php          → Tela de login
├── sair.php           → Logout
├── api.php            → API JSON (login, kits, vendas, usuários)
├── .htaccess          → proteção das pastas config/ e sql/
├── assets/
│   └── style.css      → design system
├── config/
│   ├── conexao.php    → credenciais do banco (EDITAR na HostGator)
│   ├── auth.php       → sessão e controle de acesso
│   └── navbar.php     → navbar compartilhada
└── sql/
    ├── banco.sql                → criação completa (instalação nova)
    ├── migracao_v2.sql          → v1 → v2 (multi-locais, data_anuncio)
    └── migracao_v3_usuarios.sql → v2 → v3 (usuários, login, vendedor)
```

## Usuários iniciais

| Usuário    | Senha temporária | Perfil        |
|------------|------------------|---------------|
| `admin1`   | `admin123`       | Administrador |
| `admin2`   | `admin123`       | Administrador |
| `operador` | `oper123`        | Operador      |

**TROQUE AS SENHAS no primeiro acesso**: entre como admin → menu **Usuários**
→ "Redefinir senha" em cada um. Administradores veem o menu Usuários;
operadores só registram vendas e cadastram kits. Cada venda registra
automaticamente o vendedor logado e a data — a listagem mostra o tempo
entre o anúncio e a venda, e a média por kit.

## Passo 1 — Criar o banco no cPanel

1. Acesse o cPanel da HostGator → **MySQL Databases** (Bancos de Dados MySQL).
2. Em *Create New Database*, crie um banco, ex.: `kits`.
   O cPanel prefixa com sua conta → `suaconta_kits`.
3. Em *MySQL Users*, crie um usuário (ex.: `kits`) com uma senha forte → vira `suaconta_kits`.
4. Em *Add User To Database*, adicione o usuário ao banco marcando **ALL PRIVILEGES**.

## Passo 2 — Importar as tabelas

1. cPanel → **phpMyAdmin** → selecione o banco `suaconta_kits` na lateral.
2. Aba **SQL** → cole o conteúdo de `sql/banco.sql` **a partir da linha `CREATE TABLE`**
   (pule as linhas `CREATE DATABASE` e `USE`, pois o banco já foi criado pelo cPanel).
3. Execute. Devem aparecer as tabelas `kits` e `vendas`.
4. (Opcional) O bloco final `INSERT INTO kits` cria 3 kits de exemplo — remova se não quiser.

## Passo 3 — Configurar a conexão

Edite `config/conexao.php` com os dados do Passo 1:

```php
define('DB_HOST',    'localhost');
define('DB_NOME',    'suaconta_kits');
define('DB_USUARIO', 'suaconta_kits');
define('DB_SENHA',   'senha_criada_no_cpanel');
```

## Passo 4 — Subir os arquivos

1. cPanel → **File Manager** (Gerenciador de Arquivos).
2. Para publicar em um subdomínio (ex.: `kits.seudominio.com.br`):
   - cPanel → **Domains / Subdomains** → crie o subdomínio apontando para uma pasta, ex.: `public_html/kits`.
   - Se o subdomínio abrir um site estranho/de terceiros, rode o **AutoSSL** em cPanel → *SSL/TLS Status*.
3. Compacte a pasta do projeto em `.zip`, faça upload para a pasta destino e use **Extract**.
4. Confirme que o `.htaccess` foi extraído (ative "Show Hidden Files" nas configurações do File Manager).

## Passo 5 — Testar

1. Acesse a URL → deve abrir a **Tela de Vendas**.
2. Vá em **Cadastro de Kits** e cadastre um kit.
3. Volte em **Vendas**, digite o SKU + Enter → o kit aparece no preview → informe NF e série → **Finalizar Venda**.

## Observações

- O `.htaccess` já bloqueia acesso direto às pastas `config/` e `sql/` — não precisa de regra extra de rotas (não há roteador; as páginas são acessadas direto).
- A "exclusão" de kit é lógica (campo `ativo = 0`): o histórico de vendas nunca é perdido.
- A dupla **NF + Série** é única: o sistema avisa se a mesma nota for lançada duas vezes.
- PHP mínimo: 8.0. Se necessário, ajuste em cPanel → *MultiPHP Manager*.

## Teste local (Laragon)

1. Inicie Apache + MySQL no Laragon.
2. Importe `sql/banco.sql` completo (ele cria o banco `controle_kits`).
3. `config/conexao.php` já vem configurado para o Laragon (root, sem senha).
4. Acesse `http://localhost/controle-kits/`.
