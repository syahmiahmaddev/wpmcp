<?php
/**
 * Mock WordPress & Elementor Environment for Tests
 */

namespace {
	define( 'ABSPATH', '/var/www/html/' );
	define( 'WPMCP_PATH', dirname( __DIR__ ) . '/' );
	define( 'WPMCP_URL', 'http://example.com/wp-content/plugins/wpmcp/' );
	define( 'WPMCP_VERSION', '1.0.0' );
	define( 'WPMCP_PLUGIN_BASENAME', 'wpmcp/wpmcp.php' );

	$GLOBALS['_mock_options']    = array();
	$GLOBALS['_mock_posts']      = array();
	$GLOBALS['_mock_post_meta']  = array();
	$GLOBALS['_mock_audit_logs'] = array();
	$GLOBALS['_mock_custom_css'] = '';

	function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {}
	function do_action( $tag, ...$args ) {}
	function apply_filters( $tag, $val, ...$args ) { return $val; }
	function register_activation_hook( $file, $callback ) {}
	function register_deactivation_hook( $file, $callback ) {}
	function plugin_dir_path( $file ) { return WPMCP_PATH; }
	function plugin_dir_url( $file ) { return WPMCP_URL; }
	function plugin_basename( $file ) { return WPMCP_PLUGIN_BASENAME; }
	function is_admin() { return true; }
	function get_option( $key, $default = false ) { return $GLOBALS['_mock_options'][ $key ] ?? $default; }
	function update_option( $key, $val ) { $GLOBALS['_mock_options'][ $key ] = $val; return true; }
	function wp_parse_args( $a, $b ) { return array_merge( (array) $b, (array) $a ); }
	function current_time( $type, $gmt = 0 ) { return date( 'Y-m-d H:i:s' ); }
	function wp_timezone_string() { return 'UTC'; }
	function get_bloginfo( $key ) {
		return match ( $key ) {
			'name' => 'CortiCare Store',
			'description' => 'Healthcare & Wellness',
			'version' => '6.7',
			'admin_email' => 'admin@corti-care.com',
			'language' => 'en_US',
			default => 'CortiCare',
		};
	}
	function site_url( $path = '' ) { return 'https://corti-care.com' . $path; }
	function home_url( $path = '' ) { return 'https://corti-care.com' . $path; }
	function wp_get_theme() {
		return new class {
			function get( $k ) { return 'Hello Elementor'; }
			function is_block_theme() { return false; }
			function get_stylesheet() { return 'hello-elementor'; }
			function get_template() { return 'hello-elementor'; }
		};
	}
	function get_current_user_id() { return 1; }
	function get_userdata( $id ) {
		$u = new stdClass();
		$u->ID = $id;
		$u->display_name = 'Mohamadafif';
		return $u;
	}
	function wp_get_current_user() { return get_userdata( 1 ); }
	function current_user_can( $cap ) { return true; }
	function user_can( $user, $cap ) { return true; }
	function sanitize_text_field( $s ) { return trim( (string) $s ); }
	function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
	function sanitize_title( $s ) { return strtolower( preg_replace( '/[^a-z0-9\-]/i', '-', (string) $s ) ); }
	function sanitize_textarea_field( $s ) { return trim( (string) $s ); }
	function esc_url_raw( $s ) { return filter_var( $s, FILTER_SANITIZE_URL ); }
	function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
	function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
	function esc_html__( $s, $d ) { return $s; }
	function esc_attr__( $s, $d ) { return $s; }
	function __( $s, $d ) { return $s; }
	function wp_json_encode( $d, $options = 0 ) { return json_encode( $d, $options ); }
	function wp_slash( $s ) { return $s; }
	function wp_unslash( $s ) { return $s; }
	function rest_url( $path = '' ) { return 'https://corti-care.com/wp-json/' . $path; }
	function wp_create_nonce( $action ) { return 'test_nonce_' . $action; }
	function admin_url( $path = '' ) { return 'https://corti-care.com/wp-admin/' . $path; }
	function load_plugin_textdomain( $domain, $deprecated, $plugin_rel_path ) {}
	function did_action( $tag ) { return true; }
	function register_rest_route( $ns, $route, $args ) {}
	function wp_kses_post( $s ) { return $s; }
	function wp_trim_words( $text, $num_words = 55 ) { return substr( $text, 0, 100 ); }
	function get_permalink( $id ) { return 'https://corti-care.com/?p=' . $id; }
	function get_edit_post_link( $id, $context = 'display' ) { return 'https://corti-care.com/wp-admin/post.php?post=' . $id . '&action=edit'; }
	function wp_get_custom_css() { return $GLOBALS['_mock_custom_css']; }
	function wp_update_custom_css_post( $css ) { $GLOBALS['_mock_custom_css'] = $css; return true; }
	function parse_blocks( $content ) {
		return array(
			array( 'blockName' => 'core/paragraph', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '<p>Test</p>' ),
		);
	}
	function do_blocks( $content ) { return $content; }
	function is_multisite() { return false; }
	function wp_using_ext_object_cache() { return false; }
	function wp_upload_dir() { return array( 'basedir' => '/tmp/uploads' ); }
	function wp_is_writable( $path ) { return true; }
	function get_rest_url() { return 'https://corti-care.com/wp-json/'; }

