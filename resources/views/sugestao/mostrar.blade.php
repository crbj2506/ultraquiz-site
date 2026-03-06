@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                Sugestão
            </div>

            <div class="card-body container-fluid d-flex flex-wrap">
                <div class="col-12 p-2">
                    <input-group-component
                        label="Pergunta:" 
                        type="text"
                        name="pergunta" 
                        id="pergunta" 
                        disabled="disabled"
                        value="{{isset($sugestao) ? $sugestao->pergunta : (old('pergunta')?old('pergunta'):'')}}"
                        {{!isset($sugestao->show) ? '' : 'disabled' }} 
                        class="@error('pergunta') is-invalid @enderror {{old('pergunta') ? 'is-valid' : ''}}"
                        @error('pergunta') message="{{$message}}" @enderror>
                    </input-group-component>
                </div>

                <div class="col-12 p-2">
                    <input-group-component
                        label="Resposta:" 
                        type="text"
                        name="resposta" 
                        id="resposta" 
                        disabled="disabled"
                        value="{{isset($sugestao) ? $sugestao->resposta : (old('resposta')?old('resposta'):'')}}"
                        {{!isset($sugestao->show) ? '' : 'disabled' }} 
                        class="@error('resposta') is-invalid @enderror {{old('resposta') ? 'is-valid' : ''}}"
                        @error('resposta') message="{{$message}}" @enderror>
                    </input-group-component>
                </div>

                <div class="col-12 p-2">
                    <input-group-component
                        label="Fonte:" 
                        type="text"
                        name="fonte" 
                        id="fonte" 
                        disabled="disabled"
                        value="{{isset($sugestao) ? $sugestao->fonte : (old('fonte')?old('fonte'):'')}}"
                        {{!isset($sugestao->show) ? '' : 'disabled' }} 
                        class="@error('fonte') is-invalid @enderror {{old('fonte') ? 'is-valid' : ''}}"
                        @error('fonte') message="{{$message}}" @enderror>
                    </input-group-component>
                </div>

                <div class="col-12 p-2">
                    <input-group-component
                        label="Alternativa 1:" 
                        type="text"
                        name="alternativas[0]" 
                        id="alternativas[0]" 
                        disabled="disabled"
                        value="{{isset($sugestao) ? $sugestao->respostas[0]->alternativa : (old('alternativas.0')?old('alternativas.0'):'')}}"
                        {{!isset($sugestao->show) ? '' : 'disabled' }} 
                        class="@error('alternativas.0') is-invalid @enderror {{old('alternativas.0') ? 'is-valid' : ''}}"
                        @error('alternativas.0') message="{{$message}}" @enderror>
                    </input-group-component>
                </div>

                <div class="col-12 p-2">
                    <input-group-component
                        label="Alternativa 2:" 
                        type="text"
                        name="alternativas[1]" 
                        id="alternativas[1]" 
                        disabled="disabled"
                        value="{{isset($sugestao) ? $sugestao->respostas[1]->alternativa : (old('alternativas.1')?old('alternativas.1'):'')}}"
                        {{!isset($sugestao->show) ? '' : 'disabled' }} 
                        class="@error('alternativas.1') is-invalid @enderror {{old('alternativas.1') ? 'is-valid' : ''}}"
                        @error('alternativas.1') message="{{$message}}" @enderror>
                    </input-group-component>
                </div>
                    
                <div class="col-12 p-2">
                    <input-group-component
                        label="Alternativa 3:" 
                        type="text"
                        name="alternativas[2]" 
                        id="alternativas[2]" 
                        disabled="disabled"
                        value="{{isset($sugestao) ? $sugestao->respostas[2]->alternativa : (old('alternativas.2')?old('alternativas.2'):'')}}"
                        {{!isset($sugestao->show) ? '' : 'disabled' }} 
                        class="@error('alternativas.2') is-invalid @enderror {{old('alternativas.2') ? 'is-valid' : ''}}"
                        @error('alternativas.2') message="{{$message}}" @enderror>
                    </input-group-component>
                </div>

                <div class="col-12 p-2">
                    <input-group-component
                        label="Alternativa 4:" 
                        type="text"
                        name="alternativas[3]" 
                        id="alternativas[3]" 
                        disabled="disabled"
                        value="{{isset($sugestao) ? $sugestao->respostas[3]->alternativa : (old('alternativas.3')?old('alternativas.3'):'')}}"
                        {{!isset($sugestao->show) ? '' : 'disabled' }} 
                        class="@error('alternativas.3') is-invalid @enderror {{old('alternativas.3') ? 'is-valid' : ''}}"
                        @error('alternativas.3') message="{{$message}}" @enderror>
                    </input-group-component>
                </div>

                <div class="col-sm-12 col-md-6 p-2">
                    <input-group-component
                        label="Sugerida por:" 
                        type="text"
                        name="user_id" 
                        id="user_id" 
                        disabled="disabled"
                        value="{{isset($sugestao) ? $sugestao->user->name : (old('user_id')?old('user_id'):'')}}"
                        {{!isset($sugestao->show) ? '' : 'disabled' }} 
                        class="@error('user_id') is-invalid @enderror {{old('user_id') ? 'is-valid' : ''}}"
                        @error('user_id') message="{{$message}}" @enderror>
                    </input-group-component>
                </div>

                <div class="col-12 col-md-6 p-2">
                    <input-group-component
                        label="Verificações:" 
                        type="text"
                        disabled="disabled"
                        value="{{$sugestao->verificacoes->count()}}, sendo {{$sugestao->verificacoes()->where('aprovada',1)->get()->count()}} Aprovações e {{$sugestao->verificacoes()->where('aprovada',0)->get()->count()}} Reprovações"
                        >
                    </input-group-component>
                </div>
            </div>

            <div class="card-footer bg-light p-4"> 
                <div class="row align-items-center">
                    <div class="col-md-4 d-flex gap-2 justify-content-center justify-content-md-start mb-3 mb-md-0">
                        <form method="POST" id="formAprovar" action="{{ route('sugestao.aprovar',['sugestao' => $sugestao])}}">
                            @csrf
                            <input type="hidden" value="1" name="verificacao">
                            <button 
                                type="submit" 
                                form="formAprovar" 
                                class="btn btn-success px-4 {{$sugestao->verifiquei() === 1 ? 'disabled' : ''}}" >Aprovar                                
                            </button>
                        </form>
                        <form method="POST" id="formReprovar" action="{{ route('sugestao.aprovar',['sugestao' => $sugestao])}}">
                            @csrf
                            <input type="hidden" value="0" name="verificacao">
                            <button 
                                type="submit" 
                                form="formReprovar" 
                                class="btn btn-danger px-4 {{$sugestao->verifiquei() === 0 ? 'disabled' : ''}}">Reprovar                                
                            </button>
                        </form>
                    </div>
                    <div class="col-md-4 text-center fw-bold fs-5 text-secondary">
                        {{$sugestao->verifiquei() === 1 ? 'Eu aprovei ✓' : ($sugestao->verifiquei() === 0 ? 'Eu reprovei ✗' : 'Pendente')}}
                    </div>
                    <div class="col-md-4 text-center text-md-end mt-3 mt-md-0">
                        @if(isset($proxima) && $proxima)
                            <a href="{{ route('sugestao.mostrar', ['sugestao' => $proxima]) }}" class="btn btn-primary px-4 shadow-sm">Próxima Sugestão &raquo;</a>
                        @else
                            <a href="{{ route('sugestoes.listar') }}" class="btn btn-outline-secondary px-4">Voltar à Lista</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection