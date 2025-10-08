const API_BASE = 'https://rest.coincap.io/v3';
const API_KEY = '21febbcd12d14df72f087ea10d950f611402aa2dbb32d11b59f8d408cef02c50';

const money = n => (n ? Number(n).toLocaleString('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 2 }) : '-');
const num = n => (n ? Number(n).toLocaleString('en-US', { maximumFractionDigits: 0 }) : '-');
const AUTH_HEADERS = { 'Authorization': 'Bearer ' + API_KEY };
const fetchJson = (url) => fetch(url, { headers: AUTH_HEADERS }).then(r => { if (!r.ok) throw new Error('Request failed'); return r.json(); });
const getAssets = (limit = 50) => fetchJson(`${API_BASE}/assets?limit=${limit}`);
const getAsset = (id) => fetchJson(`${API_BASE}/assets/` + encodeURIComponent(id));
const getAssetHistory = (id, start, end, interval = 'd1') => fetchJson(`${API_BASE}/assets/` + encodeURIComponent(id) + `/history?interval=${interval}&start=${start}&end=${end}`);

async function loadAssets() {
	const body = document.getElementById('coinsBody');
	body.innerHTML = '';
	const { data = [] } = await getAssets(50);
	data.forEach(a => {
		const icon = a.symbol ? `https://assets.coincap.io/assets/icons/${String(a.symbol).toLowerCase()}@2x.png` : '';
		const pct = Number(a.changePercent24Hr);
		const pctClass = Number.isFinite(pct) ? (pct >= 0 ? 'pct-pos' : 'pct-neg') : '';
		const pctText = Number.isFinite(pct) ? pct.toFixed(2) : '-';
		const tr = document.createElement('tr');
		tr.innerHTML = `
			<td><span class="badge">${icon ? `<img src="${icon}" alt="">` : ''}${a.symbol || ''}</span></td>
			<td>${a.name || ''}</td>
			<td>${money(a.priceUsd)}</td>
			<td>${money(a.marketCapUsd)}</td>
			<td class="${pctClass}">${pctText}</td>
			<td><button class="btn more-info" type="button">More info</button></td>
			<td><button class="btn btn-primary add-wallet" type="button">Add to wallet</button></td>
		`;
		const btn = tr.querySelector('.more-info');
		btn.onclick = (ev) => { ev.stopPropagation(); openModalWithAsset(a.id); };
		const addBtn = tr.querySelector('.add-wallet');
		addBtn.onclick = (ev) => { ev.stopPropagation(); openAddToWalletModal(a); };
		body.appendChild(tr);
	});
}

async function openModalWithAsset(id) {
	const overlay = document.getElementById('overlay');
	const content = document.getElementById('modalContent');
	const closeBtn = document.getElementById('modalClose');
	overlay.style.display = '';
	content.textContent = 'Loading...';
	const { data: a } = await getAsset(id);
	const now = Date.now();
	const start = now - 7 * 24 * 60 * 60 * 1000;
	const hist = await getAssetHistory(id, start, now, 'h1');
	content.innerHTML = `<h3>${a.name} <span class="muted">(${a.symbol})</span></h3>
	Price: ${money(a.priceUsd)}<br>
	Market Cap: ${money(a.marketCapUsd)}<br>
	Volume (24h): ${money(a.volumeUsd24Hr)}<br>
	Supply: ${num(a.supply)}
	<div style=\"margin-top:12px;height:220px\"><canvas id=\"assetChart\"></canvas></div>`;
	const points = (hist.data || []).map(p => ({ t: p.time, y: Number(p.priceUsd) }));
	renderLineChart('assetChart', points, a.name + ' price (USD)');
	const close = () => { overlay.style.display = 'none'; };
	closeBtn.onclick = close;
	overlay.onclick = (e) => { if (e.target === overlay) close(); };
}

let currentChart;
function renderLineChart(canvasId, points, label) {
	const ctx = document.getElementById(canvasId);
	if (!ctx || !window.Chart) return;
	if (currentChart) { currentChart.destroy(); }
	const labels = points.map(p => p.t);
	const data = points.map(p => p.y);
	currentChart = new Chart(ctx, {
		type: 'line',
		data: {
			labels,
			datasets: [{
				label,
				data,
				borderColor: '#60a5fa',
				backgroundColor: 'rgba(96,165,250,.15)',
				pointRadius: 0,
				borderWidth: 2,
				fill: true,
				tension: 0.2
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: { display: false },
				tooltip: {
					callbacks: {
						label: (ctx) => '$' + Number(ctx.parsed.y).toLocaleString('en-US', { maximumFractionDigits: 2 })
					}
				}
			},
			scales: {
				x: {
					type: 'time',
					time: { unit: 'day' },
					grid: { color: '#1f2937' },
					ticks: { color: '#94a3b8', maxTicksLimit: 7 }
				},
				y: {
					grid: { color: '#1f2937' },
					ticks: {
						color: '#94a3b8',
						callback: (v) => '$' + Number(v).toLocaleString('en-US', { maximumFractionDigits: 0 })
					}
				}
			}
		}
	});
}

// --- Wallet helpers ---
function openAddToWalletModal(asset) {
	const overlay = document.getElementById('overlay');
	const content = document.getElementById('modalContent');
	const closeBtn = document.getElementById('modalClose');
	if (!overlay || !content) { return; }
	const price = Number(asset.priceUsd);
	const title = `${asset.name} <span class="muted">(${asset.symbol})</span>`;
	content.innerHTML = `
		<h3 style="margin-bottom:8px;">${title}</h3>
		<div style="margin:6px 0">Current price ${money(price)}</div>
		<label style="display:block;margin:6px 0">Amount: <input id="addAmt" type="number" step="any" value="1" style="padding:4px"></label>
		<div id="addTotal" style="margin:6px 0">Total: ${money(price * 1)}</div>
		<button id="addConfirm" class="btn" type="button">Add</button>
	`;
	const amtEl = content.querySelector('#addAmt');
	const totalEl = content.querySelector('#addTotal');
	const addBtn = content.querySelector('#addConfirm');
	const recalc = () => {
		const val = Number(amtEl.value);
		totalEl.textContent = `Total: ${money((Number.isFinite(val) && val > 0 ? val : 0) * price)}`;
	};
	amtEl.addEventListener('input', recalc);
	addBtn.onclick = async () => {
		const amount = Number(amtEl.value);
		if (!Number.isFinite(amount) || amount <= 0) { alert('Invalid amount'); return; }
		try {
			await postJson('wallet.php?action=add_wallet', {
				coin_id: asset.id,
				coin_symbol: asset.symbol,
				coin_name: asset.name,
				price_usd: price,
				amount
			});
			overlay.style.display = 'none';
			showSuccess(`${asset.name} toegevoegd aan wallet`);
			if (document.getElementById('walletBody')) loadWallet();
		} catch (e) { alert('Add failed'); }
	};
	const close = () => { overlay.style.display = 'none'; };
	closeBtn.onclick = close;
	overlay.onclick = (e) => { if (e.target === overlay) close(); };
	overlay.style.display = '';
}

async function loadWallet() {
	const body = document.getElementById('walletBody');
	body.innerHTML = '';
	try {
		const res = await fetch('wallet.php?action=list_wallet');
		const json = await res.json();
		if (!json.ok) throw new Error('load error');
		for (const r of json.rows) {
			const tr = document.createElement('tr');
			tr.innerHTML = `
				<td>${r.id}</td>
				<td>${r.date_bought}</td>
				<td>${r.coin_name} (${r.coin_symbol})</td>
				<td>${money(r.price_usd)}</td>
				<td><input type="number" step="any" class="amt" value="${r.amount}"></td>
				<td>${money(r.total)}</td>
				<td><button class="btn btn-warn save" type="button">Save</button></td>
				<td><button class="btn btn-danger del" type="button">Delete</button></td>
			`;
			tr.querySelector('.save').onclick = async () => {
				const val = Number(tr.querySelector('.amt').value);
				if (!Number.isFinite(val) || val < 0) { alert('Invalid amount'); return; }
				try {
					await postJson('wallet.php?action=update_wallet', { id: r.id, amount: val });
					showSuccess('Saved');
					loadWallet();
				} catch (e) { alert('Save failed'); }
			};
			tr.querySelector('.del').onclick = async () => {
				if (!confirm('Delete this row?')) return;
				try {
					await postJson('wallet.php?action=delete_wallet', { id: r.id });
					showSuccess('Deleted');
					loadWallet();
				} catch (e) { alert('Delete failed'); }
			};
			body.appendChild(tr);
		}
		document.getElementById('walletGrandTotal').textContent = money(json.grandTotal);
	} catch (e) {
		body.innerHTML = '<tr><td colspan="8">Failed to load wallet</td></tr>';
	}
}

function showSuccess(msg) {
	const el = document.getElementById('success');
	if (!el) return;
	el.textContent = msg;
	el.style.display = '';
	setTimeout(() => { el.style.display = 'none'; }, 1500);
}

async function postJson(url, data) {
	const res = await fetch(url, {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: new URLSearchParams(Object.entries(data)).toString()
	});
	const json = await res.json();
	if (!json.ok) throw new Error(json.error || 'Request failed');
	return json;
}

addEventListener('DOMContentLoaded', () => {
	// detect if we are on market or wallet page by element presence
	if (document.getElementById('coinsBody')) {
		loadAssets();
	}
	if (document.getElementById('walletBody')) {
		loadWallet();
	}
});
