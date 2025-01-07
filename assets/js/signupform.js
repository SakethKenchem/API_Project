function validateForm() {
    let isValid = true;

    // Validate email
    const email = document.getElementById('email');
    const emailError = document.getElementById('emailError');
    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!emailPattern.test(email.value)) {
        emailError.textContent = 'Please enter a valid email address.';
        isValid = false;
    } else {
        emailError.textContent = '';
    }

    // Validate username
    const username = document.getElementById('username');
    const usernameError = document.getElementById('usernameError');
    if (username.value.length < 3 || username.value.length > 20) {
        usernameError.textContent = 'Username must be between 3 and 20 characters.';
        isValid = false;
    } else {
        usernameError.textContent = '';
    }

    // Validate password
    const password = document.getElementById('password');
    const passwordError = document.getElementById('passwordError');
    if (password.value.length < 8) {
        passwordError.textContent = 'Password must be at least 8 characters long.';
        isValid = false;
    } else {
        passwordError.textContent = '';
    }

    return isValid;
}