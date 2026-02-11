<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="dns-prefetch" href="//fonts.gstatic.com">
        <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div id="app">
            <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <a class="navbar-brand d-none d-sm-block" href="{{ url('/') }}">
                        <img class="" src="{{ URL::to('/') }}/img/logo_ultraquiz.png">
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <div class="container-fluid  d-flex flex-wrap-reverse justify-content-end">
                            <div class="">
                                <ul class="navbar-nav me-auto">
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('questao.principal') }}">{{ __('Responder Questões') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('partida.index') }}">{{ __('Jogar uma Partida') }}</a>
                                    </li>
                                    @guest
                                        @if (Route::has('login'))
                                            <li class="nav-item">
                                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                            </li>
                                        @endif

                                        @if (Route::has('register'))
                                            <li class="nav-item">
                                                <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                            </li>
                                        @endif
                                    @endguest
                                </ul>
                            </div>
                            <div class="">
                                <ul class="navbar-nav me-auto">
                                    @auth
                                        @if(auth()->user()->permissoes->contains('permissao', '=', 'Supervisor') || auth()->user()->permissoes->contains('permissao', '=', 'Administrador'))
                                            <li class="nav-item dropdown">
                                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                                {{ __('Menu de Supervisor') }}
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                                    <a class="dropdown-item" href="{{ route('sugestaopormim.criar') }}">
                                                        {{ __('Fazer Sugestão') }}
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('sugestoespormim.listar') }}">
                                                        {{ __('Minhas Sugestões') }}
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('sugestoes.listar') }}">
                                                        {{ __('Verificar Sugestões') }}
                                                    </a>
                                                </div>
                                            </li>
                                        @endif
                                    @endauth
                                </ul>
                            </div>


                            <div class="">
                                <ul class="navbar-nav me-auto">
                                    @auth
                                        @if(auth()->user()->permissoes->contains('permissao', '=', 'Administrador'))
                                        
                                            <li class="nav-item dropdown">
                                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                                {{ __('Menu de Administrador') }}
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                                    <a class="dropdown-item" href="{{ route('estatistica.index') }}">
                                                        {{ __('Estatisticas') }}
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('log.index') }}">
                                                        {{ __('Logs de Acesso') }}
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('permissao.index') }}">
                                                        {{ __('Permissões Possíveis') }}
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('questao.index') }}">
                                                        {{ __('Questões') }}
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('sugestao.index') }}">
                                                        {{ __('Sugestões') }}
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('user.index') }}">
                                                        {{ __('Usuários do Sistema') }}
                                                    </a>
                                                </div>
                                            </li>
                                        @endif
                                    @endauth
                                </ul>
                            </div>
                            <div class="">
                                <ul class="navbar-nav me-auto">
                                    @auth
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                        {{ Auth::user()->name }}
                                    </a>

                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                        onclick="event.preventDefault();
                                                        document.getElementById('logout-form').submit();">
                                            {{ __('Logout') }}
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </div>
                                </li>
                                    @endauth
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="py-4">
                @yield('content')
            </main>

            <footer class="footer">

            </footer>
        </div>
    </body>
</html>




