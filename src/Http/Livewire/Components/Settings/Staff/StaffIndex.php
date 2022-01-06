<?php

namespace GetCandy\Hub\Http\Livewire\Components\Settings\Staff;

use GetCandy\Hub\Models\Staff;
use Livewire\Component;
use Livewire\WithPagination;

class StaffIndex extends Component
{
    use WithPagination;

    /**
     * The search string.
     *
     * @var string
     */
    public $search = '';

    public $showInactive = false;

    /**
     * Render the livewire component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $query = Staff::query();

        if ($this->search) {
            $query->search($this->search, true);
        }

        if ($this->showInactive) {
            $query = $query->withTrashed();
        }

        return view('adminhub::livewire.components.settings.staff.index', [
            'staff' => $query->paginate(),
        ])->layout('adminhub::layouts.base');
    }
}
