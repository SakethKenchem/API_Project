<?php
session_name("user_session");
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$currentUser = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Messages</title>
    <link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #fff;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            height: 100vh;
            max-width: 1000px;
            margin: auto;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }

        .sidebar {
            width: 25%;
            border-right: 1px solid #ccc;
            padding: 10px;
            overflow-y: auto;
            background: #f1f1f1;
        }

        .chat-container {
            width: 75%;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .messages {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .input-group {
            padding: 10px;
            border-top: 1px solid #ccc;
            display: flex;
            align-items: center;
            background: #f1f1f1;
        }

        .input-group input {
            flex: 1;
            background: #fff;
            border: 1px solid #ccc;
            color: #000;
            padding: 8px;
            border-radius: 10px;
        }

        .input-group button {
            margin-left: 10px;
            border-radius: 10px;
        }
        .profile-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 10px;
}


        .message-bubble {
            padding: 8px 12px;
            border-radius: 10px;
            max-width: 75%;
            word-wrap: break-word;
            font-size: 14px;
            position: relative;
        }

        .me {
            background: #007bff;
            align-self: flex-end;
            color: white;
        }

        .other {
            background: #e0e0e0;
            align-self: flex-start;
            color: #000;
        }

        .timestamp {
            font-size: 10px;
            color: #888;
            position: absolute;
            bottom: -15px;
            right: 10px;
        }

        .user {
            padding: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            color: #333;
        }

        .user:hover {
            background: #ddd;
        }

        .user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        #chat-title {
            padding: 10px;
            font-size: 16px;
            text-align: center;
            border-bottom: 1px solid #ccc;
            background: #f1f1f1;
        }

        #search-users {
            background: #fff;
            color: #000;
            border: 1px solid #ccc;
            padding: 8px;
            border-radius: 10px;
            width: 100%;
        }
    </style>
</head>

<body>

    <?php include '../../includes/navbar.php'; ?>

    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <input type="text" id="search-users" class="form-control mb-2" placeholder="Search users...">
            <div id="users-list"></div>
        </div>

        <!-- Chat -->
        <div class="chat-container">
            <div id="chat-title">Select a user to chat</div>
            <div class="messages" id="chat-messages"></div>
            <div class="input-group">
                <input type="text" id="message-input" class="form-control" placeholder="Type a message...">
                <button class="btn btn-primary" id="send-message">Send</button>
            </div>
        </div>
    </div>

    <script>
        let chatUser = null;

        function fetchUsers() {
    $.get('dms_backend.php?action=users', function(data) {
        const users = JSON.parse(data);
        let userList = '';
        users.forEach(user => {
            let profilePic = user.profile_pic ? `../../uploads/${user.profile_pic}` : '../../assets/default-avatar.png';
            userList += `<div class="user" data-id="${user.id}">
                            <img src="${profilePic}" alt="${user.username}" class="profile-pic">
                            <span>${user.username}</span>
                         </div>`;
        });
        $('#users-list').html(userList);
    });
}


        function fetchMessages() {
            if (!chatUser) return;
            $.get('dms_backend.php?action=fetch&with=' + chatUser, function(data) {
                const messages = JSON.parse(data);
                let chatContent = '';
                messages.forEach(msg => {
                    let className = (msg.sender_id == <?= $currentUser ?>) ? 'me' : 'other';
                    let timestamp = new Date(msg.timestamp).toLocaleString();
                    chatContent += `<div class="message-bubble ${className}">
                                        ${msg.message}
                                        <div class="timestamp">${timestamp}</div>
                                    </div>`;
                });
                $('#chat-messages').html(chatContent);
                $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
            });
        }

        $('#send-message').click(function() {
            let message = $('#message-input').val().trim();
            if (message === '' || !chatUser) return;

            $.post('dms_backend.php', {
                action: 'send',
                with: chatUser,
                message: message
            }, function() {
                $('#message-input').val('');
                fetchMessages();
            });
        });

        $(document).on('click', '.user', function() {
            chatUser = $(this).data('id');
            $('#chat-title').text($(this).text());
            fetchMessages();
        });

        setInterval(fetchMessages, 3000);

        $('#search-users').keyup(function() {
            let query = $(this).val();
            $.get('dms_backend.php?action=search&query=' + query, function(data) {
                $('#users-list').html('');
                JSON.parse(data).forEach(user => {
                    $('#users-list').append(`<div class="user" data-id="${user.id}">${user.username}</div>`);
                });
            });
        });

        fetchUsers();
    </script>

</body>
</html>
