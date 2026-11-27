/* Revision Library: shortcode [revision_library] + "Quick Revision" box on study guides.
   Data: library.json in the public repo (the nightly job appends to it). */
if ( ! function_exists( 'rl_items' ) ) {
	function rl_items() {
		static $memo = null; // admins always see the live file; everyone else gets the 1-hour cache
		$items = null !== $memo ? $memo : ( current_user_can( 'manage_options' ) ? false : get_transient( 'rl_library_items' ) );
		if ( false === $items ) {
			$items = array();
			$res   = wp_remote_get( 'https://raw.githubusercontent.com/ravikiran-srini/ravikirans-marketing/main/library.json', array( 'timeout' => 8 ) );
			if ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) ) {
				$data = json_decode( wp_remote_retrieve_body( $res ), true );
				if ( is_array( $data ) && isset( $data['items'] ) && is_array( $data['items'] ) ) {
					$items = $data['items'];
				}
			}
			set_transient( 'rl_library_items', $items, HOUR_IN_SECONDS );
		}
		usort( $items, function ( $a, $b ) { return strcmp( $b['date'] ?? '', $a['date'] ?? '' ); } );
		$memo = $items;
		return $items;
	}
}

add_shortcode( 'revision_library', function () {
	$items = rl_items();
	$css = '<style>.rl-cat{margin:2.2em 0 1em;padding-top:.4em;border-top:2px solid #e5e7eb;scroll-margin-top:90px}.rl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:20px}.rl-card{border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#fff;display:flex;flex-direction:column}.rl-card img{width:100%;height:auto;display:block;aspect-ratio:3/4;object-fit:cover;background:#f3f4f6}.rl-body{padding:12px;display:flex;flex-direction:column;gap:6px;flex:1}.rl-body a.rl-t{font-weight:600;line-height:1.3;text-decoration:none}.rl-d{font-size:.82em;color:#6b7280}.rl-btn{margin-top:auto;display:inline-block;text-align:center;background:#c8102e;color:#fff!important;border-radius:6px;padding:8px 10px;text-decoration:none;font-weight:600}.rl-empty{padding:28px;border:1px dashed #d1d5db;border-radius:10px;text-align:center;color:#6b7280}</style>';
	if ( ! $items ) {
		return $css . '<div class="rl-empty"><strong>Quick revision PDFs are on the way.</strong><br>A new one-page revision PDF is added regularly. Check back soon.</div>';
	}
	$groups = array();
	foreach ( $items as $it ) {
		$groups[ $it['category_slug'] ?? 'other' ]['name']    = $it['category'] ?? 'Other';
		$groups[ $it['category_slug'] ?? 'other' ]['items'][] = $it;
	}
	uasort( $groups, function ( $a, $b ) { return strcasecmp( $a['name'], $b['name'] ); } );
	$out = $css;
	foreach ( $groups as $slug => $g ) {
		$out .= '<h2 class="rl-cat" id="' . esc_attr( $slug ) . '">' . esc_html( $g['name'] ) . '</h2><div class="rl-grid">';
		foreach ( $g['items'] as $it ) {
			$page = home_url( '/revision-library/' . rawurlencode( $it['slug'] ?? '' ) . '/' );
			$out .= '<div class="rl-card">' . ( ! empty( $it['cover'] ) ? '<a href="' . esc_url( $page ) . '"><img loading="lazy" src="' . esc_url( $it['cover'] ) . '" alt="' . esc_attr( $it['title'] ?? '' ) . '"></a>' : '' )
				. '<div class="rl-body"><a class="rl-t" href="' . esc_url( $page ) . '">' . esc_html( $it['title'] ?? '' ) . '</a><span class="rl-d">' . esc_html( ! empty( $it['date'] ) ? date_i18n( 'j M Y', strtotime( $it['date'] ) ) : '' ) . '</span>'
				. '<a class="rl-btn" href="' . esc_url( $page ) . '">Download PDF</a></div></div>';
		}
		$out .= '</div>';
	}
	return $out;
} );

add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$have = array();
	foreach ( rl_items() as $it ) {
		if ( ! empty( $it['category_slug'] ) ) { $have[ $it['category_slug'] ] = $it['category'] ?? ''; }
	}
	foreach ( (array) get_the_category() as $cat ) {
		if ( isset( $have[ $cat->slug ] ) ) {
			$url = home_url( '/revision-library/#' . $cat->slug );
			$box = '<div style="margin:2em 0;padding:18px 20px;border:1px solid #e5e7eb;border-left:4px solid #c8102e;border-radius:8px;background:#fafafa"><strong>Quick Revision</strong><br>Short on time? Get one-page revision PDFs for <strong>' . esc_html( $cat->name ) . '</strong>. <a href="' . esc_url( $url ) . '">Open the Revision Library &rarr;</a></div>';
			return $content . $box;
		}
	}
	return $content;
}, 20 );
