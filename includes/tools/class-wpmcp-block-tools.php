<?php
/**
 * WP-MCP Gutenberg Block & Template Tools
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and implements tools for Gutenberg blocks, block patterns, and FSE templates.
 */
class WPMCP_Block_Tools {

	/**
	 * Register all block tools with registry.
	 *
	 * @param WPMCP_Tool_Registry $registry Registry instance.
	 */
	public static function register( WPMCP_Tool_Registry $registry ): void {
		$registry->register_tool( new WPMCP_Tool_Parse_Blocks() );
		$registry->register_tool( new WPMCP_Tool_Render_Blocks() );
		$registry->register_tool( new WPMCP_Tool_List_Block_Patterns() );
		$registry->register_tool( new WPMCP_Tool_List_Block_Templates() );
	}
}

/**
 * Tool: Parse HTML block markup into structured block AST.
 */
class WPMCP_Tool_Parse_Blocks extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_parse_blocks';
	}

	public function get_description(): string {
		return 'Parse raw Gutenberg block content or a Post ID into a structured Block AST (block name, attributes, and inner blocks).';
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
			'properties' => array(
				'content' => array(
					'type'        => 'string',
					'description' => 'Raw block markup string to parse.',
				),
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'Optional Post ID to parse blocks directly from.',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$content = (string) ( $params['content'] ?? '' );

		if ( empty( $content ) && ! empty( $params['post_id'] ) ) {
			$post = get_post( (int) $params['post_id'] );
			if ( $post ) {
				$content = $post->post_content;
			}
		}

		if ( empty( $content ) ) {
			return $this->error( __( 'No content or valid post_id provided to parse blocks.', 'wpmcp' ) );
		}

		$parsed = parse_blocks( $content );

		// Filter empty text blocks for cleaner payload
		$cleaned = array();
		foreach ( $parsed as $block ) {
			if ( empty( $block['blockName'] ) && trim( $block['innerHTML'] ) === '' ) {
				continue;
			}
			$cleaned[] = array(
				'blockName'    => $block['blockName'] ?? 'core/html',
				'attrs'        => $block['attrs'],
				'innerBlocks'  => $block['innerBlocks'],
				'innerHTML'    => trim( $block['innerHTML'] ),
			);
		}

		return $this->success(
			array(
				'total_blocks' => count( $cleaned ),
				'blocks'       => $cleaned,
			),
			sprintf( __( 'Parsed %d blocks.', 'wpmcp' ), count( $cleaned ) )
		);
	}
}

/**
 * Tool: Render block markup or HTML to final output.
 */
class WPMCP_Tool_Render_Blocks extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_render_blocks';
	}

	public function get_description(): string {
		return 'Render Gutenberg block markup or HTML using WordPress block filters (do_blocks).';
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
			'required'   => array( 'content' ),
			'properties' => array(
				'content' => array(
					'type'        => 'string',
					'description' => 'Gutenberg block markup to render.',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$content  = (string) ( $params['content'] ?? '' );
		$rendered = do_blocks( $content );

		return $this->success(
			array(
				'raw'      => $content,
				'rendered' => $rendered,
			),
			__( 'Blocks rendered successfully.', 'wpmcp' )
		);
	}
}

/**
 * Tool: List registered block patterns.
 */
class WPMCP_Tool_List_Block_Patterns extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_list_block_patterns';
	}

	public function get_description(): string {
		return 'List all available Gutenberg block patterns registered by core, theme, and plugins.';
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
			'properties' => array(
				'category' => array(
					'type'        => 'string',
					'description' => 'Optional pattern category filter (e.g. "featured", "call-to-action", "headers", "footers").',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		if ( ! class_exists( 'WP_Block_Patterns_Registry' ) ) {
			return $this->error( __( 'Block patterns registry is not available.', 'wpmcp' ) );
		}

		$registry = WP_Block_Patterns_Registry::get_instance();
		$patterns = $registry->get_all_registered();
		$cat      = sanitize_text_field( $params['category'] ?? '' );
		$output   = array();

		foreach ( $patterns as $pattern ) {
			if ( ! empty( $cat ) && ( ! isset( $pattern['categories'] ) || ! in_array( $cat, (array) $pattern['categories'], true ) ) ) {
				continue;
			}

			$output[] = array(
				'name'        => $pattern['name'],
				'title'       => $pattern['title'],
				'categories'  => $pattern['categories'] ?? array(),
				'description' => $pattern['description'] ?? '',
				'content'     => $pattern['content'],
			);
		}

		return $this->success(
			array(
				'count'    => count( $output ),
				'patterns' => $output,
			),
			sprintf( __( 'Found %d block patterns.', 'wpmcp' ), count( $output ) )
		);
	}
}

/**
 * Tool: List Block Templates & Template Parts.
 */
class WPMCP_Tool_List_Block_Templates extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_list_block_templates';
	}

	public function get_description(): string {
		return 'List Full Site Editing (FSE) block templates and template parts (e.g., header, footer, single, index).';
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
			'properties' => array(
				'template_type' => array(
					'type'        => 'string',
					'enum'        => array( 'wp_template', 'wp_template_part' ),
					'default'     => 'wp_template',
					'description' => '"wp_template" for page templates, "wp_template_part" for headers/footers.',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		if ( ! function_exists( 'get_block_templates' ) ) {
			return $this->error( __( 'Block templates function is not supported in this WordPress version or theme.', 'wpmcp' ) );
		}

		$type      = sanitize_key( $params['template_type'] ?? 'wp_template' );
		$templates = get_block_templates( array(), $type );
		$output    = array();

		foreach ( $templates as $tpl ) {
			$output[] = array(
				'id'          => $tpl->id,
				'slug'        => $tpl->slug,
				'title'       => $tpl->title,
				'description' => $tpl->description,
				'status'      => $tpl->status,
				'source'      => $tpl->source,
				'theme'       => $tpl->theme,
				'content'     => substr( $tpl->content, 0, 500 ) . ( strlen( $tpl->content ) > 500 ? '...' : '' ),
			);
		}

		return $this->success(
			array(
				'type'      => $type,
				'count'     => count( $output ),
				'templates' => $output,
			),
			sprintf( __( 'Found %d %s templates.', 'wpmcp' ), count( $output ), $type )
		);
	}
}
