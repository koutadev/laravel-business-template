import Alpine from 'alpinejs';

import appShell from './app-shell';
import combobox from './combobox';
import registerToastStore from './toast';
import './charts';

window.Alpine = Alpine;

// レイアウト（左サイドナビの開閉）で使う Alpine コンポーネント
Alpine.data('appShell', appShell);

// コンボボックス（入力で候補を絞るセレクト）
Alpine.data('combobox', combobox);

// トースト通知（Alpine のストア）
registerToastStore(Alpine);

Alpine.start();
