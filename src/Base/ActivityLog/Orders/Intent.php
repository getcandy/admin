<?php

namespace GetCandy\Hub\Base\ActivityLog\Orders;

use GetCandy\Hub\Base\ActivityLog\AbstractRender;
use Spatie\Activitylog\Models\Activity;

class Intent extends AbstractRender
{
    public function getEvent(): string
    {
        return 'intent';
    }

    public function render(Activity $log)
    {
        return view('adminhub::partials.orders.activity.intent', [
            'log' => $log,
        ]);
    }
}
