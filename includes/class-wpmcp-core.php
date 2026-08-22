<?php
/**
 * WP-MCP Core Singleton Class
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Core plugin class that orchestrates all subsystems.
 */
class WPMCP_Core {

	/**
	 * Single instance of the class.
	 *
	 * @var WPMCP_Core|null
	 */
	private static ?WPMCP_Core $instance = null;

	/**
	 * Subsystem instances.
	 */
	public WPMCP_Settings $settings;
	public WPMCP_Security $security;
	public WPMCP_Tool_Registry $tool_registry;
	public WPMCP_Audit_Logger $audit_logger;
	public WPMCP_Rollback_Manager $rollback_manager;
	public ?WPMCP_Admin $admin = null;
	public ?WPMCP_REST_Controller $rest_controller = null;
	public ?WPMCP_MCP_Server $mcp_server = null;
	public ?WPMCP_Agent_Orchestrator $orchestrator = null;

	/**
	 * Main instance accessor.
	 */
	public static function get_instance(): WPMCP_Core {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_subsystems();
	}

	/**
	 * Instantiate all core subsystems.
	 */
	private function init_subsystems(): void {
		$this->settings         = new WPMCP_Settings();
		$this->security         = new WPMCP_Security( $this->settings );
		$this->audit_logger     = new WPMCP_Audit_Logger();
		$this->rollback_manager = new WPMCP_Rollback_Manager( $this->audit_logger );
		$this->tool_registry    = new WPMCP_Tool_Registry( $this->security, $this->audit_logger, $this->rollback_manager );
		$this->orchestrator     = new WPMCP_Agent_Orchestrator( $this->settings, $this->tool_registry );
		$this->mcp_server       = new WPMCP_MCP_Server( $this->tool_registry, $this->settings );
		$this->rest_controller  = new WPMCP_REST_Controller( $this->mcp_server, $this->security, $this->orchestrator );

		if ( is_admin() ) {
			$this->admin = new WPMCP_Admin( $this->settings, $this->orchestrator, $this->audit_logger, $this->rollback_manager );
		}
	}

	/**
	 * Run all plugin hooks and setup.
	 */
	public function run(): void {
		// Load text domain.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Register built-in tools.
		add_action( 'init', array( $this->tool_registry, 'register_core_tools' ), 5 );

		// Initialize REST routes.
		add_action( 'rest_api_init', array( $this->rest_controller, 'register_routes' ) );

		// Initialize Admin if in admin context.
		if ( is_admin() && null !== $this->admin ) {
			$this->admin->init();
		}
	}

	/**
	 * Load translation files.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wpmcp',
			false,
			dirname( WPMCP_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
