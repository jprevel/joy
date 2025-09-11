#!/usr/bin/env node

// Script to start Playwright MCP server
const { spawn } = require('child_process');
const path = require('path');

const configPath = path.join(__dirname, 'playwright-mcp-config.json');

console.log('Starting Playwright MCP Server...');
console.log(`Config: ${configPath}`);

const mcpServer = spawn('npx', [
  '@executeautomation/playwright-mcp-server',
  '--config', configPath
], {
  stdio: 'inherit',
  cwd: __dirname
});

mcpServer.on('close', (code) => {
  console.log(`MCP server exited with code ${code}`);
});

mcpServer.on('error', (err) => {
  console.error('Failed to start MCP server:', err);
});