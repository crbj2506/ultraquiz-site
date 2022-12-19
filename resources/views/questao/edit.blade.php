@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header fs-5 fw-bold">{{ __('Editar Questão - ID: ') }}{{ $questao->id}}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('questao.update', ['questao' => $questao->id]) }}" enctype="multipart/form-data" id="formUpdate">
                        @csrf
                        @method('PUT')
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
                    </form>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-sm btn-success" form="formUpdate">
                        {{ __('Salvar') }}
                    </button>
                    <a href="{{ route('questao.show', ['questao' => $questao->id]) }}" class="btn btn-sm btn-warning mx-3">Cancelar</a>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header fs-5 fw-bold">{{ __('Alternativas Incorretas da Questão') }}</div>
                <div class="card-body">

                    @foreach ($questao->respostas as $key => $r)

                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold" id="inputLabelAlternativa{{$key}}">{{ __('Alternativa ')}}{{$key+1}}</span>
                            <input id="alternativa_{{$key}}" type="text" class="form-control" name="alternativa_{{$key}}" value="{{ $r['alternativa'] }}" disabled>
                            <a class="btn btn-outline-success" type="link" href="{{ route('resposta.edit', ['resposta' => $r['id'] ]) }}" id="buttonEditar">Editar</a>
                        </div>

                    @endforeach

                    <form method="POST" action="{{ route('resposta.store') }}" enctype="multipart/form-data" id="formInsereAlternativa">
                        @csrf
                        <input id="questao_id" type="hidden" name="questao_id" value="{{ $questao->id }}">

                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold" id="inputLabelAlternativa">Alternativa Incorreta</span>
                            <input id="alternativa" type="text" class="form-control  @error('alternativa') is-invalid @enderror" name="alternativa" value="{{ old('alternativa') }}" autocomplete="alternativa" autofocus>
                            <button class="btn btn-outline-success" type="submit" id="buttonInserir" form="formInsereAlternativa">Inserir</button>
                            @error('alternativa')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </form>
                </div>
                    <div class="card-footer">

                    </div>
            </div>
        </div>
    </div>
</div>
@endsection

