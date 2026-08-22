=== WP-MCP: AI Prompt Copilot & Universal MCP Server ===
Contributors: syahmiahmad
Donate link: https://github.com/syahmiahmad/wpmcp
Tags: ai, mcp, copilot, model-context-protocol, anthropic, openai, gemini, claude, cursor, antigravity
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Prompt your WordPress site to create content, change settings, tweak CSS, manage plugins, or connect external AI agents (Antigravity, Claude, Cursor).

== Description ==

**WP-MCP** turns your WordPress site into an AI-powered control center. It allows site administrators to manage and customize WordPress using natural language prompts, and exposes a high-performance **Model Context Protocol (MCP)** server endpoint for external AI coding tools and desktop assistants.

### 🌟 Key Features

* **In-Admin AI Copilot**: Floating drawer and global `Cmd+K` / `Ctrl+K` command bar on all WP Admin screens.
* **Multi-LLM Support**: Works with OpenAI (GPT-4o), Anthropic (Claude 3.7 Sonnet), Google Gemini (2.5 Pro), OpenRouter, and local Ollama.
* **Universal MCP Server**: Compliant with standard Model Context Protocol (SSE & HTTP JSON-RPC) to connect Google Antigravity, Claude Desktop, Cursor IDE, Windsurf, Cline, and Continue.
* **Comprehensive Tool Registry**:
  * **Content**: Create, update, trash, and search Posts, Pages, Media, and Taxonomies.
  * **Site & Theme**: Update site options, write/append Theme Custom CSS, manage Navigation Menus.
  * **Plugins & System**: Inspect active/inactive plugins, toggle plugin states, switch themes, check site health.
  * **Gutenberg & Blocks**: Block AST parser, block renderer, block patterns, and FSE templates.
  * **WooCommerce**: Query products, update inventory/pricing, query orders (when WooCommerce is active).
* **Safety & Guardrails**: Destructive tools require 1-click confirmation before running; complete searchable Audit Log with 1-click Rollback for reversible changes.

== Installation ==

1. Upload the `wpmcp` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **WP-MCP > Settings** to enter your preferred AI provider API key.
4. Press `Cmd + K` (Mac) or `Ctrl + K` (Windows) anywhere in the WP Admin to open the AI Copilot!

== Frequently Asked Questions ==

= Does WP-MCP work with Claude Desktop and Cursor? =
Yes! WP-MCP provides both a direct SSE endpoint (`/wp-json/wpmcp/v1/sse`) and a universal stdio bridge (`bin/mcp-bridge.js`) allowing Claude Desktop, Cursor, and Google Antigravity to connect with ease.

= What permissions are required? =
WP-MCP strictly enforces native WordPress capability checks (e.g. `manage_options`, `edit_posts`, `activate_plugins`). Only authorized administrators and editors can run tools matching their role capabilities.

= How do rollbacks work? =
Before executing modifications to posts, site options, or Custom CSS, WP-MCP snapshots the previous state. You can revert any action with a single click from the Audit Log or in-chat card.

== Changelog ==

= 1.0.0 =
* Initial public release.
* In-Admin Prompt Copilot with `Cmd+K` command palette.
* Multi-LLM provider support (OpenAI, Anthropic Claude, Google Gemini, OpenRouter, Ollama).
* Model Context Protocol (MCP) SSE & JSON-RPC REST endpoints.
* Built-in tools for Posts, Pages, Options, Custom CSS, Plugins, Themes, Blocks, and WooCommerce.
* Audit logger and 1-click state rollback engine.
* Universal stdio bridge script for Antigravity, Cursor, and CLI agents.
