<?php
/**
 * Extendify_Partner class
 *
 * @package     Extendify
 * @since       1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Extendify_Partner', false ) ) {

	/**
	 * Extendify_Partner class.
	 */
	class Extendify_Partner {

		/**
		 * Query Themes or Plugins.
		 *
		 * @var array
		 */
		private $pages = array();

		/**
		 * Display label array.
		 *
		 * @var array
		 */
		private $labels = array();

		/**
		 * Banner image.
		 *
		 * @var string
		 */
		private $image = '';

		/**
		 * Notice key.
		 *
		 * @var string
		 */
		private $key = '';

		/**
		 * Extendify_Partner constructor.
		 *
		 * @param string       $project Project name.
		 * @param array|string $image   Array of image data or SVG string.
		 * @param array        $pages   Page name or array of pages.
		 * @param array|string $labels  Label array.
		 */
		public function __construct( string $project = '', $image = array(), $pages = array('themes'), array $labels = array() ) {

            // Checking for active banner.
			if ( isset( $GLOBALS['extendify_d_notice_showing'] ) && true === $GLOBALS['extendify_d_notice_showing'] ) {
                return;
			}

			if ( '' === $project ) {
                echo 'Extendify Partner: No project name given.  Please specify the name of your theme or plugin.';
				return;
			}

            $GLOBALS['extendify_partner_name'] = $project;

			// Labels.
			$default_labels = array(
				// translators: %1$s = theme name, %2$s = Extendify brand.
				'header'        => wp_sprintf( esc_html__( '%1$s + %2$s = Awesomeness' ), $project ),
				// translators: %1$s = theme name.
				'main_content'  => wp_sprintf( esc_html__( 'We\'re excited to announce that %1$s is partnering with the %2$s library of Gutenberg patterns and templates to bring %1$s users even more beautiful block patterns and templates! Install and activate the %2$s plugin to receive access to the library. Note: this is an optional step and %1$s will continue to work without %2$s.' ), $project ),
				// translators: %1$s = Extendify Brand.
				'install'       => wp_sprintf( esc_html__( 'Install & Activate %1$s' ) ),
				'installing'    => esc_html__( 'Installing...' ),
				'reloading'     => esc_html__( 'Finished. Reloading...' ),
				// translators: %1$s = Extendify Brand.
				'dismiss_label' => wp_sprintf( esc_attr__( 'Dismiss %1$s notice' ) ),
			);

			$this->labels = wp_parse_args( $labels, $default_labels );

			// Detect SVG or custom HTML.
			if ( ! is_array( $image ) ) {
				$this->image = $image;
			} else {
				$default_image = array(
					'img'    => '', // https://ps.w.org/extendify/assets/icon-128x128.png.
					'width'  => 96,
					'height' => 96,
				);

				$image = wp_parse_args( $image, $default_image );

				$this->image = '<img style="width:' . esc_attr( $image['width'] ) . 'px;height:' . esc_attr( $image['height'] ) . 'px" width=' . esc_attr( $image['width'] ) . ' height=' . esc_attr( $image['height'] ) . ' src="' . esc_url( $image['img'] ) . '" alt="" />';
			}

			// Pages.
			if ( ! is_array( $pages ) ) {
				$pages = array( $pages );
			}

			$this->pages = $pages;

			$this->key = 'extendify_complete_install_' . mb_strtolower( str_replace( ' ', '_', $project ) );

			if ( $this->check_standalone_active() ) {
				return;
			}

			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';

			add_action( 'admin_notices', array( $this, 'display_banner' ) );
			add_action( 'wp_ajax_handle_extendify_install_' . $this->key, array( $this, 'dismiss' ) );
		}

		/**
		 * Will be true if the plugin is installed.
		 *
		 * @return bool
		 */
		private function check_standalone_active(): bool {
			if ( ! function_exists( 'get_plugins' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			foreach ( get_plugins() as $plugin => $data ) {
				if ( 'extendify' === $data['TextDomain'] ) {
					// Side effect - just set this global to extendify in case it's not set.
					$GLOBALS['extendify_sdk_partner'] = 'standalone';

					return is_plugin_active( $plugin );
				}
			}

			return isset( $GLOBALS['extendify_sdk_partner'] ) && 'standalone' === $GLOBALS['extendify_sdk_partner'];
		}

		/**
		 * Display Extendify partner banner.
		 *
		 * @return void
		 */
		public function display_banner() {
			$current_page = get_current_screen();
			if (
				! $current_page ||
				! in_array( $current_page->base, $this->pages, true ) ||
				isset( $GLOBALS['extendify_d_notice_showing'] ) ||
				get_user_option( $this->key )
			) {
				return;
			}

			$GLOBALS['extendify_d_notice_showing'] = true;

			$nonce = wp_create_nonce( $this->key );

			ob_start();

			?>
			<p>
				<?php echo $this->labels['main_content']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</p>

			<button id="extendify-install-button" type="button" class="button-primary"
				style="margin-bottom: 1rem;margin-top: 0.5rem;"><?php echo $this->labels['install']; // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<script>
				jQuery(
					function( $ ) {
						$( '#extendify-install-button' ).on(
							'click',
							function() {
								var _this = $( this );
								var data = {
									action: 'handle_extendify_install_<?php echo $this->key; // phpcs:ignore WordPress.Security.EscapeOutput ?>',
									_wpnonce: '<?php echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput ?>'
								};
								_this.attr( 'disabled', true ).text( "<?php echo $this->labels['installing']; // phpcs:ignore WordPress.Security.EscapeOutput ?>" );
								$.post(
									ajaxurl,
									data,
									function() {
										_this.text( "<?php echo $this->labels['reloading']; // phpcs:ignore WordPress.Security.EscapeOutput ?>" );
										setTimeout(
											function() {
												// Regardless of pass/fail, refresh to hide the notice.
												window.location.reload();
											},
											1500
										);
									}
								);
							}
						);
					}
				);
			</script>
			<?php $extendify_d_notices_content = ob_get_clean(); ?>
			<div id="<?php echo $this->key; // phpcs:ignore WordPress.Security.EscapeOutput ?>" class="notice notice-info"
				style="display:flex;align-items:stretch;justify-content:space-between;position:relative;border-left-color:#29375B">
				<div style="display:flex;align-items:flex-start;position:relative">
					<div style="margin-right:1.25rem;margin-left:0.5rem; margin-top:1.25rem">
						<?php echo $this->image; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
					<div>
						<h3 style="margin-bottom:0.25rem;">
							<?php echo $this->labels['header']; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
						<div>
							<?php echo $extendify_d_notices_content; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					</div>
				</div>
				<div style="margin:5px -5px 0 0;">
					<button
						style="max-width:15px;border:0;background:0;color: #7b7b7b;white-space:nowrap;cursor: pointer;padding: 0"
						title="<?php echo $this->labels['dismiss_label'];// phpcs:ignore WordPress.Security.EscapeOutput ?>"
						aria-label="<?php echo $this->labels['dismiss_label']; // phpcs:ignore WordPress.Security.EscapeOutput ?>"
						onclick="jQuery('#<?php echo $this->key; // phpcs:ignore WordPress.Security.EscapeOutput ?>').remove();
							jQuery.post(
							window.ajaxurl,
							{
							action: 'handle_<?php echo $this->key; // phpcs:ignore WordPress.Security.EscapeOutput ?>',
							_wpnonce: '<?php echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput ?>'
							}
							);">
						<svg width="15" height="15" style="width:100%" xmlns="http://www.w3.org/2000/svg" fill="none"
							viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
						</svg>
					</button>
				</div>
			</div>
			<?php

			ob_end_flush();
		}

		/**
		 * Dismiss button click.
		 *
		 * @return void
		 */
		public function dismiss() {
			if ( isset( $_REQUEST['_wpnonce'] ) && ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), $this->key ) ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'The security check failed. Please refresh the page and try again.' ),
					),
					401
				);
			}

			update_user_option( get_current_user_id(), $this->key, time() );

			try {
				$this->install_and_activate_plugin( );
			} catch ( Exception $e ) {
				wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
			}

			wp_send_json_success();
		}

		/**
		 * Install and activate a plugin.
		 *
		 * @param string $slug Plugin slug.
		 *
		 * @return bool|WP_Error True if installation succeeded, error object otherwise.
		 * @since 5.8.0
		 */
		private function install_and_activate_plugin( $slug ) {
			$plugin_id = self::get_plugin_id_by_slug( $slug );

			if ( ! $plugin_id ) {
				$installed = $this->install_plugin( $slug );

				if ( is_wp_error( $installed ) ) {
					return $installed;
				}

				$plugin_id = $this->get_plugin_id_by_slug( $slug );
			} elseif ( is_plugin_active( $plugin_id ) ) {
				return true; // Already installed and active.
			}

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return new WP_Error( 'not_allowed', esc_html__( 'You are not allowed to activate plugins on this site.' ) );
			}

			$activated = activate_plugin( $plugin_id );

			if ( is_wp_error( $activated ) ) {
				return $activated;
			}

			return true;
		}

		/**
		 * Install a plugin.
		 *
		 * @param string $slug Plugin slug.
		 *
		 * @return WP_Error|array True if installation succeeded, error object otherwise.
		 * @since 5.8.0
		 */
		private function install_plugin( string $slug ) {
			if ( is_multisite() && ! current_user_can( 'manage_network' ) ) {
				return new WP_Error( 'not_allowed', __( 'You are not allowed to install plugins on this site.' ) );
			}

			$skin     = new Extendify_D_PluginUpgraderSkin();
			$upgrader = new Plugin_Upgrader( $skin );
			$zip_url  = $this->generate_wordpress_org_plugin_download_link( $slug );

			$result = $upgrader->install( $zip_url );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$plugin     = $this->get_plugin_id_by_slug( $slug );
			$error_code = 'install_error';
			if ( ! $plugin ) {
				$error = __( 'There was an error installing your plugin' );
			}

			if ( ! $result ) {
				$error_code = $upgrader->skin->get_main_error_code();
				$message    = $upgrader->skin->get_main_error_message();
				$error      = $message ? $message : __( 'An unknown error occurred during installation' );
			}

			if ( ! empty( $error ) ) {
				if ( 'download_failed' === $error_code ) {
					// For backwards compatibility: versions prior to 3.9 would return no_package instead of download_failed.
					$error_code = 'no_package';
				}

				return new WP_Error( $error_code, $error, 400 );
			}

			return $upgrader->skin->get_upgrade_messages();
		}

		/**
		 * Get WordPress.org zip download link from a plugin slug
		 *
		 * @param string $plugin_slug Plugin slug.
		 */
		protected function generate_wordpress_org_plugin_download_link( string $plugin_slug ): string {
			return "https://downloads.wordpress.org/plugin/$plugin_slug.latest-stable.zip";
		}

		/**
		 * Get the plugin ID (composed of the plugin slug and the name of the main plugin file) from a plugin slug.
		 *
		 * @param string $slug Plugin slug.
		 */
		private function get_plugin_id_by_slug( string $slug ) {
			// Check if get_plugins() function exists. This is required on the front end of the
			// site, since it is in a file that is normally only loaded in the admin.
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			/** This filter is documented in wp-admin/includes/class-wp-plugins-list-table.php */
			$plugins = apply_filters( 'all_plugins', array( $this, 'get_plugins' ) );
			if ( ! is_array( $plugins ) ) {
				return false;
			}

			foreach ( $plugins as $plugin_file => $plugin_data ) {
				if ( $this->get_slug_from_file_path( $plugin_file ) === $slug ) {
					return $plugin_file;
				}
			}

			return false;
		}

		/**
		 * Get the plugin slug from the plugin ID (composed of the plugin slug and the name of the main plugin file)
		 *
		 * @param string $plugin_file Plugin file (ID -- e.g. hello-dolly/hello.php).
		 */
		protected function get_slug_from_file_path( string $plugin_file ) {
			// Similar to get_plugin_slug() method.
			$slug = dirname( $plugin_file );
			if ( '.' === $slug ) {
				$slug = preg_replace( '/(.+)\.php$/', '$1', $plugin_file );
			}

			return $slug;
		}

		/**
		 * Get the activation status for a plugin.
		 *
		 * @param string $plugin_file The plugin file to check.
		 *
		 * @return string Either 'network-active', 'active' or 'inactive'.
		 * @since 8.9.0
		 */
		private function get_plugin_status( string $plugin_file ): string {
			if ( is_plugin_active_for_network( $plugin_file ) ) {
				return 'network-active';
			}

			if ( is_plugin_active( $plugin_file ) ) {
				return 'active';
			}

			return 'inactive';
		}

		/**
		 * Returns a list of all plugins in the site.
		 *
		 * @return array
		 * @uses  get_plugins()
		 * @since 8.9.0
		 */
		public function get_plugins(): array {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			/** This filter is documented in wp-admin/includes/class-wp-plugins-list-table.php */
			$plugins = apply_filters( 'all_plugins', get_plugins() );

			if ( is_array( $plugins ) && ! empty( $plugins ) ) {
				foreach ( $plugins as $plugin_slug => $plugin_data ) {
					$plugins[ $plugin_slug ]['active'] = in_array(
						$this->get_plugin_status( $plugin_slug ),
						array( 'active', 'network-active' ),
						true
					);
				}

				return $plugins;
			}

			return array();
		}
	}
}

