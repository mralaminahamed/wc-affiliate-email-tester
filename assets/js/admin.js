/* global wcaetAdmin, jQuery */
( function ( $ ) {
	'use strict';

	// ── Email type → field visibility ──────────────────────────────────────

	var fieldMap = wcaetAdmin.fieldMap || {};

	function syncFieldVisibility() {
		var type = $( '#email_type' ).val();
		var required = fieldMap[ type ] || 'affiliate';

		$( '.wcaet-id-field' ).hide();
		$( '#field-' + required ).show();
	}

	$( '#email_type' ).on( 'change', syncFieldVisibility );
	syncFieldVisibility();

	// ── Select2 ───────────────────────────────────────────────────────────

	$( '.wcaet-select2' ).each( function () {
		var $el       = $( this );
		var endpoint  = $el.data( 'endpoint' );
		var restUrl   = wcaetAdmin.restUrl.replace( /\/$/, '' );

		$el.select2( {
			placeholder:    $el.data( 'placeholder' ) || '',
			allowClear:     true,
			minimumInputLength: 0,
			ajax: {
				url:      restUrl + '/' + endpoint,
				dataType: 'json',
				delay:    300,
				cache:    true,
				headers:  { 'X-WP-Nonce': wcaetAdmin.nonce },
				data: function ( params ) {
					return {
						search:   params.term || '',
						per_page: 20,
					};
				},
				processResults: function ( data ) {
					return { results: data };
				},
			},
		} );
	} );

	// Refresh visible Select2 when email type changes.
	$( '#email_type' ).on( 'change', function () {
		$( '.wcaet-select2' ).select2( 'val', '' );
	} );

	// ── Tab switching ─────────────────────────────────────────────────────

	$( document ).on( 'click', '.wcaet-tab-btn', function () {
		var target = $( this ).data( 'tab' );

		$( '.wcaet-tab-btn' ).removeClass( 'is-active' ).attr( 'aria-selected', 'false' );
		$( this ).addClass( 'is-active' ).attr( 'aria-selected', 'true' );

		$( '.wcaet-email-tab-panel' ).attr( 'hidden', true );
		$( '#' + target ).removeAttr( 'hidden' );
	} );

	// ── Collapsible toggles ───────────────────────────────────────────────

	$( document ).on( 'click', '.wcaet-collapsible-toggle', function () {
		var $btn    = $( this );
		var target  = $btn.data( 'target' );
		var $panel  = $( '#' + target );
		var isOpen  = $btn.attr( 'aria-expanded' ) === 'true';
		var $span   = $btn.find( 'span' );

		if ( isOpen ) {
			$panel.attr( 'hidden', true );
			$btn.attr( 'aria-expanded', 'false' );
			$span.text( $btn.data( 'label-closed' ) );
		} else {
			$panel.removeAttr( 'hidden' );
			$btn.attr( 'aria-expanded', 'true' );
			$span.text( $btn.data( 'label-open' ) );
		}
	} );

	// ── Iframe auto-resize ────────────────────────────────────────────────

	function resizeIframe( iframe ) {
		try {
			var doc = iframe.contentDocument || iframe.contentWindow.document;
			iframe.style.height = ( doc.documentElement.scrollHeight || doc.body.scrollHeight ) + 'px';
		} catch ( e ) {
			// Cross-origin or data URI; ignore.
		}
	}

	$( document ).on( 'load', '.wcaet-preview-iframe', function () {
		resizeIframe( this );
	} );

	$( '.wcaet-preview-iframe' ).each( function () {
		// Already loaded (e.g., data: URI).
		if ( this.contentDocument && this.contentDocument.readyState === 'complete' ) {
			resizeIframe( this );
		}
	} );

	// ── Settings: dependent field disabling ───────────────────────────────

	function syncDependents() {
		$( '.wcaet-dependent[data-depends-on]' ).each( function () {
			var $dep     = $( this );
			var dep      = $dep.data( 'depends-on' );
			var $master  = $( '#' + dep );
			var enabled  = $master.is( ':checked' );
			$dep.toggleClass( 'is-disabled', ! enabled );
		} );
	}

	$( '.wcaet-settings-form input[type="checkbox"]' ).on( 'change', syncDependents );
	syncDependents();

} )( jQuery );
