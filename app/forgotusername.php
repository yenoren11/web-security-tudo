<?php
    session_start();
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
        header('location: /index.php');
        die();
    }

    // Giới hạn số lần thử lặp lại để làm chậm quá trình đoán tên người dùng bằng phương pháp vét cạn.
    function rate_limit_forgot_username() {
        $window_seconds = 300;
        $max_attempts = 5;
        $now = time();

        if (!isset($_SESSION['forgot_username_attempts'])) {
            $_SESSION['forgot_username_attempts'] = array();
        }

        $_SESSION['forgot_username_attempts'] = array_values(array_filter(
            $_SESSION['forgot_username_attempts'],
            function ($ts) use ($now, $window_seconds) {
                return ($now - $ts) < $window_seconds;
            }
        ));

        if (count($_SESSION['forgot_username_attempts']) >= $max_attempts) {
            return false;
        }

        $_SESSION['forgot_username_attempts'][] = $now;
        return true;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';

        if (!rate_limit_forgot_username()) {
            $rate_limited = true;
        } else {
            include('includes/db_connect.php');
            // Truy vấn tham số hóa ngăn chặn tấn công SQL injection và cũng không tiết lộ thông tin về việc tên người dùng tồn tại hay không.
            pg_query_params($db, "select uid from users where username = $1 limit 1", array($username));
            $success = true;
        }
    }
?>

<html>
    <head>
        <title>TUDO/Forgot Username</title>
        <link rel="stylesheet" href="style/style.css">
    </head>
    <body>
        <?php include('includes/header.php'); ?>
        <div id="content">
            <form class="center_form" action="forgotusername.php" method="POST">
                <h1>Forgot Username:</h1>
                <p>Forgetting your username can be very frustrating. Unfortunately, we can't just list all the accounts out for everyone 
                to see. What we can do is let you look up your username guesses and we will check if they are in the system. Hopefully it 
                won't take you too long :(</p>
                <input name="username" placeholder="Username"><br><br>
                <input type="submit" value="Send Reset Token"> 
                <?php
                    if (isset($rate_limited)) {
                        echo "<span style='color:red'>Too many attempts. Please try again in a few minutes.</span>";
                    } else if (isset($success)) {
                        // Thông báo chung chung để kẻ tấn công không thể xác nhận xem tên người dùng có tồn tại hay không.
                        echo "<span style='color:green'>If the account exists, recovery instructions have been sent.</span>";
                    }
                ?>
                <br><br>
                <?php include('includes/login_footer.php'); ?>
            </form>
        </div>
    </body>
</html>