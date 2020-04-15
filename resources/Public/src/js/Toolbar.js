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

const TOOLBAR_COOKIE = 'berlioz_toolbar_direction';

class Toolbar {
    constructor() {
        this._iframe = null;
        this._direction = null;
    }

    open() {
        if (this._iframe) {
            return;
        }

        this._iframe = document.createElement('iframe');
        this._iframe.id = 'berlioz-toolbar';
        this._iframe.src = '/_console/' + window.berlioz_debug_report + '/toolbar';
        this.refresh();
        document.body.appendChild(this._iframe);
    }

    close() {
        if (!this._iframe) {
            return;
        }

        this._iframe.remove();
    }

    refresh() {
        if (!this._iframe) {
            return;
        }

        this._iframe.setAttribute('style', this._style());
    }

    _style() {
        let style =
            'position: fixed !important;' +
            'z-index: 1000000 !important;' +
            'bottom: 0 !important;' +
            'height: 75px !important;' +
            'width: 210px !important;' +
            'background-color: transparent !important;' +
            'border:none !important;';

        if (this.direction === 'rtl') {
            style += 'right: 0 !important;';
        } else {
            style += 'left: 0 !important;';
        }

        return style;
    }

    get direction() {
        if (this._direction === null) {
            return document.cookie.replace(new RegExp('(?:(?:^|.*;\\s*)' + TOOLBAR_COOKIE + '\\s*=\\s*([^;]*).*$)|^.*$'), '$1') === 'rtl' ? 'rtl' : 'ltr';
        }

        return this._direction;
    }

    set direction(direction) {
        this._direction = direction === 'rtl' ? 'rtl' : 'ltr';
        document.cookie = TOOLBAR_COOKIE + '=' + direction + ";path=/";
        this.refresh();
    }

    flipDirection() {
        this.direction = (this.direction === 'ltr' ? 'rtl' : 'ltr');
    }
}

export default Toolbar;