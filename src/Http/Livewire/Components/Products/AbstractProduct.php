<?php

namespace GetCandy\Hub\Http\Livewire\Components\Products;

use GetCandy\Hub\Http\Livewire\Traits\HasAvailability;
use GetCandy\Hub\Http\Livewire\Traits\HasDimensions;
use GetCandy\Hub\Http\Livewire\Traits\HasImages;
use GetCandy\Hub\Http\Livewire\Traits\HasPrices;
use GetCandy\Hub\Http\Livewire\Traits\HasTags;
use GetCandy\Hub\Http\Livewire\Traits\HasUrls;
use GetCandy\Hub\Http\Livewire\Traits\Notifies;
use GetCandy\Hub\Http\Livewire\Traits\SearchesProducts;
use GetCandy\Hub\Http\Livewire\Traits\WithAttributes;
use GetCandy\Hub\Http\Livewire\Traits\WithLanguages;
use GetCandy\Hub\Jobs\Products\GenerateVariants;
use GetCandy\Models\AttributeGroup;
use GetCandy\Models\Product;
use GetCandy\Models\ProductOption;
use GetCandy\Models\ProductType;
use GetCandy\Models\ProductVariant;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;

abstract class AbstractProduct extends Component
{
    use Notifies;
    use WithFileUploads;
    use HasImages;
    use HasAvailability;
    use SearchesProducts;
    use WithAttributes;
    use WithLanguages;
    use HasPrices;
    use HasDimensions;
    use HasUrls;
    use HasTags;

    /**
     * The current product we are editing.
     *
     * @var Product
     */
    public Product $product;

    /**
     * The current variant we're editing.
     *
     * @var ProductVariant
     */
    public ProductVariant $variant;

    /**
     * The options we want to use for the product.
     *
     * @var \Illuminate\Support\Collection
     */
    public Collection $options;

    /**
     * The selected values based on product options.
     *
     * @var array
     */
    public $optionValues = [];

    /**
     * Determine whether variants are enabled.
     *
     * @var bool
     */
    public $variantsEnabled = true;

    /**
     * The current view when adding/selecting options.
     *
     * @var string
     */
    public $optionView = 'select';

    /**
     * Determines whether the options panel should be on show.
     *
     * @var bool
     */
    public bool $optionsPanelVisible = false;

    /**
     * Whether to show the delete confirmation modal.
     *
     * @var bool
     */
    public $showDeleteConfirm = false;

    /**
     * Define availability properties.
     *
     * @var array
     */
    public $availability = [];

    /**
     * The product variant attributes.
     *
     * @var \Illuminate\Support\Collection
     */
    public $variantAttributes;

    protected function getListeners()
    {
        return array_merge([
            'useProductOptions'             => 'setOptions',
            'productOptionCreated'          => 'resetOptionView',
            'option-manager.selectedValues' => 'setOptionValues',
            'urlSaved'                      => 'refreshUrls',
        ], $this->getHasImagesListeners());
    }

    /**
     * Returns any custom validation messages.
     *
     * @return void
     */
    protected function getValidationMessages()
    {
        return array_merge(
            [],
            $this->hasPriceValidationMessages(),
            $this->withAttributesValidationMessages()
        );
    }

