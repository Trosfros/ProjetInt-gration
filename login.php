<?php
session_start();

$error = $_SESSION['login_error'] ?? null;
$success = $_SESSION['register_success'] ?? null;
$verifySuccess = $_SESSION['verify_success'] ?? null;
$verifyError = $_SESSION['verify_error'] ?? null;
$verificationLink = $_SESSION['verification_link'] ?? null;
unset($_SESSION['login_error'], $_SESSION['register_success'], $_SESSION['verify_success'], $_SESSION['verify_error'], $_SESSION['verification_link']);

$pageTitle = 'Connexion | NovaBank';
require __DIR__ . '/views/layout/header.php';
require __DIR__ . '/views/layout/auth/login.php';
require __DIR__ . '/views/layout/footer.php';
