// container-test.js
const os = require('os');
const fs = require('fs');
const path = require('path');

console.log('=== NODE ENVIRONMENT CHECK ===');
console.log(`Hostname: ${os.hostname()}`);
console.log(`Platform: ${os.platform()}`);
console.log(`Node version: ${process.version}`);
console.log(`Current directory: ${process.cwd()}`);
console.log(`Environment: ${process.env.NODE_ENV || 'not set'}`);

// Check for Docker-specific files
const dockerFiles = [
    '/.dockerenv',
    '/proc/1/cgroup'
];

console.log('\nDocker indicators:');
dockerFiles.forEach(file => {
    try {
        const exists = fs.existsSync(file);
        if (exists && file === '/proc/1/cgroup') {
            const content = fs.readFileSync(file, 'utf8');
            const containsDocker = content.includes('docker') || content.includes('container');
            console.log(`${file}: exists, contains docker/container references: ${containsDocker}`);
        } else {
            console.log(`${file}: ${exists ? 'exists' : 'does not exist'}`);
        }
    } catch (error) {
        console.log(`${file}: error checking - ${error.message}`);
    }
});

// List environment variables useful for container detection
console.log('\nRelevant environment variables:');
['HOSTNAME', 'CONTAINER_NAME', 'DOCKER_CONTAINER', 'DOCKER_HOST', 'CHOKIDAR_USEPOLLING'].forEach(envVar => {
    console.log(`${envVar}: ${process.env[envVar] || 'not set'}`);
});