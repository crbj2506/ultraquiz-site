@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="container">
                        <div class="row">
                            <div class="col-auto me-auto">
                                {{ __('Visualizando Questão') }}
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('questao.index')}}" class="btn btn-sm btn-link">Listar</a>
                            </div>
                        </div>
                    </div>
                        
                </div>

                <div class="card-body">

                    <div class="row mb-3">
                        <label for="pergunta" class="col-md-2 col-form-label text-md-end">{{ __('Pergunta') }}</label>

                        <div class="col-md-9">
                            <input id="pergunta" type="text" class="form-control" name="pergunta" value="{{ $questao->pergunta }}" disabled>
                        </div>
                    </div>


                    <div class="row mb-3">
                        <label for="resposta" class="col-md-2 col-form-label text-md-end">{{ __('Resposta Correta') }}</label>

                        <div class="col-md-9">
                            <input id="resposta" type="text" class="form-control" name="resposta" value="{{ $questao->resposta }}" disabled>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="fonte" class="col-md-2 col-form-label text-md-end">{{ __('Fonte') }}</label>
                        <div class="col-md-9">
                            <input id="fonte" type="text" class="form-control" name="fonte" value="{{ $questao->fonte }}" disabled>

                        </div>
                    </div>
                    <hr>
                        Alternativas Incorretas
                    @foreach ($questao->respostas as $key => $r)
                    <div class="row mt-3 mb-3">
                        <label for="alternativa_{{$key}}" class="col-md-2 col-form-label text-md-end">{{ __('Alternativa ')}}{{$key+1}}</label>
                        <div class="col-md-9">
                            <input id="alternativa_{{$key}}" type="text" class="form-control" name="alternativa_{{$key}}" value="{{ $r['alternativa'] }}" disabled>

                        </div>
                    </div>
                    @endforeach
                    
                </div>              
                <div class="card-footer">
                    <a href="{{ route('questao.edit',['questao' => $questao->id])}}" class="btn btn-sm btn-primary">Editar</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
