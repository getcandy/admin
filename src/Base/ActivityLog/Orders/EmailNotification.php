<?php

namespace GetCandy\Hub\Base\ActivityLog\Orders;

use GetCandy\Hub\Base\ActivityLog\AbstractRender;
use Spatie\Activitylog\Models\Activity;

class EmailNotification extends AbstractRender
{
    public function getEvent(): string
    {
        return 'email-notification';
    }

    public function render(Activity $log)
    {
        return view('adminhub::partials.orders.activity.email-notification', [
            'log' => $log,
        ]);
    }
}
