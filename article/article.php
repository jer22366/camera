<?php
require_once 'pdo_connect_camera.php';




// 只在函數不存在時定義 truncate()，避免重複宣告
if (!function_exists('truncate')) {
    function truncate($text, $length = 150, $suffix = '...')
    {
        if (mb_strlen($text, 'UTF-8') > $length) {
            return mb_substr($text, 0, $length, 'UTF-8') . $suffix;
        }
        return $text;
    }
}

$current_category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;  
$categories = [  
    1 => '產品情報',  
    2 => '攝影技巧',  
    3 => '課程推薦',  
    4 => '文章分享',  
    5 => '攝影活動',  
    6 => '訊息公告'  
];  

// 日期中文顯示
if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false)
    {
        date_default_timezone_set('Asia/Taipei'); // 設定時區
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        // 計算各個單位的時間差
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        // 定義時間單位
        $string = array(
            'y' => '年',
            'm' => '個月',
            'w' => '週',
            'd' => '天',
            'h' => '小時',
            'i' => '分鐘',
            's' => '秒',
        );
        foreach ($string as $k => &$v) { //&$v是傳址
            if ($diff->$k) { //如果時間差有值
                $v = $diff->$k . $v . '前'; //diff->k是時間差 v是時間單位
            } else {
                unset($string[$k]); //如果時間差沒有值，就刪除
            }
        }
        // 返回結果
        if (!$full) $string = array_slice($string, 0, 1); //array_slice()函數從陣列中取出一段
        return $string ? reset($string) : '剛剛'; //reset()函數返回陣列中的第一個元素的值
    }
}

// 定義每頁顯示的項目數量
$per_page = 10;

// 取得當前頁數，預設為1
$p = isset($_GET['p']) ? (int)$_GET['p'] : 1;

// 計算偏移量
$offset = ($p - 1) * $per_page;

// 取得並過濾搜尋關鍵字
$search = isset($_GET['search']) ? trim($_GET['search']) : '';



// 獲取當前頁數，預設為第1頁  
$p = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;  
$p = max($p, 1); // 確保頁數不小於1  


// 獲取排序參數，預設為升冪  
$order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'desc' : 'asc';   
$sort_by = $_GET['sort'] ?? 'category_id'; // 默認按 category_id 排序  

// 根據用戶的選擇設置排序條件
switch ($sort_by) {
    case 'title':
        $order_by = "ORDER BY LENGTH(a.title) $order"; // 按標題長度排序
        break;
    case 'content':
        $order_by = "ORDER BY LENGTH(a.content) $order"; // 按內容長度排序
        break;
    case 'update_time':
        $order_by = "ORDER BY a.update_time $order"; // 按更新時間排序
        break;
    case 'is_deleted':
        $order_by = "ORDER BY a.is_deleted $order"; // 按文章狀態排序
        break;
    case 'category_id':
    default:
        $order_by = "ORDER BY a.category_id $order"; // 默認按 category_id 排序
        break;
}



// 在PHP文件頂部加入參數處理
$sort = $_GET['sort'] ?? 'update_time';
$order = $_GET['order'] ?? 'desc';
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? null;
$p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$per_page = 10;
$offset = ($p - 1) * $per_page;

// 驗證排序欄位白名單
$allowed_sort_fields = ['category_id', 'title', 'content', 'update_time', 'is_deleted'];
if (!in_array($sort, $allowed_sort_fields)) {
    $sort = 'update_time';
}

// 驗證排序方向
$order = strtolower($order) === 'asc' ? 'asc' : 'desc';

// 建立排序URL生成函數
if (!function_exists('buildSortUrl')) {
    function buildSortUrl($field) {
        global $sort, $order, $search, $category_id;
        $newOrder = ($sort === $field && $order === 'asc') ? 'desc' : 'asc';
        
        $params = [
            'sort' => $field,
            'order' => $newOrder
        ];
        
        if ($search) $params['search'] = $search;
        if ($category_id) $params['category_id'] = $category_id;
        
        return 'article.php?' . http_build_query($params);
    }
}



//搜尋功能

// 初始化變數，確保在所有情況下都有定義
$articles_page = [];
$articleCount = 0;
$total_pages = 1;

