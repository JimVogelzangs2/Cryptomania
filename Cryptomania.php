<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Cryptomania</title>
	<link href="assets/cryptomania.css" rel="stylesheet">
</head>
<body>
	<header style="background-color: #1f2937; color: white; padding: 10px;">
		<nav style="display:flex; gap:12px; align-items:center;">
			<strong style="flex:1;">Cryptomania</strong>
			<a href="Cryptomania.php" style="color:#fff; text-decoration:none;">Home</a>
			<a href="wallet.php" style="color:#fff; text-decoration:none;">Wallet</a>
		</nav>
	</header>
	<div class="container">
		<div id="home">
			<h2>Market</h2>
			<div id="note">Click a coin for details.</div>
			<div id="pre" class="pre" style="display:none;"><span class="spin"></span></div>
			<table class="table" id="coins">
				<thead>
					<tr>
						<th>Short</th>
						<th>Coin</th>
						<th>Price</th>
						<th>Market Cap</th>
						<th>%24hr</th>
						<th>More info</th>
						<th>Add</th>
					</tr>
				</thead>
				<tbody id="coinsBody"></tbody>
			</table>
			<div id="error" class="error" style="display:none;"></div>
		</div>
	</div>
	<div id="overlay" class="overlay" style="display:none;">
		<div class="modal" id="modalPanel">
			<button type="button" class="modal-close" id="modalClose" aria-label="Close">×</button>
			<div id="modalContent">Loading...</div>
		</div>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
	<script src="assets/cryptomania.js"></script>
</body>
</html>
