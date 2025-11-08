<?php
$message = '';
$installed = file_exists('LocalSettings.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    $dbHost = $_POST['dbHost'] ?? 'localhost';
    $dbName = $_POST['dbName'] ?? '';
    $dbUser = $_POST['dbUser'] ?? '';
    $dbPassword = $_POST['dbPassword'] ?? '';
    $wikiName = $_POST['wikiName'] ?? '';
    $adminUser = $_POST['adminUser'] ?? '';
    $adminPassword = $_POST['adminPassword'] ?? '';

    $conn = new mysqli($dbHost, $dbUser, $dbPassword);
    if ($conn->connect_error) {
        $message = 'DB 연결 실패: ' . $conn->connect_error;
    } else {
        // DB 생성
        if ($conn->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci") === TRUE) {
            $conn->select_db($dbName);

            // 미디어위키 기본 테이블 생성 (단순화)
            $tables = [
                "CREATE TABLE IF NOT EXISTS user (
                    user_id INT AUTO_INCREMENT PRIMARY KEY,
                    user_name VARCHAR(255) NOT NULL UNIQUE,
                    user_password VARCHAR(255) NOT NULL,
                    user_email VARCHAR(255) DEFAULT '',
                    user_registration DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS page (
                    page_id INT AUTO_INCREMENT PRIMARY KEY,
                    page_title VARCHAR(255) NOT NULL UNIQUE,
                    page_content TEXT,
                    page_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS revision (
                    rev_id INT AUTO_INCREMENT PRIMARY KEY,
                    page_id INT NOT NULL,
                    rev_content TEXT,
                    rev_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (page_id) REFERENCES page(page_id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
            ];

            foreach ($tables as $sql) {
                $conn->query($sql);
            }

            // 관리자 계정 생성
            $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO user (user_name, user_password) VALUES (?, ?)");
            $stmt->bind_param("ss", $adminUser, $hash);
            $stmt->execute();

            // 기본 페이지 생성
            $pages = [
                ["Main Page", "여기는 $wikiName 메인 페이지입니다."],
                ["Help", "도움말 페이지입니다."],
                ["About", "$wikiName에 오신 것을 환영합니다."]
            ];

            $stmtPage = $conn->prepare("INSERT INTO page (page_title, page_content) VALUES (?, ?)");
            foreach ($pages as $p) {
                $stmtPage->bind_param("ss", $p[0], $p[1]);
                $stmtPage->execute();
            }

            // LocalSettings.php 생성
            $localSettings = "<?php\n";
            $localSettings .= "\$wgDBserver = '$dbHost';\n";
            $localSettings .= "\$wgDBname = '$dbName';\n";
            $localSettings .= "\$wgDBuser = '$dbUser';\n";
            $localSettings .= "\$wgDBpassword = '$dbPassword';\n";
            $localSettings .= "\$wgSitename = '$wikiName';\n";
            file_put_contents('LocalSettings.php', $localSettings);

            // 설치 완료 후 자동 새로고침
            header("Refresh:2; url=index.php");
            $message = "설치 완료! 잠시 후 사이트로 이동합니다...";
            $installed = true;
        } else {
            $message = 'DB 생성 실패: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MediaWiki 설치</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="mw-install-container">
    <h1>MediaWiki 설치</h1>
    <p>데이터베이스 정보와 관리자 계정을 입력해주세요.</p>

    <?php if($message): ?>
        <div id="mwResult"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if(!$installed): ?>
    <form method="POST" id="mwInstallForm">
        <fieldset>
            <legend>데이터베이스 설정</legend>
            <label for="dbHost">호스트 이름</label>
            <input type="text" id="dbHost" name="dbHost" value="localhost" required>

            <label for="dbName">데이터베이스 이름</label>
            <input type="text" id="dbName" name="dbName" required>

            <label for="dbUser">사용자 이름</label>
            <input type="text" id="dbUser" name="dbUser" required>

            <label for="dbPassword">비밀번호</label>
            <input type="password" id="dbPassword" name="dbPassword">
        </fieldset>

        <fieldset>
            <legend>사이트 설정</legend>
            <label for="wikiName">위키 이름</label>
            <input type="text" id="wikiName" name="wikiName" required>

            <label for="adminUser">관리자 사용자 이름</label>
            <input type="text" id="adminUser" name="adminUser" required>

            <label for="adminPassword">관리자 비밀번호</label>
            <input type="password" id="adminPassword" name="adminPassword" required>
        </fieldset>

        <button type="submit">설치 시작</button>
    </form>
    <?php else: ?>
        <p>이미 설치되어 있습니다. <a href="index.php">사이트로 이동</a></p>
        <hr>
        <h2>기본 페이지 목록</h2>
        <ul>
        <?php
        $conn = new mysqli($dbHost ?? 'localhost', $dbUser ?? '', $dbPassword ?? '', $dbName ?? '');
        if (!$conn->connect_error) {
            $res = $conn->query("SELECT page_title FROM page");
            while($row = $res->fetch_assoc()) {
                echo "<li>" . htmlspecialchars($row['page_title']) . "</li>";
            }
        }
        ?>
        </ul>
    <?php endif; ?>
</div>
<script src="script.js"></script>
</body>
</html>
