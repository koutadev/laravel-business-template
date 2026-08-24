<?php

namespace Tests\Feature;

use App\Support\Ui\Toast;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 共通 UI 部品(1-B)の検証。
 *
 * 単体は Blade::render で、組み合わせはカタログページで確認する。
 */
class UiComponentTest extends TestCase
{
    #[Test]
    public function a_button_renders_its_variant_and_size(): void
    {
        $html = Blade::render('<x-button variant="danger" size="lg" type="button">削除</x-button>');

        $this->assertStringContainsString('削除', $html);
        $this->assertStringContainsString('bg-rose-600', $html);
        $this->assertStringContainsString('px-5', $html);
        $this->assertStringContainsString('type="button"', $html);
    }

    #[Test]
    public function a_button_can_be_disabled_or_loading(): void
    {
        $disabled = Blade::render('<x-button disabled>保存</x-button>');
        $this->assertStringContainsString('disabled', $disabled);
        $this->assertStringContainsString('pointer-events-none', $disabled);

        $loading = Blade::render('<x-button loading>保存</x-button>');
        $this->assertStringContainsString('animate-spin', $loading);
        $this->assertStringContainsString('aria-busy="true"', $loading);
        $this->assertStringContainsString('disabled', $loading, 'ローディング中は押せない。');
    }

    #[Test]
    public function a_button_with_href_renders_a_link(): void
    {
        $html = Blade::render('<x-button href="/dashboard" variant="secondary">一覧へ</x-button>');

        $this->assertStringContainsString('<a href="/dashboard"', $html);

        // 無効なリンクはフォーカスも当たらない
        $disabled = Blade::render('<x-button href="/dashboard" disabled>一覧へ</x-button>');
        $this->assertStringContainsString('aria-disabled="true"', $disabled);
        $this->assertStringContainsString('tabindex="-1"', $disabled);
    }

    #[Test]
    public function the_theme_color_is_used_by_the_primary_button(): void
    {
        $html = Blade::render('<x-button variant="primary">保存</x-button>');

        // 色は Tailwind のテーマトークン経由(= .env の THEME_PRIMARY に連動)
        $this->assertStringContainsString('bg-primary', $html);
        $this->assertStringNotContainsString('bg-indigo', $html);
    }

    #[Test]
    public function an_unknown_variant_falls_back_to_the_default(): void
    {
        $html = Blade::render('<x-button variant="unknown">保存</x-button>');

        $this->assertStringContainsString('bg-primary', $html, '未知の値でも壊れず既定に落ちる。');
    }

