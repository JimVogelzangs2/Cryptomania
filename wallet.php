<?php
// simple wallet API + page
ini_set('session.save_path', sys_get_temp_dir());
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$dbHost = 'localhost';
$dbName = 'cryptomania';
$dbUser = 'root';
$dbPass = '';

function db() {
	static $pdo = null;
	global $dbHost, $dbName, $dbUser, $dbPass;
	if ($pdo === null) {
		$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
		$pdo = new PDO($dsn, $dbUser, $dbPass, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		]);
	}
	return $pdo;
}

if (isset($_GET['action'])) {
	header('Content-Type: application/json');
	$action = $_GET['action'];
	try {
		if ($action === 'list_wallet') {
			$stmt = db()->prepare('SELECT id, date_bought, coin_symbol, coin_name, price_usd, amount FROM wallet WHERE user_id = ? ORDER BY id DESC');
			$stmt->execute([$_SESSION['user_id']]);
			$rows = $stmt->fetchAll();
			$total = 0;
			foreach ($rows as &$r) {
				$r['total'] = round(floatval($r['price_usd']) * floatval($r['amount']), 8);
				$total += $r['total'];
			}
			echo json_encode(['ok' => true, 'rows' => $rows, 'grandTotal' => $total]);
			exit;
		}
		if ($action === 'add_wallet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$coinId = trim($_POST['coin_id'] ?? '');
			$symbol = trim($_POST['coin_symbol'] ?? '');
			$name = trim($_POST['coin_name'] ?? '');
			$price = floatval($_POST['price_usd'] ?? 0);
			$amount = floatval($_POST['amount'] ?? 0);
			if ($symbol === '' || $name === '' || $amount <= 0 || $price <= 0) throw new Exception('Invalid data');

			// Check if user already has this coin
			$stmt = db()->prepare('SELECT id, amount FROM wallet WHERE user_id = ? AND coin_symbol = ?');
			$stmt->execute([$_SESSION['user_id'], $symbol]);
			$existing = $stmt->fetch();

			if ($existing) {
				// Update existing amount
				$newAmount = floatval($existing['amount']) + $amount;
				$stmt = db()->prepare('UPDATE wallet SET amount = ?, price_usd = ? WHERE id = ?');
				$stmt->execute([$newAmount, $price, $existing['id']]);
			} else {
				// Insert new entry
				$stmt = db()->prepare('INSERT INTO wallet (user_id, date_bought, coin_id, coin_symbol, coin_name, price_usd, amount) VALUES (?, CURRENT_DATE(), ?, ?, ?, ?, ?)');
				$stmt->execute([$_SESSION['user_id'], $coinId, $symbol, $name, $price, $amount]);
			}
			echo json_encode(['ok' => true]);
			exit;
		}
		if ($action === 'update_wallet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$id = intval($_POST['id'] ?? 0);
			$amount = floatval($_POST['amount'] ?? -1);
			if ($id <= 0 || $amount < 0) throw new Exception('Invalid data');
			$stmt = db()->prepare('UPDATE wallet SET amount = :amount WHERE id = :id AND user_id = :uid');
			$stmt->execute([':amount' => $amount, ':id' => $id, ':uid' => $_SESSION['user_id']]);
			echo json_encode(['ok' => true]);
			exit;
		}
		if ($action === 'delete_wallet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$id = intval($_POST['id'] ?? 0);
			if ($id <= 0) throw new Exception('Invalid id');
			$stmt = db()->prepare('DELETE FROM wallet WHERE id = :id AND user_id = :uid');
			$stmt->execute([':id' => $id, ':uid' => $_SESSION['user_id']]);
			echo json_encode(['ok' => true]);
			exit;
		}
		echo json_encode(['ok' => false, 'error' => 'Unknown action']);
		exit;
	} catch (Throwable $e) {
		http_response_code(400);
		echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
		exit;
	}
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Wallet - Cryptomania</title>
	<link href="assets/cryptomania.css" rel="stylesheet">
</head>
<body>
	<header style="background-color: #1f2937; color: white; padding: 10px;">
		<nav style="display:flex; gap:12px; align-items:center;">
			<strong style="flex:1;">Cryptomania</strong>
			<a href="Cryptomania.php" style="color:#fff; text-decoration:none;">Home</a>
			<a href="wallet.php" style="color:#fff; text-decoration:none;">Wallet</a>
			<a href="login.php?logout=1" style="color:#fff; text-decoration:none;">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
		</nav>
	</header>
	<div class="container">
		<h2>Crypto portfolio</h2>
		<div id="success" class="success" style="display:none;"></div>
		<table class="table" id="walletTable">
			<thead>
				<tr>
					<th>Id</th>
					<th>Bought on</th>
					<th>Name</th>
					<th>Price</th>
					<th>Amount</th>
					<th>Total</th>
					<th>Save</th>
					<th>Delete</th>
				</tr>
			</thead>
			<tbody id="walletBody"></tbody>
			<tfoot>
				<tr>
					<td colspan="5" style="text-align:right; font-weight:bold;">Total value</td>
					<td id="walletGrandTotal">-</td>
					<td colspan="2"></td>
				</tr>
			</tfoot>
		</table>
		<p style="margin-top:10px;opacity:.8">Tip: voeg vanaf de Home/Market pagina munten toe via “Add to wallet”.</p>
	</div>
	<script src="https://unpkg.com/mustache@4.2.0/mustache.min.js"></script>
	<script src="assets/cryptomania.js"></script>
</body>
</html>