	function wp_insert_post( $postarr, $wp_error = false ) {
		static $id_seq = 100;
		$id = ++$id_seq;
		$post = (object) array(
			'ID'            => $id,
			'post_title'    => $postarr['post_title'] ?? '',
			'post_content'  => $postarr['post_content'] ?? '',
			'post_excerpt'  => $postarr['post_excerpt'] ?? '',
			'post_status'   => $postarr['post_status'] ?? 'draft',
			'post_type'     => $postarr['post_type'] ?? 'post',
			'post_author'   => $postarr['post_author'] ?? 1,
			'post_date'     => date( 'Y-m-d H:i:s' ),
			'post_modified' => date( 'Y-m-d H:i:s' ),
			'post_name'     => sanitize_title( $postarr['post_title'] ?? 'post-' . $id ),
			'comment_count' => 0,
		);
		$GLOBALS['_mock_posts'][ $id ] = $post;
		return $id;
	}
	function wp_update_post( $postarr, $wp_error = false ) {
		$id = $postarr['ID'] ?? 0;
		if ( ! isset( $GLOBALS['_mock_posts'][ $id ] ) ) return new WP_Error( 'invalid_post', 'Post not found' );
		foreach ( $postarr as $k => $v ) {
			$GLOBALS['_mock_posts'][ $id ]->$k = $v;
		}
		return $id;
	}
	function get_post( $id ) { return $GLOBALS['_mock_posts'][ $id ] ?? null; }
	function wp_delete_post( $id, $force = false ) {
		if ( isset( $GLOBALS['_mock_posts'][ $id ] ) ) {
			unset( $GLOBALS['_mock_posts'][ $id ] );
			return true;
		}
		return false;
	}
	function wp_get_post_categories( $id, $args = array() ) { return array( 'General' ); }
	function wp_get_post_tags( $id, $args = array() ) { return array( 'Health' ); }
	function has_blocks( $content ) { return strpos( $content, '<!-- wp:' ) !== false; }
	function update_post_meta( $id, $key, $val ) { $GLOBALS['_mock_post_meta'][ $id ][ $key ] = $val; return true; }
	function get_post_meta( $id, $key, $single = false ) { return $GLOBALS['_mock_post_meta'][ $id ][ $key ] ?? ''; }

	class WP_Query {
		public array $posts = array();
		public int $found_posts = 0;
		public int $max_num_pages = 1;
		public function __construct( $args = array() ) {
			$this->posts = array_values( $GLOBALS['_mock_posts'] );
			$this->found_posts = count( $this->posts );
		}
	}

	class WP_Error {
		public string $code;
		public string $message;
		public function __construct( $code, $message ) {
			$this->code = $code;
			$this->message = $message;
		}
		public function get_error_message() { return $this->message; }
	}
	function is_wp_error( $thing ) { return is_a( $thing, 'WP_Error' ); }

	class WP_REST_Server {
		const READABLE = 'GET';
		const CREATABLE = 'POST';
		const DELETABLE = 'DELETE';
	}
	class WP_Admin_Bar { function add_node( $args ) {} }

	class MockWPDB {
		public string $prefix = 'wp_';
		public int $insert_id = 1;
		public function get_charset_collate() { return 'DEFAULT CHARACTER SET utf8mb4'; }
		public function insert( $table, $data, $format ) {
			static $seq = 1;
			$data['id'] = ++$seq;
			$GLOBALS['_mock_audit_logs'][ $data['id'] ] = (object) $data;
			$this->insert_id = $data['id'];
			return 1;
		}
		public function get_var( $sql ) {
			if ( strpos( $sql, 'COUNT' ) !== false ) {
				return count( $GLOBALS['_mock_audit_logs'] );
			}
			return null;
		}
		public function get_results( $sql ) { return array_values( $GLOBALS['_mock_audit_logs'] ); }
		public function get_row( $sql ) {
			if ( preg_match( '/id = (\d+)/', $sql, $m ) ) {
				return $GLOBALS['_mock_audit_logs'][ (int) $m[1] ] ?? null;
			}
			return null;
		}
		public function update( $table, $data, $where, $f1 = null, $f2 = null ) {
			$id = $where['id'] ?? 0;
			if ( isset( $GLOBALS['_mock_audit_logs'][ $id ] ) ) {
				foreach ( $data as $k => $v ) {
					$GLOBALS['_mock_audit_logs'][ $id ]->$k = $v;
				}
				return true;
			}
			return false;
		}
		public function prepare( $query, ...$args ) {
			if ( is_array( $args[0] ?? null ) ) $args = $args[0];
			return vsprintf( str_replace( array( '%d', '%s', '%f' ), array( '%d', "'%s'", '%f' ), $query ), $args );
		}
		public function db_version() { return '8.0.35'; }
	}
	$GLOBALS['wpdb'] = new MockWPDB();
}

namespace Elementor {
	class Files_Manager { function clear_cache() {} }
	class Kits_Manager {
		function get_active_kit() {
			return new class {
				function get_id() { return 5; }
				function get_settings( $k ) { return array( 'primary' => '#1e1e2f', 'secondary' => '#6366f1' ); }
			};
		}
	}
	class Plugin {
		public static $instance;
		public Files_Manager $files_manager;
		public Kits_Manager $kits_manager;
		public function __construct() {
			$this->files_manager = new Files_Manager();
			$this->kits_manager  = new Kits_Manager();
		}
	}
	Plugin::$instance = new Plugin();
}
