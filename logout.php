<?php
session_start();
require_once 'auth.php';

Auth::logout();
header('Location: index.php');
exit;