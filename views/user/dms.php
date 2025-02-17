<?php
session_name("user_session");
session_start();
require '../../includes/db.php';

// Redirect if not authenticated
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fff;
        }

        .container {
            display: flex;
            height: 90vh;
            max-width: 900px;
            margin: auto;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }

        .sidebar {
            width: 30%;
            border-right: 1px solid #ccc;
            padding: 10px;
            overflow-y: auto;
            background: #f1f1f1;
        }

        .chat-container {
            width: 70%;
            display: flex;
            flex-direction: column;
            background: #fff;
            position: relative;
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
            background: #f1f1f1;
        }

        .input-group input {
            flex: 1;
            border: 1px solid #ccc;
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
            margin-bottom: 10px;
        }

        .me {
            background: #007bff;
            color: white;
            align-self: flex-end;
        }

        .other {
            background: #e0e0e0;
            color: #000;
            align-self: flex-start;
        }

        .timestamp {
            font-size: 10px;
            color: black;
            text-align: right;
            margin-top: 5px;
        }

        .user {
            padding: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
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
            padding: 8px;
            border-radius: 10px;
            width: 100%;
        }
    </style>
</head>
<body>

<?php include '../../includes/navbar.php'; ?>

<div class="container">
    <div class="sidebar">
        <input type="text" id="search-users" class="form-control mb-2" placeholder="Search users..." onkeyup="searchUsers()">
        <div id="users-list"></div>
    </div>

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
            let users = JSON.parse(data);
            let userList = '';
            users.forEach(user => {
                let profilePic = user.profile_pic ? user.profile_pic : '../../assets/default-avatar.png';
                userList += `<div class="user" data-id="${user.id}">
                                <img src="${profilePic}" class="profile-pic"> 
                                ${user.username}
                             </div>`;
            });
            $('#users-list').html(userList);
        });
    }

    function searchUsers() {
        let filter = $('#search-users').val().toLowerCase();
        $('.user').each(function() {
            let username = $(this).text().toLowerCase();
            if (username.includes(filter)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    function fetchMessages() {
        if (!chatUser) return;
        $.get(`dms_backend.php?action=fetch&with=${chatUser}`, function(data) {
            let messages = JSON.parse(data);
            let chatContent = '';
            messages.forEach(msg => {
                let className = (msg.sender_id == <?= $currentUser ?>) ? 'me' : 'other';
                chatContent += `<div class="message-bubble ${className}">
                                    ${msg.message}
                                    <div class="timestamp">${moment(msg.created_at).format('h:mm A')}</div>
                                </div>`;
            });
            $('#chat-messages').html(chatContent);
        });
    }

    $('#send-message').click(function() {
        let message = $('#message-input').val().trim();
        if (message === '' || !chatUser) return;

        $.post('dms_backend.php', { action: 'send', with: chatUser, message: message }, function() {
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
    fetchUsers();
</script>

</body>
</html>
