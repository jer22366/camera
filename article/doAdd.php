<?php
require_once("./pdo_connect_camera.php");
$result = [];
// 取得標題和內容
$id = $_POST['id'];
$category_id = $_POST['category_id'];
$title = $_POST['title'];
$content = $_POST['content'];

// 在進行更新之前進行基本的資料驗證  
if (empty($id) || empty($category_id) || empty($title) || empty($content)) {  
  $result["status"] = "fail";  
  $result["message"] = "所有字段都必須填寫。";  
  echo json_encode($result);  
  exit; // 結束執行  
}  

try {
  // 準備 SQL 語句，使用 ? 作為佔位符
  $sql = "UPDATE article SET category_id = ?, title = ?, content = ? WHERE id = ?";
  $stmt = $pdo->prepare($sql);

  // 綁定參數並執行語句
  if ($stmt->execute([$category_id, $title, $content, $id])) {
    $result["status"] = "success";
    $result["message"] = "文章已成功更新。";
    // 更新成功後重定向到文章列表頁面
    header('Location: article.php');
    exit;
  } else {
    $result["status"] = "fail";
    $result["message"] = "更新文章時發生錯誤。";
    echo json_encode($result);
  }
} catch (PDOException $e) {
  $result["status"] = "error";
  $result["message"] = "資料庫連接失敗: " . $e->getMessage();
  echo json_encode($result);
}
?>