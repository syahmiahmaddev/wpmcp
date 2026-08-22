# Implementation Plan: WP-MCP (WordPress Prompt-to-Action & MCP Server Plugin)

## Overview
WP-MCP is a WordPress plugin that empowers WordPress administrators to manage, inspect, configure, and generate content for their WordPress sites directly through natural language prompts. It includes an in-admin Copilot UI (with floating command bar and chat dock) and an authenticated remote Model Context Protocol (MCP) server endpoint that connects directly to external AI tools like Claude Desktop, Cursor, and Antigravity.

---

## Architecture Decisions
1. **Separation of Tool Definition and Execution Transport**:
   - Tools are defined as independent, modular PHP classes adhering to `WPMCP_Tool_Interface`.
   - Both the internal In-Admin Copilot Agent and external Remote MCP Clients (via REST/SSE) query the same unified `WPMCP_Tool_Registry`.
2. **Multi-LLM Provider Flexibility**:
   - Client abstraction (`WPMCP_AI_Client`) supporting Anthropic Claude, OpenAI, Google Gemini, OpenRouter, and Ollama/Local OpenAI-compatible endpoints.
3. **Safety by Design**:
   - Risk categorization on tools: `read` (automatic execution), `write` (safe execution), `destructive` (explicit confirmation or dry-run required).
   - Audit logging with rollback capabilities for reversible state changes (posts, options, custom CSS).
4. **Standard WordPress Security**:
   - Strict capability checking (`manage_options`, `edit_posts`, etc.), Nonce verification for AJAX/REST, and Application Passwords / bearer token auth for MCP endpoints.

---

## Task List

### Phase 1: Foundation & Infrastructure
- [ ] Task 1: Plugin Bootstrap & Autoloader
- [ ] Task 2: Activation Lifecycle & Database Tables
- [ ] Task 3: Settings & API Configuration Management
- [ ] Task 4: Security & Permission Guard Framework

### Checkpoint 1: Foundation
- [ ] Plugin activates cleanly without PHP warnings.
- [ ] Settings page allows saving LLM keys and provider settings.

### Phase 2: Tool Registry & Core WordPress Tools
- [ ] Task 5: Extensible Tool Registry & Schema Builder
- [ ] Task 6: Content Management Tools (Posts, Pages, Media, Terms)
- [ ] Task 7: Site Configuration & Custom CSS Tools
- [ ] Task 8: Plugin & Theme Management Tools
- [ ] Task 9: Block & Template Inspection/Rendering Tools

### Checkpoint 2: Tool Registry
- [ ] All tools register with valid JSON Schema matching MCP & OpenAI formats.
- [ ] PHP unit/lint tests pass for all tool execution handlers.

### Phase 3: AI Agent Orchestrator & In-Admin Copilot UI
- [ ] Task 10: Multi-Provider AI Client & Request Normalizer
- [ ] Task 11: Agent Orchestrator & Multi-Step Reasoning Loop
- [ ] Task 12: Admin Copilot UI (`Cmd+K` Command Bar + Floating Chat Dock)
- [ ] Task 13: Interactive Tool Cards & Preview/Confirmation UI

### Checkpoint 3: In-Admin AI Agent
- [ ] Admin can open Copilot via `Cmd+K` or Admin Bar.
- [ ] Admin can prompt AI to create a post or update site settings, and the agent executes the tool and reports back.

### Phase 4: Remote MCP Server Protocol
- [ ] Task 14: MCP REST/SSE Protocol Endpoints (`/sse`, `/messages`)
- [ ] Task 15: External Client Authentication (Application Passwords & MCP API Tokens)
- [ ] Task 16: MCP Resources and Prompts Endpoints

### Checkpoint 4: Remote MCP Server
- [ ] External MCP client (Claude Desktop / Cursor) can connect and list/call WordPress tools.

### Phase 5: Safety, Audit Logging & Rollback
- [ ] Task 17: Audit Logger Table & Execution History
- [ ] Task 18: Rollback Engine for Content and Options
- [ ] Task 19: User Documentation & Example Prompts

### Checkpoint 5: Final Verification & Polish
- [ ] Complete end-to-end testing of both in-admin and remote MCP workflows.
- [ ] All code adheres to WordPress coding standards and security practices.

---

## Risks and Mitigations
| Risk | Impact | Mitigation |
|------|--------|------------|
| Destructive AI Actions (deleting posts, breaking theme) | High | Tool risk classification (`destructive`) requiring explicit admin confirmation, plus audit logging & revision rollbacks. |
| Unauthorized Remote Execution | High | Require WordPress Application Passwords or high-entropy dedicated MCP tokens with granular capability checks. |
| LLM Provider Rate Limits or Timeouts | Medium | Configurable model fallback, timeout handling, and chunked streaming in Copilot UI. |
| Incompatible WordPress Versions or Blocks | Low | Guard against block parse errors and verify compatibility across WP 6.0+. |

---

## Open Questions
- Do you have a preferred default AI provider (OpenAI, Anthropic Claude, Google Gemini, OpenRouter, or Ollama for local)?
- Would you like any specific third-party integration tools out of the box (e.g. WooCommerce products/orders, Yoast/RankMath SEO)?
