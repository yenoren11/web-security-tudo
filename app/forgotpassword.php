<?php
    session_start();
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
        header('location: /index.php');
        die();
    }

    // Giới hạn số lượng yêu cầu đặt lại mỗi phiên để giảm thiểu việc lạm dụng tự động.
    function rate_limit_forgot_password() {
        $window_seconds = 300;
        $max_attempts = 5;
        $now = time();

        if (!isset($_SESSION['forgot_password_attempts'])) {
            $_SESSION['forgot_password_attempts'] = array();
        }

        $_SESSION['forgot_password_attempts'] = array_values(array_filter(
            $_SESSION['forgot_password_attempts'],
            function ($ts) use ($now, $window_seconds) {
                return ($now - $ts) < $window_seconds;
            }
        ));

        if (count($_SESSION['forgot_password_attempts']) >= $max_attempts) {
            return false;
        }

        $_SESSION['forgot_password_attempts'][] = $now;
        return true;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';

        if (!rate_limit_forgot_password()) {
            $rate_limited = true;
        } else {
            include('includes/db_connect.php');
            // Truy vấn tham số hóa giúp ngăn chặn tấn công SQL injection trong trường tên người dùng. Kết quả truy vấn được bỏ qua để tránh tiết lộ thông tin về việc tên người dùng tồn tại hay không.
            $ret = pg_query_params($db, "select uid from users where username = $1 limit 1", array($username));

            if (pg_num_rows($ret) === 1) {
                $uid = pg_fetch_row($ret)[0];

                include('includes/utils.php');
                $token = generateToken();
                // Chỉ lưu trữ mã băm của token đặt lại, do đó việc rò rỉ cơ sở dữ liệu sẽ không làm lộ các token có thể sử dụng được.
                $token_hash = hash('sha256', $token);

                // Chỉ giữ lại một mã đặt lại token đang hoạt động cho mỗi người dùng để giảm thiểu việc lạm dụng/lạm dụng.
                pg_query_params($db, "delete from tokens where uid = $1", array($uid));
                pg_query_params($db, "insert into tokens (uid, token) values ($1, $2)", array($uid, $token_hash));
            }

            // Luôn hiển thị cùng một câu trả lời để tránh việc liệt kê tên người dùng.
            $success = true;
        }
    }
?>

<html>
    <head>
        <title>TUDO/Forgot Password</title>
        <link rel="stylesheet" href="style/style.css">
    </head>
    <body>
        <?php include('includes/header.php'); ?>
        <div id="content">
            <form class="center_form" action="forgotpassword.php" method="POST">
                <h1>Forgot Password:</h1>
                <p>Please enter your username, and we will create a reset token that you can use to change your password. It will
                be sent to your email. Please check your spam just in case</p>
                <input name="username" placeholder="Username"><br><br>
                <input type="submit" value="Send Reset Token"> 
                <?php
                    if (isset($rate_limited)) {
                        echo "<span style='color:red'>Too many requests. Please try again in a few minutes.</span>";
                    } else if (isset($success)) {
                        echo "<span style='color:green'>If the account exists, a reset link has been sent.</span>";
                    }
                ?>
                <br><br>
                <?php include('includes/login_footer.php'); ?>
            </form>
        </div>
    </body>
</html>