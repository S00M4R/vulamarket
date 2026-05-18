<?php
require_once __DIR__ . '/../includes/helpers.php';
session_boot();
logout_user();
redirect('/auth/login.php');
