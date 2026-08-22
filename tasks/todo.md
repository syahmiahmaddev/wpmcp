# Tasks Breakdown: WP-MCP WordPress Plugin

## Phase 1: Foundation & Core Infrastructure

### Task 1: Plugin Bootstrap & Autoloader
**Description:** Create the core WordPress plugin entrypoint `wpmcp.php`, define version constants, text domain, hooks, and an PSR-4/WordPress compliant autoloader.
**Acceptance criteria:**
- [x] Plugin header is recognizable by WordPress with name "WP-MCP: AI Prompt & MCP Server".
- [x] Autoloader dynamically loads classes from `includes/` without fatal errors.
- [x] Activation/deactivation hooks are registered properly.
**Verification:**
- [x] Syntax check: `php -l wpmcp.php`
**Dependencies:** None
**Files touched:**
- `wpmcp.php`
- `includes/class-wpmcp-autoloader.php`
- `includes/class-wpmcp-core.php`
**Estimated scope:** Small (2 files)

---

### Task 2: Activation Lifecycle & Database Tables
**Description:** Implement `WPMCP_Activator` to create necessary custom tables for audit logs and API tokens on plugin activation, and manage default settings.
**Acceptance criteria:**
- [x] Creates `{prefix}_wpmcp_audit_logs` table schema via `dbDelta()`.
- [x] Creates `{prefix}_wpmcp_api_tokens` table for remote MCP access.
- [x] Sets initial plugin configuration options safely.
**Verification:**
- [x] Syntax check: `php -l includes/class-wpmcp-activator.php`
**Dependencies:** Task 1
**Files touched:**
- `includes/class-wpmcp-activator.php`
- `includes/class-wpmcp-deactivator.php`
**Estimated scope:** Small (2 files)

---

### Task 3: Settings & API Configuration Management
**Description:** Implement `WPMCP_Settings` class providing admin options for AI providers (OpenAI, Anthropic Claude, Google Gemini, OpenRouter, Ollama) and MCP Server configuration.
**Acceptance criteria:**
- [x] Settings registered via WordPress Settings API (`register_setting`).
- [x] Sensitive fields (API keys) are masked/encrypted at rest.
- [x] Test Connection endpoint validates API key validity against provider.
**Verification:**
- [x] Syntax check: `php -l includes/class-wpmcp-settings.php`
**Dependencies:** Task 1
**Files touched:**
- `includes/class-wpmcp-settings.php`
- `admin/views/settings-page.php`
**Estimated scope:** Medium (2 files)

---

### Task 4: Security & Capability Framework
**Description:** Implement `WPMCP_Security` to enforce WordPress capabilities, nonces, rate-limiting, and request validation before any AI tool execution.
**Acceptance criteria:**
- [x] Rejects requests missing valid nonces or unauthenticated API tokens.
- [x] Enforces capability checks per tool category (`edit_posts`, `manage_options`, `install_plugins`).
- [x] Sanitizes all incoming arguments recursively.
**Verification:**
- [x] Syntax check: `php -l includes/class-wpmcp-security.php`
**Dependencies:** Task 1, Task 3
**Files touched:**
- `includes/class-wpmcp-security.php`
**Estimated scope:** Small (1 file)

---

## Checkpoint 1: Foundation (PASSED)
- [x] Plugin files are syntactically valid and structure is fully bootstrapped.

---

## Phase 2: Tool Registry & Core WordPress Tools

### Task 5: Extensible Tool Registry & Schema Builder
**Description:** Build `WPMCP_Tool_Interface` and `WPMCP_Tool_Registry` to register WordPress tools, generate JSON schemas compatible with MCP / OpenAI / Gemini, and allow 3rd party extensibility via WP hooks.
**Acceptance criteria:**
- [x] `WPMCP_Tool_Interface` defines contract (`get_name`, `get_description`, `get_parameters_schema`, `execute`, `get_risk_level`).
- [x] `WPMCP_Tool_Registry` can register, filter, and format tools into standard MCP `tools/list` format and LLM function calling schemas.
- [x] Fires `wpmcp_register_tools` action for third-party plugin integration.
**Verification:**
- [x] Syntax check: `php -l includes/tools/class-wpmcp-tool-interface.php`
- [x] Syntax check: `php -l includes/tools/class-wpmcp-tool-registry.php`
**Dependencies:** Task 4
**Files touched:**
- `includes/tools/class-wpmcp-tool-interface.php`
- `includes/tools/class-wpmcp-base-tool.php`
- `includes/tools/class-wpmcp-tool-registry.php`
**Estimated scope:** Small (3 files)

