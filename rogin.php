<?php
require_once 'config.php';

session_start();
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
    echo "Invalid email format";
return;false; 
}

try {
    $sql = "SELECT * FROM rogin WHERE email = :email AND password = :password";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $_POST['email']);
    $stmt->bindParam(':password', $_POST['password']);
    $stmt->execute();
    echo "Login successful";

}
catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>