    #[Test]
    public function a_text_field_renders_label_help_and_errors(): void
    {
        $this->withViewErrors(['title' => ['件名は必須です。']]);

        $html = Blade::render('<x-form.text name="title" label="件名" required help="30 文字まで" />');

        $this->assertStringContainsString('件名', $html);
        $this->assertStringContainsString('必須', $html);
        $this->assertStringContainsString('30 文字まで', $html);
        $this->assertStringContainsString('件名は必須です。', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('border-rose-400', $html);
    }

    #[Test]
    public function form_controls_render_their_values(): void
    {
        $this->withViewErrors([]);

        $number = Blade::render('<x-form.number name="amount" label="金額" :value="11000" min="0" />');
        $this->assertStringContainsString('value="11000"', $number);
        $this->assertStringContainsString('type="number"', $number);

        $date = Blade::render('<x-form.date name="closed_on" label="予定日" value="2026-08-24" />');
        $this->assertStringContainsString('type="date"', $date);
        $this->assertStringContainsString('value="2026-08-24"', $date);

        $select = Blade::render(
            '<x-form.select name="status" label="状態" :options="$options" selected="won" placeholder="選択" />',
            ['options' => ['open' => '進行中', 'won' => '受注']],
        );
        $this->assertStringContainsString('<option value="won" selected>受注</option>', $select);
        $this->assertStringContainsString('選択', $select);

        $checkbox = Blade::render('<x-form.checkbox name="is_active" label="有効" :checked="true" />');
        $this->assertStringContainsString('type="checkbox"', $checkbox);
        $this->assertStringContainsString('checked', $checkbox);

        $radio = Blade::render(
            '<x-form.radio name="plan" label="プラン" :options="$options" selected="b" />',
            ['options' => ['a' => 'A プラン', 'b' => 'B プラン']],
        );
        $this->assertStringContainsString('type="radio"', $radio);
        $this->assertMatchesRegularExpression('/value="b"[^>]*checked/s', $radio, '選択中のラジオに checked が付く。');
        $this->assertStringContainsString('<legend', $radio);
    }

    #[Test]
    public function a_badge_uses_the_tone_colors(): void
    {
        $this->assertStringContainsString('bg-emerald-100', Blade::render('<x-badge tone="success">受注</x-badge>'));
        $this->assertStringContainsString('bg-rose-100', Blade::render('<x-badge tone="danger">失注</x-badge>'));
        $this->assertStringContainsString('bg-primary-soft', Blade::render('<x-badge tone="primary">強調</x-badge>'));

        $dot = Blade::render('<x-badge tone="warning" dot>注意</x-badge>');
        $this->assertStringContainsString('bg-amber-500', $dot);
    }

    #[Test]
    public function a_kpi_card_becomes_a_link_when_a_href_is_given(): void
    {
        $plain = Blade::render('<x-kpi-card label="進行中の商談" :value="15" unit="件" note="受注・失注を除く" />');
        $this->assertStringContainsString('15', $plain);
        $this->assertStringContainsString('件', $plain);
        $this->assertStringContainsString('受注・失注を除く', $plain);
        $this->assertStringNotContainsString('<a ', $plain);

        $linked = Blade::render('<x-kpi-card label="今月の受注" :value="2334700" href="/deals" />');
        $this->assertStringContainsString('<a', $linked);
        $this->assertStringContainsString('href="/deals"', $linked);
        $this->assertStringContainsString('2,334,700', $linked, '整数は 3 桁区切りで表示する。');
    }

    #[Test]
    public function a_card_renders_its_slots(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-card title="社員" subtitle="全 32 名">
                <x-slot name="actions">操作</x-slot>
                本文
                <x-slot name="footer">補足</x-slot>
            </x-card>
        BLADE);

        foreach (['社員', '全 32 名', '操作', '本文', '補足'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    #[Test]
    public function tabs_render_buttons_and_panels(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-tabs :tabs="['overview' => '概要', 'detail' => '明細']">
                <x-tab-panel name="overview">概要の中身</x-tab-panel>
                <x-tab-panel name="detail">明細の中身</x-tab-panel>
            </x-tabs>
        BLADE);

        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('role="tab"', $html);
        $this->assertStringContainsString('role="tabpanel"', $html);
        $this->assertStringContainsString('概要の中身', $html);
        $this->assertStringContainsString('overview', $html);
    }

    #[Test]
    public function a_flashed_toast_is_rendered(): void
    {
        session()->put(Toast::SESSION_KEY, Toast::success('保存しました。'));

        $html = Blade::render('<x-toast-container />');

        $this->assertStringContainsString('保存しました。', $html);
        $this->assertStringContainsString('$store.toast.push', $html);
    }

    #[Test]
    public function the_catalog_page_shows_every_component(): void
    {
        $response = $this->get(route('ui.catalog'))->assertOk();

        $response->assertSeeInOrder([
            'UI コンポーネントカタログ',
            'ボタン',
            'フォーム部品',
            'バッジ / ステータスチップ',
            'トースト通知',
            'タブ',
            'KPI カード',
            'ページネーション',
            'カード',
        ]);

        // 状態の見本も出ている
        $response->assertSee('animate-spin', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('この項目は必須です。')
            // ページネーションは共通の見た目に差し替わっている
            ->assertSee('aria-label="ページ送り"', false)
            ->assertSee('全 137 件中');
    }
}
