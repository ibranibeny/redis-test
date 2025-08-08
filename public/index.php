<?php
// public/index.php
require_once __DIR__ . '/../redis.php';
$redis = redisClient();

/**
 * Demo user & keys
 */
$userId  = 1001;
$userKey = "user:$userId";
$cartKey = "cart:$userId";
$catalogKey = "product:catalog";

/**
 * Seed user & products (idempotent)
 */
if (!$redis->exists($userKey)) {
    $redis->hMSet($userKey, ['name' => 'Beny', 'email' => 'ibrani.beny@gmail.com']);
}
if (!$redis->exists($catalogKey)) {
    $products = [
        'sku123' => ['name' => 'iPhone 15',             'price' => 1500],
        'sku888' => ['name' => 'Macbook Air',    'price' => 1200],
        'sku555' => ['name' => 'Xiaomi 14 Pro',         'price' =>  900],
        'sku777' => ['name' => 'Pixel 8 Pro',           'price' => 1100],
    ];
    foreach ($products as $sku => $info) {
        $redis->hMSet("product:$sku", $info);
        $redis->sAdd($catalogKey, $sku); // keep a simple catalog set
    }
}

/**
 * Actions
 */
$action = $_POST['action'] ?? $_GET['action'] ?? null;

function addToCart($redis, $cartKey, $sku, $qty) {
    if ($qty <= 0) return;
    $redis->hIncrBy($cartKey, $sku, $qty);
}
function updateQty($redis, $cartKey, $sku, $qty) {
    if ($qty <= 0) {
        $redis->hDel($cartKey, $sku);
    } else {
        $redis->hSet($cartKey, $sku, $qty);
    }
}
function clearCart($redis, $cartKey) {
    $redis->del($cartKey);
}

if ($action === 'add') {
    $sku = $_POST['sku'] ?? '';
    $qty = (int)($_POST['qty'] ?? 1);
    addToCart($redis, $cartKey, $sku, $qty);
    header('Location: /'); exit;
}
if ($action === 'update') {
    $sku = $_POST['sku'] ?? '';
    $qty = (int)($_POST['qty'] ?? 1);
    updateQty($redis, $cartKey, $sku, $qty);
    header('Location: /'); exit;
}
if ($action === 'remove') {
    $sku = $_POST['sku'] ?? '';
    $redis->hDel($cartKey, $sku);
    header('Location: /'); exit;
}
if ($action === 'clear') {
    clearCart($redis, $cartKey);
    header('Location: /'); exit;
}

/**
 * Read models
 */
$user  = $redis->hGetAll($userKey);
$cart  = $redis->hGetAll($cartKey);
$skus  = $redis->sMembers($catalogKey);

// Build product list
$products = [];
foreach ($skus as $sku) {
    $p = $redis->hGetAll("product:$sku");
    if (!empty($p)) {
        $p['sku']   = $sku;
        $p['price'] = (float)$p['price'];
        $products[] = $p;
    }
}

// Compute totals
$grandTotal = 0.0;
$cartRows = [];
foreach ($cart as $sku => $qty) {
    $qty = (int)$qty;
    $p = $redis->hGetAll("product:$sku");
    if (!$p) continue;
    $price = (float)$p['price'];
    $itemTotal = $qty * $price;
    $grandTotal += $itemTotal;
    $cartRows[] = [
        'sku' => $sku,
        'name' => $p['name'] ?? $sku,
        'price' => $price,
        'qty' => $qty,
        'total' => $itemTotal,
    ];
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Redis Cart GUI</title>
  <link rel="stylesheet" href="/style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<header>
  <h1>Shopping Cart</h1>
  <div class="user">
    <strong>User:</strong> <?= htmlspecialchars($user['name'] ?? "Guest") ?>
    <span>(ID: <?= $userId ?>)</span>
  </div>
</header>

<main class="grid">
  <section>
    <h2>Products</h2>
    <div class="cards">
      <?php foreach ($products as $p): ?>
        <div class="card">
          <div class="card-title"><?= htmlspecialchars($p['name']) ?></div>
          <div class="price">$<?= number_format($p['price'], 2) ?></div>
          <form method="post" class="row">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="sku" value="<?= htmlspecialchars($p['sku']) ?>">
            <label>
              Qty
              <input type="number" min="1" step="1" name="qty" value="1">
            </label>
            <button type="submit">Add to Cart</button>
          </form>
          <small class="muted">SKU: <?= htmlspecialchars($p['sku']) ?></small>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section>
    <h2>Your Cart</h2>
    <?php if (empty($cartRows)): ?>
      <p class="muted">Your cart is empty.</p>
    <?php else: ?>
      <table>
        <thead>
        <tr>
          <th>SKU</th>
          <th>Item</th>
          <th>Price</th>
          <th>Qty</th>
          <th>Total</th>
          <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($cartRows as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['sku']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td>$<?= number_format($row['price'], 2) ?></td>
            <td>
              <form method="post" class="inline">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="sku" value="<?= htmlspecialchars($row['sku']) ?>">
                <input type="number" min="0" step="1" name="qty" value="<?= $row['qty'] ?>">
                <button type="submit">Update</button>
              </form>
            </td>
            <td>$<?= number_format($row['total'], 2) ?></td>
            <td>
              <form method="post" class="inline">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="sku" value="<?= htmlspecialchars($row['sku']) ?>">
                <button type="submit" class="danger">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" class="right"><strong>Grand Total</strong></td>
            <td colspan="2"><strong>$<?= number_format($grandTotal, 2) ?></strong></td>
          </tr>
        </tfoot>
      </table>
      <form method="post" class="right">
        <input type="hidden" name="action" value="clear">
        <button type="submit" class="secondary">Clear Cart</button>
      </form>
    <?php endif; ?>
  </section>
</main>

<footer>
  <small>Backed by Redis Hashes:
    <code>user:<?= $userId ?></code>,
    <code>cart:<?= $userId ?></code>,
    <code>product:&lt;sku&gt;</code>,
    catalog <code><?= $catalogKey ?></code>
  </small>
</footer>
</body>
</html>

