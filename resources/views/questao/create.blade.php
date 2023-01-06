@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header fs-5 fw-bold">{{ __('Adicionar Questão') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('questao.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold">{{ __('Pergunta') }}</span>
                            <input id="pergunta" type="text" class="form-control @error('pergunta') is-invalid @enderror" name="pergunta" value="{{ $questao->pergunta ?? old('pergunta') }}" required autocomplete="pergunta" autofocus>
                            @error('pergunta')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold">{{ __('Resposta') }}</span>
                            <input id="resposta" type="text" class="form-control @error('resposta') is-invalid @enderror" name="resposta" value="{{ $questao->resposta ?? old('resposta') }}" required autocomplete="resposta" autofocus>
                            @error('resposta')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>


                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold">{{ __('Fonte') }}</span>
                            <input id="fonte" type="text" class="form-control @error('fonte') is-invalid @enderror" name="fonte" value="{{ $questao->fonte ?? old('fonte') }}" required autocomplete="fonte" autofocus>
                            @error('fonte')
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
