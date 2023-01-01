@extends('layouts.principal')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12 mt-3">
            <div class="card">
                <div class="card-header"><strong>{{ $questao->pergunta }}</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{-- route('questao.update', ['questao' => $questao->id]) ----}}" enctype="multipart/form-data" id="formQuestao">
                        @csrf
                        <input type="hidden" name="questao_id" value="{{$questao->id}}">
                        @php $letras = ['A','B','C','D','E'];
                        @endphp
                        @foreach ($questao->respostas as $key => $r)
                            @php if($key == 5){
                                break;
                            };
                            @endphp                            
                            <div class="input-group mb-3">
                                <span class="input-group-text"><strong>{{$letras[$key]}}</strong></span>
                                <input type="hidden" name="alternativa_{{$key}}" value="{{$r->id}}">
                                <input type="text" 
                                    class="form-control
                                    {{isset($questao->respAnt) && $r->id == 0 ? 'is-valid' : ''}}
                                    {{isset($questao->respAnt) && $questao->respAnt != 0 && $questao->respAnt == $r->id ? 'is-invalid' : ''}}
                                    " 
                                    id="alternativa_{{$key}}" value="{{$r->alternativa}}" disabled >
                                @if (!isset($questao->respAnt))
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="resposta" value="{{$r->id}}" required>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </form>
                </div>
                <div class="card-footer">
                    <div class="container">
                        <div class="row">
                            <div class="col-auto me-auto">
                                @if (isset($questao->respAnt))
                                    <a href="{{$questao->fonte}}" target="_blank"> Fonte: jw.org</a>
                                @endif
                            </div>
                            <div class="col-auto">
                                @if (isset($questao->respAnt))
                                    <a href="{{ route('questao.principal') }}" class="btn btn-sm btn-primary">Próxima</a>
                                @else
                                    <button type="submit" class="btn btn-sm btn-success" form="formQuestao">
                                        {{ __('Responder') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>