<?php
    session_start();
    if (!isset($_SESSION['isadmin'])) {
        header('location: /index.php');
        die();
    }
    
    function e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // Sử dụng __DIR__ để đảm bảo đường dẫn chính xác bất kể thư mục làm việc của PHP là gì.
    $message_path = __DIR__ . "/../templates/motd_message.txt";
    $current_message = "";
    if (file_exists($message_path)) {
        $current_message = file_get_contents($message_path);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = isset($_POST['message']) ? $_POST['message'] : '';

        if ($message !== "") {
            // Không bao giờ ghi nội dung do người dùng kiểm soát vào tệp mẫu Smarty (SSTI/RCE).
            // Lưu trữ dưới dạng văn bản thuần và mã hóa khi xuất ra.
            $message = trim($message);
            $message = str_replace("\0", "", $message); // remove null bytes

            if (strlen($message) > 2000) {
                $message = substr($message, 0, 2000);
            }

            file_put_contents($message_path, $message, LOCK_EX);
            $current_message = $message;

            $success = "Message set!";
        } else {
            $error = "Empty message";
        }
    }
?>

<html>
    <head>
        <title>TUDO/Update MoTD</title>
        <link rel="stylesheet" href="../style/style.css">
    </head>
    <body>
        <?php 
            include('../includes/header.php'); 
            include('../includes/db_connect.php');
        ?>
        <div id="content">
            <form class="center_form" action="update_motd.php" method="POST">
                <h1>Update MoTD:</h1>
                Set a message that will be visible for all users when they log in.<br><br>
                <textarea name="message"><?php echo e($current_message); ?></textarea><br><br>
                <input type="submit" value="Update Message"> <?php if (isset($success)){echo '<span style="color:green">'.$success.'</span>';}
                else if (isset($error)){echo '<span style="color:red">'.$error.'</span>';}?>
            </form>
            <br>
            <form class="center_form" action="upload_image.php" method="POST" enctype="multipart/form-data">
                <h1>Upload Images:</h1>
                These images will display under the message of the day. <br><br>
                <input name="title" placeholder="Title" /><br><br>
                <input type="file" name="image" size="25" />
                <input type="submit" value="Upload Image">
            </form>
        </div>
    </body>
</html>