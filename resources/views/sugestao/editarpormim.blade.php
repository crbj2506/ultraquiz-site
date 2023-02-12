@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                Título
            </div>
            <form method="POST" id="formSugestaoEditarPorMim" enctype="multipart/form-data"
                @if ($errors->any()) class=" needs-validation was-validation container-fluid d-flex flex-wrap" @else class="needs-validation  container-fluid d-flex flex-wrap" @endif
                action='{{ route("sugestaopormim.atualizar", ["sugestao" => $sugestao]) }}'>
                @method('PUT')
                @csrf

                <div class="col-12 p-2">
                    <input-group-component
                        label="Pergunta:" 
                        type="text"
                        name="pergunta" 
                        id="pergunta" 
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
            </form>

            <div class="card-footer container"> {{---fluid d-flex border px-2--}}
                <div class="row p-0">
                    <div class="col container-fluid d-flex">
                        <button 
                            type="submit" 
                            form="formSugestaoEditarPorMim" 
                            class="btn btn-sm btn-outline-success">
                            {{ 'Atualizar' }}
                        </button>
                    </div>
                    <div class="col text-center">
                    </div>
                    <div class="col text-end">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection