<div class="flex-col space-y-4">
  <div class="overflow-hidden shadow sm:rounded-md">
    <div class="flex-col px-4 py-5 space-y-4 bg-white sm:p-6">
      <div class="space-y-4 md:space-y-0 md:grid md:grid-cols-2 md:gap-4">
        <x-hub::input.group :label="__('adminhub::inputs.name')" for="name" :error="$errors->first('currency.name')">
          <x-hub::input.text wire:model="currency.name" name="name" id="name" :error="$errors->first('currency.name')" />
        </x-hub::input.group>

        <x-hub::input.group :label="__('adminhub::inputs.code')" for="code" :error="$errors->first('currency.code')">
          <x-hub::input.text wire:model="currency.code" name="code" id="code" :error="$errors->first('currency.code')" />
        </x-hub::input.group>
      </div>

      <div class="space-y-4 md:space-y-0 md:grid sm:grid-cols-2 lg:grid-cols-5 md:gap-4">
        <x-hub::input.group :label="__('adminhub::inputs.exchange_rate')" for="exchange_rate" :error="$errors->first('currency.exchange_rate')">
          <x-hub::input.text type="number" step="0.0001" wire:model="currency.exchange_rate" name="exchange_rate" id="exchange_rate" :error="$errors->first('currency.exchange_rate')" />
        </x-hub::input.group>

        <x-hub::input.group
          :label="__('adminhub::inputs.format')"
          for="format"
          :error="$errors->first('currency.format')"
          :has-info="true"
        >
          <x-slot name="instructions">
            <button type="button" wire:click.prevent="$set('showFormatInfo', true)" class="text-indigo-500 hover:underline">
              {{ __('adminhub::global.info_link') }}
            </button>
          </x-slot>

          <x-hub::modal wire:model="showFormatInfo">
            <div class="px-6 py-4">
              @foreach(__('adminhub::settings.currencies.form.format_help_text') as $value)
                <p>{!! $value !!}</p>
              @endforeach
            </div>
          </x-hub::modal>
          <x-hub::input.text wire:model="currency.format" name="format" id="format" :error="$errors->first('currency.format')" />
        </x-hub::input.group>

        <x-hub::input.group :label="__('adminhub::inputs.decimal_point')" for="decimal_point" :error="$errors->first('currency.decimal_point')">
          <x-hub::input.text wire:model="currency.decimal_point" name="decimal_point" id="decimal_point" :error="$errors->first('currency.decimal_point')" />
        </x-hub::input.group>

        <x-hub::input.group :label="__('adminhub::inputs.thousand_point')" for="thousand_point" :error="$errors->first('currency.thousand_point')">
          <x-hub::input.text wire:model="currency.thousand_point" name="thousand_point" id="thousand_point" :error="$errors->first('currency.thousand_point')" />
        </x-hub::input.group>

        <x-hub::input.group :label="__('adminhub::inputs.decimal_places')" for="decimal_places" :error="$errors->first('currency.decimal_places')">
          <x-hub::input.text :disabled="$currency->id && !Auth::user()->admin" wire:model="currency.decimal_places" name="decimal_places" id="decimal_places" :error="$errors->first('currency.decimal_places')" />
        </x-hub::input.group>
      </div>
      <div class="grid-cols-2 space-y-4 md:space-y-0 md:grid md:gap-4">
        <x-hub::input.group
          :label="__('adminhub::inputs.default.label')"
          for="handle"
          :instructions="__('adminhub::inputs.default.instructions', [
            'model' => 'channel',
          ])"
        >
          <x-hub::input.toggle wire:click="toggleDefault" :on="$currency->default" name="handle" id="handle" :disabled="$currency->id && $currency->getOriginal('default')" />
        </x-hub::input.group>
        <x-hub::input.group
          :label="__('adminhub::inputs.enabled.label')"
          for="enabled"
        >
          <x-hub::input.toggle wire:click="toggleEnabled" :on="$currency->enabled" name="enabled" id="enabled" :disabled="$currency->default" />
        </x-hub::input.group>
      </div>
    </div>

    <div class="px-4 py-3 text-right bg-gray-50 sm:px-6">
      <button type="submit" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        {{ __(
          $currency->id ? 'adminhub::settings.currencies.form.update_btn' : 'adminhub::settings.currencies.form.create_btn'
        ) }}
      </button>
    </div>

  </div>
   @if($currency->id && !$currency->getOriginal('default'))
    <div class="bg-white border border-red-300 rounded shadow">
      <header class="px-6 py-4 text-red-700 bg-white border-b border-red-300 rounded-t">
        {{ __('adminhub::inputs.danger_zone.title') }}
      </header>
      <div class="p-6 space-y-4 text-sm">
        <div class="grid grid-cols-12 gap-4">
          <div class="col-span-12 md:col-span-6">
            <strong>{{ __('adminhub::inputs.danger_zone.label', [
              'model' => 'Currency'
            ]) }}</strong>
            <p class="text-xs text-gray-600">{{ __('adminhub::inputs.danger_zone.instructions', [
              'model' => 'currency',
              'attribute' => 'code',
            ]) }}</p>
          </div>
          <div class="col-span-9 lg:col-span-4">
            <x-hub::input.text type="email" wire:model="deleteConfirm" />
          </div>
          <div class="col-span-3 text-right lg:col-span-2">
            <x-hub::button theme="danger" :disabled="!$this->canDelete" wire:click="delete" type="button">Delete</x-hub::button>
          </div>
        </div>
      </div>
    </div>
  @endif
</div>
