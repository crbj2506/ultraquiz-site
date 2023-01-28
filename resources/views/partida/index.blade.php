@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col mt-3">
            <div class="card">
                <div class="card-header d-flex justify-content-center">
                    <div class="row">
                        <div class="col text-success border rounded border-success mx-2">
                            <div class="text-center fw-bold fs-4">{{isset($partida->a)?$partida->a:0}}</div>
                            <div class="text-center fw-bold d-none d-md-block">
                                <div><x-icon-check-circle-fill width="25" height="25" class="text-success"></x-icon-check-circle-fill></div>
                                Acertos
                            </div>
                        </div>
                        <div class="col text-danger border rounded border-danger mx-2">
                            <div class="text-center fw-bold fs-4">{{isset($partida->e)?$partida->e:0}}</div>
                            <div class="text-center fw-bold d-none d-md-block">
                                <div><x-icon-x-circle-fill width="25" height="25" class="text-danger"></x-icon-x-circle-fill></div>
                                Erros
                            </div>
                        </div>
                        <div class="col text-primary border rounded border-primary mx-2">
                            <div class="text-center fw-bold fs-4">{{isset($partida->b)?$partida->b:0}}</div>
                            <div class="text-center fw-bold d-none d-md-block">
                                <div><x-icon-circle width="25" height="25" class=""></x-icon-circle></div>
                                Restam
                            </div>
                        </div>
                        <div class="col rounded border rounded border-dark mx-2">
                            <div class="text-center fw-bold fs-4">{{isset($partida->a)?$partida->a/2:0}}</div>
                            <div class="text-center fw-bold d-none d-md-block">
                                <div><x-icon-clipboard-check width="25" height="25" class=""></x-icon-clipboard-check></div>
                                Nota
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-header fw-bold fs-5">{{$questao->pergunta}}</div>
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
                                <span class="input-group-text fw-bold fs-5">{{$letras[$key]}}</span>
                                <input type="hidden" name="alternativa_{{$key}}" value="{{$r->id}}">
                                <input type="text" 
                                    class="form-control fs-5
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
                                    <span class="fw-bold fs-5 d-none d-md-block">
                                        Taxa de Acerto da Questão: <span class="{{ $questao->taxaAcerto() < 33.3 ? 'text-danger' : ($questao->taxaAcerto() > 66.63 ? 'text-success' : 'text-warning')}}">{{ number_format( $questao->taxaAcerto(), 2, '.', '') . '%' }}</span>
                                        de <span class="text-primary">{{$questao->vezesRespondida() }}</span> tentativas
                                    </span>
                                @endif
                            </div>
                            <div class="col-auto">
                                @if (isset($partida->questoes[$partida->indice]->respAnt))
                                    @php($qProxima = null)
                                    @foreach($partida->questoes as $key => $questao)
                                        @if($questao->respAnt === null)
                                            @php($qProxima = $questao->id)
                                            @break
                                        @endif
                                    @endforeach
                                    <a href="{{ $qProxima ? route('partida.index',['questao' => $qProxima]) : '' }}" class="btn btn-outline-secondary @if(!$qProxima) disabled @endif">Próxima</a>
                                    @if ($partida->questoes->pluck('respAnt')->doesntContain(null))
                                        <a href="{{ route('partida.index')}}" class="btn btn-outline-primary ms-2">Nova Partida
                                            <x-icon-clipboard width="20" height="20" class=""/>
                                        </a>
                                    @endif
                                @else
                                    <button type="submit" class="btn btn-outline-success fs-5" form="formQuestao">
                                        {{ __('Responder ') }}
                                        <x-icon-clipboard-check width="20" height="20" class=""></x-icon-clipboard-check>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-none d-sm-block justify-content-center p-0">
                    <div class="row container-fluid align-items-center">
                        <div class="col text-center">
                            <a href="{{ $partida->qAnt ? route('partida.index',['questao' => $partida->qAnt]) : '' }}" class="btn btn-outline-secondary @if(!$partida->qAnt) disabled @endif">
                                <x-icon-caret-left width="50" height="50" class=""></x-icon-caret-left>
                            </a>
                        </div>
                        <div class="col text-center d-none d-xl-flex">
                            <!-- INÍCIO Painel das Questões -->
                            @foreach($partida->questoes as $key => $questao)
                                <div class="col m-1 p-0 {{$partida->indice == $key ? 'bg-white' : ''}}">
                                    <a class="btn p-2 {{$questao->respAnt === '0' ? 'btn-outline-success' : ($questao->respAnt === null ? 'btn-outline-primary' : 'btn-outline-danger')}} @if($questao->respAnt !== null) disabled @endif" @if($questao->respAnt === null) href="{{route('partida.index',['questao' => $questao->id])}}" @endif>
                                        <div class="fw-bold">{{ $key +1}}</div>
                                        @if($questao->respAnt === '0')
                                            <x-icon-check-circle-fill width="16" height="16" class="text-success"></x-icon-check-circle-fill>
                                        @elseif($questao->respAnt === null)
                                            <x-icon-circle width="16" height="16" class=""></x-icon-circle>
                                        @else
                                            <x-icon-x-circle-fill width="16" height="16" class="text-danger"></x-icon-x-circle-fill>
                                        @endif
                                    </a>
                                </div>
                            @endforeach
                            <!-- FIM Painel das Questões -->
                        </div>
                        <div class="col text-center">
                            <a href="{{ $partida->qPost ? route('partida.index',['questao' => $partida->qPost]) : '' }}" class="btn btn-outline-secondary @if(!$partida->qPost) disabled @endif">
                                <x-icon-caret-right width="50" height="50" class=""></x-icon-caret-right>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection