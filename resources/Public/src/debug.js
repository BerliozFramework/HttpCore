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

import bootstrap from 'bootstrap/dist/js/bootstrap';
import './scss/debug.scss';
import hljs from 'highlight.js/lib/core';
import 'highlight.js/styles/atom-one-dark-reasonable.css';
import {fetch} from 'whatwg-fetch';

const BERLIOZ_REPORTS_KEY = 'BERLIOZ_REPORTS';

hljs.registerLanguage('plaintext', require('highlight.js/lib/languages/plaintext'));
hljs.registerLanguage('http', require('highlight.js/lib/languages/http'));
hljs.registerLanguage('json', require('highlight.js/lib/languages/json'));
hljs.registerLanguage('php', require('highlight.js/lib/languages/php'));
hljs.registerLanguage('sql', require('highlight.js/lib/languages/sql'));
hljs.registerLanguage('twig', require('highlight.js/lib/languages/twig'));


///////////////
/// WINDOWS ///
///////////////

const parentWindow = (window.parent && window.parent !== window ? window.parent : null);
const openerWindow = (window.opener && window.opener !== window ? window.opener : null);
const loader = document.getElementById('loader-wrapper');


/////////////////
/// HIGHLIGHT ///
/////////////////

const highlight = (selector, parent) => {
    (parent || document).querySelectorAll(selector).forEach((block) => hljs.highlightBlock(block));
};
highlight('pre > code');


///////////////////////
/// CONSOLE BUTTONS ///
///////////////////////

// Dismiss console
document.querySelectorAll('[data-dismiss="berlioz-console"]').forEach(function (el) {
    el.addEventListener('click', function () {
        if (parentWindow && parentWindow.toggleBerliozConsole) {
            parentWindow.toggleBerliozConsole()
        }
    });

    el.classList.toggle('d-none', !parentWindow);
});

// New console
document.querySelectorAll('[data-toggle="berlioz-console-new-window"]').forEach(function (el) {
    el.addEventListener('click', function () {
        if (parentWindow && parentWindow.openBerliozConsoleInNewWindow) {
            parentWindow.openBerliozConsoleInNewWindow()
        }
    });

    el.classList.toggle('d-none', !parentWindow);
});


////////////////
/// Tooltips ///
////////////////

document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));


//////////////
/// DETAIL ///
//////////////

document.querySelectorAll('[data-toggle="detail"][data-type][data-target]').forEach(function (el) {
    el.addEventListener('click', function () {
        let modalEl = document.getElementById(el.dataset.type + 'Detail');
        if (!modalEl) {
            return;
        }

        let modal = new bootstrap.Modal(modalEl);
        loader.style.display = 'block';

        fetch(el.dataset.target)
            .then(function (response) {
                return response.text()
            })
            .then(function (html) {
                modalEl.querySelector('.modal-body').innerHTML = html;
                highlight('.modal-body pre > code', modalEl);
                modal.show()
            })
            .finally(() => loader.style.display = 'none');
    });
});


////////////////
/// TIMELINE ///
////////////////

document.querySelectorAll('.timeline').forEach(function (timelineEl) {
    timelineEl.querySelectorAll('.activity[href]').forEach(function (activityLinkEl) {
        activityLinkEl.addEventListener('click', function () {
            // Activities on time line
            timelineEl.querySelectorAll('.activity.bg-primary').forEach((el) => el.classList.remove('bg-primary'));
            activityLinkEl.classList.add('bg-primary');

            // Activities in list
            let activityDetailEl = document.querySelector(activityLinkEl.getAttribute('href'));
            activityDetailEl.classList.add('table-primary');
            Array.prototype.filter.call(activityDetailEl.parentNode.children, function (child) {
                return child !== activityDetailEl;
            }).map((el) => el.classList.remove('table-primary'))
        });
    });

    timelineEl.addEventListener('mousemove', function (event) {
        let
            timeLineX = this.getBoundingClientRect().left + document.body.scrollLeft,
            timeLineWidth = this.offsetWidth,
            cursorX = event.pageX - timeLineX,
            positionLeft = (cursorX * 100 / timeLineWidth),
            finalPositionLeft = '',
            finalPositionRight = '';

        if (positionLeft <= 50) {
            finalPositionLeft = positionLeft + '%'
        } else {
            finalPositionRight = (100 - positionLeft) + '%'
        }

        timelineEl.querySelectorAll('.scales .scale.cursor').forEach(function (cursorEl) {
            cursorEl.classList.toggle('cursor-inverted', finalPositionRight !== '');
            cursorEl.style.left = finalPositionLeft;
            cursorEl.style.right = finalPositionRight;
            cursorEl.querySelector('.cursor-value').textContent = (Math.round((cursorX * timelineEl.dataset.duration / timeLineWidth) * 1000 * 1000) / 1000).toString();
        });
    });
    timelineEl.addEventListener('mouseenter', function () {
        this.querySelectorAll('.scales .scale.cursor').forEach((el) => el.style.display = 'block');
    });
    timelineEl.addEventListener('mouseleave', function () {
        timelineEl.querySelectorAll('.scales .scale.cursor').forEach((el) => el.style.display = 'none');
    });
});


/////////////////////////
/// REPORTS & STORAGE ///
/////////////////////////

// Report list
document.getElementById('report_id').addEventListener('change', function () {
    window.location = window.location.toString().replace(document.body.dataset.report, this.value);
});

// Add report to storage
const refreshReports = window.refreshReports = () => {
    let currentReport = document.body.dataset.report || null;
    let currentReportFound = false;
    let reportSelect = document.getElementById('report_id');
    let reports = [];

    if (parentWindow || openerWindow) {
        reports = (parentWindow || openerWindow).berlioz.console.reports || [];
    }

    if (!parentWindow) {
        let sessionReports = JSON.parse(window.sessionStorage.getItem(BERLIOZ_REPORTS_KEY)) || [];
        sessionReports.push(...reports);

        reports = [...new Set(sessionReports)];

        window.sessionStorage.setItem(BERLIOZ_REPORTS_KEY, JSON.stringify(reports));
    }

    // Empty report select
    while (reportSelect.firstChild) reportSelect.removeChild(reportSelect.firstChild);

    reports.forEach((report) => {
        let optionSelected = currentReport === report;

        reportSelect.insertBefore(
            new Option(
                '#' + report,
                report,
                optionSelected,
                optionSelected
            ),
            reportSelect.firstChild
        );

        if (optionSelected) {
            currentReportFound = true;
        }
    });

    // Current report not found?
    if (!currentReportFound) {
        reportSelect.insertBefore(
            new Option(
                '#' + currentReport,
                currentReport,
                true,
                true
            ),
            reportSelect.firstChild
        );
    }
};

refreshReports();
window.setInterval(() => {
    refreshReports();
}, 1000);


///////////////
/// Iframes ///
///////////////

function resizeIframe(iframeEl) {
    iframeEl.height = iframeEl.contentDocument.body.scrollHeight + 100;
}

document.querySelectorAll('iframe').forEach(function (iframeEl) {
    if (iframeEl.contentDocument.readyState !== 'loading') {
        window.setTimeout(() => resizeIframe(iframeEl), 250)
        return;
    }

    iframeEl.addEventListener('DOMContentLoaded', () => window.setTimeout(() => resizeIframe(iframeEl), 250));
});


//////////////
/// LOADER ///
//////////////

document.querySelectorAll('a[href]:not([data-toggle]):not([href^="#"])').forEach(function (el) {
    el.addEventListener('click', () => loader.style.display = 'block');
});
