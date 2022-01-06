<?php

namespace GetCandy\Hub\Http\Livewire\Pages\Customers;

use GetCandy\Models\Customer;
use Livewire\Component;

class CustomerShow extends Component
{
    /**
     * The Product we are currently editing.
     *
     * @var \GetCandy\Models\Product
     */
    public Customer $customer;

    /**
     * Render the livewire component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('adminhub::livewire.pages.customers.show')
            ->layout('adminhub::layouts.app', [
                'title' => $this->customer->fullName,
            ]);
    }
}
