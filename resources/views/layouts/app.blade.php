<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- PWA Meta Tags -->
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="#ffffff">
        <link rel="apple-touch-icon" href="{{ asset('img/logo_jwquiz.png') }}">

        <!-- Fonts -->
        <link rel="dns-prefetch" href="//fonts.gstatic.com">
        <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div id="app" class="min-vh-100 d-flex flex-column bg-light pb-5">
            <nav class="navbar navbar-expand-md navbar-light bg-white bg-opacity-75 sticky-top mt-2 mb-2 mx-2 mx-md-4 rounded-4 shadow-sm" style="backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.4); z-index: 1040;">
                <div class="container-fluid">
                    <a class="navbar-brand d-none d-sm-block" href="{{ url('/') }}">
                        <img class="" src="{{ URL::to('/') }}/img/logo_jwquiz.png">
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        
                        <!-- Lado Esquerdo / Central: Modos de Jogo -->
                        <ul class="navbar-nav me-auto align-items-center mb-2 mb-md-0">
                            <li class="nav-item dropdown me-md-2 mb-2 mb-md-0">
                                <a id="jogosDropdown" class="nav-link dropdown-toggle fw-semibold text-dark fs-5" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    🎮 Modos de Jogo
                                </a>
                                <div class="dropdown-menu rounded-4 shadow border-0 p-2" aria-labelledby="jogosDropdown" style="min-width: 260px;">
                                    <a class="dropdown-item py-2 px-3 rounded-3" href="{{ route('partida.index') }}">
                                        <div class="d-flex align-items-center">
                                            <span class="fs-4 me-3">👤</span>
                                            <div>
                                                <div class="fw-bold">{{ __('Desafio Solo') }}</div>
                                                <div class="text-muted small" style="white-space: normal;">Partida de 20 questões p/ XP</div>
                                            </div>
                                        </div>
                                    </a>
                                    <a class="dropdown-item py-2 px-3 rounded-3 mt-1" href="{{ route('questao.principal') }}">
                                        <div class="d-flex align-items-center">
                                            <span class="fs-4 me-3">⚡</span>
                                            <div>
                                                <div class="fw-bold">{{ __('Treino Rápido') }}</div>
                                                <div class="text-muted small">Responda sem compromisso</div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </li>
                            <li class="nav-item mb-2 mb-md-0">
                                <a class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-flex align-items-center transition-transform" href="{{ route('lobby.index') }}" style="transition: transform 0.2s;">
                                    <span class="fs-5 me-2">👥</span> {{ __('Jogar Multiplayer') }}
                                </a>
                            </li>
                        </ul>

                        <!-- Lado Direito: Login, Register, Admin e Perfil -->
                        <ul class="navbar-nav ms-auto align-items-center">
                            @guest
                                @if (Route::has('login'))
                                    <li class="nav-item ms-md-3">
                                        <a class="nav-link fw-semibold" href="{{ route('login') }}">{{ __('Login') }}</a>
                                    </li>
                                @endif

                                @if (Route::has('register'))
                                    <li class="nav-item">
                                        <a class="nav-link fw-semibold" href="{{ route('register') }}">{{ __('Register') }}</a>
                                    </li>
                                @endif
                            @else
                                @if(auth()->user()->permissoes->contains('permissao', '=', 'Supervisor') || auth()->user()->permissoes->contains('permissao', '=', 'Administrador'))
                                    <li class="nav-item dropdown me-md-3 mb-2 mb-md-0">
                                        <a id="adminDropdown" class="nav-link dropdown-toggle text-dark fw-semibold" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                            ⚙️ Painel Admin
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end rounded-4 shadow border-0 p-2" aria-labelledby="adminDropdown">
                                            <!-- Supervisor Area -->
                                            <h6 class="dropdown-header text-primary fw-bold">Comunidade</h6>
                                            <a class="dropdown-item rounded-2" href="{{ route('sugestaopormim.criar') }}">{{ __('Fazer Sugestão') }}</a>
                                            <a class="dropdown-item rounded-2" href="{{ route('sugestoespormim.listar') }}">{{ __('Minhas Sugestões') }}</a>
                                            <a class="dropdown-item rounded-2" href="{{ route('sugestoes.listar') }}">{{ __('Verificar Sugestões') }}</a>
                                            
                                            @if(auth()->user()->permissoes->contains('permissao', '=', 'Administrador'))
                                                <div class="dropdown-divider my-2"></div>
                                                <!-- Admin Area -->
                                                <h6 class="dropdown-header text-danger fw-bold">Administração Geral</h6>
                                                <a class="dropdown-item rounded-2" href="{{ route('estatistica.index') }}">{{ __('Estatísticas') }}</a>
                                                <a class="dropdown-item rounded-2" href="{{ route('log.index') }}">{{ __('Logs de Acesso') }}</a>
                                                <a class="dropdown-item rounded-2" href="{{ route('permissao.index') }}">{{ __('Permissões') }}</a>
                                                <a class="dropdown-item rounded-2" href="{{ route('questao.index') }}">{{ __('Gestão de Questões') }}</a>
                                                <a class="dropdown-item rounded-2" href="{{ route('sugestao.index') }}">{{ __('Gestão de Sugestões') }}</a>
                                                <a class="dropdown-item rounded-2" href="{{ route('user.index') }}">{{ __('Usuários') }}</a>
                                            @endif
                                        </div>
                                    </li>
                                @endif

                                <li class="nav-item dropdown">
                                    <a id="userDropdown" class="nav-link dropdown-toggle fw-bold text-dark d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=random&color=fff&size=32" class="rounded-circle me-2" alt="Avatar">
                                        {{ Auth::user()->name }}
                                    </a>

                                    <div class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2" aria-labelledby="userDropdown">
                                        <a class="dropdown-item rounded-2 text-danger fw-bold" href="{{ route('logout') }}"
                                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            🚪 {{ __('Sair / Logout') }}
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </div>
                                </li>
                            @endguest
                        </ul>
                    </div>
                </div>
            </nav>

            <main class="py-1 flex-grow-1">
                @yield('content')
            </main>

            <footer class="footer">
                <x-rodape/>
            </footer>
        </div>
        @stack('scripts')
        <script>
            // Registrar PWA Service Worker
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js').then(registration => {
                        console.log('PWA ServiceWorker registrado com sucesso:', registration.scope);
                    }).catch(error => {
                        console.log('Falha ao registrar o PWA ServiceWorker:', error);
                    });
                });
            }
        </script>
    </body>


