# Plano de evolução do UltraQuiz

## Objetivo

Aprimorar a base do projeto sem perder o que já foi construído: a experiência de multiplayer, o lobby, a lógica de partidas cooperativas e os fluxos de autenticação/estatísticas. A prioridade é transformar o código em uma estrutura mais sustentável, testável e resiliente, mantendo o produto funcional.

## Contexto atual

O projeto está em uma fase madura de produto com vários recursos já implementados:

- lobby com PIN, criação de sala e entrada de convidados
- partidas cooperativas em tempo real via WebSocket
- sincronização de votos e cronômetro
- balanceamento de equipes por late joiners
- regras de desempate e sudden death
- ajustes de ambiente dev/prod para quantidade de questões
- auditoria e logs para acesso administrativo

A principal melhoria recomendada agora é estrutural: separar regras de negócio, reduzir responsabilidade concentrada em controladores/comando WebSocket e cobrir cenários críticos com testes automatizados.

## Estratégia geral

A evolução será feita em etapas pequenas e verificáveis, com foco em estabilidade e redução de regressão.

### Etapa 1 — Linha de base e risco

Meta: congelar o comportamento atual em testes automatizados de caracterização (sem alterar código de produção), para que as Etapas 2 e 3 possam refatorar com segurança. Hoje `tests/` só tem os `ExampleTest` padrão, ou seja, não há nenhuma rede de proteção.

Arquivos analisados: [LobbyController.php](../app/Http/Controllers/LobbyController.php) (HTTP) e [WebSocketServer.php](../app/Console/Commands/WebSocketServer.php) (tempo real, ~610 linhas).

#### 1.1 Comportamento atual a ser congelado

**Criação de sala** (`LobbyController@create`)
- Deslogado: grava `url.intended` e `lobby_auto_create` na sessão e redireciona para `login`; após login, `index` cria a sala automaticamente (consome a flag).
- Logado: PIN de 4 caracteres maiúsculos, partida `waiting`, `user_id` = anfitrião, equipes "Equipe A" (`primary`) e "Equipe B" (`danger`).

**Entrada** (`join`, `joinGuest`, `sala`)
- PIN obrigatório de exatamente 4 caracteres; é convertido para maiúsculas.
- PIN inexistente ou partida `finished`: volta com mensagem de erro.
- `joinGuest`: cria usuário `is_guest`, nome "{nick} (Convidado)", e-mail `guest_*@ultraquiz.com`, e já faz login.
- `sala`: exige login; aceita apenas `waiting`/`playing` (senão 404); se `playing` e o usuário já está em equipe, redireciona para `jogar`.

**Escolha de equipe / late joiner** (`escolherEquipe`)
- Exige login (401 JSON).
- Se a equipe escolhida tiver 2 ou mais jogadores a mais que a outra (excluindo o próprio usuário), ele é movido para a menor e recebe a mensagem de auto-balance.
- Remove vínculos anteriores do usuário na partida e cria um novo (trocar de equipe funciona).
- Responde JSON (`success`, `mensagem`) ou redirect com flash `warning`.

**Início** (`iniciarPartida`)
- Só o anfitrião; exige ao menos 1 jogador em "Equipe A" e 1 em "Equipe B".
- Zera pontuações, sorteia questões via `Partida::criar()`, limita pela config (`numero_questoes_multiplayer` > `_prod` (10) / `_dev` (3) por ambiente), grava `questoes_json`, `pergunta_atual_index = 0`, `status = playing`.

**Regras do WebSocket** (ação `join`/`votar`/`start`/`rematch_vote`)
- Voto da equipe = maioria dos votos; empate interno decidido pelo voto mais rápido. Correta é sempre `id = 0`.
- Rodada termina por timer (60s) ou quando todos os jogadores conectados votaram.
- Pontuação: 10 pontos por equipe acertadora; 1 linha em `estatisticas` por voto individual (acerto = `resposta_id` NULL); `acertos`/`erros` da questão incrementam por equipe; XP apenas para não convidados.
- Intervalo de 8s entre perguntas; ao fim, empate de pontos gera morte súbita (até 3 extras); depois disso, empate final.
- Revanche: precisa de voto de pelo menos 1 jogador de cada equipe ativa; zera placar e reinicia.

#### 1.2 Lacunas e riscos já identificados (somente registrar; corrigir em etapas posteriores)

| # | Achado | Local | Etapa |
|---|--------|-------|-------|
| R1 ✅ | O servidor WS confia em `user_id` enviado pelo cliente (sem autenticação) | `onMessage` | 5 |
| R2 ✅ | Ação `start` do WS não verifica se quem enviou é o anfitrião e não valida o DB | `onMessage` | 5 |
| R3 ✅ | `iniciarPartida` não valida o status atual (pode reiniciar partida `playing` ou `finished`) | `LobbyController` | 2 |
| R4 ✅ | `escolherEquipe` não valida o status da partida (aceita `finished`) | `LobbyController` | 2 |
| R5 ✅ | PIN único checado só entre `waiting`; `join` usa `first()` sem filtrar status, podendo achar partida antiga com o mesmo PIN | `create`/`join` | 2/4 |
| R6 ✅ | Revanche usa 3 questões fixas, ignorando a config por ambiente | `rematch_vote` | 3 |
| R7 ✅ | Apuração considera equipes de jogadores desconectados (`player_equipe` nunca é limpo) | `processarFimDaPergunta` | 3/4 |
| R8 ✅ | Morte súbita usa `Questao::inRandomOrder()->first()` sem checar nulo ou repetição | timer de 8s | 3 |
| R9 ✅ | `acertos`/`erros` da questão contam por equipe, enquanto estatísticas contam por voto (divergência intencional?) | `processarFimDaPergunta` | 1 (decidido: a dificuldade para seleção e XP vem da tabela `estatisticas`, um registro por resposta individual; `acertos`/`erros` seguem sendo gravados, mas não decidem mais o XP) |
| R10 ✅ | Apelido de convidado aceitava HTML (XSS armazenado via `innerHTML` na lista da sala) e `equipe_id` não era validado | `LobbyController`, views `lobby/*` | 5 |
| R11 ✅ | A home (`/`) retorna 500 quando não há nenhuma questão no banco (`principal/index.blade.php` lê `->pergunta` de null) | `resources/views/principal/index.blade.php` | 7 |
| R12 ✅ | `votar` não valida `alternativa_id`: um id inexistente gera erro de FK em `estatisticas`, a exceção é engolida pelo `try/catch` e a rodada fica travada até o timer | `onMessage` | 3/5 |

**Autenticação do WebSocket (R1/R2, resolvida):** as views `lobby/sala` e `lobby/jogar` embutem um token criptografado com a `APP_KEY` ([WebSocketToken.php](../app/Support/WebSocketToken.php): usuário + PIN + validade de 6 h). O `join` exige esse token; as demais ações usam a identidade já associada à conexão e ignoram qualquer `user_id` enviado pelo cliente. O `start` só é aceito do anfitrião (conferido no banco).

#### 1.3 Plano de execução (tarefas)

1. ✅ **Preparar o ambiente de teste**: `phpunit.xml` aponta para um banco MySQL dedicado, `ultraquiz_test` (mesmo servidor/credenciais do `.env`, mas banco separado do de desenvolvimento). SQLite foi descartado porque o app usa SQL exclusivo do MySQL (`GREATEST`, `HAVING` sem `GROUP BY` em `Questao::facil()`). Em vez de factories, os cenários são montados por helpers em `tests/TestCase.php` (`criarSala`, `colocarNaEquipe`, `criarQuestoesAprovadas`).
2. ✅ **Testes Feature do lobby** em `tests/Feature/Lobby/`:
   - `CriarSalaTest`: deslogado redireciona e grava intent; logado cria sala + 2 equipes + PIN válido; fluxo `lobby_auto_create`.
   - `EntrarSalaTest`: PIN inválido/inexistente/finalizada; `joinGuest` cria convidado e loga; `sala` exige login.
   - `EscolherEquipeTest`: escolha simples, troca de equipe, auto-balance (3x1), não autenticado = 401, equipe de outra partida = 404.
   - `IniciarPartidaTest`: não anfitrião bloqueado; equipe vazia bloqueada; sucesso grava status, `questoes_json` e respeita limite de questões por ambiente (testar override, prod e dev).
3. ✅ **Testes das regras do WebSocket** em `tests/Feature/Multiplayer/`: executam o `WebSocketServer` real com conexões e loop de eventos falsos (sem abrir porta). Cobrem maioria + desempate por velocidade, pontuação, estatísticas por voto, XP (convidados excluídos), fim antecipado quando todos votam, morte súbita (e o limite de 3), fim de jogo e revanche.
4. ⏳ **Roteiro manual** (complementa `docs/plano-testes-fase16-atualizado.md`): checklist para o que não é automatizável agora (timer real, reconexão com 2+ navegadores).
5. ⏳ **Decidir pontos abertos** com o dono do produto: R9 (contagem por equipe x por voto) e se R3/R4/R5/R11/R12 devem ser tratados como bugs nas etapas indicadas.
6. ✅ **Executar `php artisan test`** e registrar o resultado base (ver 1.6).

#### 1.4 Critérios de conclusão da Etapa 1

- Suíte `php artisan test` verde, cobrindo todos os itens da seção 1.1 que são HTTP.
- Nenhuma alteração em `app/` (apenas `tests/`, `phpunit.xml` e `docs/`).
- Tabela de riscos (1.2) revisada e aprovada, com decisão registrada para R9.
- Roteiro manual de multiplayer atualizado e executado uma vez como linha de base.

#### 1.5 Entregáveis

- `tests/Feature/Lobby/*Test.php` e `tests/Feature/Multiplayer/*Test.php` (com o harness `ServidorWebSocketTestCase`).
- Helpers de cenário em `tests/TestCase.php`.
- Esta seção do plano atualizada com o resultado da linha de base.

#### 1.6 Resultado da linha de base

Como executar (requer MySQL local ativo; o banco `ultraquiz_test` é recriado/migrado pelos próprios testes):

```powershell
php -r "(new PDO('mysql:host=127.0.0.1','root','SENHA'))->exec('CREATE DATABASE IF NOT EXISTS ultraquiz_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');"
$env:XDEBUG_MODE='off'   # opcional: evita lentidão do Xdebug
php artisan test
```

Resultado em 15/10/2026: **48 testes, 151 asserções, todos passando (~6 s)**. Após as correções de R3, R4, R11 e R12: **53 testes, 168 asserções, todos passando**. Após a autenticação do WebSocket (R1/R2): **61 testes, 191 asserções, todos passando**. Após a validação de entrada e escape de HTML (R10): **65 testes, 225 asserções, todos passando**. Após R5–R8: **72 testes, 238 asserções, todos passando**. Após R9 (XP pela dificuldade das estatísticas): **73 testes, 239 asserções, todos passando**.

Pendente para fechar a Etapa 1: itens 4 e 5 da seção 1.3.

### Etapa 2 — Extrair serviços do lobby ✅

Concluída: serviços em `app/Services/Lobby/` (`CriacaoSalaService`, `EntradaSalaService`, `EscolhaEquipeService`, `InicioPartidaService` e `LobbyException` para mensagens de regra). O controller só valida a requisição, chama os serviços e decide a resposta HTTP. 77 testes, 251 asserções, todos passando.

Arquivos principais envolvidos:
- [app/Http/Controllers/LobbyController.php](../app/Http/Controllers/LobbyController.php)

Ações:
- criar service para criação da sala
- criar service para entrada de usuário em partida
- criar service para escolha/equilibrio de equipe
- criar service para validação do início da partida
- centralizar regras e mensagens em classes reutilizáveis

Objetivos:
- remover lógica de negócio do controller
- simplificar as actions HTTP
- facilitar testes isolados de regras do lobby

### Etapa 3 — Refatorar o WebSocket em manager de salas ✅

Concluída: o `WebSocketServer` virou orquestrador (roteamento de mensagens e timers, ~350 linhas, antes ~660). Serviços em `app/Services/Multiplayer/`: `GerenciadorSalas` (estado, conexões, votos, revanche, broadcast), `ApuradorVotos` (maioria + desempate por velocidade), `ApuracaoService` (pontos, XP, estatísticas), `PerguntaService` (dados da pergunta, validação de alternativa, morte súbita). Troca de `SplObjectStorage::attach/detach/contains` (obsoletos no PHP 8.5) por `offset*`. 84 testes, 268 asserções, todos passando; servidor sobe e abre a porta.

Arquivos principais envolvidos:
- [app/Console/Commands/WebSocketServer.php](../app/Console/Commands/WebSocketServer.php)

Ações:
- criar classes para gerenciamento de salas e jogadores
- separar estados de partida (idle, playing, waiting_next)
- mover resolução de votos para um service específico
- separar cálculo de timer, resultados e sincronização de eventos
- remover lógica dispersa do comando principal

Objetivos:
- reduzir acoplamento do servidor de sockets
- facilitar manutenção do fluxo de perguntas e resultados
- melhorar previsibilidade em partidas com vários jogadores

### Etapa 4 — Validar concorrência e consistência ✅

Concluída. Análise: o servidor WebSocket é single-thread (sem corrida entre mensagens), mas havia falhas de estado; o HTTP roda em paralelo e o banco já tinha índices únicos em `pin` e `(partida, usuário)`. Correções: (1) voto no intervalo entre perguntas é ignorado e `processarFimDaPergunta` é idempotente (antes pontuava em dobro e pulava pergunta); (2) revanche só com a partida encerrada (antes reiniciava jogo em andamento); (3) início da partida e escolha de equipe em transação com `lockForUpdate` na sala; (4) criação de sala refaz o PIN se o índice único recusar duplicado. Pendência conhecida e de baixo risco: o XP lê e grava o campo `experiencia` do usuário sem trava (dois jogos simultâneos do mesmo usuário podem perder um incremento). 88 testes, 277 asserções.

Ações:
- verificar uso de IDs de sala e equipe em operações simultâneas
- confirmar regras quando vários jogadores entram ao mesmo tempo
- revisar risco em início de partida, votos e atualização de rankings
- identificar pontos onde lock/transaction ou estado imutável sejam necessários

Objetivos:
- reduzir risco de inconsistência em ambiente real
- evitar bugs de race condition no multiplayer
- tornar o jogo mais previsível em produção

### Etapa 5 — Segurança e observabilidade ✅

Concluída. (1) Canal de log `multiplayer` (`storage/logs/multiplayer.log`, diário, 30 dias, nível em `MULTIPLAYER_LOG_LEVEL`) via `App\Support\LogMultiplayer`; todos os `echo` do `WebSocketServer` foram substituídos por eventos estruturados (`jogador_entrou`, `rodada_iniciada`, `voto_registrado`, `rodada_apurada`, `partida_finalizada` etc.) e o token nunca é logado. (2) Payload do WebSocket validado (tamanho ≤ 2 KB, JSON objeto, ação em whitelist, PIN de 4 caracteres alfanuméricos); inválidos viram aviso `mensagem_invalida` sem resposta. (3) Exceções no `onMessage` e nos timers são capturadas (`erro_ao_processar_mensagem`, `erro_no_timer`): o cliente recebe mensagem genérica e a sala volta a `idle`, sem derrubar o servidor. (4) `throttle` em `lobby/create` (10/min) e `lobby/join` e `join-guest` (20/min). (5) Auditoria do lobby com IP: `sala_criada`, `convidado_criado`, `equipe_escolhida`, `partida_iniciada`, `inicio_recusado`, `pin_nao_encontrado`. 99 testes, 340 asserções. Pendência: `GET /lobby/{pin}/dados` é público e expõe nomes dos jogadores por PIN.
Arquivos principais envolvidos:
- [app/Http/Middleware/LogAcesso.php](../app/Http/Middleware/LogAcesso.php)
- [config/app.php](../config/app.php)

Ações:
- validar payloads de WebSocket e requisições do lobby
- padronizar logs de eventos críticos
- registrar auditoria de ações sensíveis com contexto útil
- reforçar checagens de autenticação e autorização na criação/início de partida
- tratar erros de conexão e sincronização com resposta consistente

Objetivos:
- aumentar segurança dos fluxos sensíveis
- melhorar depuração em produção
- reduzir ruído e aumentar clareza no diagnóstico de incidentes

### Etapa 6 — Cobertura de testes automatizados ✅

Concluída. As ações de criação, PIN, late joiner, início com equipe vazia, votos e fim de rodada já eram cobertas pelas etapas anteriores. Foram adicionados: `ReconexaoESincronizacaoTest` (reconexão no meio da rodada recebe o tempo restante sem criar novo timer; reconectar após fechar a aba volta a receber eventos; entrar no intervalo ou em partida encerrada não reinicia rodada; jogador sem equipe não vota; token de outra sala é recusado) e `AutorizacaoLobbyTest` (401 sem login, equipe de outra sala ou de partida encerrada, `equipe_id` inválido, início por não anfitrião ou sem login, `jogar` fora de `playing`, dados de sala inexistente). Nenhum bug novo encontrado. 114 testes, 365 asserções.
Ações:
- criar testes para criação de sala e PIN
- criar testes para late joiner com balanceamento
- criar testes para início da partida com equipes vazias
- criar testes para processamento de votos e fim de rodada
- criar testes para autenticação e bloqueio de ações não permitidas
- criar testes de websocket com reconnect e sincronização

Objetivos:
- reduzir regressão
- validar regras importantes automaticamente
- dar confiança para refatorações futuras

### Extra — Mix de dificuldade das partidas ✅

Concluído. `Questao::facil()`, `media()` e `dificil()` classificam pela taxa de acerto da tabela `estatisticas` (fácil ≥ 70%, difícil < 40%, média no meio ou com menos de 3 respostas). `Partida::criar(?int)` monta 25% fáceis, 50% médias e 25% difíceis (20 = 5/10/5), em ordem crescente; a questão pendente de curadoria conta como média e faixa sem questões é completada com aleatórias. O multiplayer passa a pedir já a quantidade certa (10 = 3/4/3) em vez de cortar as primeiras. 120 testes, 383 asserções.

### Etapa 7 — Revisão de UX e qualidade de produto

Ações:
- revisar mensagens visuais e feedback do usuário em lobby
- padronizar mensagens de erro e loading
- revisar comportamentos em telas de sala e jogo
- aprimorar acessibilidade básica e consistência visual

Objetivos:
- melhorar percepção de estabilidade e clareza
- reduzir erros de experiência do usuário
- manter a base visual premium do projeto

## Critérios de conclusão por etapa

Cada etapa deve ser considerada concluída apenas se:

- não houver regressão em cenários críticos relevantes
- a mudança estiver documentada
- houver testes cobrindo a regra principal alterada
- o código permanecer legível e sem duplicação excessiva

## Ordem sugerida de execução

1. Etapa 1 — Linha de base e risco
2. Etapa 2 — Extrair serviços do lobby
3. Etapa 3 — Refatorar o WebSocket em manager de salas
4. Etapa 6 — Cobertura de testes automatizados
5. Etapa 4 — Concorrência e consistência
6. Etapa 5 — Segurança e observabilidade
7. Etapa 7 — UX e qualidade de produto

## Observações importantes

- Priorizar estabilidade antes de otimização.
- Evitar grandes refatorações em uma única mudança.
- Alterar uma área por vez, com validação focada.
- Sempre manter a documentação alinhada com o comportamento real do app.

## Próximo passo imediato

Estado em 15/10/2026: Etapas 1 a 6 e o extra de mix de dificuldade concluídos e enviados (`master`, commit `e591c3c`, 120 testes). Os riscos R1–R12 estão tratados.

Para retomar:
1. Executar o roteiro [roteiro-testes-manuais.md](./roteiro-testes-manuais.md) (individual anônimo, solo de 20 questões e multiplayer com 3 navegadores) e anotar o que falhar. O servidor precisa ser reiniciado: `php artisan websocket:serve`. Para a partida solo de 20 questões, defina `APP_NUMERO_QUESTOES_PARTIDA=20` no `.env` e rode `php artisan config:clear`.
2. Corrigir os problemas encontrados no teste manual.
3. Etapa 7 (UX): mensagens de erro e carregamento padronizadas, feedback do lobby e da tela de jogo, acessibilidade básica.

Pendências opcionais, de baixo risco:
- `GET /lobby/{pin}/dados` é público e expõe os nomes dos jogadores a quem souber o PIN.
- O XP do usuário é lido e gravado sem trava (dois jogos simultâneos podem perder um incremento).
- Os contadores `acertos` e `erros` ainda são gravados, mas não decidem mais nada (a dificuldade vem de `estatisticas`).
- Os cortes de dificuldade (70% e 40%, em `Questao`) podem precisar de ajuste quando houver estatísticas reais.

Ambiente de testes: MySQL `ultraquiz_test` (root/123456, 127.0.0.1). Rodar com `$env:XDEBUG_MODE='off'; php artisan test` (cerca de 6 s).
