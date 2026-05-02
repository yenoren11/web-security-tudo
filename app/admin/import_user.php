<?php
    session_start();

    if (!isset($_SESSION['isadmin']) || $_SESSION['isadmin'] !== true) {
        header("location: /index.php");
        die();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($username !== "" && $password !== "") {
            include('../includes/db_connect.php');
            $ret = pg_prepare($db, "importuser_query", "insert into users (username, password, description) values ($1, $2, $3)");
            $ret = pg_execute($db, "importuser_query", array($username, $password, $description));
            header("location: /index.php");
            die();
        }
    }
?>