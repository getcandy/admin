<?php

namespace GetCandy\Hub\Http\Livewire\Traits;

use GetCandy\FieldTypes\Text;
use GetCandy\FieldTypes\TranslatedText;
use GetCandy\Models\Language;
use Illuminate\Support\Str;

trait WithAttributes
{
    /**
     * The attribute mapping for editing.
     *
     * @var array
     */
    public $attributeMapping = [];

    /**
     * Mount the WithAttributes trait.
     *
     * @return void
     */
    public function mountWithAttributes()
    {
        $this->mapAttributes();
    }

    /**
     * Listen for when product type id is updated.
     *
     * @return void
     */
    public function updatedProductProductTypeId()
    {
        $this->mapAttributes();
    }

    protected function mapAttributes()
    {
        $this->attributeMapping = $this->availableAttributes->mapWithKeys(function ($attribute, $index) {
            $data = $this->attributeData ?
                $this->attributeData->first(fn ($value, $handle) => $handle == $attribute->handle)
                : null;

            $value = $data ? $data->getValue() : null;
            // We need to make sure we give livewire all the languages if we're trying to translate.
            if ($attribute->type == TranslatedText::class) {
                $value = $this->prepareTranslatedText($value);
            }

            return [$attribute->id => [
                'name'          => $attribute->translate('name'),
                'group'         => $attribute->attributeGroup->translate('name'),
                'group_handle'  => $attribute->attributeGroup->handle,
                'id'            => $attribute->handle,
                'signature'     => 'attributeMapping.'.$attribute->id.'.data',
                'type'          => $attribute->type,
                'handle'        => $attribute->handle,
                'configuration' => $attribute->configuration,
                'required'      => $attribute->required,
                'component'     => Str::kebab(class_basename(
                    $attribute->type
                )),
                'data' => $value,
            ]];
        });
    }

    /**
     * Prepares attribute data to be ready for saving.
     *
     * @return array
     */
    public function prepareAttributeData()
    {
        return collect($this->attributeMapping)->mapWithKeys(function ($attribute) {
            $value = null;
            switch ($attribute['type']) {
                case TranslatedText::class:
                    $value = $this->mapTranslatedText($attribute['data']);
                    break;

                default:
                    $value = new Text($attribute['data']);
                    break;
            }

            return [
                $attribute['handle'] => $value,
            ];
        });
    }

    /**
     * Map translated values into field types.
     *
     * @param array $data
     *
     * @return \GetCandy\FieldTypes\TranslatedText
     */
    protected function mapTranslatedText($data)
    {
        $values = [];
        foreach ($data as $code => $value) {
            $values[$code] = new Text($value);
        }

        return new TranslatedText(collect($values));
    }

    /**
     * Prepare translated text field for Livewire modeling.
     *
     * @param string|array $value
     *
     * @return array
     */
    protected function prepareTranslatedText($value)
    {
        foreach ($this->languages as $language) {
            // If we've changed from Text to TranslatedText we might
            // have a string value. In this case we want to convert it to translated text.
            if (is_string($value)) {
                $newValue = collect();
                if ($language->default) {
                    $newValue[$language->code] = $value;
                }
                $value = $newValue;
                continue;
            }

            if (empty($value[$language->code])) {
                $value[$language->code] = null;
            }
        }

        return $value;
    }

    public function withAttributesValidationRules()
    {
        $rules = [];
        foreach ($this->attributeMapping as $index => $attribute) {
            if (($attribute['required'] ?? false) || ($attribute['system'] ?? false)) {
                if ($attribute['type'] == TranslatedText::class) {
                    // Get the default language and make that the only one required.
                    $rules["attributeMapping.{$index}.data.{$this->defaultLanguage->code}"] = 'required';
                    continue;
                }

                $rules["attributeMapping.{$index}.data"] = 'required';
            }
        }

        return $rules;
    }

    /**
     * Return extra validation messages.
     *
     * @return array
     */
    protected function withAttributesValidationMessages()
    {
        $messages = [];
        foreach ($this->attributeMapping as $index => $attribute) {
            if (($attribute['required'] ?? false) || ($attribute['system'] ?? false)) {
                if ($attribute['type'] == TranslatedText::class) {
                    $messages["attributeMapping.{$index}.data.{$this->defaultLanguage->code}.required"] =
                        __('adminhub::validation.generic_required');
                    continue;
                }
                $messages["attributeMapping.{$index}.data.required"] = __('adminhub::validation.generic_required');
            }
        }

        return $messages;
    }

    /**
     * Computed property to get attribute data.
     *
     * @return array
     */
    abstract public function getAttributeDataProperty();

    /**
     * Computed property to get available attributes.
     *
     * @return \Illuminate\Support\Collection
     */
    abstract public function getAvailableAttributesProperty();

    abstract public function getLanguagesProperty();
}
