
    function validateForm() {
        let isValid = true;

        // Clear previous error messages
        document.getElementById('emailError').textContent = '';
        document.getElementById('usernameError').textContent = '';
        document.getElementById('passwordError').textContent = '';

        // Get form input values
        const email = document.getElementById('email').value.trim();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();

        // Simple email validation (check if empty or not in a valid format)
        if (!email) {
            document.getElementById('emailError').textContent = 'Email is required.';
            isValid = false;
        } else if (!/\S+@\S+\.\S+/.test(email)) {
            document.getElementById('emailError').textContent = 'Please enter a valid email.';
            isValid = false;
        }

        // Simple username validation (check if empty or less than 3 characters)
        if (!username) {
            document.getElementById('usernameError').textContent = 'Username is required.';
            isValid = false;
        } else if (username.length < 3) {
            document.getElementById('usernameError').textContent = 'Username must be at least 3 characters long.';
            isValid = false;
        }

        // Simple password validation (check if empty or less than 6 characters)
        if (!password) {
            document.getElementById('passwordError').textContent = 'Password is required.';
            isValid = false;
        } else if (password.length < 6) {
            document.getElementById('passwordError').textContent = 'Password must be at least 6 characters long.';
            isValid = false;
        }

        return isValid;
    }

