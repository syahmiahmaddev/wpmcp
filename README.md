<div align="center">

# ⚡ WP-MCP
### Universal Model Context Protocol (MCP) Server & AI Prompt Copilot for WordPress

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg?style=for-the-badge)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759B?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MCP Protocol](https://img.shields.io/badge/MCP-2024--11--05-00D084?style=for-the-badge)](https://modelcontextprotocol.io)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=for-the-badge)](https://github.com/syahmiahmaddev/wpmcp/pulls)

<p align="center">
  <b>Turn WordPress into an AI-controllable platform.</b><br>
  Connect coding agents, external LLMs, or the native WP-Admin Copilot to query, build, design, and automate WordPress using natural language.
</p>

[Quick Start](#-quick-start-in-3-steps) •
[Client Configurations](#-connecting-your-mcp-clients) •
[Tools Catalog](#-tools-catalog) •
[Architecture](#-architecture) •
[Security & Safety](#-security--safety-first)

---

</div>

## 🌟 Overview

**WP-MCP** turns your WordPress site into a first-class participant in the AI ecosystem. Whether you are an agency managing dozens of client sites, a content creator writing at scale, or a developer automating workflows with coding agents, WP-MCP gives your AI tools safe, direct, and structured access to WordPress.

```mermaid
graph TD
    subgraph "AI Clients & Coding Agents"
        A1[Google Antigravity]
        A2[Anthropic Claude Desktop]
        A3[Cursor IDE / Windsurf]
        A4[Python / Node.js Agents]
        A5[WP-Admin Cmd+K Copilot]
    end

    subgraph "WP-MCP Bridge & Endpoints"
        B1[stdio Bridge<br><code>bin/mcp-bridge.js</code>]
        B2[REST / SSE Endpoints<br><code>/wp-json/wpmcp/v1/</code>]
    end

    subgraph "Security & Permission Layer"
        C1[SHA-256 Token Auth & App Passwords]
        C2[Capability Gates & Nonces]
        C3[Audit Logger & 1-Click Rollbacks]
    end

    subgraph "WordPress Ecosystem Tools"
        D1[Content & Media]
        D2[Gutenberg Blocks & FSE]
        D3[Elementor Builder]
        D4[WooCommerce Store]
        D5[Cache & System Health]
    end

    A1 & A2 & A3 --> B1
    A4 & A5 --> B2
    B1 & B2 --> C1 --> C2 --> C3
    C3 --> D1 & D2 & D3 & D4 & D5
```

---

## ✨ Key Features

| Capability | Description |
|---|---|
| 🔌 **Universal MCP Compatibility** | Works seamlessly over **Server-Sent Events (SSE)**, **HTTP POST**, or **stdio bridge** (`bin/mcp-bridge.js`) with Claude Desktop, Cursor, Antigravity, Cline, Windsurf, and custom agent scripts. |
| 🤖 **In-Admin AI Copilot** | Sleek command palette (`Cmd+K` / `Ctrl+K`) and floating dock in WP-Admin. Works with **OpenAI (GPT-4o)**, **Anthropic (Claude 3.7)**, **Google Gemini (2.5 Flash/Pro)**, **OpenRouter**, or **Ollama (local)**. |
| 🧱 **Gutenberg & FSE Aware** | Parse raw HTML blocks into structured Abstract Syntax Trees (AST), inspect block attributes, list block patterns, and render server-side blocks. |
| 🎨 **Elementor Integration** | Extract, analyze, and safely update Elementor page builder data structures (`_elementor_data`) with automated layout backups. |
| 🛒 **WooCommerce Ready** | Query product catalog, stock counts, order status, and update product pricing and inventory via natural language prompts. |
| ⚡ **Performance & Caching** | Inspect server environment and site health, or purge caching engines (**WP Rocket**, **LiteSpeed Cache**, **W3 Total Cache**, **WP Super Cache**) on demand. |
| 🛡️ **Enterprise Safety & Reversibility** | Strict capability checks, SHA-256 hashed API keys, immutable audit logging for all actions, and **1-Click Rollback** for destructive operations. |

---

## 🚀 Quick Start in 3 Steps

### 1. Install Plugin
1. Download [`dist/wpmcp.zip`](https://github.com/syahmiahmaddev/wpmcp/raw/main/dist/wpmcp.zip) from this repository or release tags.
2. In your WordPress Admin: navigate to **Plugins → Add New → Upload Plugin → Choose File (`wpmcp.zip`) → Install & Activate**.

### 2. Configure Settings & Get Your Token
1. Go to **Settings → WP-MCP** in WP-Admin.
2. *(Optional for In-Admin Copilot)* Choose your preferred AI Provider (OpenAI, Anthropic, Gemini, OpenRouter, or Ollama) and save your API Key.
3. Scroll to **Remote MCP Access Tokens** and click **Generate New Token**. Copy your token string (`wpmcp_xxxxxxxxxxxxxxxxxxxx`).

### 3. Connect Any Client
Point your MCP client or AI agent to your WordPress site URL and token.

---

## 💻 Connecting Your MCP Clients

WP-MCP includes a lightweight, zero-dependency stdio bridge ([`bin/mcp-bridge.js`](bin/mcp-bridge.js)) that translates MCP stdio requests to WordPress JSON-RPC endpoints.

### 🔷 1. Google Antigravity (AGY)
Add the server definition to your Antigravity MCP settings:

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "node",
      "args": [
        "/path/to/wpmcp/bin/mcp-bridge.js",
        "--url", "https://your-wordpress-site.com",
        "--token", "YOUR_WPMCP_API_TOKEN"
      ]
    }
  }
}
```

---

### 🟣 2. Anthropic Claude Desktop
Edit your `claude_desktop_config.json`:
- **macOS:** `~/Library/Application Support/Claude/claude_desktop_config.json`
- **Windows:** `%APPDATA%\Claude\claude_desktop_config.json`

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "node",
      "args": [
        "/path/to/wpmcp/bin/mcp-bridge.js",
        "--url", "https://your-wordpress-site.com",
        "--token", "YOUR_WPMCP_API_TOKEN"
      ]
    }
  }
}
```

---

### 🟢 3. Cursor IDE
Open **Cursor Settings → Features → MCP → Add New MCP Server**:
- **Name:** `wordpress`
- **Type:** `command`
- **Command:** `node /path/to/wpmcp/bin/mcp-bridge.js --url https://your-wordpress-site.com --token YOUR_WPMCP_API_TOKEN`

---

### 🟠 4. Windsurf / VS Code (Cline, Continue, Roo Code)
Add to your extension's MCP configuration (`mcp_config.json`):

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "node",
      "args": [
        "/path/to/wpmcp/bin/mcp-bridge.js",
        "--url", "https://your-wordpress-site.com",
        "--token", "YOUR_WPMCP_API_TOKEN"
      ]
    }
  }
}
```

---

### 🐍 5. Python & Node.js AI Frameworks (LangChain, CrewAI, AutoGen)
Connect directly to the SSE or POST JSON-RPC endpoint:

```python
import asyncio
from mcp.client.sse import sse_client

async def main():
    headers = {
        "Authorization": "Bearer wpmcp_xxxxxxxxxxxxxxxxxxxx"
    }
    async with sse_client("https://your-wordpress-site.com/wp-json/wpmcp/v1/sse", headers=headers) as (read, write):
        # Call WordPress tools directly from Python
        print("Connected to WordPress MCP Server!")

asyncio.run(main())
```

---

## 🧰 Tools Catalog

WP-MCP ships with an extensive catalog of 25+ specialized tools:

### 📝 Content & Media Management
| Tool Name | Risk Level | Description |
|---|---|---|
| `wpmcp_get_posts` | `read` | Search and filter posts, pages, or custom post types by status, category, date, or author. |
| `wpmcp_get_post` | `read` | Retrieve full post content, custom fields (`post_meta`), and taxonomy terms. |
| `wpmcp_create_post` | `write` | Create a new post or page with title, Gutenberg/HTML content, excerpt, status, and tags. |
| `wpmcp_update_post` | `write` | Update post title, slug/permalink, content, status, custom meta, or categories. |
| `wpmcp_delete_post` | `destructive` | Move post to trash or permanently delete. |
| `wpmcp_manage_terms` | `write` | Create, list, or update categories, tags, and custom taxonomy terms. |
| `wpmcp_get_media` | `read` | Search media attachments in the Media Library by keyword or MIME type. |

### 🧱 Block Editor & Full Site Editing (FSE)
| Tool Name | Risk Level | Description |
|---|---|---|
| `wpmcp_parse_blocks` | `read` | Parse post block markup into structured JSON AST with inner blocks and attributes. |
| `wpmcp_render_blocks` | `read` | Render raw Gutenberg block arrays into clean WordPress HTML markup. |
| `wpmcp_list_block_patterns` | `read` | Discover available core, plugin, and theme block patterns. |
| `wpmcp_list_block_templates` | `read` | Query Full Site Editing (FSE) block templates and template parts. |

### 🎨 Elementor Page Builder
| Tool Name | Risk Level | Description |
|---|---|---|
| `wpmcp_elementor_get_data` | `read` | Inspect raw element tree, sections, columns, and widgets for any Elementor page. |
| `wpmcp_elementor_update_data` | `write` | Update Elementor JSON schema and regenerate CSS cache with automatic layout snapshotting. |

### 🛒 WooCommerce E-Commerce
| Tool Name | Risk Level | Description |
|---|---|---|
| `wpmcp_wc_get_products` | `read` | Search products by SKU, stock status, category, price range, or featured flag. |
| `wpmcp_wc_update_product` | `write` | Modify regular/sale prices, stock quantities, stock status, or descriptions. |
| `wpmcp_wc_get_orders` | `read` | Query recent orders with customer details, order totals, and fulfillment status. |

### ⚙️ Site Settings, Theme & Navigation
| Tool Name | Risk Level | Description |
|---|---|---|
| `wpmcp_get_site_info` | `read` | Fetch WordPress version, active theme, home URL, admin email, and locale. |
| `wpmcp_update_site_option` | `write` | Modify safe WordPress core options (`blogname`, `blogdescription`, `timezone_string`, etc.). |
| `wpmcp_get_custom_css` | `read` | Retrieve Customizer additional CSS rules. |
| `wpmcp_update_custom_css` | `write` | Safely append or replace Custom CSS stylesheets. |
| `wpmcp_get_nav_menus` | `read` | List all registered navigation menus, locations, and menu items. |
| `wpmcp_manage_nav_menu` | `write` | Create menus or add links, post links, and custom URLs to menus. |

### 🔧 System Diagnostics & Cache
| Tool Name | Risk Level | Description |
|---|---|---|
| `wpmcp_get_site_health` | `read` | Detailed diagnostics (PHP version, MySQL, memory limit, max upload, debug mode). |
| `wpmcp_list_plugins` | `read` | List all installed plugins, version numbers, and active status. |
| `wpmcp_toggle_plugin_state` | `destructive` | Safely activate or deactivate plugins with dependency protection. |
| `wpmcp_list_themes` | `read` | List installed themes, current theme, and child theme status. |
| `wpmcp_switch_theme` | `destructive` | Switch the active WordPress theme. |
| `wpmcp_purge_cache` | `write` | Clear object cache, transients, or third-party cache engines (WP Rocket, LiteSpeed, etc.). |

---

## 💬 Example Natural Language Prompts

Once connected in WP-Admin or from your coding agent:

<details>
<summary><b>📝 Content & Editorial</b></summary>

- *"Draft a comprehensive 1,500-word article on 'Modern Headless WordPress Architecture', assign categories 'Engineering' and 'Headless', and save as draft."*
- *"Find all published posts without featured images and return their IDs and titles."*
- *"Update the excerpt and tags for post ID 104."*
</details>

<details>
<summary><b>🎨 Styling & Theme Customization</b></summary>

- *"Inspect the current Customizer CSS, add a smooth gradient button class `.btn-gradient`, and append it safely."*
- *"Change the site tagline to 'Next-Gen AI Publishing' and verify the site URL."*
</details>

<details>
<summary><b>🛒 WooCommerce Store Management</b></summary>

- *"Find all products that are currently out of stock and list their SKU and supplier names."*
- *"Put product ID 312 on sale for $49.00 (regular price $69.00) starting today."*
- *"Show me the last 5 processing orders with customer shipping locations."*
</details>

<details>
<summary><b>🔧 Diagnostics & Site Maintenance</b></summary>

- *"Run a complete site health check and alert me if PHP memory limit is below 256MB."*
- *"Purge all WP Rocket and transient caches."*
- *"List all inactive plugins so we can review them for deletion."*
</details>

---

## 🛡️ Security & Safety First

WP-MCP is engineered with enterprise security principles:

1. **Native WordPress Capability Verification**: Every single tool call strictly checks capabilities (`manage_options`, `edit_posts`, `activate_plugins`, `publish_pages`). Users cannot exceed their assigned WordPress role privileges.
2. **Double-Layered Auth**:
   - **In-Admin:** Standard WordPress nonces (`wp_create_nonce`) prevent CSRF attacks.
   - **Remote MCP:** High-entropy Bearer tokens hashed with **SHA-256** at rest, or native WordPress Application Passwords.
3. **Immutable Audit Logging**: Every prompt, tool execution, payload parameter, IP address, user ID, and outcome is logged in `{prefix}wpmcp_audit_logs`.
4. **1-Click Rollback Manager**: High-risk actions (post revisions, site option updates, Custom CSS replacements) record automatic state snapshots before modification, enabling instant 1-click rollbacks directly from the WP-Admin UI.

---

## 📂 Project Structure

```
wpmcp/
├── admin/                      # WP-Admin Copilot UI, Settings & Styles
│   ├── assets/                 # CSS and JS for palette dock & Copilot
│   ├── views/                  # Settings, Audit Log, and Help pages
│   └── class-wpmcp-admin.php   # Admin controller & menus
├── bin/
│   ├── build-release.sh        # Clean release zip generator
│   └── mcp-bridge.js           # Zero-dependency stdio-to-HTTP bridge
├── includes/
│   ├── ai/                     # Multi-LLM client (OpenAI, Claude, Gemini, Ollama)
│   ├── mcp/                    # MCP Protocol server & REST controllers
│   ├── safety/                 # Audit logging & 1-Click Rollback manager
│   ├── tools/                  # 25+ modular WordPress tool implementations
│   ├── class-wpmcp-core.php    # Plugin core loader
│   └── class-wpmcp-security.php# Capability & token authentication engine
├── tests/                      # Automated test suite (22 scenarios)
├── wpmcp.php                   # Main plugin bootstrap
├── README.md                   # Documentation
└── CHANGELOG.md                # Release changelog
```

---

## 🤝 Contributing

Contributions, feature suggestions, and bug reports are welcome!
Please check out our [Contributing Guidelines](CONTRIBUTING.md) and [Security Policy](SECURITY.md).

```bash
# Clone the repository
git clone https://github.com/syahmiahmaddev/wpmcp.git

# Verify PHP syntax
find . -name "*.php" ! -path "./vendor/*" -exec php -l {} \;

# Build release zip
bash bin/build-release.sh
```

---

## 📜 License

This project is licensed under the **GNU General Public License v2.0 or later** - see the [LICENSE](LICENSE) file for details.

---

<div align="center">
  <sub>Built with ❤️ for the open WordPress and AI developer community.</sub>
</div>
