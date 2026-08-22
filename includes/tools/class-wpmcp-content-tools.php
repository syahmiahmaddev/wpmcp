<?php
/**
 * WP-MCP Content Management Tools
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and implements all content-related tools (Posts, Pages, Media, Taxonomies).
 */
class WPMCP_Content_Tools {

	/**
	 * Register all content tools with the registry.
	 *
	 * @param WPMCP_Tool_Registry $registry Registry instance.
	 */
	public static function register( WPMCP_Tool_Registry $registry ): void {
		$registry->register_tool( new WPMCP_Tool_Get_Posts() );
		$registry->register_tool( new WPMCP_Tool_Get_Post() );
		$registry->register_tool( new WPMCP_Tool_Create_Post() );
		$registry->register_tool( new WPMCP_Tool_Update_Post() );
		$registry->register_tool( new WPMCP_Tool_Delete_Post() );
		$registry->register_tool( new WPMCP_Tool_Manage_Terms() );
		$registry->register_tool( new WPMCP_Tool_Get_Media() );
	}
}

/**
 * Tool: Query posts / pages / custom post types.
 */
class WPMCP_Tool_Get_Posts extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_get_posts';
	}

	public function get_description(): string {
		return 'Search and retrieve WordPress posts, pages, or custom post types with filtering by status, category, date, and pagination.';
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
				'search'    => array(
					'type'        => 'string',
					'description' => 'Optional search keywords to filter post titles and content.',
				),
				'post_type' => array(
					'type'        => 'string',
					'description' => 'Post type to query (e.g., "post", "page", "any", or custom post type). Defaults to "post".',
					'default'     => 'post',
				),
				'post_status' => array(
					'type'        => 'string',
					'description' => 'Status filter ("publish", "draft", "pending", "future", "trash", "any"). Defaults to "any".',
					'default'     => 'any',
				),
				'category_name' => array(
					'type'        => 'string',
					'description' => 'Category slug to filter by.',
				),
				'posts_per_page' => array(
					'type'        => 'integer',
					'description' => 'Number of results to return (max 50). Default 10.',
					'default'     => 10,
				),
				'paged' => array(
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$args = array(
			'post_type'      => sanitize_text_field( $params['post_type'] ?? 'post' ),
			'post_status'    => sanitize_text_field( $params['post_status'] ?? 'any' ),
			'posts_per_page' => min( 50, max( 1, (int) ( $params['posts_per_page'] ?? 10 ) ) ),
			'paged'          => max( 1, (int) ( $params['paged'] ?? 1 ) ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( ! empty( $params['search'] ) ) {
			$args['s'] = sanitize_text_field( $params['search'] );
		}

		if ( ! empty( $params['category_name'] ) ) {
			$args['category_name'] = sanitize_title( $params['category_name'] );
		}

		$query = new WP_Query( $args );
		$posts = array();

		foreach ( $query->posts as $post ) {
			$categories = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
			$tags       = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );

			$posts[] = array(
				'id'            => $post->ID,
				'title'         => $post->post_title,
				'slug'          => $post->post_name,
				'status'        => $post->post_status,
				'post_type'     => $post->post_type,
				'date'          => $post->post_date,
				'modified'      => $post->post_modified,
				'permalink'     => get_permalink( $post->ID ),
				'edit_url'      => get_edit_post_link( $post->ID, 'raw' ),
				'categories'    => $categories,
				'tags'          => $tags,
				'excerpt'       => wp_trim_words( $post->post_content, 30 ),
				'comment_count' => (int) $post->comment_count,
			);
		}

		return $this->success(
			array(
				'total_found' => (int) $query->found_posts,
				'max_pages'   => (int) $query->max_num_pages,
				'current_page'=> $args['paged'],
				'posts'       => $posts,
			),
			sprintf( __( 'Found %d posts.', 'wpmcp' ), count( $posts ) )
		);
	}
}

/**
 * Tool: Get single post details by ID.
 */
class WPMCP_Tool_Get_Post extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_get_post';
	}

	public function get_description(): string {
		return 'Retrieve full details of a specific WordPress post or page by its ID, including complete content, excerpt, metadata, and taxonomy terms.';
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
					'description' => 'The ID of the post/page to retrieve.',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$post_id = (int) ( $params['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return $this->error( sprintf( __( 'Post with ID %d not found.', 'wpmcp' ), $post_id ) );
		}

		$categories = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
		$tags       = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );

		$data = array(
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'slug'        => $post->post_name,
			'content'     => $post->post_content,
			'excerpt'     => $post->post_excerpt,
			'status'      => $post->post_status,
			'post_type'   => $post->post_type,
			'author_id'   => (int) $post->post_author,
			'date'        => $post->post_date,
			'modified'    => $post->post_modified,
			'permalink'   => get_permalink( $post->ID ),
			'edit_url'    => get_edit_post_link( $post->ID, 'raw' ),
			'categories'  => $categories,
			'tags'        => $tags,
			'has_blocks'  => has_blocks( $post->post_content ),
		);

		return $this->success( $data );
	}
}

