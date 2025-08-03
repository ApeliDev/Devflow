<?php
// User's password input
$password = 'apeli';

// Hash the password using the default algorithm (currently BCRYPT)
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Output the hashed password
echo $hashedPassword;
?>
