<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Crud extends Component
{
    public $l; // lista de objetos
    public $o; // objeto
    public $r; // nome para a rota
    public $tc; // título Create
    public $te; // título Edit
    public $ti; // Título Index
    public $ts; // Título Show
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($l, $o, $r, $tc, $te, $ti, $ts)
    {
        //
        $this->l = $l;
        $this->o = $o;
        $this->r = $r;
        $this->tc = $tc;
        $this->te = $te;
        $this->ti = $ti;
        $this->ts = $ts;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.crud');
    }
}