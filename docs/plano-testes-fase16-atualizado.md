# Plano de Testes - Fase 16 (Atualizado)

## Objetivo
Validar as melhorias recentes com foco em estabilidade de fluxo e correção de estatísticas/quantidade de questões por ambiente.

## Pré-requisitos
1. Subir ambiente completo com `npm run dev:all`.
2. Banco migrado com `php artisan migrate`.
3. Limpar caches antes da rodada de testes:
   - `php artisan optimize:clear`
4. Perfis prontos:
   - Usuário comum
   - Administrador
   - Convidado/deslogado

## Bloco A - Fluxos Críticos

### A1. Intent Redirect de Criar Sala (deslogado -> Google -> sala)
1. Deslogado acessa Lobby e clica em Criar Nova Sala.
2. Sistema redireciona para login.
3. Após autenticação Google, sistema deve criar sala automaticamente.
4. Usuário deve cair em `/lobby/{pin}`.

Resultado esperado:
- Não cair em 404.
- Sala criada com sucesso e usuário como anfitrião.

### A2. Late Joiner com Auto-Balance
1. Criar desequilíbrio entre equipes (ex.: 3 x 1).
2. Novo jogador escolhe a equipe maior.
3. Backend força entrada na menor equipe.
4. Alerta de auto-balance aparece.

Resultado esperado:
- Jogador realocado automaticamente para equipe menor.

## Bloco B - Estatísticas por Resposta

### B1. Solo
1. Responder 5 questões no modo solo (misturar acertos/erros).
2. Abrir `/estatistica`.

Resultado esperado:
- 5 novas linhas de estatística.
- Acerto gravado com `resposta_id = NULL`.

### B2. Multiplayer
1. Partida com 4 jogadores.
2. Jogar 3 rodadas, todos votando.
3. Abrir `/estatistica`.

Resultado esperado:
- 12 novas linhas (4 jogadores x 3 rodadas).
- Uma linha por voto individual.

## Bloco C - Quantidade de Questões por Ambiente

### C1. Desenvolvimento (reduzido)
Configurar no `.env`:
- `APP_ENV=local`
- `APP_NUMERO_QUESTOES_PARTIDA=`
- `APP_NUMERO_QUESTOES_PARTIDA_DEV=5`
- `APP_NUMERO_QUESTOES_MULTIPLAYER=`
- `APP_NUMERO_QUESTOES_MULTIPLAYER_DEV=3`

Rodar:
- `php artisan optimize:clear`

Testar:
1. Iniciar partida solo e contar total da sessão.
2. Iniciar partida multiplayer e contar rodadas da partida.

Resultado esperado:
- Solo com 5 questões.
- Multiplayer com 3 questões.

### C2. Produção (maior)
Configurar no `.env`:
- `APP_ENV=production`
- `APP_NUMERO_QUESTOES_PARTIDA=`
- `APP_NUMERO_QUESTOES_PARTIDA_PROD=20`
- `APP_NUMERO_QUESTOES_MULTIPLAYER=`
- `APP_NUMERO_QUESTOES_MULTIPLAYER_PROD=10`

Rodar:
- `php artisan optimize:clear`

Testar:
1. Iniciar partida solo e contar total.
2. Iniciar partida multiplayer e contar rodadas.

Resultado esperado:
- Solo com 20 questões.
- Multiplayer com 10 questões.

### C3. Override manual (forçar número único)
Configurar no `.env`:
- `APP_NUMERO_QUESTOES_PARTIDA=7`
- `APP_NUMERO_QUESTOES_MULTIPLAYER=4`

Rodar:
- `php artisan optimize:clear`

Resultado esperado:
- Solo usa 7, independente do ambiente.
- Multiplayer usa 4, independente do ambiente.

## Bloco D - Permissões e Erros
1. Acessar rota admin sem permissão -> 403 custom.
2. Acessar rota inexistente -> 404 custom.
3. Sessão expirada/rota protegida -> 401/redirect adequado.

## Critérios de Aprovação
1. Zero defeitos bloqueantes.
2. Zero defeitos graves em fluxo de autenticação multiplayer.
3. Contagem de questões obedecendo configuração de ambiente/override.
4. Estatística atualizando por resposta individual em todos os modos.
