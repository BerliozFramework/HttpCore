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
import 'bootstrap/dist/js/bootstrap.bundle'
import './debug.scss'
import hljs from 'highlight.js/lib/highlight'
import 'highlight.js/styles/github.css'

hljs.registerLanguage('plaintext', require('highlight.js/lib/languages/plaintext'))
hljs.registerLanguage('json', require('highlight.js/lib/languages/json'))
hljs.registerLanguage('php', require('highlight.js/lib/languages/php'))
hljs.registerLanguage('twig', require('highlight.js/lib/languages/twig'))
hljs.registerLanguage('sql', require('highlight.js/lib/languages/sql'))

global.$ = global.jQuery = jQuery

jQuery(($) => {
    const highlight = (selector) => {
        $(selector)
            .each(function (i, block) {
                hljs.highlightBlock(block)
            })
    }


    // Console buttons
    {
        let hasWindowParent = (window.parent && window.parent.toggleBerliozConsole) !== undefined

        $('[data-dismiss="berlioz-console"]')
            .click(function () {
                if (hasWindowParent) {
                    window.parent.toggleBerliozConsole()
                }
            })
            .toggleClass('d-none', !hasWindowParent)

        $('[data-toggle="berlioz-console-new-window"]')
            .click(function () {
                window.open(window.location.href, '_blank')
                window.parent.toggleBerliozConsole()
            })
            .toggleClass('d-none', !hasWindowParent)
    }


    // Ajax
    $(document).ajaxStart(function () {
        $('#loader-wrapper').show()
    })
    $(document).ajaxStop(function () {
        $('#loader-wrapper').hide()
    })


    // Highlight
    highlight('pre > code')


    // Iframes
    $('iframe.iframe-h-auto')
        .on('load', function () {
            if ($(this).get(0).contentWindow) {
                $(this).height($(this).get(0).contentWindow.document.body.scrollHeight + 100)
            }
        })
        .trigger('load')


    // Tooltips
    $('[data-toggle="tooltip"]').tooltip()


    // Time line
    $('.timeline')
        .mousemove(function (e) {
            let
                timeLineX = $(this).offset().left,
                timeLineWidth = $(this).width(),
                cursorX = e.pageX - timeLineX,
                positionLeft = (cursorX * 100 / timeLineWidth),
                finalPositionLeft = '',
                finalPositionRight = ''

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
        .mouseenter(function (e) {
            $('.scales .scale.cursor', this).show()
        })
        .mouseleave(function (e) {
            $('.scales .scale.cursor', this).hide()
        })
        .on('click',
            '.activity[href]',
            function (e) {
                // Activities on time line
                $('.activity.bg-primary', $(this).parents('.timeline')).removeClass('bg-primary')
                $(this).addClass('bg-primary')

                // Activities in list
                $($(this).attr('href'))
                    .addClass('text-primary')
                    .siblings('tr.text-primary')
                    .removeClass('text-primary')
            })


    // Detail
    $('[data-toggle="detail"][data-type][data-target]')
        .on('click',
            function () {
                let modal = $('#' + $(this).data('type') + 'Detail').filter('.modal')
                if (modal.length === 1) {
                    $.ajax({
                        "url": $(this).data('target'),
                        "success": function (data) {
                            $('.modal-body', modal).html(data)
                            highlight($('.modal-body pre > code', modal))
                            $(modal).modal('show')
                        }
                    })
                }
            })
})