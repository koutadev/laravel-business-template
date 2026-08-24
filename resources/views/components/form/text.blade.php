@props([
    'name',
    'label' => null,
    'value' => null,
    'type' => 'text',
    'size' => 'md',
    'required' => false,
    'disabled' => false,
    'help' => null,
    'placeholder' => null,
    'id' => null,
])

@php
    use App\Support\Ui\Input;
    use App\Support\Ui\Size;

    $inputId = $id ?? $name;
    $hasError = $errors->has($name);
@endphp

{{-- テキスト入力(type を変えればメール・パスワードなどにも使える) --}}
<x-form.field :name="$name" :label="$label" :for="$inputId" :required="$required" :help="$help">
    <input type="{{ $type }}"
           id="{{ $inputId }}"
           name="{{ $name }}"
           value="{{ old($name, $value) }}"
           @if ($placeholder) placeholder="{{ $placeholder }}" @endif
           @required($required)
           @disabled($disabled)
           @if ($hasError) aria-invalid="true" @endif
           {{ $attributes->merge(['class' => Input::classes(Size::resolve($size), $hasError)]) }}>
</x-form.field>