    /**
     * Define the validation rules.
     *
     * @return void
     */
    protected function rules()
    {
        $baseRules = [
            'product.status'          => 'required|string',
            'product.brand'           => 'nullable|string|max:255',
            'product.product_type_id' => 'required',
            'urls'                    => 'array',
            'variant.sku'             => get_validation('products', 'sku', [
                'alpha_dash',
                'max:255',
            ], $this->variant),
            'variant.gtin' => get_validation('products', 'gtin', [
                'string',
                'max:255',
            ], $this->variant),
            'variant.mpn' => get_validation('products', 'mpn', [
                'string',
                'max:255',
            ], $this->variant),
            'variant.ean' => get_validation('products', 'ean', [
                'string',
                'max:255',
            ], $this->variant),
        ];

        if ($this->getVariantsCount() <= 1) {
            $baseRules = array_merge(
                $baseRules,
                $this->hasPriceValidationRules(),
                [
                    'variant.stock'         => 'numeric|max:10000000',
                    'variant.backorder'     => 'numeric|max:10000000',
                    'variant.purchasable'   => 'string|required',
                    'variant.length_value'  => 'numeric|nullable',
                    'variant.length_unit'   => 'string|nullable',
                    'variant.tax_class_id'  => 'required',
                    'variant.width_value'   => 'numeric|nullable',
                    'variant.width_unit'    => 'string|nullable',
                    'variant.height_value'  => 'numeric|nullable',
                    'variant.height_unit'   => 'string|nullable',
                    'variant.weight_value'  => 'numeric|nullable',
                    'variant.weight_unit'   => 'string|nullable',
                    'variant.volume_value'  => 'numeric|nullable',
                    'variant.volume_unit'   => 'string|nullable',
                    'variant.shippable'     => 'boolean|nullable',
                    'variant.unit_quantity' => 'required|numeric|min:1|max:10000000',
                ]
            );
        }

        return array_merge(
            $baseRules,
            $this->hasImagesValidationRules(),
            $this->withAttributesValidationRules(),
        );
    }

    /**
     * Set the options to be whatever we pass through.
     *
     * @param array $options
     *
     * @return void
     */
    public function setOptions($optionIds)
    {
        $this->options = ProductOption::findMany($optionIds);
        $this->emit('products.options.updated', $optionIds);
        $this->optionsPanelVisible = false;
    }

    /**
     * Set option values.
     *
     * @param array $values
     *
     * @return void
     */
    public function setOptionValues($values)
    {
        $this->optionValues = $values;
    }

    /**
     * Remove an option by it's given position in the collection.
     *
     * @param int $key
     *
     * @return void
     */
    public function removeOption($key)
    {
        $option = $this->options[$key];

        $remainingValues = collect($this->optionValues)->diff($option->values->pluck('id'));

        $this->optionValues = $remainingValues->values();

        unset($this->options[$key]);
    }

    /**
     * Universal method to handle saving the product.
     *
     * @return void
     */
    public function save()
    {
        $this->withValidator(function (Validator $validator) {
            $validator->after(function ($validator) {
                if ($validator->errors()->count()) {
                    $this->notify(
                        __('adminhub::validation.generic'),
                        level: 'error'
                    );
                }
                // dd(1);
            });
        })->validate(null, $this->getValidationMessages());

        $isNew = !$this->product->id;

        DB::transaction(function () use ($isNew) {
            $data = $this->prepareAttributeData();
            $variantData = $this->prepareAttributeData($this->variantAttributes);

            $this->product->attribute_data = $data;

            $this->product->save();

            if (($this->getVariantsCount() <= 1) || $isNew) {
                if (!$this->variant->product_id) {
                    $this->variant->product_id = $this->product->id;
                }

                if (!$this->manualVolume) {
                    $this->variant->volume_unit = null;
                    $this->variant->volume_value = null;
                }

                $this->variant->attribute_data = $variantData;

                $this->variant->save();

                if ($isNew) {
                    $this->savePricing();
                }
            }

            // We generating variants?
            $generateVariants = (bool) count($this->optionValues);

            if ($generateVariants) {
                GenerateVariants::dispatch($this->product, $this->optionValues);
            }

            if (!$generateVariants && $this->product->variants->count() <= 1) {
                // Only save pricing if we're not generating new variants.
                $this->savePricing();
            }

            $this->saveUrls();

            $this->product->syncTags(
                collect($this->tags)
            );

            $this->updateImages($this->product);

            $channels = collect($this->availability['channels'])->mapWithKeys(function ($channel) {
                return [
                    $channel['channel_id'] => [
                        'starts_at'    => !$channel['enabled'] ? null : $channel['starts_at'],
                        'ends_at'      => !$channel['enabled'] ? null : $channel['ends_at'],
                        'enabled'      => $channel['enabled'],
                    ],
                ];
            });

            $gcAvailability = collect($this->availability['customerGroups'])->mapWithKeys(function ($group) {
                $data = Arr::only($group, ['starts_at', 'ends_at']);

                $data['purchasable'] = $group['status'] == 'purchasable';
                $data['visible'] = in_array($group['status'], ['purchasable', 'visible']);
                $data['enabled'] = $group['status'] != 'hidden';

                return [
                    $group['customer_group_id'] => $data,
                ];
            });

            $this->product->customerGroups()->sync($gcAvailability);

            $this->product->channels()->sync($channels);

            $this->product->refresh();

            $this->variantsEnabled = $this->getVariantsCount() > 1;

            $this->syncAvailability();

            $this->dispatchBrowserEvent('remove-images');

            $this->variant = $this->product->variants->first();

            $this->notify('Product Saved');
        });

        if ($isNew) {
            return redirect()->route('hub.products.show', [
                'product' => $this->product->id,
            ]);
        }
    }

