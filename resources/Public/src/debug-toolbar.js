/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2021 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

import './scss/debug-toolbar.scss'

const toggleBerliozConsole = () => {
    if ((window.parent && window.parent.toggleBerliozConsole) !== undefined) {
        window.parent.toggleBerliozConsole();
    }
};
const closeBerliozToolbar = () => {
    if ((window.parent && window.parent.closeBerliozToolbar) !== undefined) {
        window.parent.closeBerliozToolbar();
    }
};
const flipBerliozToolbar = () => {
    if ((window.parent && window.parent.flipBerliozToolbar) !== undefined) {
        window.parent.flipBerliozToolbar();
        document.querySelector('body').classList.toggle('rtl');
    }
};

document.querySelector('#toolbar-content').addEventListener('click', () => toggleBerliozConsole());
document.querySelector('#toolbar #logo').addEventListener('click', () => toggleBerliozConsole());
document.querySelector('[data-toggle="close"]').addEventListener('click', () => closeBerliozToolbar());
document.querySelector('[data-toggle="flip"]').addEventListener('click', () => flipBerliozToolbar());