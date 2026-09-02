<?php
require_once __DIR__ . '/../includes/auth.php';
pjp_start_session();
pjp_logout();
pjp_redirect('login.php');