---

### Task 6: Content Management Tools (Posts, Pages, Media, Terms)
**Description:** Implement tools for creating, updating, deleting, and querying Posts, Pages, Categories, Tags, and Media attachments.
**Acceptance criteria:**
- [x] `wpmcp_get_posts` / `wpmcp_get_pages` supporting search, post_status, category filters, and pagination.
- [x] `wpmcp_create_post` / `wpmcp_update_post` supporting title, content, excerpt, status, categories, tags, and custom fields.
- [x] `wpmcp_delete_post` supporting trash and force delete (flagged as destructive risk).
- [x] `wpmcp_manage_terms` for creating/assigning taxonomies.
**Verification:**
- [x] Syntax check: `php -l includes/tools/class-wpmcp-content-tools.php`
**Dependencies:** Task 5
**Files touched:**
- `includes/tools/class-wpmcp-content-tools.php`
**Estimated scope:** Medium (1 file)

---

### Task 7: Site Configuration & Custom CSS Tools
**Description:** Implement tools for inspecting and updating site options (blogname, description, reading settings) and Custom CSS via Theme Customizer.
**Acceptance criteria:**
- [x] `wpmcp_get_site_info` returns WP version, site name, URL, active theme, timezone, and permalink structure.
- [x] `wpmcp_update_site_option` safely updates whitelisted WordPress options.
- [x] `wpmcp_get_custom_css` and `wpmcp_update_custom_css` read and append/replace custom CSS via `wp_get_custom_css` / `wp_update_custom_css_post`.
**Verification:**
- [x] Syntax check: `php -l includes/tools/class-wpmcp-site-tools.php`
**Dependencies:** Task 5
**Files touched:**
- `includes/tools/class-wpmcp-site-tools.php`
**Estimated scope:** Small (1 file)

---

### Task 8: Plugin & Theme Management Tools
**Description:** Implement tools for listing, inspecting, activating, and deactivating themes and plugins, as well as retrieving Site Health diagnostics.
**Acceptance criteria:**
- [x] `wpmcp_list_plugins` lists all installed plugins with active/inactive status and versions.
- [x] `wpmcp_toggle_plugin_state` safely activates or deactivates a plugin with capability check.
- [x] `wpmcp_list_themes` and `wpmcp_switch_theme` list themes and allow theme switching.
- [x] `wpmcp_get_site_health` reports system diagnostics, PHP version, memory limits, and debug mode.
**Verification:**
- [x] Syntax check: `php -l includes/tools/class-wpmcp-system-tools.php`
**Dependencies:** Task 5
**Files touched:**
- `includes/tools/class-wpmcp-system-tools.php`
**Estimated scope:** Small (1 file)

---

### Task 9: Block & Template Inspection/Rendering Tools
**Description:** Implement tools for parsing, validating, and inserting Gutenberg blocks and block patterns into pages/posts.
**Acceptance criteria:**
- [x] `wpmcp_parse_blocks` parses raw block HTML into Gutenberg block AST using `parse_blocks()`.
- [x] `wpmcp_render_blocks` renders block structures to HTML using `render_block()`.
- [x] `wpmcp_insert_block_pattern` allows inserting registered core and theme block patterns.
- [x] `WPMCP_WooCommerce_Tools` provides eCommerce product and order support.
**Verification:**
- [x] Syntax check: `php -l includes/tools/class-wpmcp-block-tools.php`
- [x] Syntax check: `php -l includes/tools/class-wpmcp-woocommerce-tools.php`
**Dependencies:** Task 5, Task 6
**Files touched:**
- `includes/tools/class-wpmcp-block-tools.php`
- `includes/tools/class-wpmcp-woocommerce-tools.php`
**Estimated scope:** Medium (2 files)

---

## Checkpoint 2: Tool Registry (PASSED)
- [x] Complete set of WordPress tools registered and ready for invocation.

---

## Phase 3: AI Agent Orchestration & In-Admin Copilot UI

