<?php

namespace App\View\Components;

use App\Models\Questao;
use Illuminate\View\Component;

class Rodape extends Component
{
    public $questoesCadastradas;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
        $this->questoesCadastradas = Questao::count();
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
