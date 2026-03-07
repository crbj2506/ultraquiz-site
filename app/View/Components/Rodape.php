<?php

namespace App\View\Components;

use App\Models\Questao;
use Illuminate\View\Component;

class Rodape extends Component
{
    public $questoesCadastradas;
    public $versao;
    public $dataVersao;
    
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
        $this->questoesCadastradas = \App\Models\Questao::count();

        // Obtém automaticamente a versão baseada nos Commits do Git usando Cache para performance
        $this->versao = \Illuminate\Support\Facades\Cache::rememberForever('sistema_versao', function () {
            try {
                // Pega log de mensagens de commit (do mais antigo para o mais novo)
                $commits = [];
                exec('git -c safe.directory="*" log --reverse --format="%s"', $commits);
                
                $major = 1;
                $minor = 0;
                
                foreach ($commits as $index => $message) {
                    // O primeiro commit estabelece a base 1.000
                    if ($index === 0) continue;
                    
                    // Se a mensagem contiver palavras determinantes de Grandes Updates, sobe o Major e zera o Minor
                    if (preg_match('/Atualiza[çc][ãa]o.*LARAVEL|MAJOR|RELEASE|V\d+/i', $message)) {
                        $major++;
                        $minor = 0;
                    } else {
                        $minor++;
                    }
                }
                
                return $major . "." . str_pad($minor, 3, '0', STR_PAD_LEFT);
            } catch (\Exception $e) {
                return env('SISTEMA_VERSAO', '1.000');
            }
        });

        // Obtém a data do último commit
        $this->dataVersao = \Illuminate\Support\Facades\Cache::rememberForever('sistema_data_versao', function () {
            try {
                $data = trim(exec('git -c safe.directory="*" log -1 --format=%cd --date=format:"%d/%m/%Y"'));
                return $data ? $data : env('SISTEMA_DATA', date('d/m/Y'));
            } catch (\Exception $e) {
                return env('SISTEMA_DATA', date('d/m/Y'));
            }
        });
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.rodape');
    }
}
