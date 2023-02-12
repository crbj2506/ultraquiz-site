<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Paginacao extends Component
{
    public $p;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($p)
    {
        //
        $this->p = $p;
        
        // Dinamismo na paginação
        if($this->p->currentPage() == 1 || $this->p->currentPage() == 2){
            $this->p->d1 = $this->p->currentPage() - 1;
            $this->p->d2 = 4 - $this->p->d1;
        }elseif($this->p->currentPage() == $this->p->lastPage() || $this->p->currentPage() == $this->p->lastPage() -1){
            $this->p->d2 = $this->p->lastPage() - $this->p->currentPage();
            $this->p->d1 = 4 - $this->p->d2 ; 
        }else{
            $this->p->d1 = $this->p->d2 = 2;
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.paginacao');
    }
}