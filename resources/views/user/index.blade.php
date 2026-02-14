@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header fs-5 fw-bold">{{ __('Lista de Usuários') }}</div>

                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Nome</th>
                                <th scope="col">E-mail</th>
                                <th scope="col">Verificado em</th>
                                <th scope="col">Criado em</th>
                                <th scope="col">Ver</th>
                                <th scope="col">Editar</th>
                                <th scope="col">Excluir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $key => $p)
                                <tr>
                                    <th scope="row">{{$p['id']}}</th>
                                    <td>{{$p['name']}}</td>
                                    <td>{{$p['email']}}</td>
                                    <td>{{$p['email_verified_at']}}</td>
                                    <td>{{$p['created_at']}}</td>
                                    <td><a href="{{ route('user.show',['user' => $p['id']])}}" class="btn btn-sm btn-outline-primary" class="btn btn-sm btn-outline-warning">Ver</a></td>
                                    <td><a href="{{ route('user.edit',['user' => $p['id']])}}" class="btn btn-sm btn-outline-warning">Editar</a></td>
                                    <td>
                                        <form action="{{ route('user.destroy',['user' => $p['id']])}}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir este usuário?')">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{--$users->links() BUGADO!!!!!--}}
                    <ul class="pagination justify-content-center">
                        <li class="page-item">
                            <a class="page-link" href="{{ $users->url(1) }}"><<</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="{{ $users->previousPageUrl() }}" tabindex="-1" aria-disabled="true"><</a>
                        </li>@for ( $i= 1 ; $i <= $users->lastPage() ; $i++)
                            <li class="page-item {{ $users->currentPage() == $i ? 'active' : '' }}">
                                <a class="page-link" href="{{ $users->url($i) }}">{{ $i }}</a>
                            </li>
                        @endfor
                        <li class="page-item">
                            <a class="page-link" href="{{ $users->nextPageUrl() }}">></a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="{{ $users->url($users->lastPage()) }}">>></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
