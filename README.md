# WP-MCP: AI Prompt Copilot & Universal Model Context Protocol (MCP) Server for WordPress

**WP-MCP** is an enterprise-grade WordPress plugin that allows you to inspect, manage, configure, customize, and write content for your WordPress site using natural language prompts.

It works in two complementary modes:
1. **In-Admin AI Copilot**: A native WP-Admin command palette (`Cmd+K` / `Ctrl+K`) and floating dock powered by leading LLMs (OpenAI GPT-4o, Anthropic Claude 3.7, Google Gemini 2.5, OpenRouter, or local Ollama).
2. **Universal MCP Server (Model Context Protocol)**: Connect **ANY** MCP-enabled LLM or coding agent (**Google Antigravity**, **Claude Desktop**, **Cursor IDE**, **Windsurf**, **Cline**, **Continue**, **Roo Code**, or custom Python/Node scripts) directly to your WordPress site over HTTP/SSE or stdio bridge!

---

## 🚀 Connecting with Any MCP Client

WP-MCP supports both **Direct SSE / HTTP** and **stdio bridge** (`bin/mcp-bridge.js`), making it 100% compatible with any MCP client.

### 1. Google Antigravity (AGY)
Add WP-MCP to your Antigravity MCP server configuration:

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "node",
      "args": [
        "/path/to/wp-content/plugins/wpmcp/bin/mcp-bridge.js",
        "--url", "https://your-wordpress-site.com/wp-json/wpmcp/v1/messages",
        "--token", "YOUR_WPMCP_API_TOKEN"
      ]
    }
  }
}
```

---

### 2. Anthropic Claude Desktop
Open `~/Library/Application Support/Claude/claude_desktop_config.json` (Mac) or `%APPDATA%\Claude\claude_desktop_config.json` (Windows):

**Direct SSE Mode:**
```json
{
  "mcpServers": {
    "wordpress": {
      "url": "https://your-wordpress-site.com/wp-json/wpmcp/v1/sse",
      "headers": {
        "Authorization": "Bearer YOUR_WPMCP_API_TOKEN"
      }
    }
  }
}
```

**Or stdio Bridge Mode:**
```json
{
  "mcpServers": {
    "wordpress": {
      "command": "node",
      "args": [
        "/path/to/wp-content/plugins/wpmcp/bin/mcp-bridge.js",
        "--url", "https://your-wordpress-site.com",
        "--token", "YOUR_WPMCP_API_TOKEN"
      ]
    }
  }
}
```

---

### 3. Cursor IDE
In Cursor Settings > Features > MCP:
- **Name**: `wordpress`
- **Type**: `command`
- **Command**: `node /path/to/wp-content/plugins/wpmcp/bin/mcp-bridge.js --url https://your-wordpress-site.com --token YOUR_WPMCP_API_TOKEN`

---

### 4. Windsurf / VS Code (Cline, Continue, Roo Code)
Add to your extension's MCP configuration (`mcp_config.json` or `settings.json`):
```json
{
  "mcpServers": {
    "wordpress": {
      "command": "node",
      "args": [
        "/path/to/wp-content/plugins/wpmcp/bin/mcp-bridge.js",
        "--url", "https://your-wordpress-site.com",
        "--token", "YOUR_WPMCP_API_TOKEN"
      ]
    }
  }
}
```

---

### 5. Python & Node.js AI Agents (LangChain, CrewAI, AutoGen, MCP SDK)
You can directly query the standard JSON-RPC endpoints:
- SSE: `https://your-wordpress-site.com/wp-json/wpmcp/v1/sse`
- POST Messages: `https://your-wordpress-site.com/wp-json/wpmcp/v1/messages`

```python
# Python MCP SDK Example
from mcp.client.sse import sse_client

async with sse_client("https://your-wordpress-site.com/wp-json/wpmcp/v1/sse", headers={
    "Authorization": "Bearer wpmcp_xxxxxxxxxxxxxxxxxxxx"
}) as (read, write):
    # Call WordPress tools
    ...
```

