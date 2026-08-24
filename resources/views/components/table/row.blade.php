@props([
    'href' => null,
    'modal' => null,
    'muted' => false,
])

@php
    // 行クリックの動き。セル内のリンク・ボタン・入力を押したときは反応させない。
    $guard = "if (event.target.closest('a,button,input,select,textarea,label')) { return; }";

    $onClick = match (true) {
        $href !== null => $guard." window.location.href = '".e($href)."';",
        $modal !== null => $guard." window.dispatchEvent(new CustomEvent('open-modal', { detail: '".e($modal)."' }));",
        default => null,
    };

    $clickable = $onClick !== null;
@endphp

{{--
    一覧の 1 行。

    href を渡すと行全体がその URL へ、modal を渡すと行クリックで
    その名前のモーダルが開く(1-D と連携。フェーズ2 のマスタ UI で使う)。
--}}
<tr @if ($onClick) onclick="{{ $onClick }}"
        onkeydown="if (event.key === 'Enter' && event.target === this) { this.click(); }"
        role="link"
        tabindex="0"
    @endif
    @class([
        'transition-colors motion-reduce:transition-none',
        'odd:bg-white even:bg-gray-50/60 dark:odd:bg-gray-800 dark:even:bg-gray-900/20',
        'hover:bg-primary-soft/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary' => $clickable,
        'cursor-pointer' => $clickable,
        'hover:bg-gray-50 dark:hover:bg-gray-900/40' => ! $clickable,
        'opacity-60' => $muted,
    ])
    {{ $attributes }}>
    {{ $slot }}
</tr>
