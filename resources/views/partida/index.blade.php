@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12 mt-3">
            <div class="card">
                <div class="card-header">{{$questao->pergunta}}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('partida.index', ['questao' => $questao->id]) }}" enctype="multipart/form-data" id="formQuestao">
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
                <div class="card-footer px-0">
                    <div class="container">
                        <div class="row align-items-end">
                            <div class="col-auto me-auto">
                                @if (isset($partida->questoes[$partida->indice]->respAnt))
                                    <a href="{{$questao->fonte}}" target="_blank"> Fonte: jw.org</a>
                                @else
                                    <span class="fw-bold">
                                        Taxa de Acerto da Questão: <span class="{{ $questao->taxaAcerto() < 33.3 ? 'text-danger' : ($questao->taxaAcerto() > 66.63 ? 'text-success' : 'text-warning')}}">{{ number_format( $questao->taxaAcerto(), 2, '.', '') . '%' }}</span>
                                        de <span class="text-primary">{{$questao->vezesRespondida() }}</span> tentativas
                                    </span>
                                @endif
                            </div>
                            <div class="col-auto">
                                @if (isset($partida->questoes[$partida->indice]->respAnt))
                                    {{--<a href="{{ route('questao.principal') }}" class="btn btn-sm btn-outline-primary">Próxima</a>--}}
                                    <a href="{{ $qPost ? route('partida.index',['questao' => $qPost]) : '' }}" class="btn btn-outline-secondary @if(!$qPost) disabled @endif">Próxima</a>
                                    @if ($partida->questoes->pluck('respAnt')->doesntContain(null))
                                        <a href="{{ route('partida.index')}}" class="btn btn-outline-primary ms-2">Nova Partida</a>
                                    @endif
                                @else
                                    <button type="submit" class="btn btn-outline-success" form="formQuestao">
                                        {{ __('Responder') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer container-fluid d-flex justify-content-center px-0">
                    <div class="row container px-0">
                        <div class="col-1">
                                <a href="{{ $qAnt ? route('partida.index',['questao' => $qAnt]) : '' }}" class="btn btn-sm btn-outline-secondary @if(!$qAnt) disabled @endif">Anterior</a>
                        </div>
                        <div class="col  container-fluid d-flex justify-content-center">
                            <div class="row">
                                @foreach($partida->questoes as $key => $questao)
                                    <div class="col fw-bold text-center fs-5 {{$partida->indice == $key ? 'bg-white border rounded' : ''}}">
                                        <div class="{{$questao->respAnt === '0' ? 'text-success' : ($questao->respAnt === null ? 'text-primary' : 'text-danger')}}">{{ $key +1}}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-1 text-end">
                                <a href="{{ $qPost ? route('partida.index',['questao' => $qPost]) : '' }}" class="btn btn-sm btn-outline-secondary @if(!$qPost) disabled @endif">Posterior</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection