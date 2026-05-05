<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $validfile = true;

            // --- CODE BAN ĐẦU (Vulnerable - Dùng Blacklist & MIME check) ---
            /*
            $is_check = getimagesize($_FILES['image']['tmp_name']);
            if ($is_check === false) {
                $validfile = false;
                echo 'Failed getimagesize<br>';
            }

            $illegal_ext = Array("php","pht","phtm","phtml","phpt","pgif","phps","php2","php3","php4","php5","php6","php7","php16","inc");
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($file_ext, $illegal_ext)) {
                $validfile = false;
                echo 'Illegal file extension<br>';
            }

            $allowed_mime = Array("image/gif","image/png","image/jpeg");
            $file_mime = $_FILES['image']['type'];
            if (!in_array($file_mime, $allowed_mime)) {
                $validfile = false;
                echo 'Illegal mime type<br>';
            }
            */

            // --- CODE ĐÃ SỬA (Secured - Dùng Whitelist, Đổi tên & PDO) ---
            
            // 1. Kiểm tra ảnh thật 
            $is_check = getimagesize($_FILES['image']['tmp_name']);
            if ($is_check === false) {
                $validfile = false;
                echo "Error: Invalid image file.<br>";
            }

            // 2. Sử dụng WHITELIST thay vì Blacklist 
            $allowed_ext = array("jpg", "jpeg", "png", "gif"); 
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], INFO_EXTENSION));
            if (!in_array($file_ext, $allowed_ext)) {
                $validfile = false;
                echo "Error: File format is not allowed.<br>";
            }

            if ($validfile) {
                // 3. Đổi tên file ngẫu nhiên (chặn RCE thực thi trực tiếp)
                $safe_name = bin2hex(random_bytes(10)) . '.' . $file_ext;
                $upload_path = '../images/' . $safe_name;

                $title = trim(strip_tags(isset($_POST['title']) ? $_POST['title'] : ''));

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    
                    include('../includes/db_connect.php');
                    
                    // --- ĐOẠN SQL CŨ ---
                    /*
                    $ret = pg_prepare($db, "createimage_query", "insert into motd_images (path, title) values ($1, $2)");
                    $ret = pg_execute($db, "createimage_query", array("images/$safe_name", $title));
                    */

                    // --- ĐOẠN SQL MỚI  ---
                    $stmt = $pdo->prepare("INSERT INTO motd_images (path, title) VALUES (?, ?)");
                    $stmt->execute(array("images/$safe_name", $title));

                    echo 'Success';
                } else {
                    echo 'Failed to move file';
                }
            }
        }
    }

    header('location:/admin/update_motd.php');
    die();
?>