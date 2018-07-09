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

import 'jquery';
import './debug-toolbar.scss';

function toggleBerliozConsole() {
  if ((window.parent && window.parent.toggleBerliozConsole) !== undefined) {
    window.parent.toggleBerliozConsole();
  }
}

$(function () {
  $('#toolbar-content, #toolbar #logo')
    .click(function () {
      toggleBerliozConsole();
    });
});
