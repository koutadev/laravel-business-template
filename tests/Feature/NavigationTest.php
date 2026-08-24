<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use App\Support\Navigation\NavigationMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 左サイドナビゲーションの検証。
 *
 * メニューの構成・権限による出し分け・現在地・パンくずを見る。
 */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    #[Test]
    public function the_sidebar_groups_the_menu_into_sections(): void
    {
        $this->actingAs($this->userWithRole(RoleName::Admin))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder([
                'ダッシュボード',
                'マスタ', 'マスタ管理',
                '管理', 'ユーザー管理', '操作ログ',
            ])
            // 個々のマスタはハブ(マスタ管理)から入るので、ナビには出さない
            ->assertSee(route('masters.index'))
            ->assertSee(route('users.index'))
            ->assertSee('メインメニュー');
    }

    #[Test]
    public function the_menu_is_filtered_by_permission(): void
    {
        // 担当者はマスタまで。ユーザー管理と操作ログは出ない
        $this->actingAs($this->userWithRole(RoleName::Staff))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('masters.index'))
            ->assertDontSee(route('users.index'))
            ->assertDontSee(route('activity-logs.index'));

        // 閲覧者も同じ(参照だけできる)
        $this->actingAs($this->userWithRole(RoleName::Viewer))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('masters.index'))
            ->assertDontSee(route('users.index'));
    }

    #[Test]
    public function a_section_without_visible_items_is_dropped(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionName::DashboardView->value);

        $sections = app(NavigationMenu::class)->visibleSections($user);

        $labels = array_map(static fn ($section): string => $section->label, $sections);

        $this->assertSame([''], $labels, 'ダッシュボードだけが残り、マスタ・管理のセクションは消える。');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('masters.index'));
    }

    #[Test]
    public function the_current_page_is_highlighted_and_shown_in_the_breadcrumbs(): void
    {
        $response = $this->actingAs($this->userWithRole(RoleName::Admin))
            ->get(route('masters.employees.index'))
            ->assertOk();

        // パンくずは メニューの定義から自動で組み立てる
        $response->assertSee('パンくず')
            ->assertSeeInOrder(['ダッシュボード', 'マスタ', '社員'])
            ->assertSee('aria-current="page"', false);
    }

    #[Test]
    public function a_child_screen_keeps_its_parent_highlighted(): void
    {
        // 登録画面もパターン一致で「社員」の現在地として扱う
        $this->actingAs($this->userWithRole(RoleName::Admin))
            ->get(route('masters.employees.create'))
            ->assertOk()
            ->assertSeeInOrder(['ダッシュボード', 'マスタ', '社員']);
    }

    #[Test]
    public function a_page_can_append_its_own_breadcrumb(): void
    {
        Route::middleware(['web', 'auth'])
            ->get('/_test/breadcrumb', fn () => Blade::render(
                '<x-app-layout><x-slot name="breadcrumb">新規登録</x-slot>本文</x-app-layout>'
            ));

        $this->actingAs($this->userWithRole(RoleName::Admin))
            ->get('/_test/breadcrumb')
            ->assertOk()
            ->assertSeeInOrder(['ダッシュボード', '新規登録']);
    }

    #[Test]
    public function the_sidebar_uses_the_theme_colors(): void
    {
        config(['theme.colors.primary' => '#0f766e']);

        $this->actingAs($this->userWithRole(RoleName::Admin))
            ->get(route('dashboard'))
            ->assertOk()
            // 現在地のハイライトはテーマ由来の CSS 変数を使う
            ->assertSee('--color-primary:#0f766e', false)
            ->assertSee('bg-primary-soft', false);
    }

    #[Test]
    public function guests_are_redirected_and_never_see_the_menu(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('メインメニュー');
    }
}
