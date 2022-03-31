<div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
  {{ $toolbar ?? null }}
  <div class="w-full overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          {{ $head }}
        </tr>
      </thead>

      <tbody {{ $attributes->wire('sortable') }} @if($attributes->get('row-ref')) x-ref="{{ $attributes->get('row-ref') }}" @endif class="relative">
        {{ $body }}
      </tbody>
    </table>
  </div>
</div>
