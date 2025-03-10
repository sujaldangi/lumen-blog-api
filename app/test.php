<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Push Notification Setup</title>
    <script type="module">
        // Import Firebase SDK (modular version)
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/9.6.1/firebase-app.js';
        import { getMessaging, getToken } from 'https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging.js';

        // Firebase configuration
        const firebaseConfig = {
            apiKey: "AIzaSyB7gZBD1Vw-lW0vPkC22vAuN8oqCcIZJHA",
            authDomain: "lumenapi-11b39.firebaseapp.com",
            projectId: "lumenapi-11b39",
            storageBucket: "lumenapi-11b39.firebasestorage.app",
            messagingSenderId: "220455378166",
            appId: "1:220455378166:web:ba8a176de522a48f60e0d7",
            measurementId: "G-SX11TL34RF",
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const messaging = getMessaging(app);

        // Request Permission for Push Notifications
        async function requestPermission() {
            try {
                await Notification.requestPermission();
                console.log('Notification permission granted.');
            } catch (error) {
                console.error('Notification permission denied.', error);
            }
        }

        // Get Firebase Device Token
        async function getDeviceToken() {
            console.log("YAjd")
            try {
                const token = await getToken(messaging, { vapidKey: 'BHctJ1-cs9u8_VVSuhsBGwFXLUpaz6apaBXutBuKrbTICYqI3ZJzo8zZv1_gfZtQ6W3sERouJj7T1pbrTlfAM5g' }); // You need to provide your VAPID key here
                document.getElementById('deviceToken').textContent = token;
                console.log('Device token:', token);

                // Send token to your Lumen API
                sendDeviceTokenToServer(token);
            } catch (error) {
                console.error('Error getting device token:', error);
            }
        }

        // Send device token to your Lumen API
        function sendDeviceTokenToServer(token) {
            fetch('/api/save-device-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ device_token: token }),
            })
            .then(response => response.json())
            .then(data => console.log('Device token saved:', data))
            .catch(error => console.error('Error saving device token:', error));
        }

        // Event listener for the button
        document.getElementById('getDeviceTokenButton').addEventListener('click', () => {
            console.log("S")
            requestPermission();
            getDeviceToken();
        });
    </script>
</head>
<body>
    <h1>Firebase Push Notification</h1>

    <button id="getDeviceTokenButton">Get Device Token</button>
    <p id="deviceToken"></p>
</body>
</html>