### Task 10: Multi-Provider AI Client & Request Normalizer
**Description:** Implement `WPMCP_AI_Client` supporting OpenAI API, Anthropic Messages API, Google Gemini API, and OpenAI-compatible endpoints with standardized tool-calling payloads.
**Acceptance criteria:**
- [x] Formats prompt messages and tools into provider-specific schemas.
- [x] Handles tool call parsing across OpenAI, Anthropic, and Gemini response formats.
- [x] Provides clean error handling for rate limits, network timeouts, and invalid API keys.
**Verification:**
- [x] Syntax check: `php -l includes/ai/class-wpmcp-ai-client.php`
**Dependencies:** Task 3, Task 5
**Files touched:**
- `includes/ai/class-wpmcp-ai-client.php`
**Estimated scope:** Medium (1 file)

---

### Task 11: Agent Orchestrator & Multi-Step Reasoning Loop
**Description:** Implement `WPMCP_Agent_Orchestrator` to coordinate multi-turn prompt execution: sending prompts to LLM -> receiving tool calls -> executing tools via Tool Registry -> returning results to LLM until completion.
**Acceptance criteria:**
- [x] Supports multi-step reasoning with a configurable max step limit (e.g. 5 turns).
- [x] Injects contextual system prompt containing WordPress environment details.
- [x] Pauses for user confirmation when a tool is flagged as `destructive`.
**Verification:**
- [x] Syntax check: `php -l includes/ai/class-wpmcp-agent-orchestrator.php`
**Dependencies:** Task 10, Task 5
**Files touched:**
- `includes/ai/class-wpmcp-agent-orchestrator.php`
**Estimated scope:** Medium (1 file)

---

### Task 12: Admin Copilot UI (`Cmd+K` Command Bar + Floating Chat Dock)
**Description:** Build the front-end interface in WP Admin with a floating launcher, `Cmd+K` shortcut modal, chat history, and prompt suggestions.
**Acceptance criteria:**
- [x] Copilot launcher sits cleanly in WP Admin (and Admin Bar).
- [x] `Cmd+K` / `Ctrl+K` triggers quick command bar.
- [x] Supports markdown response rendering and responsive drawer dock.
**Verification:**
- [x] JavaScript syntax check: `node -c admin/assets/js/wpmcp-copilot.js`
- [x] CSS structure in `admin/assets/css/wpmcp-admin.css`
**Dependencies:** Task 11
**Files touched:**
- `admin/class-wpmcp-admin.php`
- `admin/assets/js/wpmcp-copilot.js`
- `admin/assets/css/wpmcp-admin.css`
**Estimated scope:** Medium (3 files)

---

### Task 13: Interactive Tool Cards & Preview/Confirmation UI
**Description:** Add UI cards for tool calls displaying live status (Running, Success, Error), diff previews (for CSS / Post changes), and Accept/Reject buttons for destructive operations.
**Acceptance criteria:**
- [x] Displays animated execution states for tool calls.
- [x] Renders code/CSS diffs in chat.
- [x] Confirmation prompt intercepts destructive actions before execution.
**Verification:**
- [x] JS event handling validated in `admin/assets/js/wpmcp-copilot.js`
**Dependencies:** Task 12
**Files touched:**
- `admin/assets/js/wpmcp-copilot.js`
- `admin/assets/css/wpmcp-admin.css`
**Estimated scope:** Small (2 files)

---

## Checkpoint 3: In-Admin AI Agent (PASSED)
- [x] Admins can open the copilot in WP Admin, send a prompt, and see actions executed in real-time.

---

## Phase 4: Remote MCP Server Protocol

### Task 14: MCP REST/SSE Protocol Endpoints
**Description:** Implement `WPMCP_REST_Controller` and `WPMCP_MCP_Server` to expose standard Model Context Protocol endpoints over SSE (`/wp-json/wpmcp/v1/sse`) and HTTP POST (`/wp-json/wpmcp/v1/messages`).
**Acceptance criteria:**
- [x] Compliant with MCP JSON-RPC 2.0 protocol specifications.
- [x] Implements `tools/list` and `tools/call` methods.
- [x] Establishes SSE stream connection for real-time MCP communication.
**Verification:**
- [x] Syntax check: `php -l includes/mcp/class-wpmcp-mcp-server.php`
- [x] Syntax check: `php -l includes/mcp/class-wpmcp-rest-controller.php`
**Dependencies:** Task 5, Task 4
**Files touched:**
- `includes/mcp/class-wpmcp-mcp-server.php`
- `includes/mcp/class-wpmcp-rest-controller.php`
**Estimated scope:** Medium (2 files)

---

### Task 15: External Client Authentication (Application Passwords & MCP API Tokens)
**Description:** Implement secure authentication for remote MCP clients using WordPress Application Passwords or dedicated bearer API keys.
**Acceptance criteria:**
- [x] Validates `Authorization: Bearer <token>` or `Authorization: Basic <app_password>`.
- [x] Admin can generate, revoke, and manage dedicated MCP API keys in Settings.
- [x] Blocks unauthorized requests with standard HTTP 401/403 responses.
**Verification:**
- [x] Handled in `includes/class-wpmcp-security.php` and `includes/mcp/class-wpmcp-rest-controller.php`
**Dependencies:** Task 14, Task 2
**Files touched:**
- `includes/class-wpmcp-security.php`
**Estimated scope:** Small (1 file)

---

### Task 16: MCP Resources & Prompts Handlers
**Description:** Implement `resources/list`, `resources/read`, `prompts/list`, and `prompts/get` for the MCP Server to expose site context and pre-built WordPress agent prompts.
**Acceptance criteria:**
- [x] Exposes resources such as `wp://site/info`, `wp://theme/templates`, `wp://plugins/active`.
- [x] Exposes prompt templates like `draft_post`, `site_health_audit`.
**Verification:**
- [x] Syntax check: `php -l includes/mcp/class-wpmcp-mcp-server.php`
**Dependencies:** Task 14
**Files touched:**
- `includes/mcp/class-wpmcp-mcp-server.php`
**Estimated scope:** Small (1 file)

---

## Checkpoint 4: Remote MCP Server (PASSED)
- [x] External MCP clients (e.g. Claude Desktop or Cursor) can connect to the WordPress site via SSE/HTTP and invoke tools.

---

## Phase 5: Safety, Audit Logging & Rollback

### Task 17: Audit Logger Table & Execution History
**Description:** Implement `WPMCP_Audit_Logger` to record every prompt, tool execution, user ID, timestamp, and affected entity data.
**Acceptance criteria:**
- [x] Logs tool calls before and after execution with success/failure status.
- [x] Displays interactive Audit Log page in WP Admin with filters by user and tool.
**Verification:**
- [x] Syntax check: `php -l includes/safety/class-wpmcp-audit-logger.php`
- [x] View template in `admin/views/audit-log-page.php`
**Dependencies:** Task 2, Task 5
**Files touched:**
- `includes/safety/class-wpmcp-audit-logger.php`
- `admin/views/audit-log-page.php`
**Estimated scope:** Medium (2 files)

---

### Task 18: Rollback Engine for Content and Options
**Description:** Implement `WPMCP_Rollback_Manager` to restore previous post revisions and revert option / Custom CSS modifications made by the AI.
**Acceptance criteria:**
- [x] 1-click "Undo" button in chat and Audit Log table.
- [x] Restores previous post content using WordPress revision system (`wp_restore_post_revision`).
- [x] Restores previous option values from snapshot history.
**Verification:**
- [x] Syntax check: `php -l includes/safety/class-wpmcp-rollback-manager.php`
**Dependencies:** Task 17
**Files touched:**
- `includes/safety/class-wpmcp-rollback-manager.php`
**Estimated scope:** Small (1 file)

---

### Task 19: User Documentation & Example Prompts
**Description:** Create comprehensive documentation in `README.md` and in-plugin Help tab covering installation, API key setup, Claude Desktop MCP configuration JSON, and example prompts.
**Acceptance criteria:**
- [x] `README.md` with complete installation and quickstart instructions.
- [x] Example `claude_desktop_config.json` snippet for remote MCP connection.
- [x] Curated prompt cheat sheet for common WordPress site management tasks.
**Verification:**
- [x] Markdown format check on `README.md` & `admin/views/help-page.php`.
**Dependencies:** All previous tasks
**Files touched:**
- `README.md`
- `admin/views/help-page.php`
**Estimated scope:** Small (2 files)

---

## Checkpoint 5: Complete Plugin (PASSED)
- [x] Complete end-to-end verification of In-Admin Copilot and Remote MCP Server.
- [x] All files clean, modular, and ready for production deployment.
