@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12 mt-3">
            <div class="card">
                <div class="card-header fs-4"><strong>{{ $questao->pergunta }}</strong></div>
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
                                <span class="input-group-text fs-4 fw-bold">{{$letras[$key]}}</span>
                                <input type="hidden" name="alternativa_{{$key}}" value="{{$r->id}}">
                                <input type="text" 
                                    class="form-control fs-4
                                    {{isset($questao->respAnt) && $r->id == 0 ? 'is-valid' : ''}}
                                    {{isset($questao->respAnt) && $questao->respAnt != 0 && $questao->respAnt == $r->id ? 'is-invalid' : ''}}
                                    " 
                                    id="alternativa_{{$key}}" value="{{$r->alternativa}}" disabled >
                                @if (!isset($questao->respAnt))
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0 fs-4" type="radio" name="resposta" value="{{$r->id}}" required>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </form>
                </div>
                <div class="card-footer px-0">
                    <div class="container">
                        <div class="row align-items-end">
                            <div class="col-auto me-auto fs-4">
                                @if (isset($questao->respAnt))
                                    <a href="{{$questao->fonte}}" target="_blank"> Fonte: jw.org</a>
                                @else
                                    <span class="fw-bold">
                                        Taxa de Acerto: <span class="{{ $questao->taxaAcerto() < 33.3 ? 'text-danger' : ($questao->taxaAcerto() > 66.63 ? 'text-success' : 'text-warning')}}">{{ number_format( $questao->taxaAcerto(), 2, '.', '') . '%' }}</span>
                                        de <span class="text-primary">{{$questao->vezesRespondida() }}</span> tentativas
                                    </span>
                                @endif
                            </div>
                            <div class="col-auto">
                                @if (isset($questao->respAnt))
                                    <a href="{{ route('questao.principal') }}" class="btn btn-sm btn-outline-primary fs-4">Próxima</a>
                                @else
                                    <button type="submit" class="btn btn-sm btn-outline-success fs-4" form="formQuestao">
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
@endsection