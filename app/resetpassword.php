<?php
    session_start();
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
        header('location: /index.php');
        die();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_GET['token']) || empty($_GET['token'])) {
            $invalid_token = true;
        } else {
            // Mã thông báo đến được băm trước khi tra cứu trong cơ sở dữ liệu vì cơ sở dữ liệu chỉ lưu trữ mã băm của mã thông báo. 
            // Điều này có nghĩa là ngay cả khi cơ sở dữ liệu bị rò rỉ, các mã thông báo đặt lại vẫn an toàn vì chúng không được lưu trữ ở dạng có thể sử dụng được.
            $token_hash = hash('sha256', $_GET['token']);
            include('includes/db_connect.php');
            $ret = pg_query_params($db, "select tid, uid from tokens where token = $1", array($token_hash));

            if (pg_num_rows($ret) === 0) {
                $invalid_token = true;
            }
        }
    }
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['token'])) {
            echo 'invalid request';
            die();
        }

        $token = $_POST['token'];
        $password1 = $_POST['password1'];
        $password2 = $_POST['password2'];

        if ($password1 !== $password2) {
            $pass_error = true;
        }
        else {
            include('includes/db_connect.php');
            // So sánh mã băm (token) với giá trị trong cơ sở dữ liệu, không bao giờ so sánh với token thô.
            $token_hash = hash('sha256', $token);
            $ret = pg_query_params($db, "select tid, uid from tokens where token = $1", array($token_hash));

            if (pg_num_rows($ret) === 0) {
                $invalid_token = true;
            } else {
                $uid = pg_fetch_row($ret)[1];
                $newpass = hash('sha256', $password1);

                $ret = pg_query_params($db, "update users set password = $1 where uid = $2", array($newpass, $uid));

                // Mã xác thực dùng một lần: sẽ bị xóa sau khi đặt lại mật khẩu thành công.
                $ret = pg_query_params($db, "delete from tokens where token = $1", array($token_hash));

                $success = true;
            }
        }
    }
?>

<html>
    <head>
        <title>TUDO/Reset Password</title>
        <link rel="stylesheet" href="style/style.css">
    </head>
    <body>
        <?php include('includes/header.php'); ?>
        <div id="content">
            <?php
                if (isset($invalid_token)) {
                    echo '<h1 style="color:red">Token is invalid.</h1>';
                    echo '<a href="#" onclick="history.back();return false">Go back</a>';
                    die();
                }
                
                if (isset($pass_error)) {
                    echo '<h1 style="color:red">Passwords don\'t match.</h1><br>';
                    echo '<a href="#" onclick="history.back();return false">Go back</a>';
                    die();
                }
            ?>
            <div id="content">
                <form class="center_form" action="resetpassword.php" method="POST">
                    <h1>Reset Password:</h1>
                    <input type="hidden" name="token" value="<?php echo $_GET['token']; ?>">
                    <input type="password" name="password1" placeholder="New password"><br><br>
                    <input type="password" name="password2" placeholder="Confirm password"><br><br>
                    <input type="submit" value="Change password"> 
                    <?php if (isset($success)){echo "<span style='color:green'>Password changed!</span>";} ?>
                    <br><br>
                    <?php include('includes/login_footer.php'); ?>
                </form>
            </div>
        </div>
    </body>
</html>