const { spawn } = require('child_process');

console.log("Starting MCP server...");
const server = spawn('npx', ['-y', 'chrome-devtools-mcp@latest'], {
    shell: true,
    stdio: ['pipe', 'pipe', 'inherit']
});

let output = '';

server.stdout.on('data', (data) => {
    const response = data.toString();
    output += response;
    console.log(`Received: ${response}`);
    
    if (output.includes('protocolVersion')) {
        console.log("Success! Received valid initialize response.");
        const listToolsRequest = {
            jsonrpc: "2.0",
            id: 2,
            method: "tools/list",
            params: {}
        };
        console.log("Sending tools/list request...");
        server.stdin.write(JSON.stringify(listToolsRequest) + '\n');
    }
    
    if (output.includes('"tools":[')) {
        console.log("Success! Received tools list.");
        const listPagesRequest = {
            jsonrpc: "2.0",
            id: 3,
            method: "pages/list",
            params: {}
        };
        console.log("Sending pages/list request...");
        server.stdin.write(JSON.stringify(listPagesRequest) + '\n');
    }
    
    if (output.includes('"pages":[')) {
        console.log("Success! Received pages list.");
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
