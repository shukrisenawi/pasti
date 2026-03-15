const { spawn } = require('child_process');

console.log("Starting MCP server...");
const server = spawn('npx.cmd', ['-y', 'chrome-devtools-mcp@latest'], {
    stdio: ['pipe', 'pipe', 'inherit']
});

let output = '';

server.stdout.on('data', (data) => {
    output += data.toString();
    console.log(`Received: ${data.toString()}`);
    if (output.includes('protocolVersion') || output.includes('serverInfo')) {
        console.log("Success! Received valid initialize response.");
        server.kill();
        process.exit(0);
    }
});

server.stderr?.on('data', (data) => {
    console.error(`Error: ${data.toString()}`);
});

server.on('error', (err) => {
    console.error(`Spawn error: ${err}`);
});

server.on('close', (code) => {
    console.log(`Process exited with code ${code}`);
});

const initRequest = {
    jsonrpc: "2.0",
    id: 1,
    method: "initialize",
    params: {
        protocolVersion: "2024-11-05",
        capabilities: {},
        clientInfo: {
            name: "test-client",
            version: "1.0.0"
        }
    }
};

setTimeout(() => {
    console.log("Sending initialize request...");
    server.stdin.write(JSON.stringify(initRequest) + '\n');
}, 2000);

setTimeout(() => {
    console.log("Timeout waiting for response.");
    server.kill();
    process.exit(1);
}, 10000);
