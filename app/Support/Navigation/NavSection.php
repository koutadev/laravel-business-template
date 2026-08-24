<?php

namespace App\Support\Navigation;

use App\Models\User;

/**
 * 左サイドナビゲーションのセクション(見出し + 項目のまとまり)。
 *
 * 見出しが空文字のセクションは、見出しを出さずに項目だけを並べる
 * (ダッシュボードのような単独項目用)。
 */
class NavSection
{
    /**
     * @param  list<NavItem>  $items
     */
    public function __construct(
        public readonly string $label,
        public readonly array $items,
    ) {}

    /**
     * 権限で絞った項目。
     *
     * @return list<NavItem>
     */
    public function visibleItems(?User $user): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (NavItem $item): bool => $item->isVisibleTo($user),
        ));
    }

    public function hasHeading(): bool
    {
        return $this->label !== '';
    }
}
