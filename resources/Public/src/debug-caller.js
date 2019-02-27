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
  // Creation of toolbar iframe
  let iFrame = document.createElement('iframe')
  iFrame.id = 'berlioz-toolbar'
  iFrame.src = '/_console/' + window.berlioz_debug_report + '/toolbar'
  iFrame.setAttribute('style',
                      'position: fixed !important;' +
                        'z-index: 1000000 !important;' +
                        'bottom: 0 !important;' +
                        'height: 75px !important;' +
                        'width: 200px !important;' +
                        'background-color: transparent !important;' +
                        'border:none !important;')
  document.body.appendChild(iFrame)
} else {
  console.error('Unable to load Berlioz Debug Toolbar without report id')
}

let
  iFrameConsole = null,
  oldOverflow = null,
  oldOverflowX = null,
  oldOverflowY = null

window.toggleBerliozConsole =
  () => {
    if (window.berlioz_debug_report) {
      iFrameConsole = document.getElementById('berlioz-console')

      if (!iFrameConsole) {
        // Creation of toolbar iframe
        iFrameConsole = document.createElement('iframe')
        iFrameConsole.id = 'berlioz-console'
        iFrameConsole.src = '/_console/' + window.berlioz_debug_report
        iFrameConsole.setAttribute('style',
                                   'position: fixed !important;' +
                                     'z-index: 1000001 !important;' +
                                     'top: 0 !important;' +
                                     'bottom: 0 !important;' +
                                     'left: 0 !important;' +
                                     'right: 0 !important;' +
                                     'width: 100% !important;' +
                                     'height: 100% !important;' +
                                     'background-color: white !important;' +
                                     'border:none !important;')
        document.body.appendChild(iFrameConsole)
      }

      if (iFrameConsole.style.display !== 'block') {
        iFrameConsole.style.display = 'block'

        oldOverflow = document.body.style.overflow
        oldOverflowX = document.body.style.overflowX
        oldOverflowY = document.body.style.overflowY
        document.body.style.overflow = 'hidden'
        document.body.style.overflowX = 'hidden'
        document.body.style.overflowY = 'hidden'
      } else {
        iFrameConsole.style.display = 'none'

        document.body.style.overflow = oldOverflow
        document.body.style.overflowX = oldOverflowX
        document.body.style.overflowY = oldOverflowY
      }
    } else {
      console.error('Unable to load Berlioz Debug Toolbar without report id')
    }
  }