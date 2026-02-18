<?php
// frontend/create_hashes.php
echo "Hash pour 'admin': " . password_hash('admin', PASSWORD_DEFAULT) . "<br>";
echo "Hash pour 'employe': " . password_hash('employe', PASSWORD_DEFAULT) . "<br>";
echo "<hr>";
echo "Copiez ces hashs dans phpMyAdmin";
?>