    /**
     * Method to return variants count.
     *
     * @return int
     */
    public function getVariantsCount()
    {
        return $this->product->variants->count();
    }

    /**
     * Remove a variant.
     *
     * @param int $variantId
     *
     * @return void
     */
    public function deleteVariant($variantId)
    {
        if ($this->getVariantsCount() == 1) {
            $this->notify(
                __('adminhub::notifications.variants.minimum_reached'),
                level: 'error'
            );

            return;
        }
        $variant = ProductVariant::find($variantId);
        $variant->values()->detach();
        $variant->delete();
        $this->product->refresh();
    }

    public function getExistingTagsProperty(): array
    {
        return $this->product->tags->pluck('value')->toArray();
    }

    /**
     * Syncs availability with the product.
     *
     * @return void
     */
    protected function syncAvailability()
    {
        $this->availability = [
            'channels'                                                        => $this->channels->mapWithKeys(function ($channel) {
                $productChannel = $this->product->channels->first(fn ($assoc) => $assoc->id == $channel->id);

                return [
                    $channel->id => [
                        'channel_id'   => $channel->id,
                        'starts_at'    => $productChannel ? $productChannel->pivot->starts_at : null,
                        'ends_at'      => $productChannel ? $productChannel->pivot->ends_at : null,
                        'enabled'      => $productChannel ? $productChannel->pivot->enabled : false,
                        'scheduling'   => $productChannel ? (bool) $productChannel->pivot->starts_at : false,
                    ],
                ];
            }),
            'customerGroups' => $this->customerGroups->mapWithKeys(function ($group) {
                $productGroup = $this->product->customerGroups->where('id', $group->id)->first();

                $pivot = $productGroup->pivot ?? null;

                $status = 'hidden';

                if ($pivot) {
                    if ($pivot->purchasable) {
                        $status = 'purchasable';
                    } elseif (!$pivot->visible && !$pivot->enabled) {
                        $status = 'hidden';
                    } elseif ($pivot->visible) {
                        $status = 'visible';
                    }
                }

                return [
                    $group->id => [
                        'customer_group_id' => $group->id,
                        'scheduling'        => false,
                        'status'            => $status,
                        'starts_at'         => $pivot->starts_at ?? null,
                        'ends_at'           => $pivot->ends_at ?? null,
                    ],
                ];
            }),
        ];
    }

    /**
     * Returns the attribute data.
     *
     * @return array
     */
    public function getAttributeDataProperty()
    {
        return $this->product->attribute_data;
    }

    /**
     * Resets the option view to the default.
     *
     * @return void
     */
    public function resetOptionView()
    {
        $this->optionView = 'select';
    }

    /**
     * Returns all available attributes.
     *
     * @return void
     */
    public function getAvailableAttributesProperty()
    {
        return ProductType::find(
            $this->product->product_type_id
        )->productAttributes->sortBy('position')->values();
    }

