// Function to perform the login and retrieve the token
function loginUser(username, password, onSuccess, onError) {
    fetch('http://localhost:8080/api/login_check', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ username, password })
    })
        .then(response => response.json())
        .then(onSuccess)
        .catch(onError);
}

// Function to fetch user data with the token
function fetchUserData(userId, token, onSuccess, onError) {
    fetch(`http://localhost:8080/api/user/${userId}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
        }
    })
        .then(response => response.json())
        .then(onSuccess)
        .catch(onError);
}

function fetchRoomData(roomId, token, onSuccess, onError) {
    fetch(`http://localhost:8080/api/room/${roomId}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
        }
    })
        .then(response => response.json())
        .then(onSuccess)
        .catch(onError);
}

function showUserProfilePreview(userData, linkElement) {
    const preview = document.getElementById('profile-preview');
    preview.innerHTML = `
        <p>Name: ${userData.firstName} ${userData.secondName}</p>
        <p>Username: ${userData.username}</p>
    `;
    preview.style.display = 'block';
    preview.style.left = `${linkElement.getBoundingClientRect().right + 10}px`;
    preview.style.top = `${linkElement.getBoundingClientRect().top}px`;
}

function hideUserProfilePreview() {
    const preview = document.getElementById('profile-preview');
    preview.style.display = 'none';
}

function showRoomPreview(roomData, linkElement) {
    const preview = document.getElementById('profile-preview');
    preview.innerHTML = `
        <p>Room name: ${roomData.name}</p>
        <p>Room code: ${roomData.buildingCode}:${roomData.code}</p>
        <p>Is private: ${roomData.isPrivate ? 'Yes' : 'No'}</p>
    `;
    preview.style.display = 'block';
    preview.style.position = 'absolute';
    preview.style.left = `${linkElement.getBoundingClientRect().right + 10}px`;
    preview.style.top = `${linkElement.getBoundingClientRect().top}px`;
}

function hideRoomPreview() {
    const preview = document.getElementById('profile-preview');
    preview.style.display = 'none';
}


document.addEventListener('DOMContentLoaded', function () {
    loginUser('admin', 'admin', (loginResponse) => {
        const token = loginResponse.token;

        const roomLinks = document.querySelectorAll('.room-link');

        roomLinks.forEach(function (link) {
            link.addEventListener('mouseenter', function () {
                const roomId = this.getAttribute('data-room-id');

                fetchRoomData(roomId, token,
                    (data) => showRoomPreview(data, link),
                    (error) => console.error('Error:', error)
                );
            });

            link.addEventListener('mouseleave', hideRoomPreview);
        });
    }, (error) => {
        console.error('Login Error:', error);
    });
});


document.addEventListener('DOMContentLoaded', function () {
    loginUser('admin', 'admin', (loginResponse) => {
        const token = loginResponse.token;

        const usernameLinks = document.querySelectorAll('.username-link');

        usernameLinks.forEach(function (link) {
            link.addEventListener('mouseenter', function () {
                const userId = this.getAttribute('data-user-id');

                fetchUserData(userId, token,
                    (data) => showUserProfilePreview(data, link),
                    (error) => console.error('Error:', error)
                );
            });

            link.addEventListener('mouseleave', hideUserProfilePreview);
        });
    }, (error) => {
        console.error('Login Error:', error);
    });
});
