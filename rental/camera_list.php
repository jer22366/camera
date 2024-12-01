<?php
require_once("../db_connect.php");

$title = isset($_GET["search"]) ? "搜尋結果：" . htmlspecialchars($_GET["search"]) : "租借列表";

// 搜尋條件
$is_deleted = isset($_GET['is_deleted']) ? $_GET['is_deleted'] : 0;

if ($is_deleted == 1) {
    $whereClause = "is_deleted = 1";
    $buttonText = "顯示上架商品";
} else {
    $whereClause = "is_deleted = 0";
    $buttonText = "顯示下架商品";
}

// 搜尋欄位處理
$search = isset($_GET["search"]) ? trim($_GET["search"]) : '';
if (!empty($search)) {
    $search_escaped = $conn->real_escape_string($search);
    $whereClause .= " AND (images.name LIKE '%$search_escaped%' OR images.description LIKE '%$search_escaped%')";
}

// 排序邏輯 - 新增 order 參數控制排序方向
$order = isset($_GET["order"]) ? $_GET["order"] : 'i1'; // 默認為按 id 降序排序
// 設定排序欄位和方向
switch ($order) {
  case 'i0':
      $order_by = 'camera.id ASC';
      break;
  case 'i1':
      $order_by = 'camera.id DESC';
      break;
  case 's0':
      $order_by = 'camera.stock ASC';
      break;
  case 's1':
      $order_by = 'camera.stock DESC';
      break;
  default:
      $order_by = 'camera.id DESC'; // 默認為 id 降序排序
      break;
}


// 設定分頁
$items_per_page = 10;
$currentPage = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;

// 計算總數與分頁
$count_sql = "SELECT COUNT(*) AS total FROM camera JOIN images ON camera.image_id = images.id WHERE $whereClause";
$count_result = $conn->query($count_sql);
$totalItems = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$totalPages = max(ceil($totalItems / $items_per_page), 1);

$offset = ($currentPage - 1) * $items_per_page;

// 撈取資料
$sql = "SELECT camera.*, images.name AS image_name, images.description AS image_description, 
        images.type AS image_type, images.image_url
        FROM camera
        JOIN images ON camera.image_id = images.id
        WHERE $whereClause
        ORDER BY $order_by
        LIMIT $items_per_page OFFSET $offset";
$result = $conn->query($sql);
$cameras = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// 圖片資料邏輯
$imgSql = "SELECT * FROM images";
$resultImg = $conn->query($imgSql);
$images = $resultImg->fetch_all(MYSQLI_ASSOC);
$imageArr = [];
foreach ($images as $image) {
    $imageArr[$image["id"]] = $image["name"];
}

$new_order = ($order === 'asc') ? 'desc' : 'asc';

?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta
    name="viewport"
    content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png" />
  <link rel="icon" type="image/png" href="../assets/img/favicon.png" />
  <title>camera</title>

  <?php include("link.php") ?>

</head>