/**
 * Tool: Create a new post or page.
 */
class WPMCP_Tool_Create_Post extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_create_post';
	}

	public function get_description(): string {
		return 'Create a new WordPress post, page, or custom post type with title, body content, excerpt, status (draft/publish), categories, tags, and custom metadata.';
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
			'required'   => array( 'title', 'content' ),
			'properties' => array(
				'title'       => array(
					'type'        => 'string',
					'description' => 'The post title.',
				),
				'content'     => array(
					'type'        => 'string',
					'description' => 'The post body content (HTML or Gutenberg block format).',
				),
				'post_type'   => array(
					'type'        => 'string',
					'description' => 'Post type ("post", "page", or custom type). Defaults to "post".',
					'default'     => 'post',
				),
				'post_status' => array(
					'type'        => 'string',
					'description' => 'Post status ("draft", "publish", "pending", "private"). Defaults to "draft".',
					'default'     => 'draft',
				),
				'excerpt'     => array(
					'type'        => 'string',
					'description' => 'Optional post excerpt/summary.',
				),
				'categories'  => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Array of category names or IDs to assign.',
				),
				'tags'        => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Array of tag names to assign.',
				),
				'meta_input'  => array(
					'type'        => 'object',
					'description' => 'Key-value map of custom post meta to store.',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$post_arr = array(
			'post_title'   => sanitize_text_field( $params['title'] ?? '' ),
			'post_content' => wp_kses_post( $params['content'] ?? '' ),
			'post_type'    => sanitize_key( $params['post_type'] ?? 'post' ),
			'post_status'  => sanitize_key( $params['post_status'] ?? 'draft' ),
			'post_author'  => $user_id > 0 ? $user_id : get_current_user_id(),
		);

		if ( ! empty( $params['excerpt'] ) ) {
			$post_arr['post_excerpt'] = sanitize_textarea_field( $params['excerpt'] );
		}

		if ( ! empty( $params['meta_input'] ) && is_array( $params['meta_input'] ) ) {
			$post_arr['meta_input'] = $params['meta_input'];
		}

		$post_id = wp_insert_post( $post_arr, true );

		if ( is_wp_error( $post_id ) ) {
			return $this->error( $post_id->get_error_message() );
		}

		// Assign categories
		if ( ! empty( $params['categories'] ) && is_array( $params['categories'] ) ) {
			$cat_ids = array();
			foreach ( $params['categories'] as $cat_name ) {
				$term = term_exists( $cat_name, 'category' );
				if ( ! $term ) {
					$new_term = wp_insert_term( $cat_name, 'category' );
					if ( ! is_wp_error( $new_term ) ) {
						$cat_ids[] = (int) $new_term['term_id'];
					}
				} else {
					$cat_ids[] = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
				}
			}
			if ( ! empty( $cat_ids ) ) {
				wp_set_post_categories( $post_id, $cat_ids );
			}
		}

		// Assign tags
		if ( ! empty( $params['tags'] ) && is_array( $params['tags'] ) ) {
			wp_set_post_tags( $post_id, $params['tags'], true );
		}

		return $this->success(
			array(
				'post_id'   => $post_id,
				'permalink' => get_permalink( $post_id ),
				'edit_url'  => get_edit_post_link( $post_id, 'raw' ),
				'status'    => $post_arr['post_status'],
			),
			sprintf( __( 'Successfully created %s (ID: %d).', 'wpmcp' ), $post_arr['post_type'], $post_id )
		);
	}
}

/**
 * Tool: Update an existing post or page.
 */
