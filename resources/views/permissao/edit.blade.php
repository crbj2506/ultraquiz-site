@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header fs-5 fw-bold">{{ __('Editar Permissão - ID: ') }}{{ $permissao->id}}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('permissao.update', ['permissao' => $permissao->id]) }}" enctype="multipart/form-data" id="formUpdate">
                        @csrf
                        @method('PUT')
                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold">{{ __('Permissão') }}</span>
                            <input id="permissao" type="text" class="form-control @error('permissao') is-invalid @enderror" name="permissao" value="{{ $permissao->permissao ?? old('permissao') }}" required autocomplete="permissao" autofocus>
                            @error('permissao')
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
                    <a href="{{ route('permissao.show', ['permissao' => $permissao->id]) }}" class="btn btn-sm btn-warning mx-3">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

