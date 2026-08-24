import Alpine from 'alpinejs';

import appShell from './app-shell';
import registerToastStore from './toast';
import './charts';

window.Alpine = Alpine;

// レイアウト（左サイドナビの開閉）で使う Alpine コンポーネント
Alpine.data('appShell', appShell);

// トースト通知（Alpine のストア）
registerToastStore(Alpine);

Alpine.start();
