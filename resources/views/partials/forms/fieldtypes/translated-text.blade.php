<x-hub::translatable>
  @include("adminhub::partials.forms.fieldtypes.text", [
    'language' => $this->defaultLanguage->code,
    'field' => $field,
  ])
  @foreach($this->languages as $language)
    <x-slot :name="$language->code">
      <div wire:key="language-{{ $language->id }}">
        @include("adminhub::partials.forms.fieldtypes.text", [
          'language' => $language->code,
          'field' => $field,
        ])
      </div>
    </x-slot>
  @endforeach
</x-hub::translatable>
