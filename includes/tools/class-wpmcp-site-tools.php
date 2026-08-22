<?php
/**
 * WP-MCP Site Configuration & Customizer Tools
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and implements tools for site options, Custom CSS, and navigation menus.
 */
class WPMCP_Site_Tools {

	/**
	 * Register all site tools with registry.
	 *
	 * @param WPMCP_Tool_Registry $registry Registry instance.
	 */
	public static function register( WPMCP_Tool_Registry $registry ): void {
		$registry->register_tool( new WPMCP_Tool_Get_Site_Info() );
		$registry->register_tool( new WPMCP_Tool_Update_Site_Option() );
		$registry->register_tool( new WPMCP_Tool_Get_Custom_CSS() );
		$registry->register_tool( new WPMCP_Tool_Update_Custom_CSS() );
		$registry->register_tool( new WPMCP_Tool_Get_Nav_Menus() );
		$registry->register_tool( new WPMCP_Tool_Manage_Nav_Menu() );
	}
}

/**
 * Tool: Get comprehensive site information.
 */
class WPMCP_Tool_Get_Site_Info extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_get_site_info';
	}

	public function get_description(): string {
		return 'Retrieve WordPress site configuration details including site title, tagline, URL, admin email, active theme, PHP/WP versions, and permalinks.';
	}

	public function get_required_capability(): string {
		return 'read';
	}

	public function get_risk_level(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => (object) array(),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$theme = wp_get_theme();

		$info = array(
			'site_title'          => get_bloginfo( 'name' ),
			'tagline'             => get_bloginfo( 'description' ),
			'site_url'            => site_url(),
			'home_url'            => home_url(),
			'admin_email'         => get_bloginfo( 'admin_email' ),
			'language'            => get_bloginfo( 'language' ),
			'wordpress_version'   => get_bloginfo( 'version' ),
			'php_version'         => phpversion(),
			'active_theme'        => array(
				'name'       => $theme->get( 'Name' ),
				'version'    => $theme->get( 'Version' ),
				'is_block'   => function_exists( 'wp_is_block_theme' ) && wp_is_block_theme(),
				'stylesheet' => get_stylesheet(),
				'template'   => get_template(),
			),
			'permalink_structure' => get_option( 'permalink_structure' ),
			'timezone'            => wp_timezone_string(),
			'date_format'         => get_option( 'date_format' ),
			'time_format'         => get_option( 'time_format' ),
			'posts_per_page'      => (int) get_option( 'posts_per_page' ),
			'users_can_register'  => (bool) get_option( 'users_can_register' ),
			'is_multisite'        => is_multisite(),
		);

		return $this->success( $info, __( 'Site information retrieved.', 'wpmcp' ) );
	}
}

/**
 * Tool: Update specific whitelisted site options.
 */
class WPMCP_Tool_Update_Site_Option extends WPMCP_Base_Tool {

	/**
	 * Whitelist of options that are safe to update via AI prompt.
	 */
	public const ALLOWED_OPTIONS = array(
		'blogname'            => 'sanitize_text_field',
		'blogdescription'     => 'sanitize_text_field',
		'posts_per_page'      => 'intval',
		'timezone_string'     => 'sanitize_text_field',
		'date_format'         => 'sanitize_text_field',
		'time_format'         => 'sanitize_text_field',
		'start_of_week'       => 'intval',
		'default_category'    => 'intval',
		'default_comment_status' => 'sanitize_text_field',
	);

	public function get_name(): string {
		return 'wpmcp_update_site_option';
	}

