<?php
function logout() {
    header('WWW-Authenticate: Basic realm="Test Authentication System"');
    header('HTTP/1.0 401 Unauthorized');
    echo "You must enter a valid login ID and password to access this resource\n";
    exit;
}

if (isset($_SERVER['PHP_AUTH_USER']) ||
    header('HTTP/1.0 401 Unauthorized');
    print "Some Logout Content????<br>\n";
}
?>
