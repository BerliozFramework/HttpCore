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

class Console {
    constructor(report) {
        this._iframe = null;
        this._reports = [report];
        this._windows = [];
        this._overflow = document.body.style.overflow;
        this._overflowX = document.body.style.overflowX;
        this._overflowY = document.body.style.overflowY;
    }

    _init() {
        this._iframe = document.createElement('iframe');
        this._iframe.id = 'berlioz-console';
        this._iframe.src = '/_console/' + this._reports[0];
        this.refresh();
        document.body.appendChild(this._iframe);
        this._windows.push(this._iframe.contentWindow);
    }

    open() {
        if (!this._iframe) {
            this._init();
        }

        if (this._iframe.style.display === 'block') {
            return;
        }

        this._overflow = document.body.style.overflow;
        this._overflowX = document.body.style.overflowX;
        this._overflowY = document.body.style.overflowY;
        this._iframe.style.display = 'block';

        document.body.style.overflow = 'hidden';
        document.body.style.overflowX = 'hidden';
        document.body.style.overflowY = 'hidden';
    }

    close() {
        if (!this._iframe) {
            return;
        }

        if (this._iframe.style.display === 'none') {
            return;
        }

        this._iframe.style.display = 'none';

        document.body.style.overflow = this._overflow;
        document.body.style.overflowX = this._overflowX;
        document.body.style.overflowY = this._overflowY;
    }

    get opened() {
        if (!this._iframe) {
            return false;
        }

        return this._iframe.style.display === 'block';
    }

    toggle() {
        if (this.opened) {
            this.close();
            return;
        }

        this.open();
    }

    newWindow() {
        let consoleLocation = '/_console/' + this._reports[0];
        if (this._iframe) {
            consoleLocation = this._iframe.contentDocument.location;
        }

        this._windows.push(window.open(consoleLocation));

        this.close();
    }

    refresh() {
        if (!this._iframe) {
            return;
        }

        this._iframe.setAttribute('style', this._style());
    }

    _style() {
        return 'position: fixed !important;' +
            'z-index: 1000001 !important;' +
            'top: 0 !important;' +
            'bottom: 0 !important;' +
            'left: 0 !important;' +
            'right: 0 !important;' +
            'width: 100% !important;' +
            'height: 100% !important;' +
            'background-color: white !important;' +
            'border:none !important;';
    }

    get reports() {
        return this._reports;
    }

    set report(report) {
        this._reports.push(report);

        this._windows.forEach((consoleWindow) => {
            consoleWindow.refreshReports();
        })
    }
}

export default Console;