<div class="container-fluid d-flex justify-content-center">
    {{--Rota INDEX Lista --}}
    @if($l)
        <div class="card m-3">
            <div class="card-header fw-bold container-fluid">
                <div class="row align-items-center">
                    <div class="col">{{ $ti }}
                    </div>
                    <div class="col-4 container-fluid d-flex-inline text-end p-0">
                            <a href='{{ isset($r) ? route("$r.create") : null}}' class="btn btn-sm btn-outline-success py-0 ">Cadastrar</a>
                    </div>  
                </div>
            </div>
            {{$filtro}}
            <div class="card-body">
                <table class="table table-striped table-hover">
                    {{$lista}}
                </table>
            </div>
            <x-paginacao :paginate="$l"></x-paginacao>
        </div>
    @else
        <div class="card m-3">
            <div class="card-header fw-bold container-fluid">
                <div class="row align-items-center">
                    <div class="col">
                        @if(isset($o->edit)) {{ $te }} @elseif(!isset($o->show)) {{ $tc }} @elseif(isset($o->show)) {{ $ts }} @endif
                    </div>
                    <div class="col-5 container-fluid d-flex-inline text-end p-0">
                            <a href='{{ route("$r.index") }}' class="btn btn-sm btn-outline-primary me-2 py-0">Listar</a>
                            @if(isset($o->edit)) 
                                <a href='{{ route("$r.create")}}' class="btn btn-sm btn-outline-success py-0 ">Cadastrar</a>
                            @elseif(!isset($o->show))
                            @elseif(isset($o->show))
                                <a href='{{ route("$r.create")}}' class="btn btn-sm btn-outline-success py-0 ">Cadastrar</a>
                            @endif
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <form method="POST" id="form{{$r}}" enctype="multipart/form-data"
                    @if ($errors->any()) class=" needs-validation was-validation" @else class="needs-validation" @endif
                    @if(isset($o->edit)) {{-- Se a rota for EDIT --}}
                        action='{{ route("$r.update",["$r" => $o]) }}'>
                        @method('PUT')
                        @csrf
                    @elseif(!isset($o->show)) {{-- Se a rota não for EDIT e nem SHOW, ela é CREATE --}}
                        action='{{ route("$r.store") }}'>
                        @csrf
                    @else {{-- Se a rota for SHOW --}}
                        >
                    @endif
                    {{$slot}}
                @if(!isset($o->show))
                </form>
                @endif                   
            </div>
            <div class="card-footer container-fluid">
                <div class="row">
                    <div class="col">
                        @if(isset($o->show))
                            <a href='{{ isset($o) ? route("$r.edit",["$r" => $o]) : null}}' class="btn btn-sm btn-outline-warning">{{ __('Editar') }}</a>
                        @else
                            <button 
                                type="submit" 
                                form="form{{$r}}" 
                                class="btn btn-sm btn-outline-success {{isset($o->show) ? 'd-none' : ''}}">
                                {{ isset($o->edit) ? 'Salvar' : 'Cadastrar' }}
                            </button>
                        @endif
                    </div>
                    <div class="col text-end">
                        @if(isset($o->show) && ($o->objetoAnterior || $o->objetoPosterior))
                            <a href='{{ $o->objetoAnterior ? route("$r.show",["$r" => $o->objetoAnterior]) : "" }}' class="btn btn-sm btn-outline-secondary @if(!$o->objetoAnterior) disabled @endif me-2">Anterior</a>
                            <a href='{{ $o->objetoPosterior ? route("$r.show",["$r" => $o->objetoPosterior]) : "" }}' class="btn btn-sm btn-outline-secondary @if(!$o->objetoPosterior) disabled @endif me-2">Posterior</a>
                        @elseif(isset($o->edit) && ($o->objetoAnterior || $o->objetoPosterior))
                            <a href='{{ $o->objetoAnterior ? route("$r.edit",["$r" => $o->objetoAnterior]) : "" }}' class="btn btn-sm btn-outline-secondary @if(!$o->objetoAnterior) disabled @endif me-2">Anterior</a>
                            <a href='{{ $o->objetoPosterior ? route("$r.edit",["$r" => $o->objetoPosterior]) : "" }}' class="btn btn-sm btn-outline-secondary @if(!$o->objetoPosterior) disabled @endif me-2">Posterior</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>