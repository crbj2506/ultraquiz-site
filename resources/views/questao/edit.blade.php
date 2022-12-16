@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Editar Questão - ID: ') }}{{ $questao->id}}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('questao.update', ['questao' => $questao->id]) }}" enctype="multipart/form-data" id="formUpdate">
                        @csrf
                        @method('PUT')
                        <div class="row mb-3">
                            <label for="pergunta" class="col-md-2 col-form-label text-md-end">{{ __('Pergunta') }}</label>

                            <div class="col-md-9">
                                <input id="pergunta" type="text" class="form-control @error('pergunta') is-invalid @enderror" name="pergunta" value="{{ $questao->pergunta ?? old('pergunta') }}" required autocomplete="pergunta" autofocus>

                                @error('pergunta')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="resposta" class="col-md-2 col-form-label text-md-end">{{ __('Resposta Correta') }}</label>

                            <div class="col-md-9">
                                <input id="resposta" type="text" class="form-control @error('resposta') is-invalid @enderror" name="resposta" value="{{ $questao->resposta ?? old('resposta') }}" required autocomplete="resposta" autofocus>

                                @error('resposta')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="fonte" class="col-md-2 col-form-label text-md-end">{{ __('Fonte') }}</label>

                            <div class="col-md-9">
                                <input id="fonte" type="text" class="form-control @error('fonte') is-invalid @enderror" name="fonte" value="{{ $questao->fonte ?? old('fonte') }}" required autocomplete="resposta" autofocus>

                                @error('fonte')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-sm btn-success" form="formUpdate">
                        {{ __('Salvar') }}
                    </button>
                    <button type="submit" class="mx-3 btn btn-sm btn-danger" form="formDelete">
                        {{ __('Excluir') }}
                    </button>
                    <a href="{{ route('questao.show', ['questao' => $questao->id]) }}" class="btn btn-sm btn-warning">Cancelar</a>

                    <form method="POST" action="{{ route('questao.destroy', ['questao' => $questao->id]) }}" id="formDelete">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header">{{ __('Alternativas Incorretas da Questão') }}</div>
                <div class="card-body">

                    @foreach ($questao->respostas as $key => $r)
                    <div class="row mt-3 mb-3">
                        <label for="alternativa_{{$key}}" class="col-md-2 col-form-label text-md-end">{{ __('Alternativa ')}}{{$key+1}}</label>
                        <div class="col-md-9">
                            <input id="alternativa_{{$key}}" type="text" class="form-control" name="alternativa_{{$key}}" value="{{ $r['alternativa'] }}" disabled>

                        </div>
                    </div>
                    @endforeach

                    <form method="POST" action="{{ route('resposta.store') }}" enctype="multipart/form-data" id="formInsereAlternativa">
                        @csrf
                        <input id="questao_id" type="hidden" name="questao_id" value="{{ $questao->id }}">

                        <div class="input-group mb-3">
                            <span class="input-group-text" id="inputLabelAlternativa">Alternativa Incorreta</span>
                            <input id="alternativa" type="text" class="form-control  @error('alternativa') is-invalid @enderror" placeholder="Alternativa" aria-label="Alternativa" aria-describedby="inputLabelAlternativa" name="alternativa" value="{{ old('alternativa') }}" autocomplete="alternativa" autofocus>
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

