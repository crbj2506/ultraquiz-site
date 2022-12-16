@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Adicionar Questão') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('questao.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="row mb-3">
                            <label for="pergunta" class="col-md-4 col-form-label text-md-end">{{ __('Pergunta  ') }}</label>

                            <div class="col-md-6">
                                <input id="pergunta" type="text" class="form-control @error('pergunta') is-invalid @enderror" name="pergunta" value="{{ old('pergunta') }}" required autocomplete="pergunta" autofocus>

                                @error('pergunta')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>


                        <div class="row mb-3">
                            <label for="resposta" class="col-md-4 col-form-label text-md-end">{{ __('Resposta') }}</label>

                            <div class="col-md-6">
                                <input id="resposta" type="text" class="form-control @error('resposta') is-invalid @enderror" name="resposta" value="{{ old('resposta') }}" required autocomplete="resposta" autofocus>

                                @error('resposta')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>


                        <div class="row mb-3">
                            <label for="fonte" class="col-md-4 col-form-label text-md-end">{{ __('Fonte') }}</label>

                            <div class="col-md-6">
                                <input id="fonte" type="text" class="form-control @error('fonte') is-invalid @enderror" name="fonte" value="{{ old('fonte') }}" required autocomplete="resposta" autofocus>

                                @error('fonte')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Cadastrar') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
