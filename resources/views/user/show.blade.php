@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="container">
                        <div class="row">
                            <div class="col-auto me-auto fs-5 fw-bold">
                                {{ __('Visualizando Usuário') }}
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('user.index')}}" class="btn btn-sm btn-link">Listar</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">

                    <div class="input-group mb-3">
                        <span class="input-group-text fw-bold">{{ __('Nome') }}</span>
                        <input id="name" type="text" class="form-control" name="name" value="{{ $user->name }}" disabled>
                    </div>

                    <div class="input-group mb-3">
                        <span class="input-group-text fw-bold">{{ __('E-mail') }}</span>
                        <input id="email" type="text" class="form-control" name="email" value="{{ $user->email }}" disabled>
                    </div>

                    <div class="input-group mb-3">
                        <span class="input-group-text fw-bold">{{ __('Verificado em') }}</span>
                        <input id="email_verified_at" type="text" class="form-control" name="email_verified_at" value="{{ $user->email_verified_at }}" disabled>
                    </div>

                    <div class="input-group mb-3">
                        <span class="input-group-text fw-bold">{{ __('Criado em') }}</span>
                        <input id="created_at" type="text" class="form-control" name="created_at" value="{{ $user->created_at }}" disabled>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.edit',['user' => $user->id])}}" class="btn btn-sm btn-primary">Editar</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
