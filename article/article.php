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

//下面function是用來計算時間差的
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



// 每頁顯示的文章數量  
$per_page = 10;  

// 獲取當前頁數，預設為第1頁  
$p = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;  
$p = max($p, 1); // 確保頁數不小於1  

$search = $_GET['search'] ?? '';  

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
    case 'category_id':  
    default:  
        $order_by = "ORDER BY a.category_id $order"; // 默認按 category_id 排序  
        break;  
}  

try {  
    if (!empty($search)) {  
        $sql = "SELECT a.*, c.name as category_name  
                FROM article a  
                JOIN article_category c ON a.category_id = c.id  
                WHERE a.title LIKE :search OR a.content LIKE :search  
                $order_by  
                LIMIT :limit OFFSET :offset";  
        $stmt = $pdo->prepare($sql);  
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);  
    } else {  
        $sql = "SELECT a.*, c.name as category_name  
                FROM article a  
                JOIN article_category c ON a.category_id = c.id  
                $order_by  
                LIMIT :limit OFFSET :offset";  
        $stmt = $pdo->prepare($sql);  
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

// 計算總文章數量
try {
  if (!empty($search)) {
      $countSql = "SELECT COUNT(*) as count FROM article WHERE title LIKE :search OR content LIKE :search";
      $countStmt = $pdo->prepare($countSql);
      $countStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
  } else {
      $countSql = "SELECT COUNT(*) as count FROM article";
      $countStmt = $pdo->prepare($countSql);
  }

  $countStmt->execute();
  $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
  $articleCount = $countResult['count'];
} catch (PDOException $e) {
  echo "計算文章數量失敗: " . $e->getMessage();
}
$total_pages = ceil($articleCount / $per_page); // 計算總頁數

        // 設定麵包屑的層級
        $breadcrumbs = [
          'article' => '首頁',
          'article_list' => '文章管理',
      ];

      $page = 'article_list';

      // 設定麵包屑的連結
      $breadcrumbLinks = [
          'article' => 'users.php',           // 第一層的連結
          'article_list' => 'article.php',      // 第二層的連結
      ];
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
  <!-- Font Awesome Icons -->
  <script
    src="https://kit.fontawesome.com/42d5adcbca.js"
    crossorigin="anonymous"></script>
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

  <script src="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.0/dist/boxicons.js" integrity="sha512-Dm5UxqUSgNd93XG7eseoOrScyM1BVs65GrwmavP0D0DujOA8mjiBfyj71wmI2VQZKnnZQsSWWsxDKNiQIqk8sQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

  <style>
    .content {
      word-wrap: break-word;
      /* 自動換行 */
      white-space: normal;
      /* 保留正常的空白符號 */
    }

    .btn-search{
      border-radius: 0 10px 10px 0;
      height: 38px;
    }
    .btn-search:hover{
      background: #FFF;
      color: #000;
    }
    .btn-addAeticle {
      width: 35px;
      height: 35px;
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
  </style>
</head>

<body class="g-sidenav-show bg-gray-100">
  <!-- 側邊欄 -->
  <?php $page = 'article'; ?>
  <?php include 'sidebar.php'; ?>
  <!-- 側邊欄 -->
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-3 shadow-none border-radius-xl" id="navbarBlur" data-scroll="true">
      <div class="container-fluid py-1 px-3 justify-content-end">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
          <?php
          // 初始化上一層的連結
          $previousPage = '';

          // 遍歷所有麵包屑
          foreach ($breadcrumbs as $key => $title) {
            // 顯示上一層頁面
            if ($previousPage) {
              echo '<li class="breadcrumb-item text-sm"><a class=" text-dark" href="' . $breadcrumbLinks[$previousPage] . '">' . $breadcrumbs[$previousPage] . '</a></li>';
            }

            // 顯示當前頁面，並標註為活動頁面
            if ($page === $key) {
              echo '<li class="breadcrumb-item text-sm text-dark opacity-5 active" aria-current="page">' . $title . '</li>';
            }

            // 設置當前頁面為上一頁
            $previousPage = $key;
          }
          ?>
        </ol>
        </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
          <!-- 添加 ms-auto 將內容推向右側 -->
          <ul class="navbar-nav d-flex align-items-center justify-content-end ms-auto">
            <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                <div class="sidenav-toggler-inner">
                  <i class="sidenav-toggler-line"></i>
                  <i class="sidenav-toggler-line"></i>
                  <i class="sidenav-toggler-line"></i>
                </div>
              </a>
            </li>
            <li class="nav-item d-flex align-items-center">
              <a href="../pages/sign-in.php" class="nav-link text-body font-weight-bold px-0">
                <i class="fa-solid fa-circle-user"></i>
              </a>
            </li>
            <li class="nav-item d-flex align-items-center ms-3">
              <a href="../pages/sign-in.php" class="nav-link text-body font-weight-bold px-0">
                <i class="fa-solid fa-right-from-bracket"></i>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <!-- Navbar -->



    <!-- 文章列表 -->
    <div class="container-fluid py-2">
      <div class="row">
        <div class="col-12">
          <!-- 搜尋 -->
          <div class="d-flex justify-content-between align-items-center pe-5 ps-1">
            <div class="input-group" style="width: 20%;">
              <form class="d-flex" method="GET" action="article.php">
                <input type="search" class="form-control border border-secondary rounded-end-0 form-control-sm " placeholder="搜尋文章" name="search" value="<?= htmlspecialchars($search) ?>" style="height: 38px; border-radius:10px 0 0 10px;">
                <button class="btn btn-dark btn-search" type="submit"><i class="fa-solid fa-magnifying-glass" ></i></button>
              </form>
            </div>
            <button class="btn btn-dark text-white btn-addAeticle px-2">
            <a
              href="articleAdd.php"
              class="text-white font-weight-bold text-sm"
              data-toggle="tooltip"
              data-original-title="Add">
              <i class="fa-solid fa-pen"></i>
            </a>
            </button>
          </div>
          <div class="px-2 mb-2">目前共有 <?= htmlspecialchars($articleCount) ?> 篇文章</div>
          <div class="card ">
            <div class="card-body px-0 pb-2">
              <div class="table-responsive p-0 rounded-top">
                <table class="table align-items-center mb-0">
                  <thead class="bg-gradient-dark sort-btn">
                    <tr>
                      <th
                        class="text-center text-uppercase text-sm  text-white" style="width:5%;">
                        分類
                        <a href="article.php?order=<?= $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= htmlspecialchars($search) ?>">  
                        <i class="fa-solid fa-sort ps-2 "></i>
                        </a>
                      </th>
                      <th
                        class="text-uppercase text-sm  text-white" style="width:25%;">
                        標題
                        <a href="article.php?sort=title&order=<?= $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= htmlspecialchars($search) ?>">
                        <i class="fa-solid fa-sort ps-2 "></i>
                        </a>
                      </th>
                      <th
                        class="text-uppercase text-secondary text-sm ps-2 text-white" style="width:10%">
                        編輯者
                      </th>
                      <th
                        class="text-uppercase text-secondary text-sm font-weight-bolder  ps-2 text-white" colspan="2" style="width:35%">
                        內文
                        <a href="article.php?sort=content&order=<?= $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= htmlspecialchars($search) ?>">
                        <i class="fa-solid fa-sort ps-2 "></i>
                        </a>
                      </th>
                      <th
                        class="text-uppercase text-sm font-weight-bolder  ps-2 text-white" style="width:10%">
                        最後更新時間
                        <a href="article.php?sort=update_time&order=<?= $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= htmlspecialchars($search) ?>">
                        <i class="fa-solid fa-sort ps-2 "></i>
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
                        刪除
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
                        <td>
                          <p class="text-xs font-weight-bold mb-0">Manager</p>
                        </td>
                        <!-- 內文 -->
                        <td colspan="2" style="width:35%">
                          <p class="text-xs font-weight-bold mb-0 content">
                            <?= htmlspecialchars(truncate(strip_tags($article['content']), 150)) ?>
                          </p>
                        </td>
                        <!--更新時間 -->
                        <td style="width:10%">
                          <p class="text-xs font-weight-bold mb-0 text-success"><?= isset($article['update_time']) ? htmlspecialchars(time_elapsed_string($article['update_time'])) : '未更新' ?></p>
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
                            class="text-secondary font-weight-bold text-sm"
                            data-toggle="tooltip"
                            data-original-title="Edit user">
                            <i class="fa-regular fa-pen-to-square"></i>
                          </a>
                        </td>
                        <!-- 刪除 -->
                        <td class="align-middle text-center">
                          <a
                            href="javascript:;"
                            class="text-danger font-weight-bold text-sm"
                            data-toggle="tooltip"
                            data-original-title="Edit">
                            <i class="fa-regular fa-trash-can"></i>
                          </a>
                        </td>
                      <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- 分頁按鈕 -->
      <?php if ($total_pages > 1): ?>
        <nav class="mt-5" aria-label="Page navigation">
          <ul class="pagination justify-content-center">
            <li class="page-item <?= ($p == 1) ? 'disabled' : '' ?>">
              <a class="page-link" href="article.php?p=<?= $p - 1 ?>&search=<?= htmlspecialchars($search) ?>"><i class="fa-solid fa-angle-left"></i></a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item <?= ($i == $p) ? 'active' : '' ?>">
                <a class="page-link" href="article.php?p=<?= $i ?>&search=<?= htmlspecialchars($search) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= ($p == $total_pages) ? 'disabled' : '' ?>">
              <a class="page-link" href="article.php?p=<?= $p + 1 ?>&search=<?= htmlspecialchars($search) ?>"><i class="fa-solid fa-chevron-right"></i></i></a>
            </li>
          </ul>
        </nav>
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
  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>
</body>

</html>