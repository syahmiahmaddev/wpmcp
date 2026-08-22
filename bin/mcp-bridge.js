#!/usr/bin/env node
/**
 * WP-MCP Universal stdio-to-HTTP/SSE Bridge
 *
 * Allows ANY stdio-based MCP client (Google Antigravity, Claude Desktop, Cursor,
 * Windsurf, Roo Code, Continue, Cline, custom agent frameworks) to connect
 * seamlessly to a remote or local WordPress site running the WP-MCP plugin.
 *
 * Usage:
 *   node bin/mcp-bridge.js --url https://example.com/wp-json/wpmcp/v1/messages --token wpmcp_xxxxxxxx
 *
 * Environment Variables:
 *   WPMCP_URL    - URL to WordPress MCP messages endpoint or site root
 *   WPMCP_TOKEN  - WP-MCP API Token or Application Password (username:password)
 */

const readline = require('readline');
const http = require('http');
const https = require('https');
const { URL } = require('url');

// Parse CLI arguments
const args = process.argv.slice(2);
let targetUrl = process.env.WPMCP_URL || '';
let authToken = process.env.WPMCP_TOKEN || '';

for (let i = 0; i < args.length; i++) {
	if (args[i] === '--url' && args[i + 1]) {
		targetUrl = args[i + 1];
		i++;
	} else if (args[i] === '--token' && args[i + 1]) {
		authToken = args[i + 1];
		i++;
	}
}

if (!targetUrl) {
	console.error('[WP-MCP Bridge Error] Missing required --url argument or WPMCP_URL environment variable.');
	process.exit(1);
}

// Normalize URL to /wp-json/wpmcp/v1/messages if needed
if (!targetUrl.includes('/wpmcp/v1/messages')) {
	if (targetUrl.endsWith('/sse')) {
		targetUrl = targetUrl.replace(/\/sse$/, '/messages');
	} else {
		targetUrl = targetUrl.replace(/\/+$/, '') + '/wp-json/wpmcp/v1/messages';
	}
}

const parsedUrl = new URL(targetUrl);
const client = parsedUrl.protocol === 'https:' ? https : http;

// Setup readline on stdin
const rl = readline.createInterface({
	input: process.stdin,
	output: process.stdout,
	terminal: false
});

rl.on('line', (line) => {
	const trimmed = line.trim();
	if (!trimmed) return;

	let jsonRpcReq;
	try {
		jsonRpcReq = JSON.parse(trimmed);
	} catch (e) {
		const errResp = {
			jsonrpc: '2.0',
			id: null,
			error: { code: -32700, message: 'Parse error: invalid JSON' }
		};
		process.stdout.write(JSON.stringify(errResp) + '\n');
		return;
	}

	forwardRpcMessage(jsonRpcReq);
});

function forwardRpcMessage(payload) {
	const postData = JSON.stringify(payload);

	const headers = {
		'Content-Type': 'application/json',
		'Content-Length': Buffer.byteLength(postData),
		'User-Agent': 'WP-MCP-Stdio-Bridge/1.0.0'
	};

	if (authToken) {
		if (authToken.includes(':')) {
			headers['Authorization'] = 'Basic ' + Buffer.from(authToken).toString('base64');
		} else {
			headers['Authorization'] = 'Bearer ' + authToken;
		}
	}

	const options = {
		hostname: parsedUrl.hostname,
		port: parsedUrl.port || (parsedUrl.protocol === 'https:' ? 443 : 80),
		path: parsedUrl.pathname + parsedUrl.search,
		method: 'POST',
		headers: headers
	};

	const req = client.request(options, (res) => {
		let rawBody = '';
		res.on('data', (chunk) => {
			rawBody += chunk;
		});

		res.on('end', () => {
			try {
				const jsonResp = JSON.parse(rawBody);
				process.stdout.write(JSON.stringify(jsonResp) + '\n');
			} catch (e) {
				const errResp = {
					jsonrpc: '2.0',
					id: payload.id || null,
					error: {
						code: -32603,
						message: 'Invalid response from WordPress server: ' + rawBody.substring(0, 200)
					}
				};
				process.stdout.write(JSON.stringify(errResp) + '\n');
			}
		});
	});

	req.on('error', (e) => {
		const errResp = {
			jsonrpc: '2.0',
			id: payload.id || null,
			error: {
				code: -32000,
				message: 'HTTP error communicating with WordPress: ' + e.message
			}
		};
		process.stdout.write(JSON.stringify(errResp) + '\n');
	});

	req.write(postData);
	req.end();
}