    /**
     * Returns all available variant attributes.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableVariantAttributesProperty()
    {
        return ProductType::find(
            $this->product->product_type_id
        )->variantAttributes->sortBy('position')->values();
    }

    /**
     * Return attribute groups available for variants.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getVariantAttributeGroupsProperty()
    {
        $groupIds = $this->variantAttributes->pluck('group_id')->unique();

        return AttributeGroup::whereIn('id', $groupIds)
            ->orderBy('position')
            ->get()->map(function ($group) {
                return [
                    'model'  => $group,
                    'fields' => $this->variantAttributes->filter(fn ($att) => $att['group_id'] == $group->id),
                ];
            });
    }

    /**
     * Return the side menu links.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getSideMenuProperty()
    {
        return collect([
            [
                'title'      => __('adminhub::menu.product.basic-information'),
                'id'         => 'basic-information',
                'has_errors' => $this->errorBag->hasAny([
                    'product.brand',
                    'product.product_type_id',
                ]),
            ],
            [
                'title'      => __('adminhub::menu.product.attributes'),
                'id'         => 'attributes',
                'has_errors' => $this->errorBag->hasAny([
                    'attributeMapping.*',
                ]),
            ],
            [
                'title'      => __('adminhub::menu.product.images'),
                'id'         => 'images',
                'has_errors' => $this->errorBag->hasAny([
                    'newImages.*',
                ]),
            ],
            [
                'title'      => __('adminhub::menu.product.availability'),
                'id'         => 'availability',
                'has_errors' => $this->errorBag->hasAny([
                ]),
            ],
            [
                'title'      => __('adminhub::menu.product.variants'),
                'id'         => 'variants',
                'has_errors' => $this->errorBag->hasAny([]),
            ],
            [
                'title'      => __('adminhub::menu.product.pricing'),
                'id'         => 'pricing',
                'hidden'     => $this->getVariantsCount() > 1,
                'has_errors' => $this->errorBag->hasAny([
                    'variant.min_quantity',
                    'basePrices.*',
                    'customerGroupPrices.*',
                    'tieredPrices.*',
                ]),
            ],
            [
                'title'      => __('adminhub::menu.product.identifiers'),
                'id'         => 'identifiers',
                'hidden'     => $this->getVariantsCount() > 1,
                'has_errors' => $this->errorBag->hasAny([
                    'variant.sku',
                    'variant.gtin',
                    'variant.mpn',
                    'variant.ean',
                ]),
            ],
            [
                'title'       => __('adminhub::menu.product.inventory'),
                'id'          => 'inventory',
                'error_check' => [],
                'has_errors'  => $this->errorBag->hasAny([
                ]),
            ],
            [
                'title'      => __('adminhub::menu.product.shipping'),
                'id'         => 'shipping',
                'hidden'     => $this->getVariantsCount() > 1,
                'has_errors' => $this->errorBag->hasAny([
                ]),
            ],
            [
                'title'      => __('adminhub::menu.product.urls'),
                'id'         => 'urls',
                'hidden'     => $this->getVariantsCount() > 1,
                'has_errors' => $this->errorBag->hasAny([
                ]),
            ],
        ])->reject(fn ($item) => ($item['hidden'] ?? false));
    }

    /**
     * Returns the model with pricing.
     *
     * @return void
     */
    protected function getPricedModel()
    {
        return $this->product->variants->first() ?: $this->variant;
    }

    protected function getHasUrlsModel()
    {
        return $this->product;
    }

    public function getHasDimensionsModel()
    {
        return $this->variant;
    }

    /**
     * Returns the model which has media associated.
     *
     * @return void
     */
    protected function getMediaModel()
    {
        return $this->product;
    }

    /**
     * Render the livewire component.
     *
     * @return \Illuminate\View\View
     */
    abstract public function render();
}
