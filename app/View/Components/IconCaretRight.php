<?php

namespace App\View\Components;

use Illuminate\View\Component;

class IconCaretRight extends Component
{
    public $width;
    public $height;
    public $class;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($width = null, $height = null, $class = null)
    {
        //
        $this->width = $width;
        $this->height = $height;
        $this->class = $class;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.icon-caret-right');
    }
}