<body class="g-sidenav-show bg-gray-100">
  <!-- 側邊欄 -->
  <?php $page = 'camera'; ?>
  <?php include '../pages/sidebar.php'; ?>
  <!-- 側邊欄 -->
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
      <?php $page = 'camera'; ?>
      <?php include '../pages/navbar.php'; ?>
    <!-- Navbar -->

    <div class="container-fluid ">
      <div class="px-1">
      <!-- 總數 -->           
      <div class="py-1">共計 <?=$totalItems?> 項目</div>
        <div class="d-flex justify-content-between align-items-center">
          <!-- 搜尋 -->
          <form method="GET" action="camera_list.php">
            <div class="input-group">
              <input type="search" name="search" class="btn btn-light text-start" value="<?= htmlspecialchars(isset($_GET['search']) ? $_GET['search'] : '') ?>" placeholder="搜尋名稱或描述">
              <div class="btn-group ps-1">
                <button type="submit" class="btn btn-dark" title="搜尋">搜尋</button>
                <a href="camera_list.php" class="btn btn-outline-secondary" title="清除搜尋">清除搜尋</a>
              </div>
            </div>
          </form>          
          <!-- 新增 -->
          <div>
            <button class="btn btn-secondary" 
                    id="toggleButton" 
                    onclick="toggleDeleted(<?php echo $newIsDeletedValue; ?>)"><?= $buttonText ?>
            </button>
            <button type="button" 
                    class="btn btn-success" 
                    data-toggle="modal" 
                    data-target="camera_create.php">新增相機
            </button>
          </div>
        </div>


      </div>

      <div class="card my-1 px-0">
        <div class="table-responsive p-0 rounded-top">
        <!-- 表格 -->
          <table class="table align-items-center mb-0">
            <thead class="bg-gradient-dark">
              <tr>
                <th class="text-uppercase text-secondary text-xs opacity-7 text-white">
                  出租商品
                  <?php if ($order === 'i1'): ?>
                      <a href="camera_list.php?order=i0" class="btn btn-borderless text-light font-weight-bold text-xs m-0"><i class="fa-solid fa-caret-up"></i></a>
                  <?php else: ?>
                      <a href="camera_list.php?order=i1" class="btn btn-borderless text-light font-weight-bold text-xs m-0" style="transform: translatey(-2px);"><i class="fa-solid fa-sort-down"></i></a>
                  <?php endif; ?>                  
                </th>
                <th class="text-uppercase text-secondary text-xs opacity-7 ps-2 text-white">
                  規格</th>
                <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7 ps-2 text-white">
                  租金 / 押金</th>
                <th class="text-uppercase text-secondary text-xs opacity-7 ps-2 text-white">
                  庫存
                  <?php if ($order === 's1'): ?>
                      <a href="camera_list.php?order=s0" class="btn btn-borderless text-light font-weight-bold text-xs m-0"><i class="fa-solid fa-caret-up"></i></a>
                  <?php else: ?>
                      <a href="camera_list.php?order=s1" class="btn btn-borderless text-light font-weight-bold text-xs m-0" style="transform: translatey(-2px);"><i class="fa-solid fa-sort-down"></i></a>
                  <?php endif; ?>
                </th>
                <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7 text-white">
                  狀態</th>
                <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7 text-white">
                  編輯</th>
                <th class="text-center text-uppercase text-secondary text-xs opacity-7 text-white">
                  上架</th>
              </tr>
            </thead> 
            <tbody>
            <?php foreach($cameras as $camera): ?>
              <tr>
                <!-- 商品 -->
                <td>                        
                  <div class="d-flex px-2 py-1">
                    <div>
                      <img src="../album/upload/<?= htmlspecialchars($camera['image_url']) ?>" 
                        class="avatar avatar-sm me-3 border-radius-lg"
                        alt="<?= htmlspecialchars($camera['image_name']) ?>" />
                    </div>
                    <div class="d-flex flex-column justify-content-center">
                      <h6 class="mb-0 text-sm"><?= htmlspecialchars($camera['image_name']) ?></h6>
                      <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($camera['image_type']) ?></p>
                    </div>
                  </div>
                </td>
                <!-- 規格 -->
                <td>
                  <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($camera['image_description']) ?></p>
                </td>
                <!-- 租金 / 押金 -->
                <td>
                  <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($camera['fee']) ?> / <?= htmlspecialchars($camera['deposit']) ?></p>
                </td>
                <!-- 庫存 -->
                <td>
                  <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($camera['stock']) ?></p>
                </td>
                <!-- 狀態 -->
                <td class="align-middle text-center">
                  <button type="button" 
                          class="btn btn-borderless text-secondary font-weight-bold text-xs m-0" 
                          data-toggle="modal" 
                          data-id="<?= $camera['id'] ?>" 
                          data-target="camera.php">
                      <i class="fa-regular fa-eye"></i>
                  </button>
                </td>
                <!-- 編輯 -->
                <td class="align-middle text-center">
                  <button type="button" 
                          class="btn btn-borderless text-secondary font-weight-bold text-xs m-0" 
                          data-toggle="modal" 
                          data-id="<?= $camera['id'] ?>" 
                          data-target="camera_edit.php">
                      <i class="fa-regular fa-pen-to-square"></i>
                  </button>
                </td>
                <td class="align-middle text-center">
                  <?php if ($camera['is_deleted'] == 0): ?>
                      <!-- 顯示刪除按鈕 -->
                      <button type="button" 
                              class="btn btn-borderless text-secondary font-weight-bold text-xs m-0" 
                              data-bs-toggle="modal" 
                              data-bs-target="#deleteModal" 
                              data-id="<?= $camera['id'] ?>">                        
                          <i class="fa-regular fa-trash-can"></i> <!-- 刪除圖標 -->
                      </button>
                  <?php else: ?>
                      <!-- 顯示還原按鈕 -->
                      <button type="button" 
                              class="btn btn-borderless text-secondary font-weight-bold text-xs m-0" 
                              data-bs-toggle="modal" 
                              data-bs-target="#revertModal" 
                              data-id="<?= $camera['id'] ?>">                        
                          <i class="fa-solid fa-rotate-left"></i> <!-- 還原圖標 -->
                      </button>
                  <?php endif; ?>
              </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <!-- 表格-end -->

          <!-- 分頁導航 -->
          <nav aria-label="Page navigation">
              <ul class="pagination justify-content-center">
                  <!-- 首頁 -->
                  <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                      <a class="page-link" href="camera_list.php?page=1&search=<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                      <i class="fa-solid fa-angles-left"></i></a>
                  </li>

                  <!-- 上一頁 -->
                  <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                      <a class="page-link" href="camera_list.php?page=<?= max(1, $currentPage - 1) ?>&search=<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                      <i class="fa-solid fa-angle-left"></i></a>
                  </li>

                  <!-- 中間頁碼 -->
                  <?php
                  $visiblePages = 5; // 最大顯示頁碼數量
                  $startPage = max(1, $currentPage - floor($visiblePages / 2));
                  $endPage = min($totalPages, $startPage + $visiblePages - 1);

                  // 確保顯示 5 個頁碼範圍
                  if ($endPage - $startPage + 1 < $visiblePages) {
                      $startPage = max(1, $endPage - $visiblePages + 1);
                  }
                  ?>

                  <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                      <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                          <a class="page-link" href="camera_list.php?page=<?= $i ?>&search=<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
                      </li>
                  <?php endfor; ?>

                  <!-- 下一頁 -->
                  <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                      <a class="page-link" href="camera_list.php?page=<?= min($totalPages, $currentPage + 1) ?>&search=<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                      <i class="fa-solid fa-chevron-right"></i></a>
                  </li>

                  <!-- 末頁 -->
                  <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                      <a class="page-link" href="camera_list.php?page=<?= $totalPages ?>&search=<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                      <i class="fa-solid fa-angles-right"></i></a>
                  </li>
              </ul>
          </nav>
          <!-- 分頁-end -->
              
        </div>
      </div>
    </div>

  <div id="cameraModalContainer"></div>
  <div id="albumModalContainer"></div>

