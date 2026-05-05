<?php
    session_start();
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
        header('location: /index.php');
        die();
    }

    // Giới hạn số lần thử lặp lại để làm chậm quá trình brute-force.
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

            // --- CODE BAN ĐẦU (Vulnerable - SQL Injection) ---
            // Code này cho phép kẻ tấn công chèn lệnh SQL trực tiếp qua biến $username.
            /*
            $query = "select uid from users where username = '" . $username . "' limit 1";
            pg_query($db, $query);
            */

            // --- CODE ĐÃ SỬA (Secured - Prepared Statements) ---
            // Truy vấn tham số hóa giúp ngăn chặn hoàn toàn SQL injection. 
            // Dữ liệu từ $username sẽ được xử lý như một chuỗi thuần túy, không phải lệnh thực thi.
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
                        // Thông báo chung chung để kẻ tấn công không thể xác nhận sự tồn tại của tài khoản (Prevent Information Enumeration).
                        echo "<span style='color:green'>If the account exists, recovery instructions have been sent.</span>";
                    }
                ?>
                <br><br>
                <?php include('includes/login_footer.php'); ?>
            </form>
        </div>
    </body>
</html>