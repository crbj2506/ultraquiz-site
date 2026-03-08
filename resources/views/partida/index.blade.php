@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col mt-3">
            <div class="card">
                <!-- Placar Premium -->
                <div class="card-header bg-white border-0 py-4">
                    <div class="d-flex flex-wrap justify-content-center gap-3 w-100">
                        <!-- Acertos -->
                        <div class="d-flex align-items-center bg-success bg-opacity-10 rounded-pill px-4 py-2 shadow-sm border border-success border-opacity-25" style="min-width: 140px;">
                            <div class="me-3 bg-success bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center">
                                <x-icon-check-circle-fill width="24" height="24" class="text-success"></x-icon-check-circle-fill>
                            </div>
                            <div>
                                <div class="text-uppercase text-success fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Acertos</div>
                                <div class="fs-4 fw-bolder text-dark" style="line-height: 1;">{{isset($partida->a)?$partida->a:0}}</div>
                            </div>
                        </div>

                        <!-- Erros -->
                        <div class="d-flex align-items-center bg-danger bg-opacity-10 rounded-pill px-4 py-2 shadow-sm border border-danger border-opacity-25" style="min-width: 140px;">
                            <div class="me-3 bg-danger bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center">
                                <x-icon-x-circle-fill width="24" height="24" class="text-danger"></x-icon-x-circle-fill>
                            </div>
                            <div>
                                <div class="text-uppercase text-danger fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Erros</div>
                                <div class="fs-4 fw-bolder text-dark" style="line-height: 1;">{{isset($partida->e)?$partida->e:0}}</div>
                            </div>
                        </div>

                        <!-- Restam -->
                        <div class="d-flex align-items-center bg-primary bg-opacity-10 rounded-pill px-4 py-2 shadow-sm border border-primary border-opacity-25" style="min-width: 140px;">
                            <div class="me-3 bg-primary bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center">
                                <x-icon-circle width="24" height="24" class="text-primary"></x-icon-circle>
                            </div>
                            <div>
                                <div class="text-uppercase text-primary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Restam</div>
                                <div class="fs-4 fw-bolder text-dark" style="line-height: 1;">{{isset($partida->b)?$partida->b:0}}</div>
                            </div>
                        </div>

                        <!-- Nota -->
                        <div class="d-flex align-items-center bg-dark bg-opacity-10 rounded-pill px-4 py-2 shadow-sm border border-dark border-opacity-25" style="min-width: 140px;">
                            <div class="me-3 bg-dark bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center">
                                <x-icon-clipboard-check width="24" height="24" class="text-dark"></x-icon-clipboard-check>
                            </div>
                            <div>
                                <div class="text-uppercase text-dark fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">{{ isset($partida) && $partida->b == 0 ? 'Nota Final' : 'Nota' }}</div>
                                <div class="fs-4 fw-bolder text-dark" style="line-height: 1;">{{isset($partida->a)?$partida->a/2:0}}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Fim Placar Premium -->
                <div class="card-header bg-primary text-white text-center fw-bold fs-4 py-4" style="line-height: 1.4;">{{$questao->pergunta}}</div>
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
                            
                            @if (!isset($questao->respAnt))
                                <label for="radio_{{$key}}" class="w-100 cursor-pointer d-block">
                            @endif
                            
                            <div class="input-group mb-3 {{ !isset($questao->respAnt) ? 'hover-scale pointer' : '' }}">
                                <span class="input-group-text fw-bold fs-4">{{$letras[$key]}}</span>
                                <input type="hidden" name="alternativa_{{$key}}" value="{{$r->id}}">
                                <div 
                                    class="form-control fs-4 d-flex align-items-center
                                    {{isset($questao->respAnt) && $r->id == 0 ? 'is-valid' : ''}}
                                    {{isset($questao->respAnt) && $questao->respAnt != 0 && $questao->respAnt == $r->id ? 'is-invalid' : ''}}
                                    " 
                                    style="{{ !isset($questao->respAnt) ? 'cursor: pointer !important; background-color: #fff;' : '' }}"
                                >
                                    {{$r->alternativa}}
                                </div>
                                
                                @if (!isset($questao->respAnt))
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="resposta" id="radio_{{$key}}" value="{{$r->id}}" required style="cursor: pointer;">
                                    </div>
                                @endif
                            </div>
                            
                            @if (!isset($questao->respAnt))
                                </label>
                            @endif
                        @endforeach
                    </form>
                </div>
                <div class="card-footer px-0">
                    <div class="container">
                        <div class="row align-items-end">
                            <div class="col-auto me-auto fs-4">
                                @if (isset($partida->questoes[$partida->indice]->respAnt))
                                    <a href="{{$questao->fonte}}" target="_blank"> Fonte: jw.org</a>
                                @else
                                    <span class="fw-bold fs-4 d-none d-md-block">
                                        Taxa de Acerto da Questão: <span class="{{ $questao->taxaAcerto() < 33.3 ? 'text-danger' : ($questao->taxaAcerto() > 66.63 ? 'text-success' : 'text-warning')}}">{{ number_format( $questao->taxaAcerto(), 2, '.', '') . '%' }}</span>
                                        de <span class="text-primary">{{$questao->vezesRespondida() }}</span> tentativas
                                    </span>
                                @endif
                            </div>
                            <div class="col-auto">
                                @if (isset($partida->questoes[$partida->indice]->respAnt))
                                    @php($qProxima = null)
                                    @foreach($partida->questoes as $key => $qIter)
                                        @if($qIter->respAnt === null)
                                            @php($qProxima = $qIter->id)
                                            @break
                                        @endif
                                    @endforeach
                                    
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

                                    <a href="{{ $qProxima ? route('partida.index',['questao' => $qProxima]) : '' }}" class="btn btn-outline-secondary fs-4 @if(!$qProxima) disabled @endif">Próxima</a>
                                    @if ($partida->questoes->pluck('respAnt')->doesntContain(null))
                                        <a href="{{ route('partida.index')}}" class="btn btn-outline-primary ms-2 fs-4">Nova Partida
                                            <x-icon-clipboard width="20" height="20" class=""/>
                                        </a>
                                    @endif
                                @else
                                    <button type="submit" class="btn btn-outline-success fs-4" form="formQuestao">
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
                                <div class="col m-1 p-0">
                                    <a class="btn p-2 fs-5 {{$questao->respAnt === '0' ? 'btn-outline-success' : ($questao->respAnt === null ? 'btn-outline-primary' : 'btn-outline-danger')}} {{$partida->indice == $key ? ' bg-info text-white' : ''}}" @if($questao->respAnt === null) href="{{route('partida.index',['questao' => $questao->id])}}" @endif>
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