// 在PHP文件頂部加入參數處理
$sort = $_GET['sort'] ?? 'update_time';
$order = $_GET['order'] ?? 'desc';
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? null;
$p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$per_page = 10;
$offset = ($p - 1) * $per_page;

// 驗證排序欄位白名單
$allowed_sort_fields = ['category_id', 'title', 'content', 'update_time', 'is_deleted'];
if (!in_array($sort, $allowed_sort_fields)) {
    $sort = 'update_time';
}

// 驗證排序方向
$order = strtolower($order) === 'asc' ? 'asc' : 'desc';

// 建立排序URL生成函數
if (!function_exists('buildSortUrl')) {
    function buildSortUrl($field) {
        global $sort, $order, $search, $category_id;
        $newOrder = ($sort === $field && $order === 'asc') ? 'desc' : 'asc';
        
        $params = [
            'sort' => $field,
            'order' => $newOrder
        ];
        
        if ($search) $params['search'] = $search;
        if ($category_id) $params['category_id'] = $category_id;
        
        return 'article.php?' . http_build_query($params);
    }
}

try {
    // 基本 SQL 查詢
    $sql = "SELECT a.*, c.name as category_name  
            FROM article a  
            JOIN article_category c ON a.category_id = c.id 
            WHERE 1=1";

    $count_sql = "SELECT COUNT(*) 
                  FROM article a 
                  JOIN article_category c ON a.category_id = c.id 
                  WHERE 1=1";

    // 初始化參數陣列
    $params = [];

    // 篩選分類
    if ($category_id !== null) {  
        $sql .= " AND a.category_id = :category_id";  
        $count_sql .= " AND a.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    // 篩選搜尋關鍵字
    if (!empty($search)) {
        // 使用不同的參數名稱來避免重複
        $sql .= " AND (a.title LIKE :search1 OR a.content LIKE :search2)";
        $count_sql .= " AND (a.title LIKE :search1 OR a.content LIKE :search2)";
        $params[':search1'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
    }

    // 篩選刪除狀態
    if (isset($_GET['is_deleted'])) {
      $sql .= " AND a.is_deleted = 1 ";
      $count_sql .= " AND a.is_deleted = 1 ";
  } elseif (isset($_GET['search'])) {
      // 搜尋時包含所有文章，不添加 is_deleted 條件
  } elseif (!isset($_GET['category_id']) && !isset($_GET['search'])) {
      // 顯示所有項目，不添加 is_deleted 條件
  } else {
      $sql .= " AND a.is_deleted = 0 ";
      $count_sql .= " AND a.is_deleted = 0 ";
  }

    // 添加排序與分頁
    $sql .= " ORDER BY a.$sort $order LIMIT :limit OFFSET :offset";

    // 準備 SQL 查詢
    $stmt = $pdo->prepare($sql);

    // 綁定參數
    foreach ($params as $key => $value) {
        if ($key === ':category_id') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } elseif ($key === ':search1' || $key === ':search2') {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }

    // 綁定 LIMIT 和 OFFSET
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    // 執行查詢
    $stmt->execute();
    $articles_page = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 計算總數
    $count_stmt = $pdo->prepare($count_sql);
    
    // 只綁定已存在於 $count_sql 的參數
    foreach ($params as $key => $value) {
        if ($key === ':category_id') {
            $count_stmt->bindValue($key, $value, PDO::PARAM_INT);
        } elseif ($key === ':search1' || $key === ':search2') {
            $count_stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }
    
    // 執行計數查詢
    $count_stmt->execute();
    $articleCount = $count_stmt->fetchColumn();
    $total_pages = ceil($articleCount / $per_page);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $articleCount = 0;
}


$is_searching = !empty($search);



// 1. 在PHP文件頂部加入參數處理
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'update_time';
$order = isset($_GET['order']) ? $_GET['order'] : 'desc';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
$p = isset($_GET['p']) ? (int)$_GET['p'] : 1;

// 2. 驗證排序欄位白名單
$allowed_sort_fields = ['category_id', 'title', 'content', 'update_time', 'is_deleted'];
if (!in_array($sort, $allowed_sort_fields)) {
    $sort = 'update_time';// 默認按更新時間排序
}

// 3. 驗證排序方向
$order = strtolower($order) === 'asc' ? 'asc' : 'desc';

// 4. 建立排序URL生成函數
function buildSortUrl($field) {
    global $sort, $order, $search, $category_id;
    $newOrder = ($sort === $field && $order === 'asc') ? 'desc' : 'asc';
    
    $params = [
        'sort' => $field,
        'order' => $newOrder
    ];
    
    if ($search) $params['search'] = $search;
    if ($category_id) $params['category_id'] = $category_id;
    
    return 'article.php?' . http_build_query($params);
}

try {  
  // 構建 SQL 語句，僅包含分類、排序及分頁
  $sql = "SELECT a.*, c.name as category_name  
          FROM article a  
          JOIN article_category c ON a.category_id = c.id 
          WHERE 1=1";  

  if ($current_category_id !== null) {  
      $sql .= " AND a.category_id = :category_id";  
  }  

  if (isset($_GET['is_deleted'])) {
    $sql .= " AND a.is_deleted = 1 ";
} elseif (!isset($_GET['category_id']) && !isset($_GET['search'])) {
    // 不添加 is_deleted 條件，顯示所有項目
} else {
    $sql .= " AND a.is_deleted = 0 ";
}

  $sql .= " $order_by LIMIT :limit OFFSET :offset";  
  
  $stmt = $pdo->prepare($sql);  

  if ($current_category_id !== null) {  
      $stmt->bindValue(':category_id', $current_category_id, PDO::PARAM_INT);  
  }  

  // 計算起始項目  
  $start_item = ($p - 1) * $per_page;  
  $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);  
  $stmt->bindValue(':offset', $start_item, PDO::PARAM_INT);  
  $stmt->execute();  
  $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);  
} catch (PDOException $e) {  
  echo "資料撈取失敗: " . $e->getMessage();  
}  

// 5. 修改SQL查詢語句
$sql = "SELECT a.*, c.name as category_name 
        FROM articles a 
        LEFT JOIN categories c ON a.category_id = c.id 
        WHERE 1=1 ";

if ($category_id) {
    $sql .= " AND a.category_id = ? ";
}
if ($search) {
    $sql .= " AND (a.title LIKE ? OR a.content LIKE ?) ";
}


$sql .= " ORDER BY a.$sort $order 
          LIMIT ? OFFSET ?";


// 計算總文章數量
try {
    $countSql = "SELECT COUNT(*) as count FROM article";  
    if ($current_category_id !== null) {  
        $countSql .= " WHERE category_id = :category_id";  
    }
    $countStmt = $pdo->prepare($countSql);  
    if ($current_category_id !== null) {  
        $countStmt->bindValue(':category_id', $current_category_id, PDO::PARAM_INT);  
    }  
    $countStmt->execute();  
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);  
    $articleCount = $countResult['count'] ?? 0; // 指派計數  
} catch (PDOException $e) {
    echo "計算文章數量失敗: " . $e->getMessage();  
    $articleCount = 0; // 預設為0
}
$total_pages = ceil($articleCount / $per_page); // 計算總頁數



function getSearchResultCount($search) {
  global $pdo; // 確保可以訪問全局的 PDO 連接
  try {
      $searchSql = "SELECT COUNT(*) as count FROM article WHERE title LIKE :search_title OR content LIKE :search_content";
      $searchStmt = $pdo->prepare($searchSql);//準備查詢
      $searchStmt->bindValue(':search_title', '%' . $search . '%', PDO::PARAM_STR);//綁定參數
      $searchStmt->bindValue(':search_content', '%' . $search . '%', PDO::PARAM_STR);
      $searchStmt->execute();//執行查詢
      $searchResult = $searchStmt->fetch(PDO::FETCH_ASSOC);
      return $searchResult['count'] ?? 0;
  } catch (PDOException $e) {
      echo "計算搜尋結果數量失敗: " . $e->getMessage();
      return 0; // 預設為0
  }
}

function getTotalArticleCount() {
  global $pdo; // 確保可以訪問全局的 PDO 連接
  try {
      $countSql = "SELECT COUNT(*) as count FROM article";
      $countStmt = $pdo->prepare($countSql);
      $countStmt->execute();
      $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
      return $countResult['count'] ?? 0;
  } catch (PDOException $e) {
      echo "計算總文章數量失敗: " . $e->getMessage();
      return 0; // 預設為0
  }
}

