!function(i){function e(e){for(var r,o,t=e[0],n=e[1],u=e[2],l=0,c=[];l<t.length;l++)o=t[l],Object.prototype.hasOwnProperty.call(a,o)&&a[o]&&c.push(a[o][0]),a[o]=0;for(r in n)Object.prototype.hasOwnProperty.call(n,r)&&(i[r]=n[r]);for(p&&p(e);c.length;)c.shift()();return f.push.apply(f,u||[]),s()}function s(){for(var e,r=0;r<f.length;r++){for(var o=f[r],t=!0,n=1;n<o.length;n++){var u=o[n];0!==a[u]&&(t=!1)}t&&(f.splice(r--,1),e=l(l.s=o[0]))}return e}var o={},a={3:0},f=[];function l(e){if(o[e])return o[e].exports;var r=o[e]={i:e,l:!1,exports:{}};return i[e].call(r.exports,r,r.exports,l),r.l=!0,r.exports}l.m=i,l.c=o,l.d=function(e,r,o){l.o(e,r)||Object.defineProperty(e,r,{enumerable:!0,get:o})},l.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},l.t=function(r,e){if(1&e&&(r=l(r)),8&e)return r;if(4&e&&"object"==typeof r&&r&&r.__esModule)return r;var o=Object.create(null);if(l.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:r}),2&e&&"string"!=typeof r)for(var t in r)l.d(o,t,function(e){return r[e]}.bind(null,t));return o},l.n=function(e){var r=e&&e.__esModule?function(){return e.default}:function(){return e};return l.d(r,"a",r),r},l.o=function(e,r){return Object.prototype.hasOwnProperty.call(e,r)},l.p="/_console/dist/";var r=window.webpackJsonp=window.webpackJsonp||[],t=r.push.bind(r);r.push=e,r=r.slice();for(var n=0;n<r.length;n++)e(r[n]);var p=t;f.push(["./resources/Public/src/debug-toolbar.js",0]),s()}({"./resources/Public/src/debug-toolbar.js":function(e,r,o){"use strict";o.r(r);var t=o("./node_modules/jquery/dist/jquery.js"),n=o.n(t);o("./resources/Public/src/debug-toolbar.scss");
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
n()(function(e){e("#toolbar-content, #toolbar #logo").click(function(){void 0!==(window.parent&&window.parent.toggleBerliozConsole)&&window.parent.toggleBerliozConsole()})})},"./resources/Public/src/debug-toolbar.scss":function(e,r,o){}});