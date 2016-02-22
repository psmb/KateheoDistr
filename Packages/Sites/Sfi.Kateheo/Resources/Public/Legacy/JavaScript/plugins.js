// Avoid `console` errors in browsers that lack a console.
(function() {
    var method;
    var noop = function () {};
    var methods = [
        'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
        'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
        'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd',
        'timeStamp', 'trace', 'warn'
    ];
    var length = methods.length;
    var console = (window.console = window.console || {});

    while (length--) {
        method = methods[length];

        // Only stub undefined methods.
        if (!console[method]) {
            console[method] = noop;
        }
    }
}());

// Place any jQuery/helper plugins in here.
var onReadyPlugins = function () {
    headingAnchors();
}

var headingAnchors = function () {
    $(".js-anchor").each(function() {
        var anchor = $('<a class="HeadingAnchor-Link"><i class="fa fa-anchor anchor-link"></i></a>').attr('href', '#' + $(this).attr('id'));
        $(this).find("h1,h2,h3,h4,h5,h6").append(anchor);
    });
}

$(document).ready(function () {
	onReadyPlugins();
});

if (typeof document.addEventListener === 'function') {
	document.addEventListener('Neos.PageLoaded', function(event) {
		onReadyPlugins();
	}, false);
}