<!-- 刪除專用 -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">商品下架</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                你確定要下架嗎？
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <a href="" id="confirmDelete" class="btn btn-danger">下架</a>
            </div>
        </div>
    </div>
</div>

<!-- 復原專用 -->
<div class="modal fade" id="revertModal" tabindex="-1" aria-labelledby="revertModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">商品上架</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                你確定要上架嗎？
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <a href="" id="confirmRevert" class="btn btn-danger">上架</a>
            </div>
        </div>
    </div>
</div>


  
  </main>
  
  <?php include("script.php") ?>


  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- jQuery -->
  <script>
// 全局輔助函數
function isValidHTML(html) {
    const doc = document.createElement('div');
    doc.innerHTML = html;
    return doc.children.length > 0;
}

// 通用模態框按鈕點擊事件
$(document).on('click', '[data-toggle="modal"]', function () {
    const targetUrl = $(this).data('target'); // 獲取目標 URL
    const dataId = $(this).data('id');
    const cameraId = dataId;

    // 發送 AJAX 請求
    $.ajax({
        url: targetUrl,
        type: 'GET',
        data: { id: dataId }, // 如果沒有 ID，傳遞空值
        success: function (response) {
            if (isValidHTML(response)) {
                $('#cameraModalContainer').html(response);

                const modal = $('#cameraModalContainer .modal');
                modal.modal('show');

                // 清理模態框內容，防止累積
                modal.off('hidden.bs.modal').on('hidden.bs.modal', function () {
                    $('#cameraModalContainer').empty();
                });
            } else {
                console.error('無效的 HTML:', response);
            }
        },
        error: function () {
            alert('無法加載內容，請稍後再試！');
        },
    });
});

