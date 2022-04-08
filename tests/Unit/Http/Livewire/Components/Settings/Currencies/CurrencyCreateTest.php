<?php

namespace GetCandy\Hub\Tests\Unit\Http\Livewire\Components\Settings\Currencies;

use GetCandy\Hub\Http\Livewire\Components\Settings\Currencies\CurrencyCreate;
use GetCandy\Hub\Models\Staff;
use GetCandy\Hub\Tests\TestCase;
use GetCandy\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * @group hub.currencies
 */
class CurrencyCreateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_create_currency()
    {
        $staff = Staff::factory()->create([
            'admin' => true,
        ]);

        $this->actingAs($staff, 'staff');

        $properties = [
            'name'           => 'Some currency name',
            'code'           => 'TST',
            'default'        => true,
            'exchange_rate'  => 0.5,
            'decimal_places' => 2,
            'enabled'        => 0,
        ];

        $component = Livewire::test(CurrencyCreate::class);

        foreach ($properties as $property => $value) {
            $component->set("currency.$property", $value);
        }

        $component->call('create')->assertHasNoErrors();

        $this->assertDatabaseHas((new Currency())->getTable(), $properties);
    }

    /** @test */
    public function validation_is_present()
    {
        $staff = Staff::factory()->create([
            'admin' => true,
        ]);

        $this->actingAs($staff, 'staff');

        Livewire::test(CurrencyCreate::class)->call('create')
            ->assertHasErrors([
                'currency.name'           => 'required',
                'currency.code'           => 'required',
                'currency.exchange_rate'  => 'required',
            ]);

        Livewire::test(CurrencyCreate::class)
            ->set('currency.name', Str::random(260))
            ->set('currency.code', Str::random(260))
            ->set('currency.exchange_rate', 1000000)
            ->call('create')
            ->assertHasErrors([
                'currency.code'           => 'max',
                'currency.name'           => 'max',
                'currency.exchange_rate'  => 'max',
            ]);

        Currency::factory()->create([
            'code' => 'GBP',
        ]);

        Livewire::test(CurrencyCreate::class)
            ->set('currency.code', 'GBP')
            ->call('create')
            ->assertHasErrors([
                'currency.code' => 'unique',
            ]);
    }
}
