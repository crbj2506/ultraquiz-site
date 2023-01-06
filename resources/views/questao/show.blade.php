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
                                {{ __('Visualizando Questão') }}
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('questao.index')}}" class="btn btn-sm btn-link">Listar</a>
                            </div>
                        </div>
                    </div>
                        
                </div>

                <div class="card-body">

                    <div class="input-group mb-3">
                        <span class="input-group-text fw-bold">{{ __('Pergunta') }}</span>
                        <input id="pergunta" type="text" class="form-control" name="pergunta" value="{{ $questao->pergunta }}" disabled>
                    </div>

                    <div class="input-group mb-3">
                        <span class="input-group-text fw-bold">{{ __('Resposta Correta') }}</span>
                        <input id="resposta" type="text" class="form-control" name="resposta" value="{{ $questao->resposta }}" disabled>
                    </div>

                    <div class="input-group mb-3">
                        <span class="input-group-text fw-bold">{{ __('Fonte') }}</span>
                        <input id="fonte" type="text" class="form-control" name="fonte" value="{{ $questao->fonte }}" disabled>
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text fw-bold">{{ __('Respondida') }}</span>
                        <input id="fonte" type="text" class="text-end form-control" name="fonte" value="{{ $questao->estatisticas->pluck('resposta_id')->count() }}" disabled>
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text fw-bold">{{ __('Acertos') }}</span>
                        <input id="fonte" type="text" class="text-end form-control" name="fonte" value="{{ $questao->estatisticas->where('resposta_id', null)->count() }}" disabled>
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text fw-bold">{{ __('Erros') }}</span>
                        <input id="fonte" type="text" class="text-end form-control" name="fonte" value="{{ $questao->estatisticas->where('resposta_id', '!=' , null)->count() }}" disabled>
                    </div>

                    <hr>
                    <p class="fs-5 fw-bold">Alternativas Incorretas</p>    
                    @foreach ($questao->respostas as $key => $r)
                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold">{{ __('Alternativa') }} {{$key+1}}</span>
                            <input id="alternativa_{{$key}}" type="text" class="form-control" name="alternativa_{{$key}}" value="{{ $r['alternativa'] }}" disabled>
                            <span class="input-group-text"> {{$r->estatisticas->count()}}</span>
                        </div>
                    @endforeach                   
                </div>              
                <div class="card-footer">
                    <a href="{{ route('questao.edit',['questao' => $questao->id])}}" class="btn btn-sm btn-outline-primary">Editar</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
