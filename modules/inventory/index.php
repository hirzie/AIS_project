<?php
require_once '../../includes/guard.php';
require_login_and_module('inventory');
header('Location: dashboard.php');
exit;
