<?php
/*
Plugin Name: BG Web Dynamics Tag Manager
Plugin URI: https://bgwebdynamics.com
Description: Install Google Tag Manager into &lt;head&gt; and &lt;body&gt;. Works with Avada, Salient, and standard WordPress themes.
Version: 2.1.8
Author: BG Web Dynamics
Author URI: https://bgwebdynamics.com
Text Domain: bgwd-tag-manager
Domain Path: /languages
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version constant
define( 'BGWD_GTM_VERSION', '2.1.8' );
define( 'BGWD_GTM_OPTION_NAME', 'bgwd_gtm_id' );

/**
 * Initialize the plugin update checker
 */
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/bgwebdynamics/bgwd-tag-manager', // Your GitHub repo URL
	__FILE__,
	'bgwd-tag-manager'
);

// Optional: Set the branch that contains the stable release (default is 'master')
$myUpdateChecker->setBranch('main');

// Optional: If your repository is private, specify the access token
// $myUpdateChecker->setAuthentication('private-access-token');

/**
 * Activation hook
 */
function bgwd_gtm_activate() {
	// Nothing needed on activation for now
}
register_activation_hook( __FILE__, 'bgwd_gtm_activate' );

/**
 * Deactivation hook
 */
function bgwd_gtm_deactivate() {
	// Clean up transients or temporary data
	delete_transient( 'bgwd_gtm_admin_notice' );
}
register_deactivation_hook( __FILE__, 'bgwd_gtm_deactivate' );

/**
 * Uninstall hook - clean up all plugin data
 */
function bgwd_gtm_uninstall() {
	delete_option( BGWD_GTM_OPTION_NAME );
	delete_option( 'bgwd_gtm_dismiss_notice' );
}
register_uninstall_hook( __FILE__, 'bgwd_gtm_uninstall' );

/**
 * Display admin notice if GTM ID is not configured
 */
function bgwd_gtm_admin_notice() {
	$gtm_id = get_option( BGWD_GTM_OPTION_NAME );
	$dismissed = get_option( 'bgwd_gtm_dismiss_notice' );
	
	// Only show on admin pages, if not configured, and not dismissed
	if ( empty( $gtm_id ) && ! $dismissed && current_user_can( 'manage_options' ) ) {
		?>
		<div class="notice notice-warning is-dismissible" data-notice="bgwd-gtm-config">
			<p>
				<strong>BG Web Dynamics Tag Manager:</strong> 
				Please <a href="<?php echo admin_url( 'options-general.php?page=bgwd-tag-manager' ); ?>">configure your Google Tag Manager ID</a> to start tracking.
			</p>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$(document).on('click', '[data-notice="bgwd-gtm-config"] .notice-dismiss', function() {
				$.post(ajaxurl, {
					action: 'bgwd_gtm_dismiss_notice',
					nonce: '<?php echo wp_create_nonce( 'bgwd_gtm_dismiss' ); ?>'
				});
			});
		});
		</script>
		<?php
	}
}
add_action( 'admin_notices', 'bgwd_gtm_admin_notice' );

/**
 * AJAX handler to dismiss admin notice
 */
function bgwd_gtm_dismiss_notice_handler() {
	check_ajax_referer( 'bgwd_gtm_dismiss', 'nonce' );
	
	if ( current_user_can( 'manage_options' ) ) {
		update_option( 'bgwd_gtm_dismiss_notice', true );
	}
	
	wp_die();
}
add_action( 'wp_ajax_bgwd_gtm_dismiss_notice', 'bgwd_gtm_dismiss_notice_handler' );

/**
 * Add settings page to WordPress admin menu
 */
function bgwd_gtm_add_admin_menu() {
	add_options_page(
		'BG Web Dynamics Tag Manager Settings',
		'BG Web Dynamics TM',
		'manage_options',
		'bgwd-tag-manager',
		'bgwd_gtm_settings_page'
	);
}
add_action( 'admin_menu', 'bgwd_gtm_add_admin_menu' );

/**
 * Register plugin settings
 */
function bgwd_gtm_settings_init() {
	register_setting( 'bgwd_gtm_settings_group', BGWD_GTM_OPTION_NAME, array(
		'sanitize_callback' => 'bgwd_gtm_sanitize_id'
	) );

	add_settings_section(
		'bgwd_gtm_settings_section',
		'Google Tag Manager Configuration',
		'bgwd_gtm_settings_section_callback',
		'bgwd_gtm_settings_group'
	);

	add_settings_field(
		'bgwd_gtm_id',
		'GTM Container ID',
		'bgwd_gtm_id_render',
		'bgwd_gtm_settings_group',
		'bgwd_gtm_settings_section'
	);
}
add_action( 'admin_init', 'bgwd_gtm_settings_init' );

