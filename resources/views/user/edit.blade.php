@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header fs-5 fw-bold">{{ __('Editar Usuário - ID: ') }}{{ $user->id}}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.update', ['user' => $user->id]) }}" enctype="multipart/form-data" id="formUpdate">
                        @csrf
                        @method('PUT')
                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold">{{ __('Nome') }}</span>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $user->name ?? old('name') }}" required autocomplete="name" autofocus>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold">{{ __('E-mail') }}</span>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $user->email ?? old('email') }}" required autocomplete="email">

                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-sm btn-success" form="formUpdate">
                        {{ __('Salvar') }}
                    </button>
                    <a href="{{ route('user.show', ['user' => $user->id]) }}" class="btn btn-sm btn-warning mx-3">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

