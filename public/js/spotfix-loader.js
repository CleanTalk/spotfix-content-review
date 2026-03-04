/**
 * Spotfix widget loader.
 * Loads the doBoard Spotfix widget script with configuration from wp_localize_script.
 */
(function () {
	'use strict';

	if ( typeof window.spotfixConfig === 'undefined' || ! window.spotfixConfig.widgetUrl ) {
		return;
	}

	window.SpotfixWidgetConfig = { verticalPosition: 'compact' };

	var script = document.createElement( 'script' );
	script.type = 'text/javascript';
	script.async = true;
	script.defer = true;
	script.src = window.spotfixConfig.widgetUrl;

	var firstScript = document.getElementsByTagName( 'script' )[ 0 ];
	firstScript.parentNode.insertBefore( script, firstScript );
})();
