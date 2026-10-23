<?php
require_once 'config.php';
session_start();

// 初回アクセス時のみデータをリセット
if (!isset($_SESSION['initialized'])) {
    $pdo->query("DELETE FROM todo");
    $_SESSION['initialized'] = true;
}

// POSTリクエストの処理振り分け
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. CREATE (追加)
    if ($action === 'add') {
        $todo = trim($_POST['todo'] ?? '');
        if ($todo !== '') {
            $stmt = $pdo->prepare("INSERT INTO todo (user_id, title, status) VALUES (:user_id, :title, :status)");
            $stmt->execute([
                ':user_id' => 1,
                ':title' => $todo,
                ':status' => 0
            ]);
        }
    }
    
    // 2. UPDATE (完了状態の切り替え)
    elseif ($action === 'done') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            // 現在のステータスを取得して反転させる（0なら1に、1なら0に）
            $stmt = $pdo->prepare("SELECT status FROM todo WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current) {
                $newStatus = ($current['status'] == 1) ? 0 : 1;
                $updateStmt = $pdo->prepare("UPDATE todo SET status = :status WHERE id = :id");
                $updateStmt->execute([':status' => $newStatus, ':id' => $id]);
            }
        }
    }
    
    // 3. UPDATE (タイトルの編集)
    elseif ($action === 'edit') {
        $id = $_POST['id'] ?? null;
        $title = trim($_POST['title'] ?? '');
        if ($id && $title !== '') {
            $stmt = $pdo->prepare("UPDATE todo SET title = :title WHERE id = :id");
            $stmt->execute([':title' => $title, ':id' => $id]);
        }
    }

    // 4. DELETE (削除)
    elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM todo WHERE id = :id");
            $stmt->execute([':id' => $id]);
        }
    }

    // 二重送信防止のためリダイレクト
    header('Location: todo.php');
    exit;
}

// READ (一覧取得)
try {
    $stmt = $pdo->query("SELECT * FROM todo ORDER BY id DESC");
    $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $todos = [];
}
?>
  
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>todolist</title>
    <link rel="stylesheet" href="style.css">
   
</head>
<body>
    <header>
        <div class="hero">
            <nav>
                  <h2>Todo App</h2>
            <h2>Todo App (CRUD)</h2>
                <ul>
                    <li><a href="todo.php">Home</a></li>
                    <li><a href="rogin.html">Logout</a></li>
                </ul>
            </nav>
          
        </div>
    </header>
    
 

    <!-- Create: タスク追加フォーム -->
    <form action="todo.php" method="post" style="margin-bottom: 20px;">
        <input type="hidden" name="action" value="add">
        <input type="text" name="todo" placeholder="Add a new todo" autocomplete="off" required>
        <button type="submit">Add</button>
    </form>
    
    <!-- Read: タスク一覧表示 -->
    <ul>
    <?php foreach ($todos as $todo): ?>
        <?php 
            $doneClass = ($todo['status'] == 1) ? 'done' : '';
            $title = htmlspecialchars($todo['title'], ENT_QUOTES, 'UTF-8');
            $id = (int)$todo['id'];
        ?>
        <li>
            <span class="<?php echo $doneClass; ?>"><?php echo $title; ?></span>
            
            <!-- Update: Done / Undone 切り替えボタン -->
            <form action="todo.php" method="post" style="display:inline; margin-left: 10px;">
                <input type="hidden" name="action" value="done">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <button type="submit"><?php echo ($todo['status'] == 1) ? 'Undone' : 'Done'; ?></button>
            </form>

            <!-- Update (Edit): タイトル編集フォーム -->
            <form action="todo.php" method="post" style="display:inline; margin-left: 5px;">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <input type="text" name="title" value="<?php echo $title; ?>" required style="width: 100px;">
                <button type="submit">Update</button>
            </form>

            <!-- Delete: 削除ボタン -->
            <form action="todo.php" method="post" style="display:inline; margin-left: 5px;" onsubmit="return confirm('本当に削除しますか？');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <button type="submit" style="color: red;">Delete</button>
            </from>
        </li>
    <?php endforeach; ?>
    </ul>
    
</body>
</html>