class WPMCP_Tool_Update_Post extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_update_post';
	}

	public function get_description(): string {
		return 'Update an existing WordPress post, page, or CPT by its ID. Can modify title, content, status, excerpt, categories, tags, and custom fields.';
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
			'required'   => array( 'post_id' ),
			'properties' => array(
				'post_id'     => array(
					'type'        => 'integer',
					'description' => 'The ID of the post to update.',
				),
				'title'       => array(
					'type'        => 'string',
					'description' => 'New post title.',
				),
				'content'     => array(
					'type'        => 'string',
					'description' => 'New post body content (HTML or block markup).',
				),
				'post_status' => array(
					'type'        => 'string',
					'description' => 'New status ("publish", "draft", "pending", "trash").',
				),
				'excerpt'     => array(
					'type'        => 'string',
					'description' => 'New post excerpt.',
				),
				'categories'  => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Array of categories to set.',
				),
				'tags'        => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Array of tags to set.',
				),
				'meta_input'  => array(
					'type'        => 'object',
					'description' => 'Custom meta key-value pairs to update.',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$post_id = (int) ( $params['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return $this->error( sprintf( __( 'Post with ID %d does not exist.', 'wpmcp' ), $post_id ) );
		}

		$post_arr = array( 'ID' => $post_id );

		if ( isset( $params['title'] ) ) {
			$post_arr['post_title'] = sanitize_text_field( $params['title'] );
		}
		if ( isset( $params['content'] ) ) {
			$post_arr['post_content'] = wp_kses_post( $params['content'] );
		}
		if ( isset( $params['post_status'] ) ) {
			$post_arr['post_status'] = sanitize_key( $params['post_status'] );
		}
		if ( isset( $params['excerpt'] ) ) {
			$post_arr['post_excerpt'] = sanitize_textarea_field( $params['excerpt'] );
		}

		$updated_id = wp_update_post( $post_arr, true );

		if ( is_wp_error( $updated_id ) ) {
			return $this->error( $updated_id->get_error_message() );
		}

		// Update categories if provided
		if ( isset( $params['categories'] ) && is_array( $params['categories'] ) ) {
			$cat_ids = array();
			foreach ( $params['categories'] as $cat_name ) {
				$term = term_exists( $cat_name, 'category' );
				if ( $term ) {
					$cat_ids[] = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
				} else {
					$new_term = wp_insert_term( $cat_name, 'category' );
					if ( ! is_wp_error( $new_term ) ) {
						$cat_ids[] = (int) $new_term['term_id'];
					}
				}
			}
			wp_set_post_categories( $post_id, $cat_ids );
		}

		// Update tags if provided
		if ( isset( $params['tags'] ) && is_array( $params['tags'] ) ) {
			wp_set_post_tags( $post_id, $params['tags'] );
		}

		// Update custom meta if provided
		if ( ! empty( $params['meta_input'] ) && is_array( $params['meta_input'] ) ) {
			foreach ( $params['meta_input'] as $k => $v ) {
				update_post_meta( $post_id, sanitize_key( $k ), $v );
			}
		}

		return $this->success(
			array(
				'post_id'   => $post_id,
				'permalink' => get_permalink( $post_id ),
				'edit_url'  => get_edit_post_link( $post_id, 'raw' ),
			),
			sprintf( __( 'Post ID %d updated successfully.', 'wpmcp' ), $post_id )
		);
	}
}

/**
 * Tool: Delete or trash a post.
 */
class WPMCP_Tool_Delete_Post extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_delete_post';
	}

	public function get_description(): string {
		return 'Move a post or page to the trash, or permanently delete it.';
	}

	public function get_required_capability(): string {
		return 'delete_posts';
	}

	public function get_risk_level(): string {
		return 'destructive';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'post_id' ),
			'properties' => array(
				'post_id'      => array(
					'type'        => 'integer',
					'description' => 'The ID of the post to delete.',
				),
				'force_delete' => array(
					'type'        => 'boolean',
					'description' => 'Whether to bypass the trash and permanently delete. Default is false (trash).',
					'default'     => false,
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$post_id      = (int) ( $params['post_id'] ?? 0 );
		$force_delete = ! empty( $params['force_delete'] );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return $this->error( sprintf( __( 'Post ID %d not found.', 'wpmcp' ), $post_id ) );
		}

		$title = $post->post_title;
		$res   = wp_delete_post( $post_id, $force_delete );

		if ( ! $res ) {
			return $this->error( sprintf( __( 'Failed to delete post ID %d.', 'wpmcp' ), $post_id ) );
		}

		$action_text = $force_delete ? 'permanently deleted' : 'moved to trash';
		return $this->success(
			array( 'post_id' => $post_id, 'force_delete' => $force_delete ),
			sprintf( __( 'Post "%s" (ID: %d) %s.', 'wpmcp' ), $title, $post_id, $action_text )
		);
	}
}

