<?php

namespace GetCandy\Hub\Tests\Unit\Menu;

use GetCandy\Hub\Auth\Manifest;
use GetCandy\Hub\Menu\MenuSlot;
use GetCandy\Hub\Models\Staff;
use GetCandy\Hub\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group hub.menu
 */
class MenuSlotTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_initialise_the_class()
    {
        $slot = new MenuSlot('foo-bar');

        $this->assertEquals('foo-bar', $slot->getHandle());
    }

    /** @test */
    public function can_add_a_new_item_to_the_slot()
    {
        $slot = new MenuSlot('foo-bar');

        $this->assertCount(0, $slot->getItems());

        $slot->addItem(function ($item) {
            $item->name('Item One');
        });

        $this->assertCount(1, $slot->getItems());
    }

    /** @test */
    public function filters_items_based_on_user_permissions()
    {
        $slot = new MenuSlot('foo-bar');

        $staff = Staff::factory()->create();

        $this->actingAs($staff, 'staff');

        $this->assertCount(0, $slot->getItems());

        $slot->addItem(function ($item) {
            $item->name('Item One');
        });

        $slot->addItem(function ($item) {
            $item->name('Gated Item')->gate('item-one-gate');
        });

        $this->assertCount(1, $slot->getItems());

        $staff->permissions()->create([
            'handle' => 'item-one-gate',
        ]);

        $manifest = $this->app->make(Manifest::class);
        $manifest->addPermission(function ($perm) {
            $perm->handle('item-one-gate');
        });

        $this->assertCount(2, $slot->getItems());
    }
}
