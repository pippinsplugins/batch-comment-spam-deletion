<?php
/*
Plugin Name: Batch Comment Spam Deletion
Plugin URL: http://pippinsplugins.com/batch-comment-spam-deletion
Description: Modifies the Empty Spam action in WordPress to process the spam deletion in batches, allowing you to delete thousands or even hundreds of thousands of spam comments at once without killing your server.
Version: 1.0.3
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: mordauk, ghost1227
*/

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class PW_BCPD {

	/**
	 * The number of comments to process per batch
	 *
	 * @access  public
	 * @since   1.0
	*/
	public static $per_batch;

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/
	public static function init() {

		if( ! defined( 'PW_BCPD_PER_BATCH' ) ) {
			define( 'PW_BCPD_PER_BATCH', 100 );
		}

		self::$per_batch = apply_filters( 'pw_bcpd_comments_per_batch', PW_BCPD_PER_BATCH );

		add_action( 'admin_init',          array( 'PW_BCPD', 'text_domain'   ) );
		add_action( 'admin_menu',          array( 'PW_BCPD', 'admin_menu'    ) );
		add_action( 'admin_head',          array( 'PW_BCPD', 'admin_head'    ) );
		add_action( 'admin_notices',       array( 'PW_BCPD', 'admin_notices' ) );
		add_action( 'manage_comments_nav', array( 'PW_BCPD', 'comments_nav'  ) );
		add_action( 'admin_init',          array( 'PW_BCPD', 'process_batch' ) );

	}

	/**
	 * Load our plugin's text domain
	 *
	 * @access  public
	 * @since   1.0
	*/
	public static function text_domain() {

		// Set filter for plugin's languages directory
		$lang_dir      = dirname( plugin_basename( __FILE__ ) ) . '/languages/';

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale',  get_locale(), 'pw-bcsd' );
		$mofile        = sprintf( '%1$s-%2$s.mo', 'pw-bcsd', $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/pw-bcsd/' . $mofile;

		if ( file_exists( $mofile_global ) ) {

			// Look in global /wp-content/languages/pw-bcsd folder
			load_textdomain( 'pw-bcsd', $mofile_global );

		} elseif ( file_exists( $mofile_local ) ) {

			// Look in local /wp-content/plugins/bcsp/languages/ folder
			load_textdomain( 'pw-bcsd', $mofile_local );

		} else {

			// Load the default language files
			load_plugin_textdomain( 'pw-bcsd', false, $lang_dir );

		}

	}

	/**
	 * Adds a pseudo submenu item under Comments. This is hidden later
	 *
	 * @access  public
	 * @since   1.0
	*/
	public static function admin_menu() {
		add_submenu_page( 'edit-comments.php', __( 'Empty Spam', 'pw-bcsd' ), __( 'Empty Spam', 'pw-bcsd' ), 'moderate_comments', 'pw-bcpd-process', array( 'PW_BCPD', 'processing_page' ) );
	}

	/**
	 * Admin head actions. Removes the pseudo menu item registered above and outputs some CSS
	 *
	 * @access  public
	 * @since   1.0
	*/
	public static function admin_head() {
		global $pagenow;

		remove_submenu_page( 'edit-comments.php', 'pw-bcpd-process' );

		if( 'edit-comments.php' != $pagenow ) {
			return;
		}

		if( empty( $_GET['comment_status'] ) || 'spam' != $_GET['comment_status'] ) {
			return;
		}

		echo '<style>#delete_all { display:none; }</style>';

	}

	/**
	 * Displays an admin notice when the batch processing is complete
	 *
	 * @access  public
	 * @since   1.0
	*/
	public static function admin_notices() {
		if( ! empty( $_GET['message'] ) && 'batch-complete' == $_GET['message'] ) {
			echo '<div class="updated"><p>' . __( 'All spam comments successfully deleted', 'pw-bcsd' ) . '</p></div>';
		}
	}

	/**
	 * Adds our custom Empty Spam button to the comments list table
	 *
	 * @access  public
	 * @since   1.0
	*/
	public static function comments_nav( $comment_status ) {

		if ( 'spam' == $comment_status ) {

			$delete_all = admin_url( 'edit-comments.php?page=pw-bcpd-process' );
			echo '<a href="' . esc_url( $delete_all ) . '" id="pw_bcpd_empty_spam" class="button apply" style="margin-top:1px;">' . __( 'Empty Spam', 'pw-bcsd' ) . '</a>';
		}
	}

	/**
	 * The batch processing page. Shows the current number of comments that have been processed and how many total to delete
	 *
	 * @access  public
	 * @since   1.0
	*/
	public static function processing_page() {
		$step    = isset( $_GET['step'] )        ? absint( $_GET['step'] )   : 1;
		$total   = isset( $_GET['total'] )       ? absint( $_GET['total'] )  : false;
		$deleted = round( ( $step *  $this->per_batch ), 0 );
		?> 
		<div class="wrap">
			<h2><?php _e( 'Empty Spam', 'pw-bcsd' ); ?></h2>

			<div id="pw-spam-processing">
				<p><?php _e( 'The deletion process has started, please be patient. This could take several minutes. You will be automatically redirected when the process is finished.', 'pw-bcsd' ); ?></p>
				<?php if( ! empty( $total ) ) : ?>
					<p><strong><?php printf( __( '%d spam comments of %d deleted', 'pw-bcsd' ), $deleted, $total ); ?>
				<?php endif; ?>
			</div>
			<script type="text/javascript">
				document.location.href = "edit-comments.php?action=pw_bcsd_process&step=<?php echo $step; ?>&total=<?php echo $total; ?>&_wpnonce=<?php echo wp_create_nonce( 'pw-bscd-nonce' ); ?>";
			</script>
		</div>
		<?php
	}

	/**
	 * Processes a batch of comments
	 *
	 * @access  public
	 * @since   1.0
	*/
	public static function process_batch() {

		if( empty( $_REQUEST['action'] ) || 'pw_bcsd_process' != $_REQUEST['action'] ) {
			return;
		}

		if( ! current_user_can( 'moderate_comments' ) ) {
			return;
		}

		if( ! wp_verify_nonce( $_GET['_wpnonce'], 'pw-bscd-nonce' ) ) {
			return;
		}

		ignore_user_abort( true );

		if (! ini_get( 'safe_mode' ) ) {
			@set_time_limit( 0 );
		}

		$step  = isset( $_GET['step'] )  ? absint( $_GET['step'] )  : 1;
		$total = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;

		if( empty( $total ) || $total <= 1 ) {
			$comments = wp_count_comments();
			$total    = $comments->spam;
		}

		$args = array(
			'number' => self::$per_batch,
			'status' => 'spam',
			'order'  => 'ASC'
		);

		$comments = get_comments( $args );

		if( $comments ) {

			foreach( $comments as $comment ) {
				
				wp_delete_comment( $comment->comment_ID, true );
					
			}

			// comments found so delete them
			$step++;
			$redirect = add_query_arg( array(
				'page'   => 'pw-bcpd-process',
				'step'   => $step,
				'total'  => $total
			), admin_url( 'edit-comments.php' ) );
			wp_redirect( $redirect ); exit;

		} else {

			// No more comments found, finish up
			wp_redirect( admin_url( 'edit-comments.php?message=batch-complete' ) ); exit;
		}
	}

}
add_action( 'plugins_loaded', array( 'PW_BCPD', 'init' ) );