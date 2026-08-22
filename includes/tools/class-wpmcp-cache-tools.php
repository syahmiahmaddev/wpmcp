<?php
/**
 * WP-MCP Cache Management Tools (WP Rocket, LiteSpeed, W3TC, etc.)
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and implements cache management tools supporting WP Rocket and major caching plugins.
 */
class WPMCP_Cache_Tools {

	/**
	 * Register cache tools with registry.
	 *
	 * @param WPMCP_Tool_Registry $registry Registry instance.
	 */
	public static function register( WPMCP_Tool_Registry $registry ): void {
		$registry->register_tool( new WPMCP_Tool_Purge_Cache() );
		$registry->register_tool( new WPMCP_Tool_Get_Cache_Status() );
	}
}

/**
 * Tool: Purge cache across WP Rocket, LiteSpeed, W3 Total Cache, WP Super Cache, etc.
 */
class WPMCP_Tool_Purge_Cache extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_purge_cache';
	}

	public function get_description(): string {
		return 'Purge or clear page cache and minified assets across WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache, Autoptimize, and WP Object Cache.';
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
			'properties' => array(
				'scope'   => array(
					'type'        => 'string',
					'enum'        => array( 'all', 'post' ),
					'default'     => 'all',
					'description' => '"all" to clear entire site cache, or "post" to clear cache for a specific post ID.',
				),
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'Target post ID if scope is "post".',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$scope    = sanitize_key( $params['scope'] ?? 'all' );
		$post_id  = (int) ( $params['post_id'] ?? 0 );
		$cleared  = array();

		// 1. WP Rocket
		if ( function_exists( 'rocket_clean_domain' ) ) {
			if ( 'post' === $scope && $post_id > 0 && function_exists( 'rocket_clean_post' ) ) {
				rocket_clean_post( $post_id );
				$cleared[] = 'WP Rocket (Post #' . $post_id . ')';
			} else {
				rocket_clean_domain();
				if ( function_exists( 'rocket_clean_minify' ) ) {
					rocket_clean_minify();
				}
				$cleared[] = 'WP Rocket (All & Minified CSS/JS)';
			}
		}

		// 2. LiteSpeed Cache
		if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
			if ( 'post' === $scope && $post_id > 0 ) {
				LiteSpeed_Cache_API::purge_post( $post_id );
				$cleared[] = 'LiteSpeed Cache (Post #' . $post_id . ')';
			} else {
				LiteSpeed_Cache_API::purge_all();
				$cleared[] = 'LiteSpeed Cache (All)';
			}
		}

		// 3. W3 Total Cache
		if ( function_exists( 'w3tc_flush_all' ) ) {
			if ( 'post' === $scope && $post_id > 0 && function_exists( 'w3tc_flush_post' ) ) {
				w3tc_flush_post( $post_id );
				$cleared[] = 'W3 Total Cache (Post #' . $post_id . ')';
			} else {
				w3tc_flush_all();
				$cleared[] = 'W3 Total Cache (All)';
			}
		}

		// 4. WP Super Cache
		if ( function_exists( 'wp_cache_clean_cache' ) ) {
			global $file_prefix;
			wp_cache_clean_cache( $file_prefix, true );
			$cleared[] = 'WP Super Cache';
		}

		// 5. Autoptimize
		if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
			autoptimizeCache::clearall();
			$cleared[] = 'Autoptimize';
		}

		// 6. Elementor CSS Cache
		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
			$cleared[] = 'Elementor Generated CSS Cache';
		}

		// 7. WordPress Core Object Cache
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
			$cleared[] = 'WordPress Core Object Cache';
		}

		return $this->success(
			array(
				'scope'            => $scope,
				'post_id'          => $post_id,
				'cleared_services' => $cleared,
			),
			sprintf( __( 'Successfully purged cache across: %s', 'wpmcp' ), implode( ', ', $cleared ) )
		);
	}
}

/**
 * Tool: Get caching engines status.
 */
class WPMCP_Tool_Get_Cache_Status extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_get_cache_status';
	}

	public function get_description(): string {
		return 'Detect active caching plugins (WP Rocket, LiteSpeed, W3TC, Redis, Memcached) and cache settings.';
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
		$status = array(
			'wp_rocket'       => array(
				'is_active' => function_exists( 'rocket_clean_domain' ),
				'version'   => defined( 'WP_ROCKET_VERSION' ) ? WP_ROCKET_VERSION : null,
			),
			'litespeed_cache' => array(
				'is_active' => class_exists( 'LiteSpeed_Cache_API' ),
			),
			'w3_total_cache'  => array(
				'is_active' => function_exists( 'w3tc_flush_all' ),
			),
			'wp_super_cache'  => array(
				'is_active' => function_exists( 'wp_cache_clean_cache' ),
			),
			'elementor_cache' => array(
				'is_active' => class_exists( '\Elementor\Plugin' ),
			),
			'object_cache'    => array(
				'using_ext_object_cache' => wp_using_ext_object_cache(),
			),
		);

		return $this->success( $status, __( 'Cache status retrieved.', 'wpmcp' ) );
	}
}