// 確保在使用之前定義這個函數
if (!empty($search)) {
  $articleCountSearch = getSearchResultCount($search);
} else {
  $articleCount = getTotalArticleCount();
}


$conditions = [];
$params = [];


if (isset($_GET['category_id'])) {
    $conditions[] = 'category_id = :category_id';
    $params[':category_id'] = $_GET['category_id'];
}


if (isset($_GET['is_deleted'])) {
    $conditions[] = 'is_deleted = 1';
}


$where = '';
if (!empty($conditions)) {
    $where = 'WHERE ' . implode(' AND ', $conditions);
}


$countStmt = $pdo->prepare("SELECT COUNT(*) FROM article $where");
$countStmt->execute($params);
$articleCount = $countStmt->fetchColumn();




// 計算下架文章數量
$query = $pdo->query("SELECT COUNT(*) FROM article WHERE is_deleted = 1");
$archived_count = $query->fetchColumn();


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta
    name="viewport"
    content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <link
    rel="apple-touch-icon"
    sizes="76x76"
    href="../assets/img/apple-icon.png" />
  <link rel="icon" type="image/png" href="../assets/img/favicon.png" />
  <title>camera</title>
  <!--     Fonts and icons     -->
  <link
    rel="stylesheet"
    type="text/css"
    href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
  <!-- Nucleo Icons -->
  <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Material Icons -->
  <link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  <!-- CSS Files -->
  <link
    id="pagestyle"
    href="../assets/css/material-dashboard.css?v=3.2.0"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css"
    integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />


  <style>
    .pagination{
      --bs-pagination-active-bg: #000;
      --bs-pagination-active-border-color: #000;
      --bs-pagination-focus-box-shadow: #ff0000;
    }
    .content {
      word-wrap: break-word;
      /* 自動換行 */
      white-space: normal;
      /* 保留正常的空白符號 */
    }
    .btn-color {
      background-color: #3C3C3C;
      color: #FFF;
    }
    .btn-search{
      border-radius: 0 10px 10px 0;
      height: 38px;
    }
    .btn-search:hover{
      background: #FFF;
      color: #000;
    }
    .btn-addArticle {
      width: 40px;
      height: 40px;
      border-radius: 50%;
    }

    .btn-addArticle:hover {
      background: #FFF;
      i {
        color: #000;
      }
    }

    .sort-btn i {
      cursor: pointer;
      color: #FFF;
    }
    .sort-btn i:hover {
      color: gray !important;
    }
    table {
      table-layout: fixed;
      width: 100%;
    }
    .title-wrap {  
    white-space: normal; /* 允許正常的空白符號和換行 */  
    word-wrap: break-word; /* 允許在單詞內換行 */  
    height: auto; /* 自動調整高度 */  
  }
  .pagination .page-item.active .page-link {
    color: #fff;
    background-color: #000; 
    border-color: #000;
  }

  /* 切換按鈕樣式 */
  .form-switch .form-check-input {
    width: 40px;
    height: 19px;
    border-radius: 20px;
    position: relative;
    background-color: green;
    transition: background-color 0.4s, box-shadow 0.4s, transform 0.4s;
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
}

.form-switch .form-check-input::before {
    content: '';
    position: absolute;
    top: 1.5px;
    left: 1.5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: #fff;
    transition: transform 0.4s ease, box-shadow 0.4s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
}

.form-switch .form-check-input:checked {
    background-color: #FF4D40;
    box-shadow: 0 0 10px rgba(255, 77, 64, 0.5);
}

.form-switch .form-check-input:checked::before {
    transform: translateX(21px);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
}

.form-switch .form-check-input::after {
    content: "";
    display: none;
}
  </style>
</head>

