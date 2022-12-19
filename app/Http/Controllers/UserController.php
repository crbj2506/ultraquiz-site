<?php

namespace App\Http\Controllers;

use App\Models\Permissao;
use App\Models\PermissaoUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */
    use RegistersUsers;

    public function __construct(User $user){
        $this->user = $user;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $users = $this->user->paginate(10);
        return view('user.index',['users' => $users]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $permissoes = Permissao::get();
        return view('user.create',[ 'permissoes' => $permissoes]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $request->validate($this->user->rules($id = null),$this->user->feedback());
        $dados = $request->all('name','email','password');
        $dados['password'] = Hash::make($dados['password']);
        $user = $this->user->create($dados);
        // Percorre as permissoes disponíveis e compara com o valor do check (on) do request
        // ID nome do check correponde ao ID da permissão no Banco
        $permissoes = Permissao::get();
        foreach ($permissoes as $key => $p) {
            if($request->all($p->id)[$p->id] == 'on'){
                $permissao_user = ['user_id' => $user->id, 'permissao_id' => $p->id ];
                PermissaoUser::create($permissao_user);
            }
        }
        return redirect()->route('user.show', ['user' => $user->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  Integer $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $user = $this->user->find($id);
        $permissoes = Permissao::get();
        return view('user.show', ['user' => $user, 'permissoes' => $permissoes]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
        $permissoes = Permissao::get();
        return view('user.edit', ['user' => $user, 'permissoes' => $permissoes]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Integer $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $request->validate($this->user->rules_update($id),$this->user->feedback());
        $user = $this->user->find($id);
        $user->update($request->all());
        //Pega todas as permissões cadastradas no banco de dados
        $permissoes = Permissao::get();
        // Percorre a colection de objetos de permissões
        foreach ($permissoes as $key => $p) {
            //Busca a permissão do usuário no banco
            $permissaoUser = PermissaoUser::where('user_id',$user->id)->where('permissao_id',$p->id)->get()->first();
            // Se houve request e a permissão não existe no banco, cria. 
            if($request->all($p->id)[$p->id] == 'on'){
                if(!$permissaoUser){
                    $permissao = ['user_id' => $user->id, 'permissao_id' => $p->id ];
                    PermissaoUser::create($permissao);
                }
            }else{
                if($permissaoUser){
                    $permissaoUser->delete();
                }
            }
        }
        return redirect()->route('user.show', ['user' => $user->id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }
}
