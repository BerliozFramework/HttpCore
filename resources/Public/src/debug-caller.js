/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2017 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

if (!window.berlioz_debug_report) {
    throw new Error('Unable to load Berlioz Debug Toolbar without report id');
}

import Console from './js/Console';
import Toolbar from './js/Toolbar';

let berliozConsole, berliozToolbar;
window.berlioz = {
    console: berliozConsole = new Console(window.berlioz_debug_report),
    toolbar: berliozToolbar = new Toolbar,
}

berliozToolbar.open();

window.closeBerliozToolbar = () => berliozToolbar.close();
window.flipBerliozToolbar = () => berliozToolbar.flipDirection();
window.toggleBerliozConsole = () => berliozConsole.toggle();
window.openBerliozConsoleInNewWindow = () => berliozConsole.newWindow();


// Replacement of window.parent.XMLHttpRequest to catch XHR requests
let oldXhrSend = XMLHttpRequest.prototype.send;
XMLHttpRequest.prototype.send =
    function () {
        this.addEventListener(
            'readystatechange',
            function () {
                if (this.readyState !== 2) {
                    return;
                }
                if (this.getAllResponseHeaders().toLowerCase().indexOf('x-berlioz-debug') === -1) {
                    return;
                }

                berliozConsole.report = this.getResponseHeader('X-Berlioz-Debug');
            },
            false
        );

        return oldXhrSend.apply(this, arguments);
    };