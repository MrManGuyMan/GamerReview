<?php
session_start();
require_once 'functions/auth.php';

Auth::logout();
header('Location: index.php');
exit;