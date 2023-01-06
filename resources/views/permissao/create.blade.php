@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header fs-5 fw-bold">{{ __('Adicionar Permissão') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('permissao.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold">{{ __('Permissão') }}</span>
                            <input id="permissao" type="text" class="form-control @error('permissao') is-invalid @enderror" name="permissao" value="{{ $permissao->permissao ?? old('permissao') }}" required autocomplete="pergunta" autofocus>
                            @error('permissao')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8">
                                <button type="submit" class="btn btn-outline-primary">
                                    {{ __('Cadastrar') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
