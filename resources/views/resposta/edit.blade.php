@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header fs-5 fw-bold">{{ __('Editar Resposta Alternativa- ID: ') }}{{ $resposta->id}}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('resposta.update', ['resposta' => $resposta->id]) }}" enctype="multipart/form-data" id="formUpdate">
                        @csrf
                        @method('PUT')
                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold">{{ __('Alternativa') }}</span>
                            <input id="alternativa" type="text" class="form-control @error('alternativa') is-invalid @enderror" name="alternativa" value="{{ $resposta->alternativa ?? old('alternativa') }}" required autocomplete="alternativa" autofocus>
                            @error('alternativa')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-sm btn-outline-success" form="formUpdate">
                        {{ __('Salvar') }}
                    </button>
                    {{--<a href="{{ route('resposta.show', ['resposta' => $resposta->id]) }}" class="btn btn-sm btn-outline-warning mx-3">Cancelar</a>--}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

