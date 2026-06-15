<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Real-Time Data Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-info { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .error { color: red; }
        .success { color: green; }
        .log { background: #000; color: #0f0; padding: 10px; margin: 10px 0; border-radius: 5px; font-family: monospace; }
    </style>
</head>
<body>
    <h1>Real-Time Data Debug</h1>
    
    <div class="debug-info">
        <h3>Authentication Status</h3>
        <p>User: {{ Auth::check() ? Auth::user()->name : 'Not logged in' }}</p>
        <p>User ID: {{ Auth::check() ? Auth::user()->id : 'N/A' }}</p>
        <p>CSRF Token: {{ csrf_token() }}</p>
    </div>

    <div class="debug-info">
        <h3>Test Real-Time Data</h3>
        <button onclick="testRealtimeData()">Test Module Data</button>
        <button onclick="testActiveModules()">Test Active Modules</button>
        <button onclick="clearLog()">Clear Log</button>
    </div>

    <div id="log" class="log"></div>

    <script>
        function log(message, type = 'info') {
            const logDiv = document.getElementById('log');
            const timestamp = new Date().toLocaleTimeString();
            const className = type === 'error' ? 'error' : type === 'success' ? 'success' : '';
            logDiv.innerHTML += `<div class="${className}">[${timestamp}] ${message}</div>`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        function clearLog() {
            document.getElementById('log').innerHTML = '';
        }

        async function testRealtimeData() {
            log('Testing real-time data endpoint...');
            
            try {
                const response = await fetch('/api/realtime/module-data', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        module: 'patients',
                        filters: {},
                        last_check: null
                    })
                });

                log(`Response status: ${response.status}`);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    log(`Error response: ${errorText}`, 'error');
                    return;
                }

                const data = await response.json();
                log(`Success: ${JSON.stringify(data, null, 2)}`, 'success');
                
            } catch (error) {
                log(`Error: ${error.message}`, 'error');
            }
        }

        async function testActiveModules() {
            log('Testing active modules endpoint...');
            
            try {
                const response = await fetch('/api/realtime/active-modules', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'same-origin'
                });

                log(`Response status: ${response.status}`);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    log(`Error response: ${errorText}`, 'error');
                    return;
                }

                const data = await response.json();
                log(`Success: ${JSON.stringify(data, null, 2)}`, 'success');
                
            } catch (error) {
                log(`Error: ${error.message}`, 'error');
            }
        }

        // Auto-test on page load
        window.onload = function() {
            log('Page loaded. User authentication status checked.');
            if ({{ Auth::check() ? 'true' : 'false' }}) {
                log('User is authenticated. You can test the endpoints.', 'success');
            } else {
                log('User is not authenticated. Please log in first.', 'error');
            }
        };
    </script>
</body>
</html>
