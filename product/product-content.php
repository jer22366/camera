<?php
require_once("db_connect.php");

if (!isset($_GET["id"])) {
  echo "請帶入 id 到此頁";
  exit;
}
$id = $_GET["id"];

$sql = "SELECT 
    product.*, 
    brand.brand_name, 
    category.category_name,
    image.image_url
FROM 
    product
INNER JOIN 
    brand 
ON 
    product.brand_id = brand.brand_id
INNER JOIN 
    category 
ON 
    product.category_id = category.category_id
LEFT JOIN
    image
ON
    product.name = image.name
WHERE 
    product.id = '$id' AND product.is_deleted = 0;
";

// $sql = "SELECT  
//     p.id,  
//     p.name AS product_name,
//     i.name AS image_name,  
//     i.image_url,  
//     p.price,  
//     b.brand_name,  
//     c.category_name,  
//     p.stock,
//     p.created_at,  
//     p.updated_at,  
//     p.state  
// FROM  
//     product p  
// INNER JOIN    
//     category c ON p.category_id = c.category_id  
// INNER JOIN  
//     brand b ON p.brand_id = b.brand_id  
// INNER JOIN  
//     image i ON p.name = i.name  -- 條件：product 的 name 必須匹配 image 的 name  
// WHERE  
//     p.is_deleted = 0";

$result = $conn->query($sql);
$row = $result->fetch_assoc();

// var_dump($row);

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
</head>

<body class="g-sidenav-show bg-gray-100">
  <!-- 側邊欄 -->
  <?php $page = 'product'; ?>
  <?php include 'sidebar.php'; ?>
  <!-- 側邊欄 -->
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-3 shadow-none border-radius-xl" id="navbarBlur" data-scroll="true">
      <div class="container-fluid py-1 px-3 justify-content-end">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
            <li class="breadcrumb-item text-sm">
              <a class="opacity-5 text-dark" href="javascript:;">Pages</a>
            </li>
            <li class="breadcrumb-item text-sm">
              <a class="opacity-5 text-dark" href="javascript:;">商品管理</a>
            </li>
            <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
              商品內容
            </li>
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
            <li class="nav-item px-3 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-body p-0">
                <i class="material-symbols-rounded fixed-plugin-button-nav">settings</i>
              </a>
            </li>
            <li class="nav-item dropdown pe-3 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-body p-0" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="material-symbols-rounded">notifications</i>
              </a>
              <ul class="dropdown-menu dropdown-menu-end px-2 py-3 me-sm-n4" aria-labelledby="dropdownMenuButton">
                <!-- 通知內容 -->
              </ul>
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

    <div class="container-fluid py-2">
      <a href="product.php" class="btn btn-dark">
        <i class="fa-solid fa-arrow-left"></i>
      </a>
      <div class="row">
        <div class="col-12">
          <div class="card my-4">
            <!-- <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
              <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3">
                <h6 class="text-white text-capitalize ps-3">Authors table</h6>
              </div>
            </div> -->
            <div class="card-body px-0 pb-2">
              <div class="table-responsive p-0 rounded-top">
                <table class="table align-items-center mb-0">
                  <thead class="bg-gradient-dark">
                    <tr>
                      <th
                        class="text-center text-uppercase text-secondary text-xxs opacity-7 text-white " colspan="10">
                        商品內容
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <div class="container">
                      <table class="table table-bordered">
                        <?php if ($result->num_rows > 0): ?>
                          <h1><?= $row["name"] ?></h1>
                          <tr>
                            <th>id</th>
                            <td><?= $row["id"] ?></td>
                          </tr>
                          <tr>
                            <th>照片</th>
                            <td>
                              <div class="ratio ratio-4x3" style="width: 300px;">
                                <img class="object-fit-cover" src="../album/upload/<?= $row["image_url"] ?>" alt="">
                              </div>
                            </td>
                          </tr>
                          <tr>
                            <th>價格</th>
                            <td><?= number_format($row["price"]) ?></td>
                          </tr>
                          <tr>
                            <th>品牌</th>
                            <td><?= $row["brand_name"] ?></td>
                          </tr>
                          <tr>
                            <th>商品規格</th>
                            <td><?= nl2br($row["spec"]) ?></td>
                          </tr>
                      </table>
                    <?php else: ?>
                      <h1>查無資料</h1>
                    <?php endif; ?>
                    </div>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
    </div>
    </div>
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



<!-- <div class="container">
        <a href="product.php" class="btn btn-primary"><i class="fa-solid fa-arrow-left fa-fw"></i></a>
        <table class="table table-bordered">
            <?php if ($result->num_rows > 0): ?>
                <h1><?= $row["name"] ?></h1>
                <tr>
                    <th>id</th>
                    <td><?= $row["id"] ?></td>
                </tr>
                <tr>
                    <th>照片</th>
                    <td>
                        <div class="ratio ratio-4x3" style="width: 300px;">
                            <img class="object-fit-cover" src="upload/<?= $row["image_url"] ?>" alt="">
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>價格</th>
                    <td><?= number_format($row["price"]) ?></td>
                </tr>
                <tr>
                    <th>品牌</th>
                    <td><?= $row["brand_name"] ?></td>
                </tr>
                <tr>
                    <th>商品規格</th>
                    <td><?= nl2br($row["spec"]) ?></td>
                </tr>
        </table>
    <?php else: ?>
        <h1>查無資料</h1>
    <?php endif; ?>
    </div> -->