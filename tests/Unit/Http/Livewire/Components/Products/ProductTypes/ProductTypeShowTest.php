<?php

namespace GetCandy\Hub\Tests\Unit\Http\Livewire\Components\Products\ProductTypes;

use GetCandy\Hub\Http\Livewire\Components\Products\ProductTypes\ProductTypeShow;
use GetCandy\Hub\Models\Staff;
use GetCandy\Hub\Tests\TestCase;
use GetCandy\Models\Attribute;
use GetCandy\Models\Currency;
use GetCandy\Models\Language;
use GetCandy\Models\Product;
use GetCandy\Models\ProductType;
use GetCandy\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * @group hub.product-types
 */
class ProductTypeShowTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Language::factory()->create([
            'default' => true,
            'code'    => 'en',
        ]);

        Language::factory()->create([
            'default' => false,
            'code'    => 'fr',
        ]);

        Currency::factory()->create([
            'default' => true,
        ]);
    }

    /** @test  */
    public function component_mounts_correctly()
    {
        $staff = Staff::factory()->create([
            'admin' => true,
        ]);

        $productType = ProductType::factory()->create();

        LiveWire::actingAs($staff, 'staff')
            ->test(ProductTypeShow::class, [
                'productType' => $productType,
            ])->assertSeeHtml('Update product type');
    }

    /** @test  */
    public function component_has_system_attributes_preselected()
    {
        $staff = Staff::factory()->create([
            'admin' => true,
        ]);

        Attribute::factory(2)->create([
            'attribute_type' => Product::class,
            'system'         => true,
        ]);

        Attribute::factory(2)->create([
            'system' => false,
        ]);

        $productType = ProductType::factory()->create();

        LiveWire::actingAs($staff, 'staff')
            ->test(ProductTypeShow::class, [
                'productType' => $productType,
            ])->assertCount('selectedProductAttributes', Attribute::system(Product::class)->count());
    }

    /** @test  */
    public function can_populate_product_type_data_and_attributes()
    {
        $staff = Staff::factory()->create([
            'admin' => true,
        ]);

        $attribute = Attribute::factory()->create([
            'handle' => 'new-attribute',
        ]);

        $variantAttribute = Attribute::factory()->create([
            'handle'         => 'variant-attribute',
            'attribute_type' => ProductVariant::class,
        ]);

        $productType = ProductType::factory()->create();

        LiveWire::actingAs($staff, 'staff')
            ->test(ProductTypeShow::class, [
                'productType' => $productType,
            ])->assertCount('selectedProductAttributes', 0)
            ->call('addAttribute', $attribute->id, 'products')
            ->assertCount('selectedProductAttributes', 1)
            ->assertCount('selectedVariantAttributes', 0)
            ->call('addAttribute', $variantAttribute->id, 'variants')
            ->assertCount('selectedVariantAttributes', 1)
            ->set('productType.name', 'Foobar')
            ->call('update');

        $this->assertEquals('Foobar', $productType->refresh()->name);

        $tablePrefix = config('getcandy.database.table_prefix');

        $this->assertDatabaseHas("{$tablePrefix}attributables", [
            'attributable_id'   => $productType->id,
            'attributable_type' => ProductType::class,
            'attribute_id'      => $attribute->id,
        ]);
    }
}
