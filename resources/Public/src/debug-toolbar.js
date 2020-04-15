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

import jQuery from 'jquery'
import './scss/debug-toolbar.scss'

jQuery(($) => {
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
            $('body').toggleClass('rtl');
        }
    };

    $('#toolbar-content, #toolbar #logo').click(() => toggleBerliozConsole());
    $('[data-toggle="close"]').click(() => closeBerliozToolbar());
    $('[data-toggle="flip"]').click(() => flipBerliozToolbar());
});