<body class="g-sidenav-show bg-gray-100">
  <!-- 側邊欄 -->
  <?php $page = 'article'; ?>
  <?php include '../sidebar.php'; ?>
  <!-- 側邊欄 -->
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
          <!-- Navbar -->
          <?php
          $breadcrumbs = [
              'users' => '首頁',
              'article' => '文章管理',
          ];

          $page = 'article';

          $breadcrumbLinks = [
              'users' => '../users/users.php',
              'article' => 'article.php',
          ];

          include '../navbar.php';
          ?>


    <!-- 文章列表 -->
    <div class="container-fluid py-2">
      <div class="row">
        <div class="col-12">
          <!-- 搜尋 -->
          <div class="d-flex justify-content-between align-items-center pe-4 ps-2">
            <div class="input-group" style="width: 20%;">
              <form class="d-flex" method="GET" action="article.php">
                <input type="hidden" name="sort" value="update_time">
                <input type="hidden" name="order" value="DESC">
                <input type="search" class="form-control border border-secondary rounded-end-0 form-control-sm " placeholder="搜尋文章" name="search" value="<?= htmlspecialchars($search) ?>" style="height: 38px; border-radius:10px 0 0 10px;">
                <button class="btn btn-dark btn-search" type="submit"><i class="fa-solid fa-magnifying-glass" ></i></button>
              </form>
            </div>
            <button class="btn btn-dark text-white px-2 btn-addArticle">
              <a
                href="articleAdd.php"
                class="text-white font-weight-bold text-sm"
                data-toggle="tooltip"
                data-original-title="Add">
                <i class="fa-solid fa-pen"><span>+</span></i>
              </a>
            </button>
          </div>
          <div class="d-flex justify-content-between">
            <div class="px-2 mb-2">
            <?php if (!empty($search)): ?>
                搜尋「<?= htmlspecialchars($search) ?>」共有 <?= htmlspecialchars($articleCountSearch) ?> 筆結果
            <?php else: ?>
                目前共有 <?= htmlspecialchars($articleCount) ?> 篇文章
            <?php endif; ?>
            </div>
            <div class="btn-group pe-4">  
              <a href="article.php?sort=update_time_desc" class="btn btn-secondary btn-color <?= $current_category_id === null && !isset($_GET['is_deleted']) ? 'active' : '' ?>">全部</a>
              <?php foreach ($categories as $id => $name): ?>  
                  <a href="?category_id=<?= $id ?>&sort=update_time_desc" class="btn btn-dark btn-color <?= $current_category_id == $id ? 'active' : '' ?>"><?= $name ?></a>  
              <?php endforeach; ?>
              <a href="?is_deleted=1&sort=update_time_desc" class="btn btn-secondary btn-color <?= isset($_GET['is_deleted']) ? 'active' : '' ?>">
                  下架文章 <span class="badge bg-white text-danger border rounded-circle"><?= $archived_count ?></span>
              </a>
            </div>
          </div>


    <?php if ($is_searching): ?>
      <!-- 搜尋內容 -->
          <!-- 搜尋結果顯示區塊 -->
              <div class="row">
                  <div class="col-12">
                      <!-- 搜尋結果列 -->
                      <div class="card">
                          <div class="card-body px-0 pb-2">
                              <div class="table-responsive p-0 rounded-top">
                                  <table class="table align-items-center mb-0">
                                      <thead class="bg-gradient-dark sort-btn">
                                          <tr>
                                              <th class="text-center text-uppercase text-sm text-white" style="width:5%;">分類</th>
                                              <th class="text-uppercase text-sm text-white" style="width:25%;">標題</th>
                                              <th class="text-uppercase text-secondary text-center text-sm ps-2 text-white" style="width:10%;">編輯者</th>
                                              <th class="text-uppercase text-sm font-weight-bolder ps-2 text-white" colspan="2" style="width:35%;">內文</th>
                                              <th class="text-uppercase text-sm font-weight-bolder text-center ps-2 text-white" style="width:10%;">最後更新時間</th>
                                              <th class="text-center text-uppercase text-sm font-weight-bolder text-white" style="width:5%;">檢視</th>
                                              <th class="text-center text-uppercase text-sm font-weight-bolder text-white" style="width:5%;">編輯</th>
                                              <th class="text-center text-uppercase text-sm text-white" style="width:5%;">狀態
                                                  <a href="<?= buildSortUrl('is_deleted') ?>" class="text-white">
                                                      <i class="fa-solid fa-sort ps-2 <?= $sort === 'is_deleted' ? ($order === 'asc' ? 'fa-sort-up' : 'fa-caret-down') : '' ?>"></i>
                                                  </a>
                                              </th>
                                          </tr>
                                      </thead>

                                      <tbody>
                                          <?php if ($articles_page): ?>
                                              <?php foreach ($articles_page as $article): ?>
                                                  <tr>
                                                      <!-- 分類 -->
                                                      <td class="text-center">
                                                          <p class="text-xs font-weight-bold mb-0 text-warning"><?= htmlspecialchars($article['category_name']) ?></p>
                                                      </td>
                                                      <!-- 標題 -->
                                                      <td style="width:25%">
                                                          <div class="d-flex px-2 py-1 title-wrap">
                                                              <div class="d-flex flex-column justify-content-center">
                                                                  <h6 class="mb-0 text-sm"><?= htmlspecialchars(strip_tags($article['title'])) ?></h6>
                                                              </div>
                                                          </div>
                                                      </td>
                                                      <!-- 編輯者 -->
                                                      <td style="cursor:pointer;">
                                                          <p class="text-xs font-weight-bold text-center mb-0">管理員</p>
                                                      </td>
                                                      <!-- 內文 -->
                                                      <td colspan="2" style="width:35%">
                                                          <p class="text-xs font-weight-bold mb-0 content">
                                                              <?= htmlspecialchars(truncate(strip_tags($article['content']), 150)) ?>
                                                          </p>
                                                      </td>
                                                      <!-- 更新時間 -->
                                                      <td style="width:10%; cursor:pointer;" title="<?= isset($article['update_time']) ? htmlspecialchars($article['update_time']) : '未更新' ?>">
                                                          <p class="text-xs font-weight-bold mb-0 text-center text-primary">
                                                              <?= isset($article['update_time']) ? htmlspecialchars(time_elapsed_string($article['update_time'])) : '未更新' ?>
                                                          </p>
                                                      </td>
                                                      <!-- 檢視 -->
                                                      <td class="align-middle text-center" >
                                                          <a href="javascript:;" class="text-secondary font-weight-bold text-sm" data-toggle="tooltip" data-original-title="View">
                                                              <i class="fa-regular fa-eye"></i>
                                                          </a>
                                                      </td>
                                                      <!-- 編輯 -->
                                                      <td class="align-middle text-center">
                                                          <a href="articleEdit.php?id=<?= $article['id'] ?>" class="text-danger font-weight-bold text-sm" data-toggle="tooltip" data-original-title="Edit">
                                                              <i class="fa-regular fa-pen-to-square"></i>
                                                          </a>
                                                      </td>
                                                      <!-- 文章狀態 -->
                                                      <td class="text-center align-middle">  
                                                          <div class="form-check form-switch d-flex justify-content-center align-items-center">  
                                                              <input class="form-check-input toggle-delete" type="checkbox" role="switch" id="toggleSwitch<?= $article['id'] ?>" data-id="<?= $article['id'] ?>" <?= $article['is_deleted'] ? 'checked' : '' ?>>  
                                                              <label class="form-check-label" for="toggleSwitch<?= $article['id'] ?>"></label>  
                                                          </div>  
                                                      </td>  
                                                  </tr>
                                              <?php endforeach; ?>
                                          <?php else: ?>
                                              <tr>
                                                  <td colspan="9" class="text-center">沒有找到相關文章。</td>
                                              </tr>
                                          <?php endif; ?>
                                      </tbody>
                                  </table>
                              </div>
                          </div>
                          <?php if (isset($_GET['is_deleted']) && $articleCount == 0): ?>
                              <div class="alert alert-danger text-center text-white mt-3" role="alert">
                                  目前沒有下架文章
                              </div>
                          <?php endif; ?>
                      </div>
                  </div>
              </div>
              <!-- 分頁按鈕 -->
              <?php if (($p == 1 && $articleCountSearch > 10) || ($p > 1 && $total_pages > 0)): ?>
                    <nav class="mt-5" aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php
                            // 構建基礎URL
                            $base_url = 'article.php?';
                            $params = $_GET;
                            unset($params['p']); // 移除當前頁數參數
                            $query_string = http_build_query($params);
                            $base_url .= $query_string ? $query_string . '&' : '';
                            ?>
                            <li class="page-item <?= ($p == 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $base_url ?>p=<?= max($p - 1, 1) ?>"><i class="fa-solid fa-angle-left"></i></a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($i == $p) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $base_url ?>p=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($p == $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $base_url ?>p=<?= min($p + 1, $total_pages) ?>"><i class="fa-solid fa-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
          </div>
    <?php else: ?>





              <!-- 文章列 -->
              <div class="card me-2">
                <div class="card-body px-0 pb-2">
                  <div class="table-responsive p-0 rounded-top">
                    <table class="table align-items-center mb-0">
                      <thead class="bg-gradient-dark sort-btn">
                        <tr>
                          <th class="text-center text-uppercase text-sm text-white" style="width:5%;">
                            分類
                            <a href="<?= buildSortUrl('category_id') ?>" class="text-white">
                                <i class="fa-solid fa-sort ps-2 <?= $sort === 'category_id' ? ($order === 'asc' ? 'fa-sort-up' : 'fa-caret-down') : '' ?>"></i>
                            </a>
                          </th>

                          <th class="text-uppercase text-sm text-white" style="width:25%;">
                            標題
                            <a href="<?= buildSortUrl('title') ?>" class="text-white">
                              <i class="fa-solid fa-sort ps-2 <?= $sort === 'title' ? ($order === 'asc' ? 'fa-sort-up' : 'fa-caret-down') : '' ?>"></i>
                            </a>
                          </th>

                          <th
                            class="text-uppercase text-secondary text-center text-sm ps-2 text-white" style="width:10%">
                            編輯者
                          </th>
                          <th class="text-uppercase text-sm font-weight-bolder ps-2 text-white" colspan="2" style="width:35%">
                            內文
                            <a href="<?= buildSortUrl('content') ?>" class="text-white">
                                <i class="fa-solid fa-sort ps-2 <?= $sort === 'content' ? ($order === 'asc' ? 'fa-sort-up' : 'fa-caret-down') : '' ?>"></i>
                            </a>
                          </th>

                          <th class="text-uppercase text-sm font-weight-bolder text-center ps-2 text-white" style="width:10%">
                            最後更新時間
                            <a href="<?= buildSortUrl('update_time') ?>" class="text-white">
                              <i class="fa-solid fa-sort ps-2 <?= $sort === 'update_time' ? ($order === 'asc' ? 'fa-sort-up' : 'fa-caret-down') : '' ?>"></i>
                            </a>
                          </th>
                          <th
                            class="text-center text-uppercase text-sm font-weight-bolder text-white" style="width:5%">
                            檢視
                          </th>
                          <th
                            class="text-center text-uppercase text-sm font-weight-bolder text-white" style="width:5%">
                            編輯
                          </th>
                          <th
                            class="text-center text-uppercase text-sm text-white" style="width:5%">
                            狀態
                            <a href="<?= buildSortUrl('is_deleted') ?>" class="text-white">
                              <i class="fa-solid fa-sort ps-2 <?= $sort === 'is_deleted' ? ($order === 'asc' ? 'fa-sort-up' : 'fa-caret-down') : '' ?>"></i>
                            </a>
                          </th>
                        </tr>
                      </thead>
                      
                      <tbody>
                        <?php foreach ($articles as $article): ?>
                          <tr>
                            <!-- 分類 -->
                            <td class="text-center">
                              <p class="text-xs font-weight-bold mb-0 text-warning"><?= htmlspecialchars($article['category_name']) ?></p>
                            </td>
                            <!-- 標題 -->
                            <td style="width:25%">
                              <div class="d-flex px-2 py-1 title-wrap">
                                <div class="d-flex flex-column justify-content-center">
                                  <h6 class="mb-0 text-sm"><?= htmlspecialchars(strip_tags($article['title'])) ?></h6>
                                </div>
                              </div>
                            </td>
                            <!-- 編輯者 -->
                            <td style="cursor:pointer;">
                              <p class="text-xs font-weight-bold text-center mb-0">管理員</p>
                            </td>
                            <!-- 內文 -->
                            <td colspan="2" style="width:35%">
                              <p class="text-xs font-weight-bold mb-0 content">
                                <?= htmlspecialchars(truncate(strip_tags($article['content']), 150)) ?>
                              </p>
                            </td>
                            <!--更新時間 -->
                            <td style="width:10%; cursor:pointer;" title="<?= isset($article['update_time']) ? htmlspecialchars($article['update_time']) : '未更新' ?>">
                              <p class="text-xs font-weight-bold mb-0 text-center text-primary"><?= isset($article['update_time']) ? htmlspecialchars(time_elapsed_string($article['update_time'])) : '未更新' ?></p>
                            </td>
                            <!-- 檢視-->
                            <td class="align-middle text-center">
                              <a
                                href="javascript:;"
                                class="text-secondary font-weight-bold text-sm"
                                data-toggle="tooltip"
                                data-original-title="Edit user">
                                <i class="fa-regular fa-eye"></i>
                              </a>
                            </td>
                            <!-- 編輯 -->
                            <td class="align-middle text-center">
                              <a
                                href="articleEdit.php?id=<?= $article['id'] ?>"
                                class="text-danger font-weight-bold text-sm"
                                data-toggle="tooltip"
                                data-original-title="Edit user">
                                <i class="fa-regular fa-pen-to-square"></i>
                              </a>
                            </td>
                            <!-- 文章狀態 -->
                            <td class="text-center align-middle">  
                              <div class="form-check form-switch d-flex justify-content-center align-items-center">  
                                <input   
                                  class="form-check-input toggle-delete"  
                                  type="checkbox"   
                                  role="switch"  
                                  id="toggleSwitch<?= $article['id'] ?>"   
                                  data-id="<?= $article['id'] ?>"  
                                  <?= $article['is_deleted'] ? 'checked' : '' ?>  
                                >  
                                <label class="form-check-label" for="toggleSwitch<?= $article['id'] ?>"></label>  
                              </div>  
                            </td>  
                          <?php endforeach; ?>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <?php if (isset($_GET['is_deleted']) && $archived_count == 0): ?>
                    <div class="alert alert-danger text-center text-white mt-3" role="alert">
                        目前沒有下架文章
                    </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <!-- 分頁按鈕 -->
            <?php if (($p == 1 && $articleCount > 10) || ($p > 1 && $articleCount > 0)): ?>
                <nav class="mt-5" aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php
                        // 構建基礎URL
                        $base_url = 'article.php?';
                        $params = $_GET;
                        unset($params['p']); // 移除當前頁數參數
                        $query_string = http_build_query($params);
                        $base_url .= $query_string ? $query_string . '&' : '';
                        ?>
                        <li class="page-item <?= ($p == 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $base_url ?>p=<?= max($p - 1, 1) ?>"><i class="fa-solid fa-angle-left"></i></a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i == $p) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $base_url ?>p=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($p == $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $base_url ?>p=<?= min($p + 1, $total_pages) ?>"><i class="fa-solid fa-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
    <?php endif; ?>
    </div>
  </main>

  <!--   Core JS Files   -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script>
    var win = navigator.platform.indexOf("Win") > -1;
    if (win && document.querySelector("#sidenav-scrollbar")) {
      var options = {
        damping: "0.5",
      };
      Scrollbar.init(document.querySelector("#sidenav-scrollbar"), options);
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const toggleSwitches = document.querySelectorAll('.toggle-delete');

  toggleSwitches.forEach(function(toggleSwitch) {
    toggleSwitch.addEventListener('change', function() {//監聽change事件
      const id = this.getAttribute('data-id');//取得data-id的值
      const is_deleted = this.checked ? 1 : 0;//如果被選取，is_deleted為1，否則為0

      fetch('update_article.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id, is_deleted: is_deleted })
      })
      .then(response => response.json())
      .then(data => {
        if (!data.success) {
          alert('更新失敗: ' + data.message);
          // 如果更新失敗，恢復原來的狀態
          this.checked = !this.checked;
        }
      })
      .catch(error => {
        console.error('錯誤:', error);
        alert('伺服器錯誤');
        // 如果發生錯誤，恢復原來的狀態
        this.checked = !this.checked;//如果被選取，取消選取，否則選取
      });
    });
  });
});
</script>
  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>
</body>

</html>