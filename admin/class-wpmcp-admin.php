<?php
/**
 * WP-MCP Admin Class
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles WP Admin menu pages, script enqueuing, admin bar items, and floating copilot drawer.
 */
class WPMCP_Admin {

	private WPMCP_Settings $settings;
	private WPMCP_Agent_Orchestrator $orchestrator;
	private WPMCP_Audit_Logger $audit_logger;
	private WPMCP_Rollback_Manager $rollback_manager;

	public function __construct(
		WPMCP_Settings $settings,
		WPMCP_Agent_Orchestrator $orchestrator,
		WPMCP_Audit_Logger $audit_logger,
		WPMCP_Rollback_Manager $rollback_manager
	) {
		$this->settings         = $settings;
		$this->orchestrator     = $orchestrator;
		$this->audit_logger     = $audit_logger;
		$this->rollback_manager = $rollback_manager;
	}

	/**
	 * Initialize admin hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
		add_action( 'admin_bar_menu', array( $this, 'register_admin_bar_shortcut' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_footer', array( $this, 'render_copilot_drawer' ) );
	}

	/**
	 * Register menu items in WP Admin.
	 */
	public function register_admin_menus(): void {
		$icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v2a4 4 0 0 1-8 0V6a4 4 0 0 1 4-4z"></path><rect x="3" y="10" width="18" height="12" rx="4"></rect><circle cx="8" cy="16" r="1"></circle><circle cx="16" cy="16" r="1"></circle></svg>'
		);

		add_menu_page(
			__( 'WP-MCP Copilot', 'wpmcp' ),
			__( 'WP-MCP', 'wpmcp' ),
			'edit_posts',
			'wpmcp',
			array( $this, 'render_playground_page' ),
			$icon_svg,
			65
		);

		add_submenu_page(
			'wpmcp',
			__( 'AI Copilot Playground', 'wpmcp' ),
			__( 'Copilot Chat', 'wpmcp' ),
			'edit_posts',
			'wpmcp',
			array( $this, 'render_playground_page' )
		);

		add_submenu_page(
			'wpmcp',
			__( 'WP-MCP Settings', 'wpmcp' ),
			__( 'Settings', 'wpmcp' ),
			'manage_options',
			'wpmcp-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'wpmcp',
			__( 'Audit Log & History', 'wpmcp' ),
			__( 'Audit Log', 'wpmcp' ),
			'manage_options',
			'wpmcp-audit-log',
			array( $this, 'render_audit_log_page' )
		);

