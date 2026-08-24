@props([
    'name',
    'label' => null,
    'value' => null,
    'size' => 'md',
    'required' => false,
    'disabled' => false,
    'help' => null,
    'min' => null,
    'max' => null,
    'id' => null,
    'messages' => null,
])

@php
    use App\Support\Ui\Input;
    use App\Support\Ui\Size;
    use Illuminate\Support\Carbon;

    $inputId = $id ?? $name;
    // messages を明示したときはそちらを優先する(カタログや独自表示用)
    $hasError = $messages !== null ? $messages !== [] : $errors->has($name);

    // Carbon を渡しても input[type=date] の形式に整える
    $dateValue = $value instanceof \DateTimeInterface
        ? Carbon::instance($value)->format('Y-m-d')
        : $value;
@endphp

{{-- 日付入力 --}}
<x-form.field :name="$name" :label="$label" :for="$inputId" :required="$required" :help="$help" :messages="$messages">
    <input type="date"
           id="{{ $inputId }}"
           name="{{ $name }}"
           value="{{ old($name, $dateValue) }}"
           @if ($min !== null) min="{{ $min }}" @endif
           @if ($max !== null) max="{{ $max }}" @endif
           @required($required)
           @disabled($disabled)
           @if ($hasError) aria-invalid="true" @endif
           {{ $attributes->merge(['class' => Input::classes(Size::resolve($size), $hasError)]) }}>
</x-form.field>
