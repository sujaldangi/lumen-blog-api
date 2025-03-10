// Import the Firebase scripts
importScripts('https://www.gstatic.com/firebasejs/8.0.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.0.0/firebase-messaging.js');

// Initialize Firebase in the service worker
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
firebase.initializeApp(firebaseConfig);

// Retrieve an instance of Firebase Messaging
const messaging = firebase.messaging();

// Handle background notifications
messaging.setBackgroundMessageHandler(function(payload) {
    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: payload.notification.icon,
    };

    return self.registration.showNotification(notificationTitle, notificationOptions);
});
