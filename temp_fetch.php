<?php
$html = file_get_contents('http://localhost/portal/index_v2026.php');
file_put_contents('c:/Xampp/htdocs/portal/temp_portal.html', $html);
echo "Saved HTML";
?>
