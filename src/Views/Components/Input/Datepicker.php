<?php

namespace GetCandy\Hub\Views\Components\Input;

use Illuminate\View\Component;

class Datepicker extends Component
{
    /**
     * Whether or not the input has an error to show.
     *
     * @var bool
     */
    public bool $error = false;

    /**
     * Any options to pass to the datepicker.
     *
     * @var array
     */
    public array $options = [];

    /**
     * Initialise the component.
     *
     * @param  bool  $error
     */
    public function __construct($error = false, array $options = [])
    {
        $this->error = $error;
        $this->options = $options;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|\Closure|string
     */
    public function render()
    {
        return view('adminhub::components.input.datepicker');
    }
}
