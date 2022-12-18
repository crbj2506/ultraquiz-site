@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="container">
                        <div class="row">
                            <div class="col-auto me-auto fs-5 fw-bold">
                                {{ __('Visualizando Questão') }}
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('permissao.index')}}" class="btn btn-sm btn-link">Listar</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">

                    <div class="input-group mb-3">
                        <span class="input-group-text fw-bold">{{ __('Permissão') }}</span>
                        <input id="permissao" type="text" class="form-control" name="permissao" value="{{ $permissao->permissao }}" disabled>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('permissao.edit',['permissao' => $permissao->id])}}" class="btn btn-sm btn-primary">Editar</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