/**
 * Sanitize GTM ID input
 */
function bgwd_gtm_sanitize_id( $input ) {
	$input = sanitize_text_field( $input );
	
	// Validate GTM ID format
	if ( ! empty( $input ) && ! preg_match( '/^GTM-[A-Z0-9]+$/', $input ) ) {
		add_settings_error(
			BGWD_GTM_OPTION_NAME,
			'invalid_gtm_id',
			'Please enter a valid GTM Container ID (format: GTM-XXXXXXX)',
			'error'
		);
		return get_option( BGWD_GTM_OPTION_NAME );
	}
	
	// Clear the dismiss notice flag when ID is saved
	if ( ! empty( $input ) ) {
		delete_option( 'bgwd_gtm_dismiss_notice' );
	}
	
	return $input;
}

/**
 * Render GTM ID input field
 */
function bgwd_gtm_id_render() {
	$gtm_id = get_option( BGWD_GTM_OPTION_NAME );
	?>
	<input type="text" name="<?php echo BGWD_GTM_OPTION_NAME; ?>" value="<?php echo esc_attr( $gtm_id ); ?>" placeholder="GTM-XXXXXXX" class="regular-text">
	<p class="description">Enter your Google Tag Manager Container ID (e.g., GTM-XXXXXXX)</p>
	<?php
}

/**
 * Settings section description
 */
function bgwd_gtm_settings_section_callback() {
	echo '<p>Configure your Google Tag Manager integration. The GTM code will be automatically inserted into your site.</p>';
}

/**
 * Render settings page
 */
function bgwd_gtm_settings_page() {
	?>
	<div class="wrap">
		<h1>BG Web Dynamics Tag Manager Settings</h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'bgwd_gtm_settings_group' );
			do_settings_sections( 'bgwd_gtm_settings_group' );
			submit_button();
			?>
		</form>
		
		<div style="margin-top: 30px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1;">
			<h3>Theme Compatibility</h3>
			<p><strong>Detected Theme:</strong> <?php echo wp_get_theme()->get('Name'); ?></p>
			<p>This plugin automatically works with:</p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li>Avada Theme (uses <code>avada_before_body_content</code> hook)</li>
				<li>Salient Theme (uses <code>nectar_hook_after_body_open</code> hook)</li>
				<li>All other themes (uses standard <code>wp_body_open</code> hook)</li>
			</ul>
		</div>
		
		<div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-left: 4px solid #2196F3;">
			<h3>Plugin Information</h3>
			<p><strong>Version:</strong> <?php echo BGWD_GTM_VERSION; ?></p>
			<p><strong>Support:</strong> <a href="https://bgwebdynamics.com" target="_blank">BG Web Dynamics</a></p>
		</div>
	</div>
	<?php
}

/**
 * Add Google Tag Manager javascript code to <head>
 */
function bgwd_gtm_add_head() {
	$gtm_id = get_option( BGWD_GTM_OPTION_NAME );
	
	// Don't output if GTM ID is not set
	if ( empty( $gtm_id ) ) {
		return;
	}
	
	// Optional: Don't load for logged-in admins (uncomment if desired)
	// if ( current_user_can( 'manage_options' ) ) {
	// 	return;
	// }
	?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( $gtm_id ); ?>');</script>
<!-- End Google Tag Manager -->
	<?php
}
add_action( 'wp_head', 'bgwd_gtm_add_head', 0 );

/**
 * Add Google Tag Manager noscript code after opening <body> tag
 */
function bgwd_gtm_add_body() {
	$gtm_id = get_option( BGWD_GTM_OPTION_NAME );
	
	// Don't output if GTM ID is not set
	if ( empty( $gtm_id ) ) {
		return;
	}
	
	// Optional: Don't load for logged-in admins (uncomment if desired)
	// if ( current_user_can( 'manage_options' ) ) {
	// 	return;
	// }
	?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $gtm_id ); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
	<?php
}

/**
 * Hook into the appropriate body tag location based on active theme
 */
function bgwd_gtm_register_body_hook() {
	// Check for Avada theme hook
	if ( has_action( 'avada_before_body_content' ) ) {
		add_action( 'avada_before_body_content', 'bgwd_gtm_add_body' );
	}
	// Check for Salient theme hook
	elseif ( has_action( 'nectar_hook_after_body_open' ) ) {
		add_action( 'nectar_hook_after_body_open', 'bgwd_gtm_add_body' );
	}
	// Fall back to standard WordPress hook (WordPress 5.2+)
	else {
		add_action( 'wp_body_open', 'bgwd_gtm_add_body' );
	}
}
add_action( 'after_setup_theme', 'bgwd_gtm_register_body_hook' );