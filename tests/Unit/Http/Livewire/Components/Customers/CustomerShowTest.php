<?php

namespace GetCandy\Hub\Tests\Unit\Http\Livewire\Components\Customers;

use GetCandy\FieldTypes\Text;
use GetCandy\Hub\Http\Livewire\Components\Customers\CustomerShow;
use GetCandy\Hub\Models\Staff;
use GetCandy\Hub\Tests\TestCase;
use GetCandy\Models\Attribute;
use GetCandy\Models\Currency;
use GetCandy\Models\Customer;
use GetCandy\Models\CustomerGroup;
use GetCandy\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * @group hub.customers
 */
class CustomerShowTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Currency::factory()->create([
            'default' => true,
        ]);
        
        Language::factory()->create([
            'default' => true,
            'code'    => 'en',
        ]);
    }

    /** @test  */
    public function component_mounts_correctly()
    {
        $staff = Staff::factory()->create([
            'admin' => true,
        ]);

        $customer = Customer::factory()->create();

        LiveWire::actingAs($staff, 'staff')
            ->test(CustomerShow::class, [
                'customer' => $customer,
            ])->assertSet('customer', $customer);
    }

    /** @test  */
    public function correct_customer_is_loaded()
    {
        $staff = Staff::factory()->create([
            'admin' => true,
        ]);

        $customer = Customer::factory()->create();

        LiveWire::actingAs($staff, 'staff')
            ->test(CustomerShow::class, [
                'customer' => $customer,
            ])->assertSet('customer.id', $customer->id);
    }

    /** @test  */
    public function can_update_customer_groups()
    {
        $staff = Staff::factory()->create([
            'admin' => true,
        ]);

        $initialCustomerGroups = CustomerGroup::factory(2)->create();

        $customer = Customer::factory()->create();

        $customer->customerGroups()->sync($initialCustomerGroups);

        $updatedCustomerGroups = CustomerGroup::factory(2)->create()->merge($initialCustomerGroups);

        LiveWire::actingAs($staff, 'staff')
            ->test(CustomerShow::class, [
                'customer' => $customer,
            ])->assertSet('syncedGroups', $initialCustomerGroups->pluck('id')->toArray())
              ->set('syncedGroups', $updatedCustomerGroups->pluck('id')->toArray())
              ->call('save')
              ->assertSet('syncedGroups', $updatedCustomerGroups->pluck('id')->toArray());
    }

    /** @test  */
    public function can_update_customer_details()
    {
        $staff = Staff::factory()->create([
            'admin' => true,
        ]);

        $customer = Customer::factory()->create();

        LiveWire::actingAs($staff, 'staff')
            ->test(CustomerShow::class, [
                'customer' => $customer,
            ])->assertSet('customer.title', $customer->title)
            ->assertSet('customer.first_name', $customer->first_name)
            ->assertSet('customer.last_name', $customer->last_name)
            ->assertSet('customer.company_name', $customer->company_name)
            ->assertSet('customer.vat_no', $customer->vat_no)
            ->set('customer.title', 'Mr')
            ->set('customer.first_name', 'Something')
            ->set('customer.last_name', 'Else')
            ->set('customer.company_name', 'ACME Supplies')
            ->set('customer.vat_no', 'VATNO123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('customer.title', 'Mr')
            ->assertSet('customer.first_name', 'Something')
            ->assertSet('customer.last_name', 'Else')
            ->assertSet('customer.company_name', 'ACME Supplies')
            ->assertSet('customer.vat_no', 'VATNO123');
    }

    /** @test */
    public function can_set_customer_attribute_data()
    {
        $staff = Staff::factory()->create([
            'admin' => true,
        ]);

        // Need some attributes...
        $name = Attribute::factory()->create([
            'handle' => 'name',
            'attribute_type' => 'GetCandy\Models\Customer',
        ]);
        
        $description = Attribute::factory()->create([
            'handle' => 'description',
            'attribute_type' => 'GetCandy\Models\Customer',
        ]);

        $customer = Customer::factory()->create();

        $customer->mappedAttributes()->attach(Attribute::get());

        $component = LiveWire::actingAs($staff, 'staff')
            ->test(CustomerShow::class, [
                'customer' => $customer,
            ])->set('attributeMapping.'.'a_'.$name->id.'.data', 'nouseforaname')
            ->set('attributeMapping.'.'a_'.$description->id.'.data', 'nouseforadescription')
            ->call('save')
            ->assertHasNoErrors();

        $newData = $customer->refresh()->attribute_data;

        $name = $newData['name'];
        $description = $newData['description'];

        $this->assertInstanceOf(Text::class, $name);
        $this->assertInstanceOf(Text::class, $description);

        $this->assertEquals('nouseforaname', $name->getValue());
        $this->assertEquals('nouseforadescription', $description->getValue());
    }
}
