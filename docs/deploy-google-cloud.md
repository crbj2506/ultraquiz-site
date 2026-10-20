# Implantação em produção no Google Cloud

## Estado conhecido da VM

Com base no diagnóstico executado na VM:

- Debian GNU/Linux 13 (Trixie).
- PHP CLI 8.4.26.
- Apache ativo nas portas 80 e 443; Nginx não está instalado.
- O Apache usa `mod_php`; `proxy`, `proxy_http`, `proxy_wstunnel`, `rewrite` e
  `ssl` estão carregados. `headers` não apareceu na lista de módulos.
- O virtual host `jwquiz.site`/`*.jwquiz.site` serve
  `/var/www/ultraquiz-site/public` com `AllowOverride All`. O domínio público
  da aplicação é `https://jwquiz.site`. `p.jwquiz.site` e
  `compras.jwquiz.site` são sites separados.
- O virtual host HTTPS da aplicação já encaminha `/app` para
  `ws://127.0.0.1:8090/app`.
- MariaDB escutando em `127.0.0.1:3306`.
- O Laravel está em `/var/www/ultraquiz-site`.
- O Supervisor mantém `websockets:websockets_00` em execução; o processo atual
  escuta em `0.0.0.0:8090`. A configuração está em
  `/etc/supervisor/conf.d/websockets.conf`, com `autostart=true`,
  `autorestart=true` e `user=crbj2506`.

Ainda é preciso confirmar as regras de firewall do Google Cloud. Não instale
Nginx, crie um segundo gerenciador de processos nem substitua a configuração
existente do Apache.

O README explica como iniciar a aplicação localmente. Os documentos da fase 16
registram 20 questões solo e 10 multiplayer para produção; não havia um roteiro
de implantação específico. Este guia documenta o cenário observado e indica o
que ainda precisa ser confirmado. Não contém nem requer credenciais da nuvem.

## Arquitetura e exposição de portas

- Publique o Laravel pelo Apache, apontando o `DocumentRoot` para
  `<raiz-do-projeto>/public`.
- O Apache tem `mod_php` carregado; não há motivo conhecido para migrar para
  PHP-FPM nesta publicação.
- O login Google deve usar `https://jwquiz.site/auth/google/callback`; o
  multiplayer usa WebSocket seguro em `wss://jwquiz.site/app`.
- O Apache já encaminha `/app` para `ws://127.0.0.1:8090/app` no virtual host
  da aplicação; preserve essa configuração.
- Configure o WebSocket para escutar somente em `127.0.0.1`. O diagnóstico
  atual mostra `0.0.0.0:8090`, que escuta em todas as interfaces.
- Na firewall do Google Cloud, não permita conexões de entrada públicas para
  as portas 3306 ou 8090. A aplicação pública deve usar 80/443; restrinja SSH
  a endereços administrativos confiáveis.
- Mantenha uma única instância WebSocket. As salas ficam na memória: reiniciar
  o processo interrompe conexões e partidas em andamento, e várias instâncias
  não compartilham o estado.
- O MariaDB já está acessível localmente pela VM; não altere sua configuração
  de rede para permitir acesso público.

## Confirmar a configuração existente

Estes comandos são somente para consulta. Eles ajudam a confirmar os virtual
hosts, os módulos Apache e o programa do Supervisor:

```bash
sudo apachectl -S
sudo apachectl -M | grep -E 'php|proxy|ssl|rewrite|headers'
sudo supervisorctl status
sudo grep -Rnl --include='*.conf' 'websocket:serve' /etc/supervisor
sudo ss -lntp | grep -E ':(80|443|3306|8090)\b'
```

O virtual host da aplicação é
`/etc/apache2/sites-enabled/003-site-jwquiz-padrao-le-ssl.conf`; sua versão
HTTP está em `003-site-jwquiz-padrao.conf`. Não confunda esses arquivos com os
virtual hosts separados de `p.jwquiz.site` e `compras.jwquiz.site`. O PID do
WebSocket pode mudar. Mantenha o Supervisor como único gerenciador do processo.

Para ver somente diretivas não secretas do arquivo do Supervisor que contém o
comando WebSocket:

```bash
sudo grep -nE '^\s*(\[program:|command=|directory=|user=|autostart=|autorestart=|stopsignal=|stopwaitsecs=)' <arquivo-conf-do-supervisor>
```

O arquivo encontrado foi `/etc/supervisor/conf.d/websockets.conf`. Foram
confirmados o programa `websockets`, o comando, `autostart=true`,
`autorestart=true` e `user=crbj2506`.

```bash
sudo grep -nE '^\s*(\[program:|command=|directory=|user=|autostart=|autorestart=|stopsignal=|stopwaitsecs=)' /etc/supervisor/conf.d/websockets.conf
```

Não compartilhe `.env`, linhas `environment=`, senhas, chaves privadas TLS ou
segredos OAuth.

## Preparar a aplicação

Verifique que Composer, Node.js/npm e as extensões PHP necessárias estão
instalados. O PHP precisa do driver PDO MySQL; a VM já mostrou o módulo
`php_module` carregado no Apache, mas confirme que o site consegue executar
PHP antes de publicar.

O site já tem um virtual host HTTPS. Confirme que `jwquiz.site` aponta para o
IP externo da VM, que o certificado é válido e que as portas 80/443 são
permitidas pela firewall do Google Cloud e pelo firewall do sistema operacional.
Não altere o Apache se a configuração existente atender ao site e ao proxy.

No cliente OAuth do Google, autorize o domínio de produção e adicione a URI de
callback exata:

```text
https://jwquiz.site/auth/google/callback
```

Use `https://jwquiz.site` em `APP_URL` e a URI acima em `GOOGLE_REDIRECT_URI`.

## Configurar o `.env` de produção

Crie o `.env` diretamente no servidor a partir de `.env.example`. Mantenha-o
fora do repositório e não copie o `.env` local. Insira os segredos somente no
servidor ou por um gerenciador de segredos:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jwquiz.site
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<banco>
DB_USERNAME=<usuario-da-aplicacao>
DB_PASSWORD=<segredo>

GOOGLE_CLIENT_ID=<id-do-cliente-oauth>
GOOGLE_CLIENT_SECRET=<segredo-oauth>
GOOGLE_REDIRECT_URI=https://jwquiz.site/auth/google/callback

APP_NUMERO_QUESTOES_PARTIDA=
APP_NUMERO_QUESTOES_PARTIDA_PROD=20
APP_NUMERO_QUESTOES_MULTIPLAYER=
APP_NUMERO_QUESTOES_MULTIPLAYER_PROD=10
```

Gere `APP_KEY` uma única vez na instalação inicial com `php artisan key:generate`.
Preserve a mesma chave em todas as publicações: substituí-la invalida sessões e
dados criptografados. Restrinja a leitura do `.env` às contas que executam a
aplicação. Confirme um backup recuperável do MariaDB antes de migrar.

## Publicação inicial

Execute os comandos abaixo na raiz do projeto, depois de confirmar a cópia de
segurança e a conexão com o banco de produção:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan optimize
```

Não execute seeders de desenvolvimento em produção. O `DocumentRoot` do Apache
deve apontar para `public/`, nunca para a raiz do repositório. Garanta que o
usuário que executa o PHP pelo Apache e o usuário do WebSocket possam ler o
código e gravar em `storage/` e `bootstrap/cache/`.

## Apache e proxy WebSocket

O virtual host HTTPS da aplicação já foi identificado em
`/etc/apache2/sites-enabled/003-site-jwquiz-padrao-le-ssl.conf`; ele serve
`/var/www/ultraquiz-site/public`, permite `AllowOverride All` e encaminha
`/app` para `127.0.0.1:8090/app`. Se o site e o multiplayer já funcionam, não
reescreva a configuração existente.

Se uma verificação mostrar que algum desses valores precisa ser corrigido,
faça backup do arquivo primeiro e compare-o com estas diretivas:

```apache
DocumentRoot /var/www/ultraquiz-site/public

<Directory /var/www/ultraquiz-site/public>
    AllowOverride All
    Require all granted
    Options -Indexes
    DirectoryIndex index.php
</Directory>

ProxyRequests Off
ProxyPass /app ws://127.0.0.1:8090/app
ProxyPassReverse /app ws://127.0.0.1:8090/app
ProxyTimeout 3600
```

Os módulos `proxy`, `proxy_wstunnel`, `rewrite` e `ssl` já estão carregados.
`RequestHeader` não é necessário neste exemplo e exigiria `headers`, que não
apareceu na lista atual. Se alterar a configuração, valide antes de recarregar:

```bash
sudo apachectl configtest
sudo systemctl reload apache2
```

O diagnóstico confirmou `php_module` carregado no Apache; PHP-FPM não é
necessário para seguir este roteiro. Recarregue Apache apenas após alterar e
validar sua configuração.

## WebSocket gerenciado pelo Supervisor

O Supervisor já gerencia `websockets:websockets_00`. **Não crie uma unidade
systemd para o mesmo processo.** A configuração em
`/etc/supervisor/conf.d/websockets.conf` usa `autostart=true`,
`autorestart=true` e `user=crbj2506`. O comando atual é
`/usr/bin/php /var/www/ultraquiz-site/artisan websocket:serve --port=8090`,
e ele escuta em todas as interfaces.

O código local agora aceita `--host`; o padrão `0.0.0.0` mantém o funcionamento
existente. Na produção, após publicar esta alteração, configure o `command` da
seção `[program:websockets]` existente para escutar em loopback. Preserve as
outras opções existentes:

```ini
[program:websockets]
command=/usr/bin/php /var/www/ultraquiz-site/artisan websocket:serve --host=127.0.0.1 --port=8090
autostart=true
autorestart=true
user=crbj2506
```

Salve uma cópia de segurança e coordene a alteração do `command` com a
publicação do código novo: a versão antiga pode não reconhecer `--host`.
Depois de publicar o código, aplique a alteração na configuração existente:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
sudo ss -lntp | grep ':8090'
```

O `status` deve mostrar `websockets:websockets_00` ativo e a porta deve
aparecer como `127.0.0.1:8090`, não `0.0.0.0:8090`. Confirme também na
firewall do Google Cloud que 8090 não está liberada para entrada pública.

## Atualizações

1. Faça backup do MariaDB e anote o commit atualmente publicado.
2. Coloque a aplicação em manutenção e pare o WebSocket pelo Supervisor:

   ```bash
   php artisan down --retry=60
   sudo supervisorctl stop websockets:websockets_00
   ```

3. Atualize o código para um commit revisado, preservando `.env` e `APP_KEY`.
4. Instale dependências, compile os assets e aplique migrations revisadas:

   ```bash
   composer install --no-dev --prefer-dist --optimize-autoloader
   npm ci
   npm run build
   php artisan migrate --force
   php artisan optimize:clear
   php artisan optimize
   ```

5. Inicie o WebSocket pelo Supervisor e retire a aplicação da manutenção.
   Recarregue Apache apenas se sua configuração tiver sido alterada:

   ```bash
   sudo apachectl configtest
   sudo systemctl reload apache2
   sudo supervisorctl start websockets:websockets_00
   php artisan up
   ```

Se uma verificação após a publicação falhar, mantenha a aplicação em
manutenção e reverta o código ao commit anterior. Reverter migrations exige um
plano compatível com as alterações e o backup; não execute
`migrate:rollback` às cegas.

## Verificação após publicar

- A página inicial responde em `https://jwquiz.site` sem erro 5xx.
- CSS e JavaScript carregam de `/build/` e não há `public/hot` no servidor.
- O login Google conclui o retorno em `/auth/google/callback`.
- Duas sessões de navegador entram numa sala e recebem eventos por
  `wss://jwquiz.site/app`.
- `sudo supervisorctl status` mostra o WebSocket ativo e `ss -lntp` confirma
  `127.0.0.1:8090`.
- Consulte os logs do Supervisor e `storage/logs/` se houver falhas. Valide as
  rotas solo, lobby e autenticadas com perfis apropriados.

## Limites conhecidos antes de escalar

- O estado de cada sala WebSocket está em memória. Reiniciar o processo
  interrompe partidas e exige uma única instância do servidor WebSocket.
- `SESSION_DRIVER=file` é o padrão; sessões em arquivos locais não são
  compartilhadas por várias VMs. Planeje um armazenamento compartilhado, como
  Redis, antes de escalar horizontalmente.
- Faça e teste backups do MariaDB e monitore disco, memória, CPU, Apache e o
  processo WebSocket no Supervisor.
