<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config/db.php";

/* ✅ Get Blog ID */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: /fitzone/index.php#blog");
    exit;
}

/* ✅ View Counter */
$upd = $conn->prepare("UPDATE blogs SET views = views + 1 WHERE id=?");
$upd->bind_param("i", $id);
$upd->execute();

/* ✅ Fetch Blog + Category */
$stmt = $conn->prepare("
  SELECT b.id, b.title, b.content, b.created_at, b.views, b.category_id, c.name AS category
  FROM blogs b
  LEFT JOIN categories c ON c.id = b.category_id
  WHERE b.id=? LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$blog = $stmt->get_result()->fetch_assoc();

if (!$blog) {
    header("Location: /fitzone/index.php#blog");
    exit;
}

/* ✅ Likes count */
$likesStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM blog_likes WHERE blog_id=?");
$likesStmt->bind_param("i", $id);
$likesStmt->execute();
$likesCount = (int)$likesStmt->get_result()->fetch_assoc()['cnt'];

/* ✅ Comments fetch */
$cq = $conn->prepare("
  SELECT name, comment, created_at
  FROM blog_comments
  WHERE blog_id=? AND status='approved'
  ORDER BY created_at DESC
");
$cq->bind_param("i", $id);
$cq->execute();
$comments = $cq->get_result();

/* ✅ Helpers */
$title = htmlspecialchars($blog['title'] ?? 'Blog');
$categoryName = $blog['category'] ?? 'General';
$category = htmlspecialchars($categoryName);
$dateText = date("F d, Y", strtotime($blog['created_at']));
$views = (int)($blog['views'] ?? 0);
$contentSafe = nl2br(htmlspecialchars($blog['content'] ?? ''));

/* ✅ Category detect */
$catRaw   = strtolower(trim($categoryName));
$titleRaw = strtolower(trim($blog['title'] ?? ''));

$isWorkout   = (str_contains($catRaw, 'workout') || str_contains($catRaw, 'fitness') || str_contains($catRaw, 'training') || str_contains($titleRaw, 'warm') || str_contains($titleRaw, 'routine'));
$isBreakfast = (str_contains($catRaw, 'breakfast') || str_contains($catRaw, 'nutrition') || str_contains($catRaw, 'diet') || str_contains($titleRaw, 'breakfast'));
$isSuccess   = (str_contains($catRaw, 'success') || str_contains($catRaw, 'story') || str_contains($catRaw, 'member') || str_contains($titleRaw, 'success') || str_contains($titleRaw, 'story'));

/* ✅ Badges based on type */
if ($isWorkout) {
  $badgeLeft  = "📈 Difficulty: Beginner";
  $badgeRight = "🟢 Estimated Calories: 30–60 kcal";
} elseif ($isBreakfast) {
  $badgeLeft  = "🥣 Category: Nutrition";
  $badgeRight = "⚡ Goal: Energy + Health";
} elseif ($isSuccess) {
  $badgeLeft  = "🏆 Category: Success Story";
  $badgeRight = "🔥 Focus: Consistency";
} else {
  $badgeLeft  = "✅ Category: General";
  $badgeRight = "💡 Tip: Stay Active";
}

/* ✅ Share Links */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$currentUrl = $scheme . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$shareText = "FitZone Blog: " . ($blog['title'] ?? 'Check this');
$waLink = "https://wa.me/?text=" . urlencode($shareText . " " . $currentUrl);
$fbLink = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($currentUrl);

/* ✅ Related Posts */
$relatedPosts = [];
$catId = (int)($blog['category_id'] ?? 0);

if ($catId > 0) {
  $rs = $conn->prepare("
    SELECT id, title, created_at
    FROM blogs
    WHERE category_id = ? AND id <> ?
    ORDER BY created_at DESC
    LIMIT 3
  ");
  $rs->bind_param("ii", $catId, $id);
  $rs->execute();
  $relatedPosts = $rs->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $title ?> | FitZone</title>

  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body{ min-height:100vh; display:flex; flex-direction:column; }
    main{ flex:1; }

    .blog-detail{ padding:60px 20px; }
    .blog-card-detail{
      background:#16313c;
      border:1px solid rgba(255,255,255,0.08);
      border-radius:14px;
      padding:26px 22px;
      max-width: 900px;
      margin: 0 auto;
      color:#fff;
    }
    .blog-top{
      display:flex;
      justify-content:space-between;
      flex-wrap:wrap;
      gap:12px;
      margin-bottom:14px;
      opacity:.9;
      font-size:14px;
    }
    .badge{
      display:inline-flex;
      align-items:center;
      gap:8px;
      background: rgba(255,255,255,0.06);
      padding:8px 12px;
      border-radius:999px;
      border:1px solid rgba(255,255,255,0.08);
    }

    .blog-title{ font-size:34px; margin-bottom:10px; }
    .blog-content{ line-height:1.95; font-size:20px; opacity:.95; margin-top:14px; }

    .actions{
      display:flex;
      gap:12px;
      flex-wrap:wrap;
      margin-top:18px;
      align-items:center;
    }
    .like-btn{
      background:#ff6b00;
      border:none;
      color:#fff;
      padding:10px 16px;
      border-radius:12px;
      cursor:pointer;
      font-weight:700;
      display:inline-flex;
      align-items:center;
      gap:8px;
    }
    .like-btn:hover{ background:#e65c00; }

    .meta-small{ opacity:.85; font-size:14px; }

    .back-link{
      display:inline-block;
      margin-top:20px;
      color:#c9a227;
      text-decoration:none;
      font-weight:700;
    }
    .back-link:hover{ text-decoration:underline; }

    .share-box{
      display:flex;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
      margin-top:8px;
    }
    .share-box a{
      width:38px;
      height:38px;
      border-radius:10px;
      background: rgba(255,255,255,0.06);
      display:inline-flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      text-decoration:none;
      transition:.25s;
    }
    .share-box a:hover{
      transform:translateY(-3px);
      background: rgba(255,255,255,0.14);
    }

    .comment-wrap{ max-width: 900px; margin: 22px auto 0; }
    .comment-item{
      background:#16313c;
      border:1px solid rgba(255,255,255,0.08);
      border-radius:14px;
      padding:16px 16px;
      margin-bottom:12px;
      color:#fff;
    }
    .comment-item p{ margin:10px 0 0; line-height:1.8; opacity:.95; }
    .comment-meta{ opacity:.75; font-size:13px; margin-top:4px; }

    .comment-form{
      background:#0f2027;
      border:1px solid rgba(255,255,255,0.08);
      border-radius:14px;
      padding:16px;
      margin-top:16px;
      color:#fff;
    }
    .comment-form input, .comment-form textarea{
      width:100%;
      background:#111315;
      border:1px solid #1f3b45;
      color:#fff;
      border-radius:12px;
      padding:12px;
      outline:none;
      box-sizing:border-box;
    }
    .comment-form textarea{ resize:vertical; min-height:120px; }
    .comment-form .btn{ margin-top:12px; }

    .pro-card{
      background:#0f2430;
      border:1px solid rgba(255,255,255,0.08);
      border-radius:14px;
      padding:16px;
      margin-top:18px;
      color:#fff;
    }
    .pro-card h3{ margin:0 0 12px; font-size:18px; }
    .pro-card ul{ margin:0; padding-left:18px; line-height:1.9; }
    .pro-card li{ opacity:.95; }

    .exercise-grid{
      display:grid;
      grid-template-columns: repeat(4, 1fr);
      gap:12px;
      margin-top:12px;
    }
    @media(max-width: 850px){
      .exercise-grid{ grid-template-columns: repeat(2, 1fr); }
    }
    .exercise-box{
      background: rgba(0,0,0,0.18);
      border:1px solid rgba(255,255,255,0.08);
      border-radius:14px;
      padding:10px;
      transition:.2s;
    }
    .exercise-box:hover{ transform: translateY(-2px); }
    .exercise-box img{
      width:100%;
      height:130px;
      object-fit:cover;
      border-radius:12px;
      transition:.25s;
    }
    .exercise-box img:hover{ transform: scale(1.02); }
    .ex-name{ margin-top:8px; font-size:13px; opacity:.9; }

    table.pro-table{
      width:100%;
      border-collapse:collapse;
      overflow:hidden;
      border-radius:12px;
    }
    table.pro-table th, table.pro-table td{
      padding:10px;
      border-bottom:1px solid rgba(255,255,255,0.08);
      text-align:left;
      font-size:14px;
      opacity:.95;
    }
    table.pro-table th{ opacity:.8; font-weight:700; }
  </style>
</head>

<body>

<?php include __DIR__ . "/includes/navbar.php"; ?>

<main>
  <section class="blog-detail reveal">
    <div class="blog-card-detail">

      <h1 class="blog-title"><?= $title ?></h1>

      <div class="blog-top">
        <span class="badge"><i class="fa-solid fa-calendar"></i> <?= htmlspecialchars($dateText) ?></span>
        <span class="badge"><i class="fa-solid fa-tag"></i> <?= $category ?></span>
        <span class="badge"><i class="fa-solid fa-eye"></i> <?= $views ?> views</span>
      </div>

      <div class="blog-top" style="margin-top:8px;">
        <span class="badge" style="background:rgba(25,135,84,0.18);border-color:rgba(25,135,84,0.3);">
          <?= htmlspecialchars($badgeLeft) ?>
        </span>
        <span class="badge" style="background:rgba(255,193,7,0.15);border-color:rgba(255,193,7,0.3);">
          <?= htmlspecialchars($badgeRight) ?>
        </span>
      </div>

      <div class="blog-content">
        <?= $contentSafe ?>
      </div>

      <?php if ($isWorkout): ?>

        <div class="pro-card">
          <h3>⏱ Duration Breakdown (1 Minute Each)</h3>
          <div style="overflow:auto;">
            <table class="pro-table">
              <thead>
                <tr>
                  <th>Minute</th><th>Exercise</th><th>Purpose</th>
                </tr>
              </thead>
              <tbody>
                <tr><td><b>1</b></td><td>Jumping Jacks</td><td>Increase heart rate & warm the body</td></tr>
                <tr><td><b>2</b></td><td>Arm Circles + Shoulder Rolls</td><td>Loosen upper body joints</td></tr>
                <tr><td><b>3</b></td><td>Dynamic Stretching (Leg Swings)</td><td>Improve mobility & flexibility</td></tr>
                <tr><td><b>4</b></td><td>Bodyweight Squats</td><td>Activate legs & glutes</td></tr>
                <tr><td><b>5</b></td><td>Push-ups (or Knee Push-ups)</td><td>Engage chest, arms & core</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="pro-card">
          <h3>🎯 Who Should Do This Routine?</h3>
          <ul>
            <li>Beginners starting workouts 💪</li>
            <li>People doing weight training 🏋️</li>
            <li>Cardio like running / HIIT 🚴</li>
            <li>Anyone feeling stiffness 🧘</li>
          </ul>
        </div>

        <div class="pro-card">
          <h3>📌 Benefits</h3>
          <ul>
            <li>✅ Reduces injury risk</li>
            <li>✅ Improves circulation</li>
            <li>✅ Better mobility & flexibility</li>
            <li>✅ Boosts workout performance</li>
          </ul>
        </div>

        <div class="pro-card">
          <h3>📷 Exercise Examples</h3>
          <div class="exercise-grid">
            <div class="exercise-box">
              <img src="assets/img/exercises/jumping-jacks.jpg" alt="Jumping Jacks">
              <div class="ex-name">Jumping Jacks</div>
            </div>
            <div class="exercise-box">
              <img src="assets/img/exercises/arm-circles.jpg" alt="Arm Circles">
              <div class="ex-name">Arm Circles</div>
            </div>
            <div class="exercise-box">
              <img src="assets/img/exercises/leg-swings.jpg" alt="Leg Swings">
              <div class="ex-name">Leg Swings</div>
            </div>
            <div class="exercise-box">
              <img src="assets/img/exercises/squats.jpg" alt="Squats">
              <div class="ex-name">Bodyweight Squats</div>
            </div>
          </div>

          <p class="meta-small" style="margin-top:10px;">
            ⚠️ Add these images inside <b>assets/img/exercises/</b> folder with same names.
          </p>
        </div>

      <?php elseif ($isBreakfast): ?>

        <div class="pro-card">
          <h3>🥣 Healthy Breakfast Ideas</h3>
          <ul>
            <li>🍌 Oats + banana + peanut butter</li>
            <li>🥚 2 eggs + whole wheat toast</li>
            <li>🥛 Yogurt + fruits + nuts</li>
            <li>🍞 Avocado toast + boiled egg</li>
            <li>🍛 Sri Lankan healthy: red rice + dhal + egg / fish</li>
          </ul>
        </div>

        <div class="pro-card">
          <h3>📌 Benefits</h3>
          <ul>
            <li>✅ Steady energy for the day</li>
            <li>✅ Better digestion</li>
            <li>✅ Supports fat loss & muscle gain</li>
            <li>✅ Improves focus</li>
          </ul>
        </div>

        <div class="pro-card">
          <h3>💡 Quick Tip</h3>
          <p class="meta-small" style="font-size:16px;line-height:1.9;">
            Always include <b>Protein + Fiber</b> in breakfast. It keeps you full longer and prevents cravings.
          </p>
        </div>

      <?php elseif ($isSuccess): ?>

        <div class="pro-card">
          <h3>🏆 Member Success Story</h3>
          <p class="meta-small" style="font-size:16px;line-height:1.9;">
            Real transformation comes from consistent small habits. This story is a reminder that patience + discipline wins 💪
          </p>
        </div>

        <div class="pro-card">
          <h3>📌 What Worked for Them</h3>
          <ul>
            <li>✅ 3 workouts per week</li>
            <li>✅ High-protein meals</li>
            <li>✅ Daily steps target</li>
            <li>✅ Good sleep (7–8 hours)</li>
          </ul>
        </div>

        <div class="pro-card">
          <h3>🔥 Motivation</h3>
          <p class="meta-small" style="font-size:16px;line-height:1.9;">
            Small progress every week = big results in months ✨ Keep going!
          </p>
        </div>

      <?php else: ?>

        <div class="pro-card">
          <h3>✅ Quick Takeaway</h3>
          <p class="meta-small" style="font-size:16px;line-height:1.9;">
            Stay active, eat smart, and stay consistent — your future self will thank you 💙
          </p>
        </div>

      <?php endif; ?>

      <?php if (!empty($relatedPosts)): ?>
        <div class="pro-card">
          <h3>🧠 Related Posts</h3>
          <ul>
            <?php foreach ($relatedPosts as $rp): ?>
              <li style="margin-bottom:10px;">
                <a href="blog.php?id=<?= (int)$rp['id'] ?>" style="color:#ffd36a;text-decoration:none;font-weight:800;">
                  <?= htmlspecialchars($rp['title']) ?>
                </a>
                <div class="meta-small" style="margin-top:4px;">
                  <i class="fa-regular fa-calendar"></i>
                  <?= date("F d, Y", strtotime($rp['created_at'])) ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="actions">
        <button class="like-btn" id="likeBtn" type="button">
          <i class="fa-solid fa-heart"></i> Like
        </button>
        <span class="meta-small" id="likeCount"><?= $likesCount ?> likes</span>
      </div>

      <div class="share-box" style="margin-top:14px;">
        <span class="meta-small">Share:</span>

        <a href="<?= htmlspecialchars($fbLink) ?>" target="_blank" rel="noopener" title="Facebook" aria-label="Facebook">
          <i class="fa-brands fa-facebook-f"></i>
        </a>

        <a href="<?= htmlspecialchars($waLink) ?>" target="_blank" rel="noopener" title="WhatsApp" aria-label="WhatsApp">
          <i class="fa-brands fa-whatsapp"></i>
        </a>
      </div>

      <a href="/fitzone/index.php#blog" class="back-link">← Back to Blogs</a>
    </div>

    <div class="comment-wrap">
      <h2 style="margin:22px 0 12px;">Comments</h2>

      <?php if ($comments->num_rows > 0): ?>
        <?php while($c = $comments->fetch_assoc()): ?>
          <div class="comment-item">
            <strong><?= htmlspecialchars($c['name']) ?></strong>
            <div class="comment-meta">
              <i class="fa-regular fa-clock"></i>
              <?= date("F d, Y h:i A", strtotime($c['created_at'])) ?>
            </div>
            <p><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="opacity:.85;">No comments yet. Be the first one 🙂</p>
      <?php endif; ?>

      <div class="comment-form">
        <h3 style="margin-bottom:10px;">Add a Comment</h3>
        <form method="post" action="comment_add.php">
          <input type="hidden" name="blog_id" value="<?= (int)$id ?>">

          <input required name="name" placeholder="Your Name">
          <br><br>
          <textarea required name="comment" placeholder="Write your comment..."></textarea>

          <button class="btn" type="submit">Post Comment</button>
        </form>
      </div>
    </div>

  </section>
</main>

<?php include __DIR__ . "/includes/footer.php"; ?>

<script>
document.getElementById("likeBtn")?.addEventListener("click", async () => {
  const res = await fetch("like.php", {
    method: "POST",
    headers: {"Content-Type": "application/x-www-form-urlencoded"},
    body: "id=<?= (int)$id ?>"
  });

  const data = await res.json();
  if (data.ok) {
    document.getElementById("likeCount").innerText = data.likes + " likes";
  } else {
    alert(data.msg || "Unable to like");
  }
});
</script>

<script src="js/script.js"></script>
</body>
</html>