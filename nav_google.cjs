const { spawn } = require('child_process');

console.log("Starting Chrome with remote debugging...");
// Start Chrome - This might fail if the path is wrong, but we'll try common Windows paths or just use 'start chrome'
spawn('cmd', ['/c', 'start chrome --remote-debugging-port=9222'], { shell: true });

setTimeout(() => {
    console.log("Starting MCP server...");
    const server = spawn('npx', ['-y', 'chrome-devtools-mcp@latest'], {
        shell: true,
        stdio: ['pipe', 'pipe', 'inherit']
    });

    let step = 0;

    server.stdout.on('data', (data) => {
        const response = data.toString();
        console.log(`Received: ${response}`);
        
        if (response.includes('protocolVersion') && step === 0) {
            step = 1;
            console.log("Success! Received valid initialize response.");
            const navigateRequest = {
                jsonrpc: "2.0",
                id: 2,
                method: "tools/call",
                params: {
                    name: "navigate_page",
                    arguments: {
                        url: "https://www.google.com"
                    }
                }
            };
            console.log("Sending navigate_page request...");
            server.stdin.write(JSON.stringify(navigateRequest) + '\n');
        } else if (response.includes('"id":2') && step === 1) {
            console.log("Success! Navigation command sent.");
            server.kill();
            process.exit(0);
        }
    });

    setTimeout(() => {
        console.log("Timeout waiting for response.");
        server.kill();
        process.exit(1);
    }, 15000);
}, 3000);
