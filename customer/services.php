<?php
require_once __DIR__ . "/_guard.php";
require_once __DIR__ . "/../config/db.php";

$name = $_SESSION['name'] ?? 'Customer';

/* ✅ CSS auto detect */
$cssPath = "../assets/css/style.css";
if (file_exists(__DIR__ . "/../assets/style.css")) $cssPath = "../assets/style.css";
if (file_exists(__DIR__ . "/../assets/style.csss")) $cssPath = "../assets/style.csss";

/* ✅ Filters */
$q = trim($_GET['q'] ?? '');
$cat = trim($_GET['cat'] ?? '');
$sort = trim($_GET['sort'] ?? 'new'); // new | price_asc | price_desc | title

/* ✅ Build SQL safely */
$where = [];
$params = [];
$types = "";

if ($q !== '') {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $like = "%" . $q . "%";
    $params[] = $like; $params[] = $like;
    $types .= "ss";
}

if ($cat !== '' && $cat !== 'all') {
    $where[] = "category = ?";
    $params[] = $cat;
    $types .= "s";
}

$orderBy = "created_at DESC";
if ($sort === 'price_asc')  $orderBy = "price ASC";
if ($sort === 'price_desc') $orderBy = "price DESC";
if ($sort === 'title')      $orderBy = "title ASC";

$sql = "SELECT id, title, category, description, price, duration_minutes FROM services";
if (!empty($where)) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY $orderBy";

/* ✅ Fetch categories for dropdown */
$categories = [];
try {
    $r = $conn->query("SELECT DISTINCT category FROM services ORDER BY category ASC");
    while ($row = $r->fetch_assoc()) {
        $categories[] = $row['category'];
    }
} catch (Exception $e) {
    $categories = [];
}

/* ✅ Fetch services */
$services = [];
try {
    $st = $conn->prepare($sql);
    if (!empty($params)) {
        $st->bind_param($types, ...$params);
    }
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) $services[] = $row;
    $st->close();
} catch (Exception $e) {
    $services = [];
    $errMsg = $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Services | FitZone</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($cssPath) ?>">

  <style>
    body{margin:0;background:#0b1220;color:#eaf0ff;font-family:Arial,Helvetica,sans-serif;}
    .container{max-width:1100px;margin:0 auto;padding:28px;}
    .topbar{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:18px;}
    .title{margin:0;font-size:26px;line-height:1.2;}
    .muted{opacity:.8;font-size:14px;margin-top:6px;}
    .btn{display:inline-block;padding:10px 14px;border-radius:12px;text-decoration:none;font-weight:800;}
    .btn-outline{border:1px solid rgba(255,255,255,.20);color:#eaf0ff;background:transparent;}
    .btn-outline:hover{border-color:rgba(76,159,255,.5);}

    .panel{
      background:#111a2e;border:1px solid rgba(255,255,255,.08);
      border-radius:18px;padding:16px;margin-bottom:16px;
    }

    form.filters{display:grid;grid-template-columns: 1.4fr .8fr .8fr auto; gap:10px; align-items:end;}
    @media (max-width: 850px){ form.filters{grid-template-columns: 1fr 1fr; } }
    @media (max-width: 520px){ form.filters{grid-template-columns: 1fr; } }

    label{display:block;margin-bottom:6px;font-size:13px;opacity:.85;}
    input, select{
      width:100%;padding:12px;border-radius:12px;
      border:1px solid rgba(255,255,255,.12);
      background:#0b1220;color:#eaf0ff;outline:none;
    }
    input:focus, select:focus{border-color:rgba(76,159,255,.7);}

    .btn-primary{background:#4c9fff;color:#071022;border:0;cursor:pointer;}
    .btn-primary:hover{filter:brightness(1.05);}

    .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
    @media (max-width: 950px){ .grid{grid-template-columns:repeat(2,1fr);} }
    @media (max-width: 560px){ .grid{grid-template-columns:1fr;} }

    .card{
      background:#111a2e;border:1px solid rgba(255,255,255,.08);
      border-radius:18px;padding:18px;min-height:170px;
      display:flex;flex-direction:column;justify-content:space-between;
    }
    .card h3{margin:0 0 8px;}
    .badge{
      display:inline-block;padding:6px 10px;border-radius:999px;
      border:1px solid rgba(255,255,255,.15); font-size:12px; opacity:.9;
      margin-bottom:10px;
    }
    .desc{margin:0;opacity:.85;line-height:1.4;}
    .meta{display:flex;justify-content:space-between;gap:10px;margin-top:14px;opacity:.9;font-weight:700;}
    .empty{
      text-align:center;padding:26px;border-radius:18px;
      background:#111a2e;border:1px dashed rgba(255,255,255,.15);
      opacity:.9;
    }
    .error{
      padding:12px;border-radius:12px;background:rgba(255,77,79,.12);
      border:1px solid rgba(255,77,79,.30); margin-bottom:14px;
    }
    .crumbs{opacity:.8;font-size:13px;margin-top:2px;}
    .crumbs a{color:#4c9fff;text-decoration:none;font-weight:800;}
    .crumbs a:hover{text-decoration:underline;}
  </style>
</head>
<body>

<div class="container">
  <div class="topbar">
    <div>
      <h2 class="title">🏋️ Services</h2>
      <div class="crumbs"><a href="dashboard.php">← Back to Dashboard</a></div>
      <div class="muted">Search and explore FitZone gym services & classes</div>
    </div>
    <a class="btn btn-outline" href="logout.php">Logout</a>
  </div>

  <?php if (!empty($errMsg ?? '')): ?>
    <div class="error">
      <b>DB Error:</b> <?= htmlspecialchars($errMsg) ?>
    </div>
  <?php endif; ?>

  <div class="panel">
    <form class="filters" method="get">
      <div>
        <label>Search</label>
        <input type="text" name="q" placeholder="e.g., yoga, zumba, personal training..." value="<?= htmlspecialchars($q) ?>">
      </div>

      <div>
        <label>Category</label>
        <select name="cat">
          <option value="all">All</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= ($cat === $c ? 'selected' : '') ?>>
              <?= htmlspecialchars($c) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label>Sort</label>
        <select name="sort">
          <option value="new" <?= ($sort==='new'?'selected':'') ?>>Newest</option>
          <option value="title" <?= ($sort==='title'?'selected':'') ?>>Title A-Z</option>
          <option value="price_asc" <?= ($sort==='price_asc'?'selected':'') ?>>Price Low → High</option>
          <option value="price_desc" <?= ($sort==='price_desc'?'selected':'') ?>>Price High → Low</option>
        </select>
      </div>

      <div>
        <label>&nbsp;</label>
        <button class="btn btn-primary" type="submit">Search</button>
      </div>
    </form>
  </div>

  <?php if (empty($services)): ?>
    <div class="empty">
      <h3 style="margin:0 0 6px;">No services found 😕</h3>
      <div style="opacity:.85;">Try changing search keywords or filters.</div>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($services as $s): ?>
        <div class="card">
          <div>
            <span class="badge"><?= htmlspecialchars($s['category']) ?></span>
            <h3><?= htmlspecialchars($s['title']) ?></h3>
            <p class="desc">
              <?= htmlspecialchars(mb_strimwidth($s['description'] ?? '', 0, 140, '...')) ?>
            </p>
          </div>

          <div class="meta">
            <div>⏱ <?= (int)($s['duration_minutes'] ?? 60) ?> min</div>
            <div>💰 Rs <?= number_format((float)($s['price'] ?? 0), 2) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

</body>
</html>