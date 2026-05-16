/* global aanp_ajax */
jQuery( document ).ready( function ( $ ) {

	// ── Tab switching ──────────────────────────────────────────────────────
	$( '.aanp-tab-btn' ).on( 'click', function () {
		var tab = $( this ).data( 'tab' );

		$( '.aanp-tab-btn' ).removeClass( 'is-active' ).attr( 'aria-selected', 'false' );
		$( '.aanp-tab-pane' ).removeClass( 'is-active' );

		$( this ).addClass( 'is-active' ).attr( 'aria-selected', 'true' );
		$( '#aanp-tab-' + tab ).addClass( 'is-active' );

		// persist active tab in localStorage so refresh stays on same tab
		try { localStorage.setItem( 'aanp_active_tab', tab ); } catch ( e ) {}
	} );

	// restore last active tab
	try {
		var savedTab = localStorage.getItem( 'aanp_active_tab' );
		if ( savedTab ) {
			$( '.aanp-tab-btn[data-tab="' + savedTab + '"]' ).trigger( 'click' );
		}
	} catch ( e ) {}

	// ── Add RSS Feed ───────────────────────────────────────────────────────
	$( '#add-feed' ).on( 'click', function () {
		var row = $(
			'<div class="rss-feed-row">' +
			'<input type="url" name="aanp_settings[rss_feeds][]" value="" placeholder="https://example.com/feed.xml" />' +
			'<button type="button" class="test-feed">' + aanp_ajax.test_text + '</button>' +
			'<button type="button" class="remove-feed">&#x2715;</button>' +
			'<span class="feed-test-result"></span>' +
			'</div>'
		);
		$( '#rss-feeds-container' ).append( row );
		row.find( 'input' ).focus();
	} );

	// ── Test RSS Feed ──────────────────────────────────────────────────────
	$( document ).on( 'click', '.test-feed', function () {
		var row     = $( this ).closest( '.rss-feed-row' );
		var feedUrl = row.find( 'input[type="url"]' ).val().trim();
		var result  = row.find( '.feed-test-result' );

		if ( ! feedUrl ) {
			result.html( '<span class="aanp-status-error">Enter a URL first.</span>' );
			return;
		}

		var btn = $( this ).prop( 'disabled', true ).text( 'Testing…' );

		$.ajax( {
			url:  aanp_ajax.ajax_url,
			type: 'POST',
			data: { action: 'aanp_test_feed', nonce: aanp_ajax.nonce, feed_url: feedUrl },
			success: function ( response ) {
				if ( response.success ) {
					result.html( '<span class="aanp-status-success">✓ ' + escapeHtml( response.data.message ) + '</span>' );
				} else {
					result.html( '<span class="aanp-status-error">✗ ' + escapeHtml( response.data || 'Error' ) + '</span>' );
				}
			},
			error: function () {
				result.html( '<span class="aanp-status-error">✗ Request failed.</span>' );
			},
			complete: function () {
				btn.prop( 'disabled', false ).text( aanp_ajax.test_text );
			}
		} );
	} );

	// ── Remove RSS Feed ────────────────────────────────────────────────────
	$( document ).on( 'click', '.remove-feed', function () {
		$( this ).closest( '.rss-feed-row' ).slideUp( 200, function () {
			$( this ).remove();
		} );
	} );

	// ── Generate Posts — two-phase ─────────────────────────────────────────
	$( '#aanp-generate-posts' ).on( 'click', function () {
		var button      = $( this );
		var statusDiv   = $( '#aanp-generation-status' );
		var statusText  = $( '#aanp-status-text' );
		var progressBar = $( '.aanp-progress-bar' );
		var resultsDiv  = $( '#aanp-generation-results' );
		var resultsList = $( '#aanp-results-list' );

		button.prop( 'disabled', true ).addClass( 'is-loading' );
		button.find( '.dashicons' ).addClass( 'spin' );
		statusDiv.slideDown( 200 );
		resultsDiv.hide();
		resultsList.empty();
		progressBar.css( 'width', '0%' );
		statusText.text( aanp_ajax.generating_text );

		// Phase 1: fetch article list
		$.ajax( {
			url:  aanp_ajax.ajax_url,
			type: 'POST',
			data: { action: 'aanp_fetch_articles', nonce: aanp_ajax.nonce },
			success: function ( response ) {
				if ( ! response.success ) {
					var errMsg = ( response.data && response.data.message ) ? response.data.message : ( response.data || aanp_ajax.error_text );
					statusText.html( '<span class="aanp-status-error">✗ ' + escapeHtml( errMsg ) + '</span>' );
					finishGeneration( button, statusDiv );
					return;
				}

				var articles  = response.data.articles;
				var total     = articles.length;
				var generated = [];

				if ( total === 0 ) {
					statusText.html( '<span class="aanp-status-error">✗ ' + escapeHtml( aanp_ajax.error_text ) + '</span>' );
					finishGeneration( button, statusDiv );
					return;
				}

				// Phase 2: generate each article one-at-a-time for real progress feedback
				function generateNext( index ) {
					if ( index >= total ) {
						progressBar.css( 'width', '100%' );
						var doneMsg = generated.length + ' ' + aanp_ajax.success_text;

						if ( generated.length > 0 ) {
							statusText.html( '<span class="aanp-status-success">✓ ' + escapeHtml( doneMsg ) + '</span>' );

							$.each( generated, function ( i, post ) {
								var li = $(
									'<li class="is-success">' +
									'<span class="aanp-result-icon">✓</span>' +
									'<span class="aanp-result-title">' + escapeHtml( post.title ) + '</span>' +
									'<a class="aanp-result-link" href="' + escapeHtml( post.edit_link ) + '" target="_blank">Edit →</a>' +
									'</li>'
								);
								resultsList.append( li );
							} );
							resultsDiv.slideDown( 200 );
						} else {
							statusText.html( '<span class="aanp-status-error">✗ ' + escapeHtml( aanp_ajax.error_text ) + '</span>' );
						}

						finishGeneration( button, statusDiv );
						return;
					}

					var article = articles[ index ];
					statusText.text( '(' + ( index + 1 ) + '/' + total + ') ' + article.title );
					progressBar.css( 'width', Math.round( ( index / total ) * 100 ) + '%' );

					$.ajax( {
						url:  aanp_ajax.ajax_url,
						type: 'POST',
						data: {
							action:  'aanp_generate_single',
							nonce:   aanp_ajax.nonce,
							article: article
						},
						success: function ( res ) {
							if ( res.success ) { generated.push( res.data ); }
							generateNext( index + 1 );
						},
						error: function () {
							generateNext( index + 1 );
						}
					} );
				}

				generateNext( 0 );
			},
			error: function ( xhr, status, error ) {
				statusText.html( '<span class="aanp-status-error">✗ AJAX Error: ' + escapeHtml( error ) + '</span>' );
				finishGeneration( button, statusDiv );
			}
		} );
	} );

	// ── After generation: countdown cooldown ───────────────────────────────
	function finishGeneration( button, statusDiv ) {
		button.find( '.dashicons' ).removeClass( 'spin' );
		button.removeClass( 'is-loading' );

		var cooldown  = aanp_ajax.cooldown_seconds || 60;
		var remaining = cooldown;
		var origHtml  = button.html();

		var timer = setInterval( function () {
			remaining--;
			button.html( '<span class="dashicons dashicons-clock spin"></span> ' + aanp_ajax.cooldown_text.replace( '%d', remaining ) );
			if ( remaining <= 0 ) {
				clearInterval( timer );
				button.prop( 'disabled', false );
				button.html( origHtml );
			}
		}, 1000 );

		// hide status after a moment if nothing else is pending
		setTimeout( function () {
			if ( ! $( '#aanp-generation-results' ).is( ':visible' ) ) {
				statusDiv.slideUp( 300 );
			}
		}, 5000 );
	}

	// ── HTML escape helper ─────────────────────────────────────────────────
	function escapeHtml( text ) {
		if ( typeof text !== 'string' ) { text = String( text ); }
		var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
		return text.replace( /[&<>"']/g, function ( m ) { return map[ m ]; } );
	}

} );
