<?php
/**
 * WP-MCP Elementor Page Builder Tools
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers tools for inspecting and adjusting Elementor layouts, widgets, kits, and Custom CSS.
 */
class WPMCP_Elementor_Tools {

	/**
	 * Register Elementor tools with registry.
	 *
	 * @param WPMCP_Tool_Registry $registry Registry instance.
	 */
	public static function register( WPMCP_Tool_Registry $registry ): void {
		if ( ! did_action( 'elementor/loaded' ) && ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		$registry->register_tool( new WPMCP_Tool_Elementor_Get_Page_Data() );
		$registry->register_tool( new WPMCP_Tool_Elementor_Update_Page_Data() );
		$registry->register_tool( new WPMCP_Tool_Elementor_Get_Kit_Settings() );
		$registry->register_tool( new WPMCP_Tool_Elementor_Update_Custom_CSS() );
	}
}

/**
 * Tool: Get Elementor JSON structure and widgets for a page.
 */
class WPMCP_Tool_Elementor_Get_Page_Data extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_elementor_get_page_data';
	}

	public function get_description(): string {
		return 'Retrieve the Elementor layout hierarchy (sections, containers, columns, and widgets) for an Elementor-built page or post.';
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function get_risk_level(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'post_id' ),
			'properties' => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the page/post built with Elementor.',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$post_id = (int) ( $params['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return $this->error( sprintf( __( 'Post ID %d not found.', 'wpmcp' ), $post_id ) );
		}

		$is_elementor = get_post_meta( $post_id, '_elementor_edit_mode', true );
		$raw_data     = get_post_meta( $post_id, '_elementor_data', true );
		$page_settings= get_post_meta( $post_id, '_elementor_page_settings', true );

		$elements = array();
		if ( ! empty( $raw_data ) ) {
			$elements = is_string( $raw_data ) ? json_decode( $raw_data, true ) : $raw_data;
		}

		return $this->success(
			array(
				'post_id'       => $post_id,
				'post_title'    => $post->post_title,
				'is_elementor'  => ( 'builder' === $is_elementor ),
				'element_count' => is_array( $elements ) ? count( $elements ) : 0,
				'elements'      => $elements,
				'page_settings' => is_array( $page_settings ) ? $page_settings : array(),
			),
			sprintf( __( 'Retrieved Elementor layout for "%s" (ID: %d).', 'wpmcp' ), $post->post_title, $post_id )
		);
	}
}

/**
 * Tool: Update Elementor page elements/widgets.
 */
class WPMCP_Tool_Elementor_Update_Page_Data extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_elementor_update_page_data';
	}

	public function get_description(): string {
		return 'Update, replace, or insert Elementor layout elements (sections, containers, headings, buttons, text) on a page and regenerate Elementor CSS.';
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function get_risk_level(): string {
		return 'write';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'post_id', 'elements' ),
			'properties' => array(
				'post_id'  => array(
					'type'        => 'integer',
					'description' => 'The ID of the post/page to update in Elementor.',
				),
				'elements' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'object' ),
					'description' => 'Array of Elementor element objects (sections, columns, widgets).',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$post_id  = (int) ( $params['post_id'] ?? 0 );
		$elements = $params['elements'] ?? array();

		$post = get_post( $post_id );
		if ( ! $post ) {
			return $this->error( sprintf( __( 'Post ID %d not found.', 'wpmcp' ), $post_id ) );
		}

		if ( ! is_array( $elements ) ) {
			return $this->error( __( 'Elements must be an array of Elementor widget structures.', 'wpmcp' ) );
		}

		// Save Elementor data
		$json_str = wp_json_encode( $elements );
		update_post_meta( $post_id, '_elementor_data', wp_slash( $json_str ) );
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );

		// Clear Elementor CSS cache
		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		// Clear WP Rocket / Caching if active
		if ( function_exists( 'rocket_clean_post' ) ) {
			rocket_clean_post( $post_id );
		}

		return $this->success(
			array(
				'post_id'       => $post_id,
				'element_count' => count( $elements ),
			),
			sprintf( __( 'Updated Elementor design for "%s" (ID: %d) and regenerated CSS cache.', 'wpmcp' ), $post->post_title, $post_id )
		);
	}
}

/**
 * Tool: Get Elementor Active Kit Global Settings (Colors, Typography).
 */
class WPMCP_Tool_Elementor_Get_Kit_Settings extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_elementor_get_kit_settings';
	}

	public function get_description(): string {
		return 'Inspect Elementor Site Settings: Global Colors (Primary, Secondary, Text, Accent) and Global Typography.';
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
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return $this->error( __( 'Elementor is not active.', 'wpmcp' ) );
		}

		$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
		if ( ! $kit ) {
			return $this->error( __( 'No active Elementor Kit found.', 'wpmcp' ) );
		}

		$custom_colors     = $kit->get_settings( 'custom_colors' ) ?: array();
		$custom_typography = $kit->get_settings( 'custom_typography' ) ?: array();
		$system_colors     = $kit->get_settings( 'system_colors' ) ?: array();
		$system_typography = $kit->get_settings( 'system_typography' ) ?: array();

		return $this->success(
			array(
				'kit_id'            => $kit->get_id(),
				'system_colors'     => $system_colors,
				'custom_colors'     => $custom_colors,
				'system_typography' => $system_typography,
				'custom_typography' => $custom_typography,
			),
			__( 'Elementor Site Settings (Global Colors & Typography) retrieved.', 'wpmcp' )
		);
	}
}

/**
 * Tool: Update Elementor Page-Level Custom CSS.
 */
class WPMCP_Tool_Elementor_Update_Custom_CSS extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_elementor_update_custom_css';
	}

	public function get_description(): string {
		return 'Update or append Custom CSS specifically within an Elementor page settings.';
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
			'required'   => array( 'post_id', 'css' ),
			'properties' => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'Target post/page ID.',
				),
				'css'     => array(
					'type'        => 'string',
					'description' => 'Custom CSS code to apply to this Elementor page.',
				),
				'mode'    => array(
					'type'        => 'string',
					'enum'        => array( 'append', 'replace' ),
					'default'     => 'append',
					'description' => '"append" to existing Elementor page CSS or "replace" to overwrite.',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$post_id  = (int) ( $params['post_id'] ?? 0 );
		$new_css  = (string) ( $params['css'] ?? '' );
		$mode     = sanitize_key( $params['mode'] ?? 'append' );

		$page_settings = get_post_meta( $post_id, '_elementor_page_settings', true );
		if ( ! is_array( $page_settings ) ) {
			$page_settings = array();
		}

		$current_css = (string) ( $page_settings['custom_css'] ?? '' );
		$final_css   = ( 'append' === $mode && ! empty( $current_css ) )
			? rtrim( $current_css ) . "\n\n/* Added by WP-MCP */\n" . trim( $new_css )
			: $new_css;

		$page_settings['custom_css'] = $final_css;
		update_post_meta( $post_id, '_elementor_page_settings', $page_settings );

		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		return $this->success(
			array(
				'post_id' => $post_id,
				'css'     => $final_css,
			),
			sprintf( __( 'Updated Elementor Custom CSS for post ID %d.', 'wpmcp' ), $post_id )
		);
	}
}
