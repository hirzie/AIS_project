<?php
require_once '../../includes/guard.php';
require_login_and_module('finance');
header('Location: dashboard.php');
exit;
