@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="row p-0">
                    <div class="col ">{{ __('Sugerir Questão') }}
                    </div>
                    <div class="col text-center">
                    </div>
                    <div class="col text-end">
                            <a href='{{ route("sugestoespormim.listar")}}' class="btn btn-sm btn-outline-primary">{{ __('Milhas Sugestões') }}</a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form method="POST" id="formSugerir" enctype="multipart/form-data"
                    @if ($errors->any()) class=" needs-validation was-validation container-fluid d-flex flex-wrap" @else class="needs-validation  container-fluid d-flex flex-wrap" @endif
                    action='{{ route("sugestaopormim.armazenar") }}'>
                    @csrf

                    <div class="col-12 p-2">
                        <input-group-component
                            label="Pergunta:" 
                            type="text"
                            name="pergunta" 
                            id="pergunta" 
                            value="{{old('pergunta')?old('pergunta'):''}}"
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
                            value="{{old('resposta')?old('resposta'):''}}"
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
                            value="{{old('fonte')?old('fonte'):''}}"
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
                            value="{{old('alternativas.0')?old('alternativas.0'):''}}"
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
                            value="{{old('alternativas.1')?old('alternativas.1'):''}}"
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
                            value="{{old('alternativas.1')?old('alternativas.2'):''}}"
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
                            value="{{old('alternativas.3')?old('alternativas.3'):''}}"
                            class="@error('alternativas.3') is-invalid @enderror {{old('alternativas.3') ? 'is-valid' : ''}}"
                            @error('alternativas.3') message="{{$message}}" @enderror>
                        </input-group-component>
                    </div>
                </form>
            </div>

            <div class="card-footer"> 
                <button 
                    type="submit" 
                    form="formSugerir" 
                    class="btn btn-sm btn-outline-success">
                    {{ 'Sugerir' }}
                </button>
            </div>
        </div>
    </div>
@endsection