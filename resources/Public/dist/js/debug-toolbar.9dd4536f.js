/*! For license information please see debug-toolbar.9dd4536f.js.LICENSE.txt */
(()=>{"use strict";var e=function(){void 0!==(window.parent&&window.parent.toggleBerliozConsole)&&window.parent.toggleBerliozConsole()};document.querySelector("#toolbar-content").addEventListener("click",(function(){return e()})),document.querySelector("#toolbar #logo").addEventListener("click",(function(){return e()})),document.querySelector('[data-toggle="close"]').addEventListener("click",(function(){void 0!==(window.parent&&window.parent.closeBerliozToolbar)&&window.parent.closeBerliozToolbar()})),document.querySelector('[data-toggle="flip"]').addEventListener("click",(function(){void 0!==(window.parent&&window.parent.flipBerliozToolbar)&&(window.parent.flipBerliozToolbar(),document.querySelector("body").classList.toggle("rtl"))}))})();