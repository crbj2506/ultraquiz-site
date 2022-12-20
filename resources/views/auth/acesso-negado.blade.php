@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Acesso não autorizado') }}</div>

                <div class="card-body">
                    {{ __('Você não tem acesso a esse recurso') }}              
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
