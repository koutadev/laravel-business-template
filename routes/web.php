<?php

use App\Enums\PermissionName;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Masters\DepartmentController;
use App\Http\Controllers\Masters\EmployeeController;
use App\Http\Controllers\Masters\PartnerController;
use App\Http\Controllers\Masters\PositionController;
use App\Http\Controllers\Masters\ProductCategoryController;
use App\Http\Controllers\Masters\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Support\Routing\MasterRoutes;
use App\Support\Ui\DateRange;
use App\Support\Ui\Toast;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // 環境の疎通確認用(STEP 1 から継続)
    try {
        $pdo = DB::connection()->getPdo();

        $database = [
            'connected' => true,
            'message' => sprintf(
                '%s / %s',
                DB::connection()->getDatabaseName(),
                $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            ),
        ];
    } catch (Throwable $e) {
        $database = [
            'connected' => false,
            'message' => $e->getMessage(),
        ];
    }

    return view('welcome', [
        'database' => $database,
        'status' => [
            'Laravel' => app()->version(),
            'PHP' => PHP_VERSION,
            'Database' => config('database.default'),
        ],
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:'.PermissionName::DashboardView->value)
        ->name('dashboard');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('permission:'.PermissionName::ActivityLogView->value)
        ->name('activity-logs.index');

    // --- 共通マスタ -------------------------------------------------------
    // 一覧 / CSV は master.view、登録・編集・削除・復元は master.manage が必要
    Route::prefix('masters')->name('masters.')->group(function () {
        MasterRoutes::register('employees', EmployeeController::class, 'employees');
        MasterRoutes::register('partners', PartnerController::class, 'partners');
        MasterRoutes::register('products', ProductController::class, 'products');

        // サブマスタ
        MasterRoutes::register('departments', DepartmentController::class, 'departments');
        MasterRoutes::register('positions', PositionController::class, 'positions');
        MasterRoutes::register('product-categories', ProductCategoryController::class, 'product-categories');
    });

    // --- ユーザー管理(ロールの付け替え) ----------------------------------
    Route::middleware('permission:'.PermissionName::UserManage->value)
        ->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| UI コンポーネントカタログ(開発・デモ用)
|--------------------------------------------------------------------------
|
| 共通部品の見た目と状態を 1 ページで確認するためのページ。
| 本番環境では登録しない。
|
*/
if (! app()->environment('production')) {
    Route::get('/_ui', function () {
        // ページネーションの見た目を確認するためのダミー
        $paginator = new LengthAwarePaginator(
            items: range(1, 20),
            total: 137,
            perPage: 20,
            currentPage: 3,
            options: ['path' => url('/_ui')],
        );

        $customers = [
            1 => '株式会社アオイ商事', 2 => '有限会社イロハ物産', 3 => 'ウエノ電機株式会社',
            4 => '株式会社エダサキ工業', 5 => 'オオトリ製作所', 6 => '株式会社カシワギシステムズ',
            7 => 'キタムラ運輸株式会社', 8 => '株式会社クスノキ設計', 9 => 'ケヤキ食品株式会社',
            10 => '株式会社コウヨウホールディングス',
        ];

        return view('ui.catalog', [
            'paginator' => $paginator,
            'customers' => $customers,
            // 日付範囲ピッカーの送信値をサーバ側で解決した結果(見本)
            'demoRange' => DateRange::fromRequest(request(), 'demo_range'),
        ]);
    })->name('ui.catalog');

    // 編集フォーム用モーダルの見本(バリデーションエラーで開き直す)
    Route::post('/_ui/demo-form', function () {
        request()->validate(
            ['demo_title' => ['required', 'string', 'max:20']],
            [],
            ['demo_title' => '件名'],
        );

        return back()->with(
            Toast::SESSION_KEY,
            Toast::success('保存しました(デモなので実際には保存していません)。'),
        );
    })->name('ui.catalog.demo-form');

    // コンボボックスの非同期モードの見本(?q= で絞り込み、[{value,label}] を返す)
    Route::get('/_ui/options', function () {
        $prefectures = ['北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県', '群馬県',
            '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県',
            '岐阜県', '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県',
            '鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県', '福岡県',
            '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'];

        $query = trim((string) request('q'));

        return collect($prefectures)
            ->when($query !== '', fn ($items) => $items->filter(
                fn (string $name): bool => str_contains($name, $query)
            ))
            ->take(20)
            ->values()
            ->map(fn (string $name, int $index): array => ['value' => (string) ($index + 1), 'label' => $name])
            ->all();
    })->name('ui.catalog.options');
}

require __DIR__.'/auth.php';
