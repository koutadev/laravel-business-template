import Alpine from 'alpinejs';

import appShell from './app-shell';
import './charts';

window.Alpine = Alpine;

// レイアウト（左サイドナビの開閉）で使う Alpine コンポーネント
Alpine.data('appShell', appShell);

Alpine.start();
