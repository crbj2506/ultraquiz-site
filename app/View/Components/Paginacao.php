<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Paginacao extends Component
{
    public $paginate;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($paginate = null)
    {
        // allow component to be instantiated without parameter
        $this->paginate = $paginate;

        // only calculate ranges when paginate is provided
        if (!$this->paginate) {
            return;
        }

        // Dinamismo na paginação
        if($this->paginate->currentPage() == 1 || $this->paginate->currentPage() == 2){
            $this->paginate->d1 = $this->paginate->currentPage() - 1;
            $this->paginate->d2 = 4 - $this->paginate->d1;
        }elseif($this->paginate->currentPage() == $this->paginate->lastPage() || $this->paginate->currentPage() == $this->paginate->lastPage() -1){
            $this->paginate->d2 = $this->paginate->lastPage() - $this->paginate->currentPage();
            $this->paginate->d1 = 4 - $this->paginate->d2 ; 
        }else{
            $this->paginate->d1 = $this->paginate->d2 = 2;
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