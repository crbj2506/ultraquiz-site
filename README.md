# JW Quiz

Aplicação web de quiz teocrático desenvolvida com Laravel + Vue.

O sistema permite:
- responder perguntas sem autenticação na página principal;
- autenticar usuário para jogar uma partida com **20 questões**;
- registrar estatísticas das respostas;
- gerenciar conteúdo e permissões (Supervisor/Administrador).

## Tecnologias

- PHP 8.2+ (compatível com PHP 8.5)
- Laravel 12
- MySQL
- Vite 6
- Vue 3
- Bootstrap 5

## Requisitos

- PHP e Composer instalados
- Node.js e npm instalados
- MySQL em execução

## Instalação

1. Clone o repositório e acesse a pasta do projeto.
2. Instale dependências do backend:

```bash
composer install
```

3. Instale dependências do frontend:

```bash
npm install
```

4. Crie o arquivo de ambiente:

```bash
cp .env.example .env
```

No Windows PowerShell, use:

```powershell
Copy-Item .env.example .env
```

5. Gere a chave da aplicação:

```bash
php artisan key:generate
```

6. Configure banco no `.env` (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

7. Execute as migrations:

```bash
php artisan migrate
```

## Execução em desenvolvimento

Abra dois terminais na raiz do projeto.

Terminal 1 (Laravel):

```bash
php artisan serve --host=127.0.0.1 --port=8080
```

Terminal 2 (Vite):

```bash
npm run dev
```

A aplicação ficará disponível em:
- Backend: `http://127.0.0.1:8080`
- Frontend (assets Vite): `http://localhost:5173`

## Fluxos principais

### 1) Quiz sem login

- Rota principal: `GET /`
- Permite responder perguntas diretamente.

### 2) Partida autenticada (20 questões)

- Rota: `GET|POST /partida/{questao?}`
- Requer e-mail verificado e permissão de jogador.
- Quantidade de questões controlada por `APP_NUMERO_QUESTOES_PARTIDA` no `.env`.
- Valor padrão atual: `20`.

## Permissões e áreas administrativas

- **Jogador**: jogar partidas
- **Supervisor**: gestão de sugestões
- **Administrador**: CRUD de questões, usuários, permissões, respostas e logs

## Scripts úteis

- Dev frontend:

```bash
npm run dev
```

- Build frontend:

```bash
npm run build
```

- Limpar cache do Laravel:

```bash
php artisan optimize:clear
```

## Problemas comuns

### Erro Rollup no Windows

Se ocorrer erro de módulo opcional do Rollup (ex.: `@rollup/rollup-win32-x64-msvc`):

```powershell
Remove-Item -Recurse -Force node_modules
Remove-Item -Force package-lock.json
npm cache verify
npm install
```

### Aviso de depreciação no PHP 8.5

Este projeto já está ajustado para a mudança da constante `PDO::MYSQL_ATTR_SSL_CA` para `Pdo\Mysql::ATTR_SSL_CA` em `config/database.php`.

## Observações

- O projeto usa autenticação padrão do Laravel UI (`Auth::routes`).
- Para recursos que dependem de permissões, garanta que os usuários/perfis estejam configurados no banco.
