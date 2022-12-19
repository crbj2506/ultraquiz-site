@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header fs-5 fw-bold">{{ __('Adicionar Usuario') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('user.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold">{{ __('Nome') }}</span>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $user->name ?? old('name') }}" required autocomplete="name" autofocus>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold">{{ __('E-mail') }}</span>
                            <input id="email" type="text" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $user->email ?? old('email') }}"  required autocomplete="email" autofocus>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>



                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold">{{ __('Senha') }}</span>


                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror

                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text fw-bold">{{ __('Confirme a Senha') }}</span>

                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">

                        </div>


                        <div class="row mb-0">
                            <div class="col-md-8">
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
