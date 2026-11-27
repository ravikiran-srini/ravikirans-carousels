/* Revision Library: per-PDF page, email-link gate, personal-link smartcode.
   Needs the snippet "Revision Library (page + Quick Revision box)" (function rl_items). Source of truth: website/snippets/.
   Route /revision-library/<slug>/ is served through the Revision Library page itself, so it is public only while that page is published
   (drafts show to admins only). No password, key or token is stored here; signing uses the site's own WordPress salt. */
if ( ! function_exists( 'rl_sign' ) ) {

	function rl_sign( $data ) {
		return substr( hash_hmac( 'sha256', $data, wp_salt( 'auth' ) ), 0, 32 );
	}
	function rl_b64( $s ) {
		return rtrim( strtr( base64_encode( $s ), '+/', '-_' ), '=' );
	}
	function rl_unb64( $s ) {
		return base64_decode( strtr( $s, '-_', '+/' ) );
	}
	function rl_find( $slug ) {
		foreach ( function_exists( 'rl_items' ) ? rl_items() : array() as $it ) {
			if ( ( $it['slug'] ?? '' ) === $slug ) {
				return $it;
			}
		}
		return null;
	}
	function rl_page_url( $it ) {
		return home_url( '/revision-library/' . rawurlencode( $it['slug'] ) . '/' );
	}

	/* Emailed download link: signed, 7 days, bound to one PDF. */
	function rl_token( $email, $slug ) {
		$body = rl_b64( wp_json_encode( array( $email, $slug, time() + 7 * DAY_IN_SECONDS ) ) );
		return $body . '.' . rl_sign( 't|' . $body );
	}
	function rl_token_email( $tok, $slug ) {
		$p = explode( '.', (string) $tok );
		if ( 2 !== count( $p ) || ! hash_equals( rl_sign( 't|' . $p[0] ), $p[1] ) ) {
			return false;
		}
		$d = json_decode( rl_unb64( $p[0] ), true );
		if ( ! is_array( $d ) || 3 !== count( $d ) || $d[1] !== $slug || $d[2] < time() || ! is_email( $d[0] ) ) {
			return false;
		}
		return strtolower( $d[0] );
	}

	/* "Known subscriber" cookie, set after the emailed link is clicked. */
	function rl_cookie_set( $email ) {
		$v = rl_b64( $email ) . '.' . rl_sign( 'c|' . $email );
		setcookie( 'rl_ok', $v, array( 'expires' => time() + YEAR_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
		$_COOKIE['rl_ok'] = $v;
	}
	function rl_cookie_ok() {
		$p = explode( '.', isset( $_COOKIE['rl_ok'] ) ? wp_unslash( $_COOKIE['rl_ok'] ) : '' );
		return 2 === count( $p ) && hash_equals( rl_sign( 'c|' . rl_unb64( $p[0] ) ), $p[1] );
	}

	/* Personal key for newsletter links: {{rl.key}} in a FluentCRM email becomes <contact id>.<signature>. */
	function rl_personal_key( $id ) {
		return (int) $id . '.' . substr( rl_sign( 'p|' . (int) $id ), 0, 16 );
	}
	function rl_personal_email( $key ) {
		$p = explode( '.', (string) $key );
		if ( 2 !== count( $p ) || ! ctype_digit( $p[0] ) || ! hash_equals( substr( rl_sign( 'p|' . (int) $p[0] ), 0, 16 ), $p[1] ) || ! function_exists( 'FluentCrmApi' ) ) {
			return false;
		}
		$c = FluentCrmApi( 'contacts' )->getContact( (int) $p[0] );
		return ( $c && 'subscribed' === $c->status ) ? strtolower( $c->email ) : false;
	}

	function rl_known() {
		if ( isset( $_GET['rl_visitor'] ) && current_user_can( 'manage_options' ) ) { // admin preview of the visitor view
			return false;
		}
		if ( rl_cookie_ok() ) {
			return true;
		}
		$h = isset( $_COOKIE['fc_hash_secure'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['fc_hash_secure'] ) ) : '';
		if ( $h && function_exists( 'FluentCrmApi' ) ) { // FluentCRM's own cookie, set when a subscriber opens a newsletter link (a WordPress login alone does not count)
			$c = FluentCrmApi( 'contacts' )->getContactBySecureHash( $h );
			if ( $c && 'subscribed' === $c->status ) {
				rl_cookie_set( strtolower( $c->email ) );
				return true;
			}
		}
		return false;
	}

	function rl_limit( $key, $max ) {
		$k = 'rl_rl_' . md5( $key );
		$n = (int) get_transient( $k );
		if ( $n >= $max ) {
			return false;
		}
		set_transient( $k, $n + 1, HOUR_IN_SECONDS );
		return true;
	}

	/* Clicking the emailed link confirms the address: create or confirm the FluentCRM contact (never resurrects an unsubscribed one). */
	function rl_subscribe( $email, $it ) {
		if ( ! function_exists( 'FluentCrmApi' ) ) {
			return;
		}
		$api  = FluentCrmApi( 'contacts' );
		$cat  = $it['category'] ?? '';
		$data = array( 'email' => $email, 'status' => 'subscribed', 'lists' => array( 'Newsletter' ), 'tags' => array() );
		if ( $cat ) {
			$data['tags'][] = 'Downloaded: ' . $cat;
		}
		if ( ! $api->getContact( $email ) ) {
			$data['tags'][] = 'Website signup';
			if ( function_exists( 'fluentcrm_get_custom_contact_fields' ) ) {
				foreach ( (array) fluentcrm_get_custom_contact_fields() as $f ) {
					if ( in_array( $f['label'] ?? '', array( 'Signup source', 'Signup page' ), true ) && $cat ) {
						$data['custom_values'] = array( $f['slug'] => $cat );
						if ( 'Signup source' === $f['label'] ) {
							break;
						}
					}
				}
			}
		}
		$api->createOrUpdate( $data );
	}

	function rl_mail( $email, $it ) {
		$link = add_query_arg( 'rl_t', rl_token( $email, $it['slug'] ), rl_page_url( $it ) );
		$body = '<p>Hi,</p><p>Here is your free PDF: <strong>' . esc_html( $it['title'] ?? '' ) . '</strong></p>'
			. '<p><a href="' . esc_url( $link ) . '" style="display:inline-block;background:#c8102e;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;font-weight:bold">Download the PDF</a></p>'
			. '<p>Clicking the button confirms your email address and subscribes you to my free weekly exam-prep newsletter. The link works for 7 days and you can unsubscribe at any time.</p>'
			. '<p>If you did not ask for this, ignore this email and nothing happens.</p>'
			. '<p>Ravikiran<br>ravikirans.com</p><p style="color:#6b7280;font-size:12px">Button not working? Copy this link: ' . esc_html( $link ) . '</p>';
		return wp_mail( $email, 'Your PDF: ' . wp_strip_all_tags( $it['title'] ?? 'Revision PDF' ), $body, array( 'Content-Type: text/html; charset=UTF-8', 'From: Ravikiran Srinivasulu <contact@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '>' ) );
	}

	/* The PDF is streamed through the site so the page never shows the GitHub address. */
	function rl_stream( $it ) {
		$src = $it['pdf'] ?? '';
		$res = 0 === strpos( $src, 'https://raw.githubusercontent.com/ravikiran-srini/ravikirans-marketing/' ) ? wp_remote_get( $src, array( 'timeout' => 25 ) ) : false;
		if ( ! $res || is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
			wp_die( 'The PDF is not available right now. Please try again in a few minutes.', 'PDF unavailable', array( 'response' => 502 ) );
		}
		$body = wp_remote_retrieve_body( $res );
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $it['slug'] ) . '.pdf"' );
		header( 'Content-Length: ' . strlen( $body ) );
		echo $body; // phpcs:ignore
		exit;
	}

	function rl_item_html( $it ) {
		$url   = rl_page_url( $it );
		$dl    = add_query_arg( 'rl_get', '1', $url );
		$flag  = function ( $k ) { return isset( $_GET[ $k ] ) ? sanitize_key( wp_unslash( $_GET[ $k ] ) ) : ''; };
		$known = rl_known();
		$msgs  = array(
			'email' => 'That email address does not look right. Please try again.',
			'limit' => 'Too many requests. Please try again in an hour.',
			'mail'  => 'I could not send the email just now. Please try again in a few minutes.',
			'link'  => 'That link has expired or is not valid. Enter your email to get a new one.',
		);
		$css = '<style>.rl-p{display:flex;flex-wrap:wrap;gap:24px;margin:8px 0 24px}.rl-p img{width:210px;max-width:100%;height:auto;border:1px solid #e5e7eb;border-radius:8px;align-self:flex-start}.rl-p-main{flex:1;min-width:240px}.rl-p-meta{color:#6b7280;font-size:.9em;margin:0 0 12px}.rl-p-box{margin:18px 0;padding:16px 18px;border:1px solid #e5e7eb;border-left:4px solid #c8102e;border-radius:8px;background:#fafafa}.rl-p-box input[type=email]{width:100%;max-width:340px;padding:9px 10px;margin:6px 0 10px;border:1px solid #d1d5db;border-radius:6px}.rl-p-btn{display:inline-block;background:#c8102e;color:#fff!important;border:0;border-radius:6px;padding:10px 18px;font-weight:600;text-decoration:none;cursor:pointer}.rl-p-note{font-size:.82em;color:#6b7280;margin:10px 0 0}.rl-p-ok{padding:10px 12px;background:#ecfdf5;border-radius:6px;margin-bottom:10px}.rl-p-bad{padding:10px 12px;background:#fef2f2;border-radius:6px;margin-bottom:10px}.rl-p-links a{margin-right:16px}</style>';
		$out = $css . '<div class="rl-p">';
		if ( ! empty( $it['cover'] ) ) {
			$out .= '<img src="' . esc_url( $it['cover'] ) . '" alt="' . esc_attr( $it['title'] ?? '' ) . '">';
		}
		$out .= '<div class="rl-p-main"><p class="rl-p-meta">' . esc_html( $it['category'] ?? '' ) . ( ! empty( $it['date'] ) ? ' &middot; ' . esc_html( date_i18n( 'j M Y', strtotime( $it['date'] ) ) ) : '' ) . '</p>';
		$out .= '<p>' . esc_html( $it['summary'] ?? '' ) . '</p><div class="rl-p-box">';
		if ( $known || 'done' === $flag( 'rl_done' ) || '1' === $flag( 'rl_done' ) ) {
			if ( '1' === $flag( 'rl_done' ) ) {
				$out .= '<div class="rl-p-ok">Thank you, you are confirmed and subscribed. Your download starts now.</div><script>setTimeout(function(){location.href=' . wp_json_encode( $dl ) . '},800)</script>';
			}
			$out .= '<a class="rl-p-btn" href="' . esc_url( $dl ) . '">Download PDF</a>';
		} else {
			if ( '1' === $flag( 'rl_sent' ) ) {
				$out .= '<div class="rl-p-ok"><strong>Check your inbox.</strong> I have emailed you the download link (valid for 7 days). If it is not there in a minute, check spam or promotions.</div>';
			} elseif ( isset( $msgs[ $flag( 'rl_err' ) ] ) ) {
				$out .= '<div class="rl-p-bad">' . esc_html( $msgs[ $flag( 'rl_err' ) ] ) . '</div>';
			}
			$out .= '<form method="post" action="' . esc_url( $url ) . '"><label for="rl_email"><strong>Get this PDF free</strong><br>Enter your email and I will send you the download link.</label><br>'
				. '<input type="email" id="rl_email" name="rl_email" required placeholder="you@example.com" autocomplete="email">'
				. '<input type="text" name="rl_website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px"><br>'
				. '<button type="submit" class="rl-p-btn">Email me the PDF</button>'
				. '<p class="rl-p-note">Clicking the link in the email confirms your address and subscribes you to my free weekly exam-prep newsletter. Unsubscribe any time. Already on the list? Use the same email.</p></form>';
		}
		$out .= '</div><p class="rl-p-links">';
		if ( ! empty( $it['guide_url'] ) ) {
			$out .= '<a href="' . esc_url( $it['guide_url'] ) . '">Read the full study guide &rarr;</a>';
		}
		$out .= '<a href="' . esc_url( home_url( '/ravikiran-srinivasulu-udemy-courses/' ) ) . '">Practice tests on Udemy &rarr;</a><a href="' . esc_url( home_url( '/revision-library/#' . ( $it['category_slug'] ?? '' ) ) ) . '">More PDFs for ' . esc_html( $it['category'] ?? 'this category' ) . ' &rarr;</a></p></div></div>';
		return $out;
	}

	add_filter( 'query_vars', function ( $v ) {
		$v[] = 'rl_item';
		return $v;
	} );
	add_action( 'init', function () {
		add_rewrite_rule( '^revision-library/([^/]+)/?$', 'index.php?pagename=revision-library&rl_item=$matches[1]', 'top' );
		if ( '2' !== get_option( 'rl_rewrite_v' ) ) {
			flush_rewrite_rules( false );
			update_option( 'rl_rewrite_v', '2' );
		}
	}, 20 );
	add_filter( 'redirect_canonical', function ( $url ) {
		return get_query_var( 'rl_item' ) ? false : $url;
	} );

	add_action( 'template_redirect', function () {
		$slug = get_query_var( 'rl_item' );
		if ( isset( $_GET['rlp'] ) && ( $slug || is_page( 'revision-library' ) ) ) { // personal link from a newsletter
			$e = rl_personal_email( wp_unslash( $_GET['rlp'] ) );
			if ( $e ) {
				rl_cookie_set( $e );
			}
			wp_safe_redirect( remove_query_arg( 'rlp' ) );
			exit;
		}
		if ( ! $slug || is_404() ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		$it = rl_find( $slug );
		if ( ! $it ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			return;
		}
		$page = rl_page_url( $it );
		if ( isset( $_GET['rl_t'] ) ) { // emailed link clicked
			$e = rl_token_email( wp_unslash( $_GET['rl_t'] ), $it['slug'] );
			if ( $e ) {
				rl_subscribe( $e, $it );
				rl_cookie_set( $e );
				wp_safe_redirect( add_query_arg( 'rl_done', '1', $page ) );
			} else {
				wp_safe_redirect( add_query_arg( 'rl_err', 'link', $page ) );
			}
			exit;
		}
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['rl_email'] ) ) { // email form
			$email = strtolower( trim( sanitize_email( wp_unslash( $_POST['rl_email'] ) ) ) );
			$r     = array( 'rl_sent' => '1' );
			if ( ! empty( $_POST['rl_website'] ) ) {
				$r = array( 'rl_sent' => '1' ); // bot: pretend it worked
			} elseif ( ! is_email( $email ) ) {
				$r = array( 'rl_err' => 'email' );
			} elseif ( ! rl_limit( 'ip|' . ( $_SERVER['REMOTE_ADDR'] ?? '' ), 10 ) || ! rl_limit( 'em|' . $email, 3 ) ) {
				$r = array( 'rl_err' => 'limit' );
			} elseif ( ! rl_mail( $email, $it ) ) {
				$r = array( 'rl_err' => 'mail' );
			}
			wp_safe_redirect( add_query_arg( $r, $page ) );
			exit;
		}
		if ( isset( $_GET['rl_get'] ) ) {
			if ( rl_known() ) {
				rl_stream( $it );
			}
			wp_safe_redirect( $page );
			exit;
		}
		status_header( 200 );
	} );

	add_filter( 'the_content', function ( $c ) {
		$it = get_query_var( 'rl_item' ) && in_the_loop() && is_main_query() ? rl_find( get_query_var( 'rl_item' ) ) : null;
		return $it ? rl_item_html( $it ) : $c;
	}, 99 );
	add_filter( 'the_title', function ( $t, $id = 0 ) {
		$it = get_query_var( 'rl_item' ) && in_the_loop() && is_main_query() ? rl_find( get_query_var( 'rl_item' ) ) : null;
		return $it ? ( $it['title'] ?? $t ) : $t;
	}, 10, 2 );
	add_filter( 'pre_get_document_title', function ( $t ) {
		$it = get_query_var( 'rl_item' ) ? rl_find( get_query_var( 'rl_item' ) ) : null;
		return $it ? ( $it['title'] ?? '' ) . ' - ' . get_bloginfo( 'name' ) : $t;
	}, 99 );
	add_filter( 'wpseo_metadesc', function ( $d ) {
		$it = get_query_var( 'rl_item' ) ? rl_find( get_query_var( 'rl_item' ) ) : null;
		return $it && ! empty( $it['summary'] ) ? $it['summary'] : $d;
	} );
	add_filter( 'wpseo_canonical', function ( $u ) {
		$it = get_query_var( 'rl_item' ) ? rl_find( get_query_var( 'rl_item' ) ) : null;
		return $it ? rl_page_url( $it ) : $u;
	} );

	/* Smartcode for newsletter links: https://ravikirans.com/revision-library/?rlp={{rl.key}} */
	add_action( 'fluent_crm/after_init', function () {
		FluentCrmApi( 'extender' )->addSmartCode( 'rl', 'Revision Library', array( 'key' => 'Personal link key' ), function ( $code, $valueKey, $defaultValue, $subscriber ) {
			return ( 'key' === $valueKey && $subscriber && $subscriber->id ) ? rl_personal_key( $subscriber->id ) : $defaultValue;
		} );
	} );
}
