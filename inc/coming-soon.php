<?php
/**
 * This file adds a coming soon page for new installs.
 *
 * @package HostGator
 */

/**
 * Display coming soon notice.
 */
function mojo_cs_notice_display() {
	$screen = get_current_screen();
	if ( 'true' === get_option( 'mm_coming_soon', 'false' ) &&
			false === strpos( $screen->id, 'hostgator' ) &&
			current_user_can( 'manage_options' )
		) {
		?>
		<div class='notice notice-warning'>
			<p>
				<?php
				printf(
					/* translators: %1$s is replaced with the opening link tag and %2$s is replaced with the closing link tag. */
					__( 'Your site is currently displaying a "Coming Soon" page. Once you are ready, %1$slaunch your site%2$s.', 'hostgator-wordpress-plugin' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'<a href="' . esc_url( admin_url( 'admin.php?page=hostgator#/home' ) ) . '">',
					'</a>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'mojo_cs_notice_display' );


/**
 * Customize the admin bar.
 *
 * @param WP_Admin_Bar $admin_bar An instance of the WP_Admin_Bar class.
 */
function hostgator_add_tool_bar_items( WP_Admin_Bar $admin_bar ) {
	if ( current_user_can( 'manage_options' ) ) {
		if ( 'true' === get_option( 'mm_coming_soon', 'false' ) ) {
			$cs_args = array(
				'id'    => 'hostgator-coming_soon',
				'href'  => admin_url( 'admin.php?page=hostgator#/home' ),
				'title' => '<div class="hostgator-coming_soon-highlight">' . esc_html__( 'Coming Soon Active', 'hostgator-wordpress-plugin' ) . '</div>',
				'meta'  => array(
					'title' => esc_attr__( 'Launch Your Site', 'hostgator-wordpress-plugin' ),
				),
			);
			$admin_bar->add_menu( $cs_args );
		}
	}
}

add_action( 'admin_bar_menu', 'hostgator_add_tool_bar_items', 100 );

/**
 * Load the coming soon page, if necessary.
 */
function mojo_cs_load() {
	if ( ! is_user_logged_in() ) {
		$coming_soon = get_option( 'mm_coming_soon', 'false' );
		if ( 'true' === $coming_soon ) {
			mojo_cs_content();
			die();
		}
	}
}
add_action( 'template_redirect', 'mojo_cs_load' );

/**
 * Render the coming soon page.
 */
function mojo_cs_content() {
	require HOSTGATOR_PLUGIN_DIR . 'inc/pages/coming-soon.php';
}

/**
 * Handle the AJAX subscribe action.
 */
function mojo_coming_soon_subscribe() {

	$response   = array();
	$a_response = array();
	$email      = sanitize_email( wp_unslash( $_POST['email'] ) );

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'mojo_coming_soon_subscribe_nonce' ) ) {

		$a_response['message'] = __( 'Gotcha!', 'hostgator-wordpress-plugin' );
		$a_response['status']  = 'nonce_failure';

	} else {

		if ( ! is_email( $email ) ) {

			$a_response['message'] = __( 'Please provide a valid email address', 'hostgator-wordpress-plugin' );
			$a_response['status']  = 'invalid_email';

		} else {

			// Initialize JetPack_Subscriptions
			$jetpack = Jetpack_Subscriptions::init();
			// Get JetPack response and subscribe email if response is true
			$response = $jetpack->subscribe(
				$email,
				0,
				false,
				// See Jetpack subscribe `extra_data` attribute
				array(
					'server_data' => jetpack_subscriptions_cherry_pick_server_data(),
				)
			);

			if ( isset( $response[0]->errors ) ) {

				$error_text = array_keys( $response[0]->errors );
				$error_text = $error_text[0];

				$a_response['message'] = __( 'There was an error with the subscription', 'hostgator-wordpress-plugin' );
				$a_response['status']  = $error_text;

			} else {

				$a_response['message'] = __( 'Subscription successful', 'hostgator-wordpress-plugin' );
				$a_response['status']  = 'success';

			}
		}

		wp_send_json( $a_response );

	}

}
add_action( 'wp_ajax_mojo_coming_soon_subscribe', 'mojo_coming_soon_subscribe' );
add_action( 'wp_ajax_nopriv_mojo_coming_soon_subscribe', 'mojo_coming_soon_subscribe' );

/**
 * When the coming soon module is enabled, add a filter to override Jetpack to prevent emails from being sent.
 */
function mojo_coming_soon_prevent_emails() {

	$enabled = get_option( 'mm_coming_soon', 'false' );
	if ( 'true' === $enabled ) {
		add_filter(
			'jetpack_subscriptions_exclude_all_categories_except',
			'mojo_coming_soon_prevent_emails_return_array'
		);
	}

}
add_action( 'plugins_loaded', 'mojo_coming_soon_prevent_emails' );

/**
 * Prevent emails from being sent.
 *
 * @see mojo_coming_soon_prevent_emails
 *
 * @return string[]
 */
function mojo_coming_soon_prevent_emails_return_array() {

	return array(
		'please-for-the-love-of-all-things-do-not-exist',
	);

}