	public function get_description(): string {
		return 'Update safe WordPress site settings (site title "blogname", tagline "blogdescription", posts_per_page, timezone_string, date_format, time_format).';
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function get_risk_level(): string {
		return 'write';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'option_name', 'option_value' ),
			'properties' => array(
				'option_name'  => array(
					'type'        => 'string',
					'enum'        => array_keys( self::ALLOWED_OPTIONS ),
					'description' => 'Name of the WordPress option to update.',
				),
				'option_value' => array(
					'type'        => 'string',
					'description' => 'New value for the option.',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$name = sanitize_key( $params['option_name'] ?? '' );

		if ( ! isset( self::ALLOWED_OPTIONS[ $name ] ) ) {
			return $this->error( sprintf( __( 'Option "%s" is not permitted to be updated via prompt for security reasons.', 'wpmcp' ), $name ) );
		}

		$sanitizer = self::ALLOWED_OPTIONS[ $name ];
		$value     = call_user_func( $sanitizer, $params['option_value'] ?? '' );

		$prev_value = get_option( $name );
		$updated    = update_option( $name, $value );

		return $this->success(
			array(
				'option_name' => $name,
				'old_value'   => $prev_value,
				'new_value'   => $value,
			),
			sprintf( __( 'Option "%s" updated successfully from "%s" to "%s".', 'wpmcp' ), $name, (string) $prev_value, (string) $value )
		);
	}
}

/**
 * Tool: Retrieve Custom CSS.
 */
class WPMCP_Tool_Get_Custom_CSS extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_get_custom_css';
	}

	public function get_description(): string {
		return 'Retrieve the existing Additional / Custom CSS code configured for the active WordPress theme.';
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function get_risk_level(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => (object) array(),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$css = function_exists( 'wp_get_custom_css' ) ? wp_get_custom_css() : '';
		return $this->success(
			array(
				'custom_css' => $css,
				'length'     => strlen( $css ),
			),
			__( 'Custom CSS retrieved.', 'wpmcp' )
		);
	}
}

/**
 * Tool: Update Custom CSS.
 */
class WPMCP_Tool_Update_Custom_CSS extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_update_custom_css';
	}

	public function get_description(): string {
		return 'Update or append Custom CSS styles to the WordPress theme.';
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function get_risk_level(): string {
		return 'write';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'css' ),
			'properties' => array(
				'css'  => array(
					'type'        => 'string',
					'description' => 'The CSS code to apply.',
				),
				'mode' => array(
					'type'        => 'string',
					'enum'        => array( 'replace', 'append' ),
					'default'     => 'append',
					'description' => '"append" to add to existing CSS, or "replace" to overwrite all custom CSS.',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		if ( ! function_exists( 'wp_update_custom_css_post' ) ) {
			return $this->error( __( 'wp_update_custom_css_post function is not available.', 'wpmcp' ) );
		}

		$mode        = sanitize_key( $params['mode'] ?? 'append' );
		$new_css_raw = (string) ( $params['css'] ?? '' );
		$current_css = wp_get_custom_css();

		$final_css = ( 'append' === $mode && ! empty( $current_css ) )
			? rtrim( $current_css ) . "\n\n/* Added via WP-MCP */\n" . trim( $new_css_raw )
			: $new_css_raw;

		$res = wp_update_custom_css_post( $final_css );

		if ( is_wp_error( $res ) ) {
			return $this->error( $res->get_error_message() );
		}

		return $this->success(
			array(
				'mode'        => $mode,
				'applied_css' => $final_css,
			),
			sprintf( __( 'Custom CSS successfully %s (%d chars).', 'wpmcp' ), ( 'append' === $mode ? 'appended' : 'replaced' ), strlen( $final_css ) )
		);
	}
}

/**
 * Tool: Get Navigation Menus.
 */
class WPMCP_Tool_Get_Nav_Menus extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_get_nav_menus';
	}

	public function get_description(): string {
		return 'List all navigation menus and their menu items and assigned theme locations.';
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function get_risk_level(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => (object) array(),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$menus     = wp_get_nav_menus();
		$locations = get_nav_menu_locations();
		$output    = array();

		foreach ( $menus as $menu ) {
			$items      = wp_get_nav_menu_items( $menu->term_id );
			$item_list  = array();
			if ( is_array( $items ) ) {
				foreach ( $items as $item ) {
					$item_list[] = array(
						'id'       => $item->ID,
						'title'    => $item->title,
						'url'      => $item->url,
						'type'     => $item->type_label,
						'order'    => $item->menu_order,
						'parent'   => (int) $item->menu_item_parent,
					);
				}
			}

			// Find assigned locations
			$assigned = array();
			foreach ( $locations as $loc => $term_id ) {
				if ( (int) $term_id === (int) $menu->term_id ) {
					$assigned[] = $loc;
				}
			}

			$output[] = array(
				'id'        => $menu->term_id,
				'name'      => $menu->name,
				'slug'      => $menu->slug,
				'count'     => $menu->count,
				'locations' => $assigned,
				'items'     => $item_list,
			);
		}

		return $this->success( $output, sprintf( __( 'Found %d navigation menus.', 'wpmcp' ), count( $output ) ) );
	}
}

