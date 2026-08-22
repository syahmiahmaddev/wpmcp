# Changelog

All notable changes to the **WP-MCP** plugin will be documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-08-22

### Added
- **Plugin Core & Bootstrap**: Autoloader, version constants, settings management, and activation database tables (`wpmcp_audit_logs`, `wpmcp_api_tokens`).
- **In-Admin AI Copilot**: Global keyboard shortcut (`Cmd+K` / `Ctrl+K`), floating dock launcher, interactive chat drawer, and prompt pill suggestions.
- **Multi-Provider AI Client**: Unified HTTP client supporting OpenAI (`gpt-4o`), Anthropic (`claude-3-7-sonnet`), Google Gemini (`gemini-2.5-pro`), OpenRouter, and Ollama/Local LLMs.
- **Universal Model Context Protocol (MCP) Server**:
  - SSE endpoint (`/wp-json/wpmcp/v1/sse`) and JSON-RPC message endpoint (`/wp-json/wpmcp/v1/messages`).
  - Compatibility with Google Antigravity, Claude Desktop, Cursor IDE, Windsurf, Cline, Continue, and Roo Code.
  - Zero-dependency Node.js stdio bridge (`bin/mcp-bridge.js`).
- **Extensible WordPress Tools Engine**:
  - `wpmcp_get_posts`, `wpmcp_get_post`, `wpmcp_create_post`, `wpmcp_update_post`, `wpmcp_delete_post`.
  - `wpmcp_manage_terms`, `wpmcp_get_media`.
  - `wpmcp_get_site_info`, `wpmcp_update_site_option`, `wpmcp_get_custom_css`, `wpmcp_update_custom_css`, `wpmcp_get_nav_menus`, `wpmcp_manage_nav_menu`.
  - `wpmcp_list_plugins`, `wpmcp_toggle_plugin_state`, `wpmcp_list_themes`, `wpmcp_switch_theme`, `wpmcp_get_site_health`.
  - `wpmcp_parse_blocks`, `wpmcp_render_blocks`, `wpmcp_list_block_patterns`, `wpmcp_list_block_templates`.
  - WooCommerce integration: `wpmcp_wc_get_products`, `wpmcp_wc_update_product`, `wpmcp_wc_get_orders`.
- **Safety & Rollback Engine**:
  - Multi-tier risk categorization (`read`, `write`, `destructive`).
  - Interactive pre-execution confirmation for destructive actions.
  - Snapshot capture and 1-click state rollback for post revisions, site options, and Custom CSS.
  - Searchable audit log interface in WP Admin.
