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

import jQuery from 'jquery';
import 'bootstrap/dist/js/bootstrap.bundle';
import './scss/debug.scss';
import hljs from 'highlight.js/lib/highlight';
import 'highlight.js/styles/github.css';

const BERLIOZ_REPORTS_KEY = 'BERLIOZ_REPORTS';

hljs.registerLanguage('plaintext', require('highlight.js/lib/languages/plaintext'));
hljs.registerLanguage('json', require('highlight.js/lib/languages/json'));
hljs.registerLanguage('php', require('highlight.js/lib/languages/php'));
hljs.registerLanguage('twig', require('highlight.js/lib/languages/twig'));
hljs.registerLanguage('sql', require('highlight.js/lib/languages/sql'));

global.$ = global.jQuery = jQuery;


///////////////
/// WINDOWS ///
///////////////

let parentWindow = (window.parent && window.parent !== window ? window.parent : null);
let openerWindow = (window.opener && window.opener !== window ? window.opener : null);


jQuery(($) => {
    /////////////////
    /// HIGHLIGHT ///
    /////////////////

    const highlight = (selector) => {
        $(selector)
            .each(function (i, block) {
                hljs.highlightBlock(block)
            })
    };


    ///////////////////////
    /// CONSOLE BUTTONS ///
    ///////////////////////

    {
        $('[data-dismiss="berlioz-console"]')
            .click(function () {
                if (parentWindow && parentWindow.toggleBerliozConsole) {
                    parentWindow.toggleBerliozConsole()
                }
            })
            .toggleClass('d-none', !parentWindow);

        $('[data-toggle="berlioz-console-new-window"]')
            .click(function () {
                if (parentWindow && parentWindow.openBerliozConsoleInNewWindow) {
                    parentWindow.openBerliozConsoleInNewWindow()
                }
            })
            .toggleClass('d-none', !parentWindow)
    }


    /////////////////////////
    /// REPORTS & STORAGE ///
    /////////////////////////

    // Report list
    $('#report_id')
        .on('change',
            function () {
                window.location = window.location.toString().replace($('body').data('report'), this.value);
            });

    // Add report to storage
    const refreshReports = window.refreshReports = () => {
        let currentReport = $('body').data('report') || null;
        let currentReportFound = false;
        let $reportSelect = $('#report_id');
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

        $reportSelect.empty();
        reports.forEach((report) => {
            let optionSelected = currentReport === report;

            $reportSelect.prepend(
                new Option(
                    '#' + report,
                    report,
                    optionSelected,
                    optionSelected
                )
            );

            if (optionSelected) {
                currentReportFound = true;
            }
        });

        // Current report not found?
        if (!currentReportFound) {
            $reportSelect.prepend(
                new Option(
                    '#' + currentReport,
                    currentReport,
                    true,
                    true
                )
            );
        }
    };
    refreshReports();
    window.setInterval(() => {
        refreshReports();
    }, 1000);


    //////////////
    /// LOADER ///
    //////////////

    $(document).ajaxStart(function () {
        $('#loader-wrapper').show()
    });
    $(document).ajaxStop(function () {
        $('#loader-wrapper').hide()
    });
    $(document).on('click', 'a[href]:not([data-toggle]):not([href="#"])', () => {
        $('#loader-wrapper').show()
    });


    /////////////////
    /// Highlight ///
    /////////////////

    highlight('pre > code');


    ///////////////
    /// Iframes ///
    ///////////////

    $('iframe.iframe-h-auto')
        .on('load',
            function () {
                if ($(this).get(0).contentWindow) {
                    $(this).height($(this).get(0).contentWindow.document.body.scrollHeight + 100)
                }
            })
        .trigger('load');


    ////////////////
    /// Tooltips ///
    ////////////////

    $('[data-toggle="tooltip"]').tooltip();


    ////////////////
    /// Timeline ///
    ////////////////

    $('.timeline')
        .mousemove(function (e) {
            let
                timeLineX = $(this).offset().left,
                timeLineWidth = $(this).width(),
                cursorX = e.pageX - timeLineX,
                positionLeft = (cursorX * 100 / timeLineWidth),
                finalPositionLeft = '',
                finalPositionRight = '';

            if (positionLeft <= 50) {
                finalPositionLeft = positionLeft + '%'
            } else {
                finalPositionRight = (100 - positionLeft) + '%'
            }

            $('.scales .scale.cursor', this)
                .toggleClass('cursor-inverted', finalPositionRight !== '')
                .css({left: finalPositionLeft, right: finalPositionRight})
                .find('.cursor-value')
                .text(Math.round((cursorX * $(this).data('duration') / timeLineWidth) * 1000 * 1000) / 1000)
        })
        .mouseenter(function () {
            $('.scales .scale.cursor', this).show()
        })
        .mouseleave(function () {
            $('.scales .scale.cursor', this).hide()
        })
        .on('click',
            '.activity[href]',
            function () {
                // Activities on time line
                $('.activity.bg-primary', $(this).parents('.timeline')).removeClass('bg-primary');
                $(this).addClass('bg-primary');

                // Activities in list
                $($(this).attr('href'))
                    .addClass('text-primary')
                    .siblings('tr.text-primary')
                    .removeClass('text-primary')
            });


    ///////////////
    /// DETAILS ///
    ///////////////

    $('[data-toggle="detail"][data-type][data-target]')
        .on('click',
            function () {
                let modal = $('#' + $(this).data('type') + 'Detail').filter('.modal');

                if (modal.length !== 1) {
                    return;
                }

                $.ajax({
                    "url": $(this).data('target'),
                    "success": function (data) {
                        $('.modal-body', modal).html(data);
                        highlight($('.modal-body pre > code', modal));
                        $(modal).modal('show')
                    }
                })
            })
});