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

if (window.berlioz_debug_report) {
    let berliozDebugConsole = null;
    let berliozOpenedConsoles = [];
    window.berlioz_reports = [window.berlioz_debug_report];

    // Creation of toolbar iframe
    let iFrame = document.createElement('iframe');
    iFrame.id = 'berlioz-toolbar';
    iFrame.src = '/_console/' + window.berlioz_debug_report + '/toolbar';
    iFrame.setAttribute('style',
        'position: fixed !important;' +
        'z-index: 1000000 !important;' +
        'bottom: 0 !important;' +
        'height: 75px !important;' +
        'width: 200px !important;' +
        'background-color: transparent !important;' +
        'border:none !important;');
    document.body.appendChild(iFrame);

    let
        oldOverflow = null,
        oldOverflowX = null,
        oldOverflowY = null;

    // Toggle Berlioz console
    window.toggleBerliozConsole =
        () => {
            if (!berliozDebugConsole) {
                // Creation of toolbar iframe
                berliozDebugConsole = document.createElement('iframe');
                berliozDebugConsole.id = 'berlioz-console';
                berliozDebugConsole.src = '/_console/' + window.berlioz_debug_report;
                berliozDebugConsole.setAttribute('style',
                    'position: fixed !important;' +
                    'z-index: 1000001 !important;' +
                    'top: 0 !important;' +
                    'bottom: 0 !important;' +
                    'left: 0 !important;' +
                    'right: 0 !important;' +
                    'width: 100% !important;' +
                    'height: 100% !important;' +
                    'background-color: white !important;' +
                    'border:none !important;');
                document.body.appendChild(berliozDebugConsole);
                berliozOpenedConsoles.push(berliozDebugConsole.contentWindow);
            }

            if (berliozDebugConsole.style.display !== 'block') {
                berliozDebugConsole.style.display = 'block';

                oldOverflow = document.body.style.overflow;
                oldOverflowX = document.body.style.overflowX;
                oldOverflowY = document.body.style.overflowY;
                document.body.style.overflow = 'hidden';
                document.body.style.overflowX = 'hidden';
                document.body.style.overflowY = 'hidden';
            } else {
                berliozDebugConsole.style.display = 'none';

                document.body.style.overflow = oldOverflow;
                document.body.style.overflowX = oldOverflowX;
                document.body.style.overflowY = oldOverflowY;
            }
        };
    window.openBerliozConsoleInNewWindow = () => {
        let consoleLocation = '/_console/' + window.berlioz_debug_report;
        if (berliozDebugConsole) {
            consoleLocation = berliozDebugConsole.contentDocument.location;
        }

        let consoleWindow = window.open(consoleLocation);
        berliozOpenedConsoles.push(consoleWindow);
        toggleBerliozConsole();
    };

    // Reports
    const addReport = (report) => {
        window.berlioz_reports.push(report);
        berliozOpenedConsoles.forEach((consoleWindow) => {
            consoleWindow.refreshReports();
        })
    };

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

                    addReport(this.getResponseHeader('X-Berlioz-Debug'));
                },
                false);

            return oldXhrSend.apply(this, arguments);
        };
} else {
    console.error('Unable to load Berlioz Debug Toolbar without report id');
}