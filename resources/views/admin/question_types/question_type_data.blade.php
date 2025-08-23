@php
    $label = strtoupper($name ?? '');
    $name = strtolower($name ?? '');

    // Tailwind-style required star
    $required_label = '<span class="text-rose-600">*</span>';

    $num_field           = $name . '_number';
    $mark_field          = $name . '_mark';
    $ng_mark_field       = $name . '_negative_mark';
    $ng_mark_range_field = $name . '_negative_mark_range';

    $num_value           = old($num_field, $question_type->{$num_field} ?? '');
    $mark_value          = old($mark_field, $question_type->{$mark_field} ?? '');
    $ng_mark_value       = old($ng_mark_field, $question_type->{$ng_mark_field} ?? '');
    $ng_mark_range_value = old($ng_mark_range_field, $question_type->{$ng_mark_range_field} ?? '');

    $requiredAttr = ($required ?? false) ? 'required' : '';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
  {{-- Number --}}
  <div>
    <label class="number-label block text-sm font-medium text-gray-700 mb-1">
      Number of {{ $label }}
      {!! ($required ?? false) ? $required_label : '' !!}
    </label>
    <input
      type="number"
      name="{{ $num_field }}"
      value="{{ $num_value }}"
      {!! $requiredAttr !!}
      inputmode="numeric"
      autocomplete="off"
      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
    >
  </div>

  {{-- Mark --}}
  <div>
    <label class="mark-label block text-sm font-medium text-gray-700 mb-1">
      Mark of {{ $label }}
      {!! ($required ?? false) ? $required_label : '' !!}
    </label>
    <input
      type="number"
      name="{{ $mark_field }}"
      value="{{ $mark_value }}"
      step="any"
      {!! $requiredAttr !!}
      inputmode="decimal"
      autocomplete="off"
      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
    >
  </div>

  {{-- Negative Mark / stamp --}}
  <div>
    <label class="ng-mark-label block text-sm font-medium text-gray-700 mb-1">
      {{ $label }} Negative Mark/stamp
      {!! ($required ?? false) ? $required_label : '' !!}
    </label>
    <input
      type="number"
      name="{{ $ng_mark_field }}"
      value="{{ $ng_mark_value }}"
      step="any"
      {!! $requiredAttr !!}
      inputmode="decimal"
      autocomplete="off"
      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
    >
  </div>

  {{-- Negative Mark Range (optional) --}}
  <div class="nagetive_mark_range">
    <label class="ng-mark-range-label block text-sm font-medium text-gray-700 mb-1">
      {{ $label }} Negative Mark Range
    </label>
    <input
      type="text"
      name="{{ $ng_mark_range_field }}"
      value="{{ $ng_mark_range_value }}"
      autocomplete="off"
      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
      placeholder="e.g. -0.25,-0.5 or -1 - -0.25"
    >
    <p class="mt-1 text-xs text-gray-500">
      Optional — comma separated (e.g., <code>-0.25,-0.5</code> or ranges like <code>-1 - -0.25</code>)
    </p>
  </div>
</div>
