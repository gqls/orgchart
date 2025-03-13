<!-- Create a file: resources/views/test-login.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Test Login</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<h1>Test Login</h1>

<form id="loginForm">
    <div>
        <label>Email:</label>
        <input type="email" id="email" value="admin@example.com">
    </div>
    <div>
        <label>Password:</label>
        <input type="password" id="password" value="password">
    </div>
    <button type="submit">Login</button>
</form>

<div>
    <h2>Test Authentication</h2>
    <button id="testAuth">Test Auth</button>
    <pre id="result"></pre>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        try {
            // Get CSRF cookie
            await fetch('/sanctum/csrf-cookie', {
                method: 'GET',
                credentials: 'include'
            });

            // Login
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    email: document.getElementById('email').value,
                    password: document.getElementById('password').value
                }),
                credentials: 'include'
            });

            const data = await response.json();
            document.getElementById('result').textContent = JSON.stringify(data, null, 2);

            // Save token
            if (data.access_token) {
                localStorage.setItem('token', data.access_token);
            }
        } catch (error) {
            document.getElementById('result').textContent = 'Error: ' + error.message;
        }
    });

    document.getElementById('testAuth').addEventListener('click', async function() {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch('/api/user', {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json'
                },
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                document.getElementById('result').textContent = 'Auth Success: ' + JSON.stringify(data, null, 2);
            } else {
                document.getElementById('result').textContent = 'Auth Failed: ' + response.status;
            }
        } catch (error) {
            document.getElementById('result').textContent = 'Error: ' + error.message;
        }
    });
</script>
</body>
</html>