/**
 * Allows us to capture that the site doesn't have proper file system access.
 * In order to update the plugin.
 */
if ( ! class_exists( 'Extendify_D_PluginUpgraderSkin' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';

	class Extendify_D_PluginUpgraderSkin extends WP_Upgrader_Skin {
		/**
		 * Stores the last error key;
		 *
		 * @var string
		 **/
		protected $main_error_code = 'install_error';

		/**
		 * Stores the last error message.
		 *
		 * @var string
		 **/
		protected $main_error_message = 'An unknown error occurred during installation';

		/**
		 * Overwrites the set_upgrader to be able to tell if we e ven have the ability to write to the files.
		 *
		 * @param WP_Upgrader $upgrader WP Upgrader class.
		 */
		public function set_upgrader( &$upgrader ) {
			parent::set_upgrader( $upgrader );

			// Check if we even have permission to.
			$result = $upgrader->fs_connect( array( WP_CONTENT_DIR, WP_PLUGIN_DIR ) );
			if ( ! $result ) {
				// set the string here since they are not available just yet.
				$upgrader->generic_strings();
				$this->feedback( 'fs_unavailable' );
			}
		}

		/**
		 * Overwrites the error function.
		 *
		 * @param WP_Error $errors WP error.
		 */
		public function error( $errors ) {
			if ( is_wp_error( $errors ) ) {
				$this->feedback( $errors );
			}
		}

		/**
		 * Sets the main error code.
		 *
		 * @param string|null $code Error code.
		 *
		 * @return void
		 */
		private function set_main_error_code( $code ) {
			// Don't set the process_failed as code since it is not that helpful unless we don't have one already set.
			$this->main_error_code = ( 'process_failed' === $code && $this->main_error_code ? $this->main_error_code : $code );
		}

		/**
		 * Sets the main error message.
		 *
		 * @param string      $message Error message.
		 * @param string|null $code    Error code.
		 *
		 * @return void
		 */
		private function set_main_error_message( string $message, $code ) {
			// Don't set the process_failed as message since it is not that helpful unless we don't have one already set.
			$this->main_error_message = ( 'process_failed' === $code && $this->main_error_code ? $this->main_error_code : $message );
		}

		/**
		 * Gets the main error code.
		 *
		 * @return string
		 */
		public function get_main_error_code(): string {
			return $this->main_error_code;
		}

		/**
		 * Gets the main error message.
		 *
		 * @return string
		 */
		public function get_main_error_message(): string {
			return $this->main_error_message;
		}

		/**
		 * Overwrites the feedback function
		 *
		 * @param string|array|WP_Error $feedback    Data.
		 * @param mixed                 ...$args Optional text replacements.
		 */
		public function feedback( $feedback, ...$args ) {
			$current_error = null;
			if ( is_wp_error( $feedback ) ) {
				$this->set_main_error_code( $feedback->get_error_code() );
				$string = $feedback->get_error_message();
			} elseif ( is_array( $feedback ) ) {
				return;
			} else {
				$string = $feedback;
			}

			if ( ! empty( $this->upgrader->strings[ $string ] ) ) {
				$this->set_main_error_code( $string );

				$current_error = $string;
				$string        = $this->upgrader->strings[ $string ];
			}

			if ( strpos( $string, '%' ) !== false ) {
				if ( ! empty( $args ) ) {
					$string = vsprintf( $string, $args );
				}
			}

			$string = trim( $string );
			$string = wp_kses(
				$string,
				array(
					'a'      => array(
						'href' => true,
					),
					'br'     => true,
					'em'     => true,
					'strong' => true,
				)
			);

			$this->set_main_error_message( $string, $current_error );
			$this->messages[] = $string;
		}
	}
}