// 切換模態框的按鈕點擊事件
$(document).on('click', '.modalChange', function () {
    const targetUrl = $(this).data('target');
    const dataId = $(this).data('id');
    const cameraId = dataId;
    const currentModal = $(this).closest('.modal');

    currentModal.modal('hide'); // 關閉當前模態框

    // AJAX 請求加載新模態框
    $.ajax({
        url: targetUrl,
        type: 'GET',
        data: { id: dataId },
        success: function (response) {
            if (isValidHTML(response)) {
                $('#cameraModalContainer').html(response);

                const newModal = $('#cameraModalContainer .modal');
                newModal.modal('show');

                // 清理新模態框內容
                newModal.off('hidden.bs.modal').on('hidden.bs.modal', function () {
                    $('#cameraModalContainer').empty();
                });
            } else {
                console.error('無效的 HTML:', response);
            }
        },
        error: function () {
            alert('無法加載內容，請稍後再試！');
        },
    });
});

// 提交表單（如更新）
$(document).on('submit', '#updateForm', function (e) {
    e.preventDefault();

    $.ajax({
        url: 'camera_edit.php',
        type: 'POST',
        data: $(this).serialize(), // 序列化表單數據
        success: function (response) {
            alert('更新成功！');
            location.reload(); // 刷新頁面
        },
        error: function () {
            alert('更新失敗，請稍後再試！');
        },
    });
});

// 分頁超鏈接點擊事件
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('album-page-link')) {
        e.preventDefault();

        const url = e.target.href;

        fetch(url)
            .then(response => response.text())
            .then(data => {
                if (isValidHTML(data)) {
                    document.getElementById('albumContainer').innerHTML = data;
                } else {
                    console.error('無效的 HTML:', data);
                }
            })
            .catch(() => {
                alert('分頁加載失敗，請稍後再試！');
            });
    }
});

// 自定義關閉模態框按鈕
$(document).on('click', '.modalClose', function () {
    const modal = $(this).closest('.modal');
    modal.modal('hide'); // 隱藏模態框

    // 隱藏後刷新頁面
    modal.off('hidden.bs.modal').on('hidden.bs.modal', function () {
        location.reload();
    });
});
  </script>

<!-- 刪除專用 -->
<script>
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        // 取得觸發按鈕
        var button = event.relatedTarget;
        // 從按鈕的 data-id 屬性中取得相機 ID
        var cameraId = button.getAttribute('data-id');
        // 更新模態框中的刪除連結
        var confirmDelete = deleteModal.querySelector('#confirmDelete');
        confirmDelete.href = 'doDelete.php?id=' + cameraId;
    });
</script>

<!-- 復原專用 -->
<script>
    var revertModal = document.getElementById('revertModal');
    revertModal.addEventListener('show.bs.modal', function (event) {
        // 取得觸發按鈕
        var button = event.relatedTarget;
        // 從按鈕的 data-id 屬性中取得相機 ID
        var cameraId = button.getAttribute('data-id');
        // 更新模態框中的刪除連結
        var confirmRevert = revertModal.querySelector('#confirmRevert');
        confirmRevert.href = 'doRevert.php?id=' + cameraId;
    });
</script>

<!-- 切換刪除/沒有刪除 -->
<script>
    document.getElementById("toggleButton").addEventListener("click", function () {
        // 判斷 URL 中是否有 is_deleted 參數
        const urlParams = new URLSearchParams(window.location.search);
        let is_deleted = urlParams.get('is_deleted');
        
        // 切換 is_deleted 值
        if (is_deleted == 1) {
            is_deleted = 0; // 切換回顯示未刪除
        } else {
            is_deleted = 1; // 切換成顯示已刪除
        }

        // 更新 URL 並重新載入頁面
        urlParams.set('is_deleted', is_deleted);
        window.location.search = urlParams.toString();
    });
</script>

<script>
    function toggleDeleted(newIsDeletedValue) {
        // 更新 URL 的 is_deleted 參數，並重新載入頁面
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('is_deleted', newIsDeletedValue);
        window.location.search = urlParams.toString();
    }
</script>
</body>

</html>