/**
 * Tool: Manage Navigation Menu (create menu / add items).
 */
class WPMCP_Tool_Manage_Nav_Menu extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_manage_nav_menu';
	}

	public function get_description(): string {
		return 'Create a navigation menu or add a link/page item to an existing menu.';
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function get_risk_level(): string {
		return 'write';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'action' ),
			'properties' => array(
				'action'     => array(
					'type'        => 'string',
					'enum'        => array( 'create_menu', 'add_item' ),
					'description' => 'Action to perform.',
				),
				'menu_name'  => array(
					'type'        => 'string',
					'description' => 'Name of menu to create or target menu name.',
				),
				'menu_id'    => array(
					'type'        => 'integer',
					'description' => 'Target menu term ID (for add_item action).',
				),
				'item_title' => array(
					'type'        => 'string',
					'description' => 'Title of the menu item to add.',
				),
				'item_url'   => array(
					'type'        => 'string',
					'description' => 'URL link for custom menu item.',
				),
				'object_id'  => array(
					'type'        => 'integer',
					'description' => 'Post/Page ID if linking directly to a WordPress page/post.',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$action = sanitize_key( $params['action'] ?? '' );

		if ( 'create_menu' === $action ) {
			if ( empty( $params['menu_name'] ) ) {
				return $this->error( __( 'menu_name is required to create a menu.', 'wpmcp' ) );
			}
			$name    = sanitize_text_field( $params['menu_name'] );
			$menu_id = wp_create_nav_menu( $name );

			if ( is_wp_error( $menu_id ) ) {
				return $this->error( $menu_id->get_error_message() );
			}

			return $this->success( array( 'menu_id' => $menu_id, 'menu_name' => $name ), sprintf( __( 'Created navigation menu "%s" (ID: %d).', 'wpmcp' ), $name, $menu_id ) );
		}

		if ( 'add_item' === $action ) {
			$menu_id = (int) ( $params['menu_id'] ?? 0 );
			if ( ! $menu_id && ! empty( $params['menu_name'] ) ) {
				$menu_obj = wp_get_nav_menu_object( $params['menu_name'] );
				if ( $menu_obj ) {
					$menu_id = $menu_obj->term_id;
				}
			}

			if ( ! $menu_id ) {
				return $this->error( __( 'Valid menu_id or menu_name is required.', 'wpmcp' ) );
			}

			$item_data = array(
				'menu-item-status' => 'publish',
			);

			if ( ! empty( $params['object_id'] ) ) {
				$post = get_post( (int) $params['object_id'] );
				if ( ! $post ) {
					return $this->error( __( 'Referenced post/page object not found.', 'wpmcp' ) );
				}
				$item_data['menu-item-object-id'] = $post->ID;
				$item_data['menu-item-object']    = $post->post_type;
				$item_data['menu-item-type']      = 'post_type';
				$item_data['menu-item-title']     = sanitize_text_field( $params['item_title'] ?? $post->post_title );
			} else {
				$item_data['menu-item-title'] = sanitize_text_field( $params['item_title'] ?? 'Link' );
				$item_data['menu-item-url']   = esc_url_raw( $params['item_url'] ?? home_url() );
				$item_data['menu-item-type']  = 'custom';
			}

			$item_id = wp_update_nav_menu_item( $menu_id, 0, $item_data );
			if ( is_wp_error( $item_id ) ) {
				return $this->error( $item_id->get_error_message() );
			}

			return $this->success( array( 'menu_item_id' => $item_id, 'menu_id' => $menu_id ), sprintf( __( 'Added item to menu ID %d.', 'wpmcp' ), $menu_id ) );
		}

		return $this->error( __( 'Invalid action specified.', 'wpmcp' ) );
	}
}