---

## 🌟 What You Can Do via Prompt

Once connected in WP-Admin or from your external AI agent:

- **Content Creation & Editing**:
  - *"Draft a 1000-word SEO-optimized post about AI in Healthcare, add tags, categorize under 'Tech', and save as draft."*
  - *"Find all posts in category 'News' published last month and update their excerpt."*
- **Theme & Customizer CSS**:
  - *"Update the site tagline to 'AI-Powered Publishing' and make all headings dark violet in Custom CSS."*
  - *"Switch active theme to Twenty Twenty-Four and inspect the template parts."*
- **System & Site Health**:
  - *"Check site health, memory limits, and list all inactive plugins."*
  - *"Activate the WooCommerce plugin."*
- **Block & Gutenberg Inspection**:
  - *"Parse the blocks in post ID 42 and render the block AST structure."*
  - *"List all registered header block patterns."*
- **WooCommerce (if active)**:
  - *"List products low on stock and update product ID 50 price to \$29.99."*

---

## 🧰 Built-in WordPress Tools Catalog

| Tool Name | Risk Level | Description |
|---|---|---|
| `wpmcp_get_posts` | `read` | Search and retrieve posts/pages/CPTs with filters |
| `wpmcp_get_post` | `read` | Retrieve full post content, metadata, and taxonomy terms |
| `wpmcp_create_post` | `write` | Create a new post/page with title, content, status, terms |
| `wpmcp_update_post` | `write` | Update existing post content, title, status, or meta |
| `wpmcp_delete_post` | `destructive` | Move post to trash or permanently delete |
| `wpmcp_manage_terms` | `write` | Create or list categories, tags, and taxonomy terms |
| `wpmcp_get_media` | `read` | Search media attachments in the Media Library |
| `wpmcp_get_site_info` | `read` | Get site title, URL, active theme, and versions |
| `wpmcp_update_site_option` | `write` | Update safe site settings (title, tagline, timezone, etc.) |
| `wpmcp_get_custom_css` | `read` | Retrieve Customizer additional CSS |
| `wpmcp_update_custom_css` | `write` | Append or replace Custom CSS styles |
| `wpmcp_get_nav_menus` | `read` | List navigation menus, items, and theme locations |
| `wpmcp_manage_nav_menu` | `write` | Create menus or add menu items |
| `wpmcp_list_plugins` | `read` | List all installed plugins and active status |
| `wpmcp_toggle_plugin_state` | `destructive` | Activate or deactivate plugins |
| `wpmcp_list_themes` | `read` | List installed themes and active theme |
| `wpmcp_switch_theme` | `destructive` | Switch active WordPress theme |
| `wpmcp_get_site_health` | `read` | Server and WordPress diagnostic report |
| `wpmcp_parse_blocks` | `read` | Parse block HTML markup into structured AST |
| `wpmcp_render_blocks` | `read` | Render Gutenberg block markup |
| `wpmcp_list_block_patterns` | `read` | List available core & theme block patterns |
| `wpmcp_list_block_templates` | `read` | List FSE templates and template parts |
| `wpmcp_wc_get_products` | `read` | (WooCommerce) Query products & stock |
| `wpmcp_wc_update_product` | `write` | (WooCommerce) Update price, stock, title |
| `wpmcp_wc_get_orders` | `read` | (WooCommerce) Query recent store orders |

---

## 🔒 Security Architecture

- **Strict Capability Enforcement**: All tools enforce native WordPress capabilities (`manage_options`, `edit_posts`, `activate_plugins`).
- **Nonces & Token Authentication**: Admin requests require standard WordPress nonces; remote requests require SHA-256 hashed API tokens or Application Passwords.
- **Audit Logging**: Every prompt and tool execution is recorded with timestamp, user ID, arguments, and outcome.
- **1-Click Rollback**: Reversible state changes (post revisions, site options, Custom CSS) can be undone with 1 click.

---

## 📄 License
GPL v2 or later.
