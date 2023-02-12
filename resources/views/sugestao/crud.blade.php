@extends('layouts.app')

@section('content')
    @php($sugestoes = isset($sugestoes) ? $sugestoes : null)
    @php($sugestao = isset($sugestao) ? $sugestao : null)
    <x-crud
        :l="$sugestoes"
        :o="$sugestao"
        r="sugestao"
        tc="Cadastra Sugestão"
        te="Altera Sugestão"
        ti="Lista de Sugestões"
        ts="Mostra Sugestão"
    >
        @if($sugestoes)
            <x-slot:filtro>
            </x-slot>
            <x-slot:lista>
                <thead>
                    <tr>
                        <th class="text-center" scope="col">#</th>
                        <th class="text-center" scope="col">Pergunta</th>
                        <th class="text-center" scope="col">Resposta</th>
                        <th class="text-center" scope="col">Fonte</th>
                        <th class="text-center" scope="col">Alternativas</th>
                        <th class="text-center" scope="col">Criada por</th>
                        <th class="text-center" scope="col">Verificações</th>
                        <th class="text-center" scope="col">Aprovações</th>
                        <th class="text-center" scope="col">Reprovações</th>
                        <th class="text-center" scope="col">Ver</th>
                        <th class="text-center" scope="col">Editar</th> 
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sugestoes as $key => $q)
                        <tr>
                            <th class="text-center py-0" scope="row">{{$q->id}}</th>
                            <td class=" py-0" scope="row">{{$q->pergunta}}</td>
                            <td class=" py-0" scope="row">{{$q->resposta}}</td>
                            <td class=" py-0" scope="row">{{$q->fonte}}</td>
                            <td class=" py-0" scope="row">{{$q->respostas->count()}}</td>
                            <td class=" py-0" scope="row">{{$q->user ? $q->user->name : ''}}</td>
                            <td class="text-center py-0" scope="row">{{isset($q->verificacoes) ? $q->verificacoes->count(): 'Sem verificações'}}</td>
                            <td class="text-center py-0" scope="row">{{$q->aprovacoes()}}</td>
                            <td class="text-center py-0" scope="row">{{$q->reprovacoes()}}</td>
                            <td class="text-center py-0" scope="row"><a href="{{ route('sugestao.show',['sugestao' => $q])}}" class="btn btn-sm btn-outline-primary py-0">Ver</a></td>
                            <td class="text-center py-0" scope="row"><a href="{{ route('sugestao.edit',['sugestao' => $q])}}" class="btn btn-sm btn-outline-warning py-0">Editar</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-slot>
        @else
            <x-slot:filtro>
            </x-slot>
            <x-slot:lista>
            </x-slot>
            <div class="container-fluid d-flex flex-wrap">
                <div class="col-12 p-2">
                    <input-group-component
                        label="Pergunta:" 
                        type="text"
                        name="pergunta" 
                        id="pergunta" 
                        required="required"
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
                        required="required"
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
                        required="required"
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
                        required="required"
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
                        required="required"
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
                        required="required"
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
                        required="required"
                        value="{{isset($sugestao) ? $sugestao->respostas[3]->alternativa : (old('alternativas.3')?old('alternativas.3'):'')}}"
                        {{!isset($sugestao->show) ? '' : 'disabled' }} 
                        class="@error('alternativas.3') is-invalid @enderror {{old('alternativas.3') ? 'is-valid' : ''}}"
                        @error('alternativas.3') message="{{$message}}" @enderror>
                    </input-group-component>
                </div>

                <div class="col-12 p-2">
                    <input-group-component
                        label="Sugerida por:" 
                        type="user_id"
                        name="user_id" 
                        id="user_id" 
                        disabled="disabled"
                        value="{{isset($sugestao) ? $sugestao->user->name : (old('user_id')?old('user_id'):'')}}"
                        {{!isset($sugestao->show) ? '' : 'disabled' }} 
                        class="@error('user_id') is-invalid @enderror {{old('user_id') ? 'is-valid' : ''}}"
                        @error('user_id') message="{{$message}}" @enderror>
                    </input-group-component>
                </div>

            </div>
        @endif
    </x-crud>
@endsection