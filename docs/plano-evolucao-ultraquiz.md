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

- Revisar cenários críticos já existentes
- Registrar casos de uso principais e regras de negócio em documentação de testes
- Validar o comportamento atual antes de qualquer refatoração
- Garantir que mudanças futuras não quebrem lobby, multiplayer e autenticação

Objetivos:
- documentar fluxo básico de criação de sala
- mapear regras de início de partida
- confirmar comportamentos de late joiner, empate e fim de rodada
- identificar gaps de regressão antes da refatoração

### Etapa 2 — Extrair serviços do lobby

Arquivos principais envolvidos:
- [app/Http/Controllers/LobbyController.php](C:/projetos/ultraquiz-site.worktrees/code-review-and-improvements/app/Http/Controllers/LobbyController.php)

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

### Etapa 3 — Refatorar o WebSocket em manager de salas

Arquivos principais envolvidos:
- [app/Console/Commands/WebSocketServer.php](C:/projetos/ultraquiz-site.worktrees/code-review-and-improvements/app/Console/Commands/WebSocketServer.php)

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

### Etapa 4 — Validar concorrência e consistência

Ações:
- verificar uso de IDs de sala e equipe em operações simultâneas
- confirmar regras quando vários jogadores entram ao mesmo tempo
- revisar risco em início de partida, votos e atualização de rankings
- identificar pontos onde lock/transaction ou estado imutável sejam necessários

Objetivos:
- reduzir risco de inconsistência em ambiente real
- evitar bugs de race condition no multiplayer
- tornar o jogo mais previsível em produção

### Etapa 5 — Segurança e observabilidade

Arquivos principais envolvidos:
- [app/Http/Middleware/LogAcesso.php](C:/projetos/ultraquiz-site.worktrees/code-review-and-improvements/app/Http/Middleware/LogAcesso.php)
- [config/app.php](C:/projetos/ultraquiz-site.worktrees/code-review-and-improvements/config/app.php)

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

### Etapa 6 — Cobertura de testes automatizados

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

Iniciar pela Etapa 1, validando os fluxos críticos do lobby e multiplayer e registrando os cenários que devem permanecer estáveis quando as refatorações começarem.
