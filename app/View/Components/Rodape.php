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
                $commits = [];
                $resultCode = null;
                // Força o git a usar o diretório base do projeto e ignora issues de posse (Dubious Ownership)
                $command = 'git -C ' . escapeshellarg(base_path()) . ' -c safe.directory="*" log --reverse --format="%s"';
                exec($command, $commits, $resultCode);
                
                // Se o comando falhou por falta de permissão do www-data ou função exec() desativada
                if ($resultCode !== 0 || empty($commits)) {
                    \Illuminate\Support\Facades\Log::warning('Falha ao obter versão git', ['code' => $resultCode]);
                    return env('SISTEMA_VERSAO', '1.000');
                }

                $major = 1;
                $minor = 0;
                
                foreach ($commits as $index => $message) {
                    if ($index === 0) continue;
                    
                    if (preg_match('/Atualiza[çc][ãa]o.*LARAVEL|MAJOR|RELEASE|V\d+/i', $message)) {
                        $major++;
                        $minor = 0;
                    } else {
                        $minor++;
                    }
                }
                
                return $major . "." . str_pad($minor, 3, '0', STR_PAD_LEFT);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Erro fatal Versao Git: ' . $e->getMessage());
                return env('SISTEMA_VERSAO', '1.000');
            }
        });

        // Obtém a data do último commit
        $this->dataVersao = \Illuminate\Support\Facades\Cache::rememberForever('sistema_data_versao', function () {
            try {
                $data = [];
                $resultCode = null;
                $command = 'git -C ' . escapeshellarg(base_path()) . ' -c safe.directory="*" log -1 --format=%cd --date=format:"%d/%m/%Y"';
                exec($command, $data, $resultCode);
                
                if ($resultCode !== 0 || empty($data)) {
                    return env('SISTEMA_DATA', date('d/m/Y'));
                }
                return trim($data[0]);
            } catch (\Throwable $e) {
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
