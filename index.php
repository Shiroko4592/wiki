<?php
session_start();

// DB 설정
if(file_exists('LocalSettings.php')) {
    include 'LocalSettings.php';
} else {
    $installed = false;
}

$conn = new mysqli($wgDBserver ?? 'localhost', $wgDBuser ?? '', $wgDBpassword ?? '', $wgDBname ?? '');
if ($conn->connect_error) die("DB 연결 실패: " . $conn->connect_error);

// 로그인 처리
if(isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, user_password FROM user WHERE user_name=?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($uid, $hash);
    if($stmt->fetch() && password_verify($pass, $hash)) {
        $_SESSION['user_id'] = $uid;
        $_SESSION['username'] = $user;
    } else {
        $login_error = "로그인 실패!";
    }
    $stmt->close();
}

// 로그아웃 처리
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
}

// 페이지 보기/편집 처리
$page = $_GET['page'] ?? 'Main Page';
$action = $_GET['action'] ?? 'view';

if($action === 'edit' && isset($_POST['content'])) {
    $content = $_POST['content'];
    $stmt = $conn->prepare("SELECT page_id FROM page WHERE page_title=?");
    $stmt->bind_param("s", $page);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($pid);
    if($stmt->fetch()) {
        $stmt->close();
        $stmt = $conn->prepare("UPDATE page SET page_content=?, page_timestamp=NOW() WHERE page_id=?");
        $stmt->bind_param("si", $content, $pid);
        $stmt->execute();
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO page (page_title, page_content) VALUES (?, ?)");
        $stmt->bind_param("ss", $page, $content);
        $stmt->execute();
    }
    $stmt->close();
    header("Location: index.php?page=".urlencode($page));
    exit;
}

// 페이지 내용 가져오기
$stmt = $conn->prepare("SELECT page_content FROM page WHERE page_title=?");
$stmt->bind_param("s", $page);
$stmt->execute();
$stmt->bind_result($page_content);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($page); ?> - <?php echo htmlspecialchars($wgSitename ?? 'Wiki'); ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="mw-container">
    <header>
        <h1><a href="index.php"><?php echo htmlspecialchars($wgSitename ?? 'Wiki'); ?></a></h1>
        <nav>
            <?php if(isset($_SESSION['username'])): ?>
                안녕하세요, <?php echo htmlspecialchars($_SESSION['username']); ?> | <a href="index.php?logout=1">로그아웃</a>
            <?php else: ?>
                <form method="POST" class="login-form">
                    <input type="text" name="username" placeholder="사용자" required>
                    <input type="password" name="password" placeholder="비밀번호" required>
                    <button type="submit" name="login">로그인</button>
                </form>
                <?php if(isset($login_error)) echo '<span style="color:red;">'.$login_error.'</span>'; ?>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <?php if($action === 'edit' && isset($_SESSION['user_id'])): ?>
            <h2>편집: <?php echo htmlspecialchars($page); ?></h2>
            <form method="POST">
                <textarea name="content" rows="20"><?php echo htmlspecialchars($page_content); ?></textarea>
                <button type="submit">저장</button>
            </form>
            <p><a href="index.php?page=<?php echo urlencode($page); ?>">취소</a></p>
        <?php else: ?>
            <h2><?php echo htmlspecialchars($page); ?></h2>
            <div class="page-content"><?php echo nl2br(htmlspecialchars($page_content)); ?></div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <p><a href="index.php?page=<?php echo urlencode($page); ?>&action=edit">편집</a></p>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <aside>
        <h3>페이지 목록</h3>
        <ul>
        <?php
        $res = $conn->query("SELECT page_title FROM page");
        while($row = $res->fetch_assoc()) {
            echo '<li><a href="index.php?page='.urlencode($row['page_title']).'">'.htmlspecialchars($row['page_title']).'</a></li>';
        }
        ?>
        </ul>
        <?php if(isset($_SESSION['user_id'])): ?>
            <p><a href="index.php?action=edit&page=새 페이지">새 페이지 만들기</a></p>
        <?php endif; ?>
    </aside>
</div>
</body>
</html>
