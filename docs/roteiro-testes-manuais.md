# Roteiro de testes manuais — UltraQuiz

Complementa os testes automatizados (114 testes). Foca no que eles não enxergam: tela, tempo real e uso com várias pessoas.

## 0. Preparação

1. Banco de desenvolvimento com questões aprovadas (pelo menos 25) e um usuário Jogador verificado (e-mail verificado).
2. Para o teste da partida solo com 20 questões, defina no `.env`: `APP_NUMERO_QUESTOES_PARTIDA=20` (em dev o padrão é 5). Para ver o multiplayer curto, use `APP_NUMERO_QUESTOES_MULTIPLAYER=3`. Depois rode `php artisan config:clear`.
3. Terminais separados:
   - `php artisan serve`
   - `php artisan websocket:serve`
   - `Get-Content storage\logs\multiplayer-*.log -Wait -Tail 20` (acompanha o log do multiplayer)
4. Navegadores: Chrome normal (anfitrião), Chrome anônimo (jogador 2), Edge ou Firefox (convidado). Cada um tem sessão própria.
5. Abra o DevTools (F12) na aba Console em todos. **Qualquer erro vermelho é falha.**

Legenda: ☐ passou · ✗ falhou (anote o que viu).

---

## 1. Pergunta individual anônima (página inicial, sem login)

Abra `/` em janela anônima, sem estar logado.

| # | Ação | Esperado | |
|---|------|----------|---|
| 1.1 | Abrir `/` | Aparece uma pergunta com alternativas | ☐ |
| 1.2 | Clicar numa alternativa errada | Feedback de erro, mostra a resposta certa | ☐ |
| 1.3 | Recarregar e responder a certa | Feedback de acerto | ☐ |
| 1.4 | Responder várias perguntas seguidas | Perguntas não repetem em sequência; nenhum erro 500 | ☐ |
| 1.5 | Votar na questão (curtir/não curtir), se o botão estiver visível sem login | Voto aceito ou pedido de login, sem erro técnico | ☐ |
| 1.6 | Votar duas vezes na mesma questão | Não duplica o voto | ☐ |
| 1.7 | Conferir a tabela `estatisticas` | Existe um registro por resposta dada | ☐ |
| 1.8 | Abrir `/partida` sem login | Redireciona ao login | ☐ |

---

## 2. Partida solo de 20 questões (logado)

Entre com o usuário Jogador e abra `/partida`.

| # | Ação | Esperado | |
|---|------|----------|---|
| 2.1 | Iniciar a partida | Primeira pergunta aparece; placar zerado | ☐ |
| 2.2 | Contar as perguntas até o fim | Total de **20** (1 pode ser questão pendente de curadoria) | ☐ |
| 2.3 | Observar a dificuldade | Começa fácil e vai subindo (hoje: até 5 fáceis, depois as demais) | ☐ |
| 2.4 | Nenhuma pergunta repetida na partida | Sem repetição | ☐ |
| 2.5 | Acertar e errar de propósito | Placar e XP mudam conforme o resultado | ☐ |
| 2.6 | Recarregar a página (F5) no meio | Continua na mesma pergunta, placar preservado | ☐ |
| 2.7 | Abrir a mesma partida em outra aba | Estado coerente, sem erro | ☐ |
| 2.8 | Chegar ao fim | Tela final com placar; dá para iniciar nova partida | ☐ |
| 2.9 | Conferir `experiencia` do usuário e `estatisticas` | XP aumentou; um registro por resposta | ☐ |
| 2.10 | Usuário não verificado ou sem permissão tenta `/partida` | Bloqueado | ☐ |

Observação: a distribuição de fáceis e difíceis (item 2.3) é o ponto em que a regra ainda pode evoluir (seleção `dificil()`).

---

## 3. Multiplayer (2 equipes, 3 navegadores)

### 3.1 Criar e entrar

| # | Ação | Esperado | |
|---|------|----------|---|
| 3.1.1 | Chrome A: logado, abrir `/lobby` e criar sala | Redireciona para a sala com PIN de 4 caracteres e 2 equipes | ☐ |
| 3.1.2 | Chrome anônimo B: digitar o PIN (minúsculo, ex.: `ab12`) | Aceita e leva à sala | ☐ |
| 3.1.3 | B sem login | Pede login ou apelido de convidado | ☐ |
| 3.1.4 | Edge C: entrar como convidado com apelido `<b>x</b>` | Recusa (apelido inválido) | ☐ |
| 3.1.5 | C: apelido válido, ex.: `João 1` | Entra; nome aparece escapado e correto | ☐ |
| 3.1.6 | PIN inexistente `ZZZZ` | Mensagem de erro amigável | ☐ |
| 3.1.7 | Digitar PIN errado mais de 20 vezes em 1 min | Passa a retornar 429 (limite) | ☐ |

### 3.2 Equipes

| # | Ação | Esperado | |
|---|------|----------|---|
| 3.2.1 | A escolhe Equipe A, B escolhe Equipe B | Lista atualiza em tempo real nas telas | ☐ |
| 3.2.2 | C tenta entrar em Equipe A (já com mais gente) | Realocado para a menor, com aviso | ☐ |
| 3.2.3 | Só o anfitrião vê o botão de iniciar | B e C não veem | ☐ |
| 3.2.4 | Anfitrião inicia com uma equipe vazia | Recusa com mensagem | ☐ |

### 3.3 Partida

| # | Ação | Esperado | |
|---|------|----------|---|
| 3.3.1 | Anfitrião inicia | Todos vão para a tela de jogo ao mesmo tempo | ☐ |
| 3.3.2 | Primeira pergunta | Aparece igual para todos; timer de 60 s sincronizado | ☐ |
| 3.3.3 | Votar em A e B (mesma equipe com 2 jogadores: votos diferentes) | Apuração por maioria; empate pelo voto mais rápido | ☐ |
| 3.3.4 | Todos votaram | Rodada encerra na hora ("Todos votaram!") | ☐ |
| 3.3.5 | Ninguém vota | Rodada encerra em 60 s ("Tempo esgotado!") | ☐ |
| 3.3.6 | Votar durante o intervalo de 8 s | Voto ignorado, placar não dobra | ☐ |
| 3.3.7 | F5 no meio da pergunta (jogador B) | Volta com a pergunta e o **tempo restante correto**, sem reiniciar a rodada | ☐ |
| 3.3.8 | Fechar a aba de B e reabrir pelo link | Reconecta, recebe a pergunta atual | ☐ |
| 3.3.9 | Pontuação | 10 pontos por equipe que acertou | ☐ |
| 3.3.10 | Fim da partida | Tela final com vencedora e resultados por pergunta | ☐ |

### 3.4 Casos especiais

| # | Ação | Esperado | |
|---|------|----------|---|
| 3.4.1 | Empate no fim | Morte súbita (até 3 rodadas); depois, empate declarado | ☐ |
| 3.4.2 | Revanche: só uma equipe aceita | Mostra "aguardando"; não reinicia | ☐ |
| 3.4.3 | Revanche: todas as equipes aceitam | Novo jogo, placar zerado, mesmo número de questões | ☐ |
| 3.4.4 | Clicar em revanche antes do fim | Nada acontece | ☐ |
| 3.4.5 | Jogador entra tarde (partida em andamento) | Vê o lobby, escolhe equipe e participa | ☐ |
| 3.4.6 | Jogador fecha tudo no meio | Deixa de contar na apuração; jogo continua | ☐ |
| 3.4.7 | Dois cliques rápidos em "iniciar" | Só uma partida iniciada | ☐ |

### 3.5 Segurança (DevTools de B, aba Console)

| # | Ação | Esperado | |
|---|------|----------|---|
| 3.5.1 | B envia `start` pelo WebSocket manualmente | Ignorado (log: `start_recusado`) | ☐ |
| 3.5.2 | Enviar `join` com token adulterado | `auth_error`, sem entrar | ☐ |
| 3.5.3 | Enviar JSON inválido ou PIN `!!!!` | Sem resposta; log: `mensagem_invalida` | ☐ |
| 3.5.4 | Procurar o token no log | Não aparece | ☐ |

### 3.6 Log esperado (terminal do passo 0.3)

Na ordem: `jogador_entrou` → `partida_iniciada` → `rodada_iniciada` → `voto_registrado` → `rodada_apurada` → … → `partida_finalizada`. Sem `erro_ao_processar_mensagem` nem `erro_no_timer`.

---

## 4. Servidor e implantação

| # | Ação | Esperado | |
|---|------|----------|---|
| 4.1 | Parar o `websocket:serve` e reabrir a sala | Tela mostra desconexão sem quebrar; religando, volta | ☐ |
| 4.2 | Reiniciar o servidor no meio da partida | Jogadores recarregam e voltam à pergunta atual | ☐ |
| 4.3 | Fim de partida: log não cresce sem controle | Arquivo diário `multiplayer-AAAA-MM-DD.log` | ☐ |

---

## 5. Registro de problemas

| Teste | Navegador | O que aconteceu | Erro no Console / log |
|-------|-----------|-----------------|-----------------------|
| | | | |
