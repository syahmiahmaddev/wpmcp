/**
 * WP-MCP Copilot Client Controller
 */

(function ($) {
	'use strict';

	const WPMCP = {
		history: [],
		isOpen: false,
		isProcessing: false,
		pendingConfirmation: null,

		init: function () {
			this.bindEvents();
			this.initSettingsPage();
			this.initAuditLogPage();
		},

		bindEvents: function () {
			const self = this;

			// Global Cmd+K / Ctrl+K keyboard shortcut
			$(document).on('keydown', function (e) {
				if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
					e.preventDefault();
					self.toggleDrawer();
				}
				if (e.key === 'Escape' && self.isOpen) {
					self.closeDrawer();
				}
			});

			// Dock launcher click
			$('#wpmcp-dock-launcher').on('click', function () {
				self.toggleDrawer();
			});

			// Close button & backdrop click
			$('#wpmcp-btn-close, .wpmcp-drawer-backdrop').on('click', function () {
				self.closeDrawer();
			});

			// Clear chat button
			$('#wpmcp-btn-clear').on('click', function () {
				self.clearChat();
			});

			// Prompt submission form
			$('#wpmcp-chat-form').on('submit', function (e) {
				e.preventDefault();
				const input = $('#wpmcp-prompt-input');
				const prompt = input.val().trim();
				if (prompt && !self.isProcessing) {
					input.val('');
					self.sendPrompt(prompt);
				}
			});

			// Quick prompt pills
			$(document).on('click', '.wpmcp-pill', function () {
				const prompt = $(this).data('prompt');
				if (prompt && !self.isProcessing) {
					self.sendPrompt(prompt);
				}
			});

			// Auto-resize input textarea
			$('#wpmcp-prompt-input').on('input', function () {
				this.style.height = 'auto';
				this.style.height = (this.scrollHeight) + 'px';
			}).on('keydown', function (e) {
				if (e.key === 'Enter' && !e.shiftKey) {
					e.preventDefault();
					$('#wpmcp-chat-form').trigger('submit');
				}
			});

			// Expose global toggle
			window.wpmcpToggleCopilot = function () {
				self.toggleDrawer();
			};
		},

		toggleDrawer: function () {
			if (this.isOpen) {
				this.closeDrawer();
			} else {
				this.openDrawer();
			}
		},

		openDrawer: function () {
			this.isOpen = true;
			$('#wpmcp-drawer-container').removeClass('wpmcp-drawer-closed').addClass('wpmcp-drawer-open');
			$('#wpmcp-prompt-input').focus();
		},

		closeDrawer: function () {
			this.isOpen = false;
			$('#wpmcp-drawer-container').removeClass('wpmcp-drawer-open').addClass('wpmcp-drawer-closed');
		},

		clearChat: function () {
			this.history = [];
			$('#wpmcp-chat-messages').html(`
				<div class="wpmcp-message wpmcp-message-assistant">
					<div class="wpmcp-msg-avatar">🤖</div>
					<div class="wpmcp-msg-bubble">
						<p>Chat cleared. What would you like to do next?</p>
						<div class="wpmcp-prompt-pills">
							<button class="wpmcp-pill" data-prompt="Draft a blog post about 'Top 5 Trends in Web Design'">✍️ Draft a blog post</button>
							<button class="wpmcp-pill" data-prompt="Check site health diagnostics">🩺 Check site health</button>
							<button class="wpmcp-pill" data-prompt="List all installed plugins">🔌 List plugins</button>
						</div>
					</div>
				</div>
			`);
		},

		sendPrompt: function (prompt, confirmedAction = null) {
			const self = this;
			self.isProcessing = true;
			$('#wpmcp-send-btn').prop('disabled', true);

			if (!confirmedAction) {
				self.appendMessage('user', prompt);
			}

			const loadingId = 'wpmcp-loading-' + Date.now();
			self.appendLoadingMessage(loadingId);

			const payload = {
				prompt: prompt,
				history: self.history,
				confirmed_action: confirmedAction,
			};

			$.ajax({
				url: wpmcpData.restUrl + 'chat',
				method: 'POST',
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', wpmcpData.nonce);
				},
				contentType: 'application/json',
				data: JSON.stringify(payload),
				success: function (res) {
					$('#' + loadingId).remove();
					self.isProcessing = false;
					$('#wpmcp-send-btn').prop('disabled', false);

					if (!res.success && res.error) {
						self.appendMessage('assistant', '⚠️ **Error**: ' + res.error);
						return;
					}

					// Check if confirmation is required
					if (res.require_confirmation && res.pending_action) {
						self.renderConfirmationPrompt(prompt, res.pending_action, res.executed_tools);
						return;
					}

					// Render executed tools if any
					if (res.executed_tools && res.executed_tools.length > 0) {
						self.renderExecutedTools(res.executed_tools);
					}

					// Render final text response
					if (res.answer) {
						self.appendMessage('assistant', res.answer);
						self.history.push({ role: 'user', content: prompt });
						self.history.push({ role: 'assistant', content: res.answer });
					}
				},
				error: function (xhr) {
					$('#' + loadingId).remove();
					self.isProcessing = false;
					$('#wpmcp-send-btn').prop('disabled', false);

					const err = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Server communication failed.';
					self.appendMessage('assistant', '⚠️ **Request Failed**: ' + err);
				}
			});
		},

		appendMessage: function (role, content) {
			const avatar = role === 'user' ? '👤' : '🤖';
			const formattedHtml = this.formatMarkdown(content);

			const html = `
				<div class="wpmcp-message wpmcp-message-${role}">
					<div class="wpmcp-msg-avatar">${avatar}</div>
					<div class="wpmcp-msg-bubble">
						${formattedHtml}
					</div>
				</div>
			`;

			const container = $('#wpmcp-chat-messages');
			container.append(html);
			container.scrollTop(container[0].scrollHeight);
		},

		appendLoadingMessage: function (id) {
			const html = `
				<div class="wpmcp-message wpmcp-message-assistant" id="${id}">
					<div class="wpmcp-msg-avatar">🤖</div>
					<div class="wpmcp-msg-bubble">
						<span class="wpmcp-spinner"></span> <em>Thinking and inspecting WordPress...</em>
					</div>
				</div>
			`;
			const container = $('#wpmcp-chat-messages');
			container.append(html);
			container.scrollTop(container[0].scrollHeight);
		},

		renderExecutedTools: function (tools) {
			let toolsHtml = '<div class="wpmcp-executed-tools">';
			tools.forEach(t => {
				const isSuccess = t.result && t.result.success;
				const statusClass = isSuccess ? 'wpmcp-tool-card-success' : 'wpmcp-tool-card-error';
				const statusIcon = isSuccess ? '✅' : '❌';
				const message = (t.result && t.result.message) ? t.result.message : (t.result && t.result.error ? t.result.error : 'Tool executed');

				toolsHtml += `
					<div class="wpmcp-tool-card ${statusClass}">
						<div class="wpmcp-tool-card-header">
							<span>${statusIcon} <code>${t.tool || t.name}</code></span>
						</div>
						<div>${this.escapeHtml(message)}</div>
					</div>
				`;
			});
			toolsHtml += '</div>';

			const container = $('#wpmcp-chat-messages');
			container.append(toolsHtml);
			container.scrollTop(container[0].scrollHeight);
		},

		renderConfirmationPrompt: function (prompt, action, executedTools) {
			const self = this;
			if (executedTools && executedTools.length > 0) {
				self.renderExecutedTools(executedTools);
			}

			const confirmId = 'wpmcp-confirm-' + Date.now();
			const html = `
				<div class="wpmcp-message wpmcp-message-assistant" id="${confirmId}">
					<div class="wpmcp-msg-avatar">⚠️</div>
					<div class="wpmcp-msg-bubble">
						<div class="wpmcp-confirmation-box">
							<strong>Action Confirmation Required:</strong>
							<p>The AI is about to execute destructive tool <code>${this.escapeHtml(action.name)}</code>.</p>
							<p><em>${this.escapeHtml(action.description)}</em></p>
							<pre style="background:#fff; padding:6px; font-size:11px; border:1px solid #ddd; border-radius:4px;">${this.escapeHtml(JSON.stringify(action.arguments, null, 2))}</pre>
							<div class="wpmcp-confirmation-actions">
								<button class="button button-primary wpmcp-btn-confirm-action">Confirm & Run</button>
								<button class="button wpmcp-btn-cancel-action">Cancel</button>
							</div>
						</div>
					</div>
				</div>
			`;

			const container = $('#wpmcp-chat-messages');
			container.append(html);
			container.scrollTop(container[0].scrollHeight);

			$('#' + confirmId + ' .wpmcp-btn-confirm-action').on('click', function () {
				$('#' + confirmId).remove();
				self.sendPrompt(prompt, action);
			});

			$('#' + confirmId + ' .wpmcp-btn-cancel-action').on('click', function () {
				$('#' + confirmId).remove();
				self.appendMessage('assistant', 'Action cancelled by user.');
			});
		},

		formatMarkdown: function (text) {
			if (!text) return '';
			let escaped = this.escapeHtml(text);

			// Code blocks
			escaped = escaped.replace(/```([a-z]*)\n([\s\S]*?)```/gm, '<pre class="wpmcp-code-block"><code>$2</code></pre>');
			// Inline code
			escaped = escaped.replace(/`([^`]+)`/g, '<code>$1</code>');
			// Bold
			escaped = escaped.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
			// Italic
			escaped = escaped.replace(/\*([^*]+)\*/g, '<em>$1</em>');
			// Line breaks to <p> / <br>
			escaped = escaped.replace(/\n\n/g, '</p><p>');
			escaped = escaped.replace(/\n/g, '<br>');

			return '<p>' + escaped + '</p>';
		},

		escapeHtml: function (str) {
			if (typeof str !== 'string') return '';
			return str.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');
		},

		initSettingsPage: function () {
			// Provider selector toggle visibility
			$('#ai_provider').on('change', function () {
				const selected = $(this).val();
				$('.provider-section').hide();
				$('#section-' + selected).show();
			}).trigger('change');

			// Test connection buttons
			$('.wpmcp-test-btn').on('click', function () {
				const btn = $(this);
				const provider = btn.data('provider');
				const statusSpan = btn.siblings('.wpmcp-test-status');

				let apiKey = '';
				let model = '';
				let endpoint = '';

				if (provider === 'openai') {
					apiKey = $('#openai_api_key').val();
					model = $('#openai_model').val();
				} else if (provider === 'anthropic') {
					apiKey = $('#anthropic_api_key').val();
					model = $('#anthropic_model').val();
				} else if (provider === 'gemini') {
					apiKey = $('#gemini_api_key').val();
					model = $('#gemini_model').val();
				} else if (provider === 'openrouter') {
					apiKey = $('#openrouter_api_key').val();
					model = $('#openrouter_model').val();
				}

				btn.prop('disabled', true);
				statusSpan.html('<span class="wpmcp-spinner"></span> Testing connection...');

				$.ajax({
					url: wpmcpData.restUrl + 'test-connection',
					method: 'POST',
					beforeSend: function (xhr) {
						xhr.setRequestHeader('X-WP-Nonce', wpmcpData.nonce);
					},
					contentType: 'application/json',
					data: JSON.stringify({ provider, api_key: apiKey, model, endpoint }),
					success: function (res) {
						btn.prop('disabled', false);
						statusSpan.html('<span style="color:#0f5132; font-weight:600;">✅ ' + (res.message || 'Connected!') + '</span>');
					},
					error: function (xhr) {
						btn.prop('disabled', false);
						const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Connection failed';
						statusSpan.html('<span style="color:#842029; font-weight:600;">❌ ' + msg + '</span>');
					}
				});
			});

			// Create Token Button
			$('#wpmcp-btn-create-token').on('click', function () {
				const name = $('#wpmcp-token-name').val().trim() || 'MCP Client';
				const btn = $(this);
				const display = $('#wpmcp-new-token-display');

				btn.prop('disabled', true);
				$.ajax({
					url: wpmcpData.restUrl + 'tokens',
					method: 'POST',
					beforeSend: function (xhr) {
						xhr.setRequestHeader('X-WP-Nonce', wpmcpData.nonce);
					},
					contentType: 'application/json',
					data: JSON.stringify({ name }),
					success: function (res) {
						btn.prop('disabled', false);
						if (res.success && res.token_data) {
							display.show().html(`
								<div class="notice notice-success" style="padding:10px; margin:0;">
									<strong>New MCP Token Generated:</strong><br>
									<code style="display:block; padding:6px; background:#fff; margin:6px 0; word-break:break-all;">${res.token_data.token}</code>
									<small style="color:#d63638;">Save this token now; it cannot be shown again.</small>
								</div>
							`);
						}
					},
					error: function () {
						btn.prop('disabled', false);
						alert('Failed to generate token.');
					}
				});
			});
		},

		initAuditLogPage: function () {
			$('.wpmcp-btn-rollback').on('click', function () {
				const btn = $(this);
				const logId = btn.data('log-id');
				if (!confirm('Are you sure you want to rollback this action?')) {
					return;
				}

				btn.prop('disabled', true).text('Reverting...');
				$.ajax({
					url: wpmcpData.restUrl + 'rollback',
					method: 'POST',
					beforeSend: function (xhr) {
						xhr.setRequestHeader('X-WP-Nonce', wpmcpData.nonce);
					},
					contentType: 'application/json',
					data: JSON.stringify({ log_id: logId }),
					success: function (res) {
						if (res.success) {
							alert(res.message || 'Action rolled back successfully.');
							location.reload();
						} else {
							alert('Rollback failed: ' + (res.error || 'Unknown error'));
							btn.prop('disabled', false).text('Undo');
						}
					},
					error: function (xhr) {
						const err = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Rollback failed.';
						alert('Error: ' + err);
						btn.prop('disabled', false).text('Undo');
					}
				});
			});
		}
	};

	$(document).ready(function () {
		WPMCP.init();
	});

})(jQuery);
