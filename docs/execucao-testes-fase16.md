# Execucao de Testes - Fase 16

## Instrucoes Rapidas
1. Preencha Status com: Nao iniciado, Passou, Falhou, Bloqueado.
2. Em Evidencias, anote print, horario e IDs (sala, usuario, questao).
3. Em Defeito, coloque o codigo do bug (ex.: BUG-012) quando houver.

## Resumo da Rodada
| Campo | Valor |
|---|---|
| Responsavel | |
| Data | |
| Ambiente | Local / Homolog / Producao |
| Commit/Branch | |
| Resultado Geral | |

## Matriz de Casos
| ID | Area | Cenario | Resultado Esperado | Status | Evidencias | Defeito |
|---|---|---|---|---|---|---|
| F16-001 | Auth + Lobby | Deslogado clica em Criar Sala, autentica no Google | Usuario retorna e cai direto em sala criada, sem 404 | Nao iniciado | | |
| F16-002 | Lobby | Entrar em sala por PIN valido | Redireciona para a sala correta | Nao iniciado | | |
| F16-003 | Lobby | PIN invalido no join | Exibe erro amigavel de sala nao encontrada | Nao iniciado | | |
| F16-004 | Lobby | Sala finalizada | Exibe erro de partida encerrada | Nao iniciado | | |
| F16-005 | Balanceamento | Late joiner escolhe equipe com vantagem >= 2 | Backend realoca para equipe menor + alerta | Nao iniciado | | |
| F16-006 | Multiplayer | Host inicia partida com 1 jogador em cada equipe | Partida inicia sem bloqueio | Nao iniciado | | |
| F16-007 | Multiplayer | Tentativa de iniciar sem jogadores em ambas equipes | Bloqueia inicio com mensagem clara | Nao iniciado | | |
| F16-008 | Estatistica Solo | Responder 5 questoes no solo | 5 novas linhas em /estatistica | Nao iniciado | | |
| F16-009 | Estatistica Solo | Acerto no solo | Linha criada com resposta_id nulo | Nao iniciado | | |
| F16-010 | Estatistica MP | 4 jogadores, 3 rodadas com voto de todos | 12 novas linhas em /estatistica | Nao iniciado | | |
| F16-011 | Estatistica MP | Rodada com acertos e erros mistos | Cada voto gera 1 linha individual | Nao iniciado | | |
| F16-012 | XP/Elo | Solo com acerto e erro | XP atualiza conforme regra, sem valor negativo indevido | Nao iniciado | | |
| F16-013 | XP/Elo | Multiplayer com usuarios logados | XP de cada jogador atualiza ao fim da rodada | Nao iniciado | | |
| F16-014 | Admin | /admin/partidas-ativas com admin | Lista salas waiting/playing com dados corretos | Nao iniciado | | |
| F16-015 | Admin | /admin/partidas-ativas com usuario comum | Acesso negado (403 custom) | Nao iniciado | | |
| F16-016 | Erros | Rota inexistente | Pagina 404 custom exibida | Nao iniciado | | |
| F16-017 | Erros | Rota protegida sem login | Fluxo de 401/login coerente, sem tela branca | Nao iniciado | | |
| F16-018 | Menu | CTA Jogar Multiplayer no dropdown | Navegacao correta para lobby | Nao iniciado | | |
| F16-019 | Qtd Questoes DEV | APP_ENV=local | Solo com 5 questoes e MP com 3 questoes | Nao iniciado | | |
| F16-020 | Qtd Questoes PROD | APP_ENV=production | Solo com 20 questoes e MP com 10 questoes | Nao iniciado | | |
| F16-021 | Override Manual | APP_NUMERO_QUESTOES_PARTIDA=7 e APP_NUMERO_QUESTOES_MULTIPLAYER=4 | Solo usa 7 e MP usa 4, independente do ambiente | Nao iniciado | | |

## Defeitos Encontrados
| Defeito | Severidade | Caso ID | Descricao | Passos para Reproduzir | Status |
|---|---|---|---|---|---|
| | | | | | |

## Checklist de Fechamento
- Sem defeito bloqueante aberto
- Sem defeito alto nos fluxos criticos (auth lobby, estatistica, XP, quantidade de questoes)
- Evidencias anexadas para todos os casos executados
- Resultado final aprovado pelo responsavel