		add_submenu_page(
			'wpmcp',
			__( 'Remote MCP & Docs', 'wpmcp' ),
			__( 'MCP & Docs', 'wpmcp' ),
			'manage_options',
			'wpmcp-help',
			array( $this, 'render_help_page' )
		);
	}

	/**
	 * Add quick launcher to WordPress Admin Bar.
	 */
	public function register_admin_bar_shortcut( WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'wpmcp-launcher',
				'title' => '<span class="ab-icon dashicons dashicons-superhero"></span><span class="ab-label">WP-MCP Copilot</span>',
				'href'  => '#',
				'meta'  => array(
					'onclick' => 'if(window.wpmcpToggleCopilot){window.wpmcpToggleCopilot();return false;}',
					'title'   => __( 'Open WP-MCP AI Copilot (Cmd+K / Ctrl+K)', 'wpmcp' ),
				),
			)
		);
	}

	/**
	 * Enqueue styles and scripts in WP Admin.
	 */
	public function enqueue_admin_assets(): void {
		wp_enqueue_style(
			'wpmcp-admin-css',
			WPMCP_URL . 'admin/assets/css/wpmcp-admin.css',
			array(),
			WPMCP_VERSION
		);

		wp_enqueue_script(
			'wpmcp-copilot-js',
			WPMCP_URL . 'admin/assets/js/wpmcp-copilot.js',
			array( 'jquery' ),
			WPMCP_VERSION,
			true
		);

		$provider_config = $this->settings->get_active_provider_config();

		wp_localize_script(
			'wpmcp-copilot-js',
			'wpmcpData',
			array(
				'restUrl'          => esc_url_raw( rest_url( 'wpmcp/v1/' ) ),
				'nonce'            => wp_create_nonce( 'wp_rest' ),
				'activeProvider'   => $provider_config['provider'],
				'activeModel'      => $provider_config['model'],
				'hasApiKey'        => ! empty( $provider_config['api_key'] ) || 'ollama' === $provider_config['provider'],
				'siteName'         => get_bloginfo( 'name' ),
				'settingsUrl'      => admin_url( 'admin.php?page=wpmcp-settings' ),
				'userDisplayName'  => wp_get_current_user()->display_name,
			)
		);
	}

	/**
	 * Render the persistent floating Copilot drawer markup at the bottom of WP Admin.
	 */
	public function render_copilot_drawer(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		?>
		<div id="wpmcp-dock-launcher" title="<?php esc_attr_e( 'Open WP-MCP Copilot (Cmd+K)', 'wpmcp' ); ?>">
			<div class="wpmcp-dock-icon">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 2a4 4 0 0 1 4 4v2a4 4 0 0 1-8 0V6a4 4 0 0 1 4-4z"></path>
					<rect x="3" y="10" width="18" height="12" rx="4"></rect>
					<circle cx="8" cy="16" r="1"></circle>
					<circle cx="16" cy="16" r="1"></circle>
				</svg>
			</div>
			<span class="wpmcp-dock-label"><?php esc_html_e( 'AI Copilot', 'wpmcp' ); ?></span>
			<span class="wpmcp-dock-shortcut">⌘K</span>
		</div>

		<div id="wpmcp-drawer-container" class="wpmcp-drawer-closed">
			<div class="wpmcp-drawer-backdrop"></div>
			<div class="wpmcp-drawer-panel">
				<div class="wpmcp-drawer-header">
					<div class="wpmcp-header-title">
						<span class="wpmcp-bot-badge">AI</span>
						<h3><?php esc_html_e( 'WP-MCP Copilot', 'wpmcp' ); ?></h3>
						<span class="wpmcp-model-badge" id="wpmcp-badge-model"><?php echo esc_html( $this->settings->get( 'ai_provider' ) ); ?></span>
					</div>
					<div class="wpmcp-header-actions">
						<button id="wpmcp-btn-clear" class="wpmcp-icon-btn" title="<?php esc_attr_e( 'Clear Chat', 'wpmcp' ); ?>">
							<span class="dashicons dashicons-trash"></span>
						</button>
						<button id="wpmcp-btn-close" class="wpmcp-icon-btn" title="<?php esc_attr_e( 'Close Copilot', 'wpmcp' ); ?>">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>
				</div>

				<div class="wpmcp-drawer-body" id="wpmcp-chat-messages">
					<!-- Welcome Message -->
					<div class="wpmcp-message wpmcp-message-assistant">
						<div class="wpmcp-msg-avatar">🤖</div>
						<div class="wpmcp-msg-bubble">
							<p><?php printf( esc_html__( 'Hello %s! I am your WordPress AI assistant. How can I help adjust or manage your site today?', 'wpmcp' ), '<strong>' . esc_html( wp_get_current_user()->display_name ) . '</strong>' ); ?></p>
							<div class="wpmcp-prompt-pills">
								<button class="wpmcp-pill" data-prompt="Draft a blog post about 'Top 5 Trends in Web Design' with 3 paragraphs"><?php esc_html_e( '✍️ Draft a blog post', 'wpmcp' ); ?></button>
								<button class="wpmcp-pill" data-prompt="Check site health and list any performance bottlenecks"><?php esc_html_e( '🩺 Check site health', 'wpmcp' ); ?></button>
								<button class="wpmcp-pill" data-prompt="List all installed plugins and their active status"><?php esc_html_e( '🔌 List plugins', 'wpmcp' ); ?></button>
								<button class="wpmcp-pill" data-prompt="Change the site tagline to 'Powered by AI' and update custom CSS"><?php esc_html_e( '🎨 Tweak site & CSS', 'wpmcp' ); ?></button>
							</div>
						</div>
					</div>
				</div>

				<div class="wpmcp-drawer-footer">
					<form id="wpmcp-chat-form">
						<div class="wpmcp-input-wrap">
							<textarea id="wpmcp-prompt-input" rows="1" placeholder="<?php esc_attr_e( 'Ask AI to write a post, adjust CSS, change settings, list plugins...', 'wpmcp' ); ?>"></textarea>
							<button type="submit" id="wpmcp-send-btn" title="<?php esc_attr_e( 'Send Prompt', 'wpmcp' ); ?>">
								<span class="dashicons dashicons-arrow-right-alt2"></span>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	public function render_playground_page(): void {
		?>
		<div class="wrap wpmcp-admin-wrap">
			<h1><?php esc_html_e( 'WP-MCP: AI Prompt Copilot', 'wpmcp' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Prompt your WordPress site to create content, change options, tweak CSS, manage plugins, or run health checks.', 'wpmcp' ); ?></p>
			
			<div class="wpmcp-playground-container">
				<div class="wpmcp-card wpmcp-card-hero">
					<h2><?php esc_html_e( 'Quick AI Launcher', 'wpmcp' ); ?></h2>
					<p><?php esc_html_e( 'You can open the AI Copilot from anywhere in your WordPress admin by pressing ', 'wpmcp' ); ?> <kbd>Cmd + K</kbd> (Mac) or <kbd>Ctrl + K</kbd> (Windows), or by clicking the floating button on the bottom right.</p>
					<button class="button button-primary button-hero" onclick="if(window.wpmcpToggleCopilot){window.wpmcpToggleCopilot();}"><?php esc_html_e( 'Open Copilot Drawer', 'wpmcp' ); ?></button>
				</div>

				<div class="wpmcp-grid-cards">
					<div class="wpmcp-card">
						<h3><?php esc_html_e( '✨ Content Creation', 'wpmcp' ); ?></h3>
						<p><?php esc_html_e( 'Draft posts, create landing pages, manage categories, and query the media library using natural language prompts.', 'wpmcp' ); ?></p>
					</div>
					<div class="wpmcp-card">
						<h3><?php esc_html_e( '🎨 Site Customizer & CSS', 'wpmcp' ); ?></h3>
						<p><?php esc_html_e( 'Update site title, tagline, reading settings, and write or append theme Custom CSS instantly.', 'wpmcp' ); ?></p>
					</div>
					<div class="wpmcp-card">
						<h3><?php esc_html_e( '🔌 Plugins & Theme Control', 'wpmcp' ); ?></h3>
						<p><?php esc_html_e( 'Inspect active plugins, toggle plugin states, inspect theme templates, and check site health diagnostics.', 'wpmcp' ); ?></p>
					</div>
					<div class="wpmcp-card">
						<h3><?php esc_html_e( '🌐 Remote MCP Server', 'wpmcp' ); ?></h3>
						<p><?php esc_html_e( 'Connect Claude Desktop, Cursor, or external IDE agents directly to your site over authenticated MCP protocol.', 'wpmcp' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function render_settings_page(): void {
		require_once WPMCP_PATH . 'admin/views/settings-page.php';
	}

	public function render_audit_log_page(): void {
		require_once WPMCP_PATH . 'admin/views/audit-log-page.php';
	}

	public function render_help_page(): void {
		require_once WPMCP_PATH . 'admin/views/help-page.php';
	}
}