/**
 * Tool: Manage Terms & Taxonomies.
 */
class WPMCP_Tool_Manage_Terms extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_manage_terms';
	}

	public function get_description(): string {
		return 'List, create, or search terms in categories, tags, or custom taxonomies.';
	}

	public function get_required_capability(): string {
		return 'manage_categories';
	}

	public function get_risk_level(): string {
		return 'write';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'action' ),
			'properties' => array(
				'action'   => array(
					'type'        => 'string',
					'enum'        => array( 'list', 'create' ),
					'description' => 'Action to perform: "list" terms or "create" a new term.',
				),
				'taxonomy' => array(
					'type'        => 'string',
					'description' => 'Taxonomy name (e.g., "category", "post_tag"). Default "category".',
					'default'     => 'category',
				),
				'name'     => array(
					'type'        => 'string',
					'description' => 'Term name (required for "create" action).',
				),
				'slug'     => array(
					'type'        => 'string',
					'description' => 'Optional slug for creating term.',
				),
				'search'   => array(
					'type'        => 'string',
					'description' => 'Search filter when listing terms.',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$action   = sanitize_key( $params['action'] ?? 'list' );
		$taxonomy = sanitize_key( $params['taxonomy'] ?? 'category' );

		if ( 'create' === $action ) {
			if ( empty( $params['name'] ) ) {
				return $this->error( __( 'Term "name" is required for creating a term.', 'wpmcp' ) );
			}
			$args = array();
			if ( ! empty( $params['slug'] ) ) {
				$args['slug'] = sanitize_title( $params['slug'] );
			}
			$res = wp_insert_term( sanitize_text_field( $params['name'] ), $taxonomy, $args );
			if ( is_wp_error( $res ) ) {
				return $this->error( $res->get_error_message() );
			}
			return $this->success(
				array( 'term_id' => $res['term_id'], 'term_taxonomy_id' => $res['term_taxonomy_id'] ),
				sprintf( __( 'Created term "%s" in %s.', 'wpmcp' ), $params['name'], $taxonomy )
			);
		}

		// List terms
		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		);
		if ( ! empty( $params['search'] ) ) {
			$args['search'] = sanitize_text_field( $params['search'] );
		}

		$terms  = get_terms( $args );
		$output = array();

		if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$output[] = array(
					'id'    => $term->term_id,
					'name'  => $term->name,
					'slug'  => $term->slug,
					'count' => $term->count,
				);
			}
		}

		return $this->success( $output, sprintf( __( 'Retrieved %d terms.', 'wpmcp' ), count( $output ) ) );
	}
}

/**
 * Tool: Query Media Library attachments.
 */
class WPMCP_Tool_Get_Media extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_get_media';
	}

	public function get_description(): string {
		return 'Search and retrieve media attachments from the WordPress Media Library.';
	}

	public function get_required_capability(): string {
		return 'upload_files';
	}

	public function get_risk_level(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'search'         => array(
					'type'        => 'string',
					'description' => 'Search keywords for file title or filename.',
				),
				'mime_type'      => array(
					'type'        => 'string',
					'description' => 'MIME type filter (e.g. "image", "image/jpeg", "image/png", "application/pdf").',
				),
				'posts_per_page' => array(
					'type'        => 'integer',
					'description' => 'Number of media items to return (max 50). Default 10.',
					'default'     => 10,
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => min( 50, max( 1, (int) ( $params['posts_per_page'] ?? 10 ) ) ),
		);

		if ( ! empty( $params['search'] ) ) {
			$args['s'] = sanitize_text_field( $params['search'] );
		}

		if ( ! empty( $params['mime_type'] ) ) {
			$args['post_mime_type'] = sanitize_text_field( $params['mime_type'] );
		}

		$query = new WP_Query( $args );
		$items = array();

		foreach ( $query->posts as $attachment ) {
			$items[] = array(
				'id'        => $attachment->ID,
				'title'     => $attachment->post_title,
				'mime_type' => $attachment->post_mime_type,
				'url'       => wp_get_attachment_url( $attachment->ID ),
				'alt_text'  => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
				'date'      => $attachment->post_date,
			);
		}

		return $this->success( $items, sprintf( __( 'Found %d media items.', 'wpmcp' ), count( $items ) ) );
	}
}
