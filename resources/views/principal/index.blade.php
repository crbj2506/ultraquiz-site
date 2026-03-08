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
                            <input type="hidden" name="alternativa_{{$key}}" value="{{$r->id}}">
                            <label for="home_radio_{{$key}}" class="w-100 cursor-pointer d-block">
                                <div class="input-group mb-3 hover-scale pointer">
                                    <span class="input-group-text fw-bold fs-4">{{$letras[$key]}}</span>
                                    <div 
                                        class="form-control fs-4 d-flex align-items-center
                                        {{isset($questao->respAnt) && $r->id == 0 ? 'is-valid' : ''}}
                                        {{isset($questao->respAnt) && $questao->respAnt != 0 && $questao->respAnt == $r->id ? 'is-invalid' : ''}}
                                        " 
                                        style="cursor: pointer !important; background-color: #fff;"
                                    >
                                        {{$r->alternativa}}
                                    </div>
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0 fs-4" type="radio" name="resposta" id="home_radio_{{$key}}" value="{{$r->id}}" required style="cursor: pointer;">
                                    </div>
                                </div>
                            </label>
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
                                    @auth
                                        <div class="d-inline-flex gap-2 me-3 align-items-center">
                                            <span class="fs-6 text-muted">Avalie:</span>
                                            <form action="{{ route('questao.votar', $questao->id) }}" method="POST" class="m-0">
                                                @csrf
                                                <input type="hidden" name="voto" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Gostei da Pergunta">
                                                    <x-icon-hand-thumbs-up width="18" height="18" />
                                                </button>
                                            </form>
                                            <form action="{{ route('questao.votar', $questao->id) }}" method="POST" class="m-0">
                                                @csrf
                                                <input type="hidden" name="voto" value="-1">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Não gostei da Pergunta">
                                                    <x-icon-hand-thumbs-down width="18" height="18" />
                                                </button>
                                            </form>
                                        </div>
                                    @endauth
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