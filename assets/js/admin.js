(function () {
	'use strict';

	const $ = (sel) => document.querySelector(sel);
	const $$ = (sel) => Array.from(document.querySelectorAll(sel));

	const form     = $('#ssr-form');
	const previewBtn = $('#ssr-preview');
	const executeBtn = $('#ssr-execute');
	const progress = $('#ssr-progress');
	const progressText = $('#ssr-progress-text');
	const progressFill = $('#ssr-progress-fill');
	const results  = $('#ssr-results');
	const summary  = $('#ssr-results-summary');
	const tbody    = $('#ssr-results-table tbody');
	const regexErr = $('#ssr-regex-error');

	let lastPreviewHash = null;
	let lastChangesCount = 0;

	function formData(extra) {
		const fd = new FormData(form);
		fd.set('action', extra.action);
		Object.entries(extra).forEach(([k, v]) => {
			if (k !== 'action' && v !== undefined && v !== null) {
				fd.set(k, v);
			}
		});
		return fd;
	}

	async function post(extra) {
		const res = await fetch(SSR.ajaxUrl, { method: 'POST', body: formData(extra), credentials: 'same-origin' });
		const json = await res.json().catch(() => ({ success: false, data: { message: 'Invalid response' } }));
		if (!json.success) {
			throw new Error(json.data && json.data.message ? json.data.message : 'Request failed');
		}
		return json.data;
	}

	function clearResults() {
		tbody.innerHTML = '';
		summary.textContent = '';
		results.hidden = true;
	}

	function appendChanges(changes) {
		const frag = document.createDocumentFragment();
		changes.forEach((c) => {
			const tr = document.createElement('tr');
			tr.innerHTML = `
				<td></td><td></td><td></td>
				<td class="ssr-before"></td>
				<td class="ssr-after"></td>
			`;
			tr.children[0].textContent = c.table;
			tr.children[1].textContent = String(c.pk);
			tr.children[2].textContent = c.column;
			tr.children[3].textContent = c.diff.before;
			tr.children[4].textContent = c.diff.after;
			frag.appendChild(tr);
		});
		tbody.appendChild(frag);
	}

	function showProgress(label) {
		progress.hidden = false;
		progressText.textContent = label;
		progressFill.style.width = '0%';
	}
	function setProgress(pct, label) {
		progressFill.style.width = `${Math.min(100, Math.max(0, pct))}%`;
		if (label) progressText.textContent = label;
	}
	function hideProgress() {
		progress.hidden = true;
	}

	async function runLoop(action, extra) {
		let targetIndex = 0;
		let cursor = '';
		let totalRows = 0;
		let totalChanges = 0;
		let planHash = null;
		let firstTargetIndex = null;
		// We don't know total upfront; show indeterminate progress that nudges forward.
		let nudge = 0;

		while (true) {
			const data = await post({
				action,
				target_index: targetIndex,
				cursor,
				...extra,
			});
			planHash = data.plan_hash;
			totalRows += data.rows_scanned || 0;
			totalChanges += (data.changes || []).length;
			appendChanges(data.changes || []);
			results.hidden = false;
			summary.textContent = `${totalChanges} ${SSR.strings.changesFound} · ${totalRows} ${SSR.strings.rowsScanned}`;

			if (!data.next) break;

			if (firstTargetIndex === null) firstTargetIndex = data.next.target_index;
			if (data.next.target_index !== targetIndex) {
				nudge = Math.min(95, nudge + 10);
			} else {
				nudge = Math.min(95, nudge + 1);
			}
			setProgress(nudge);

			targetIndex = data.next.target_index;
			cursor = data.next.cursor === null ? '' : String(data.next.cursor);
		}

		setProgress(100);
		return { planHash, totalChanges, totalRows };
	}

	previewBtn.addEventListener('click', async () => {
		if (!validate()) return;
		clearResults();
		showProgress('…');
		previewBtn.disabled = true;
		executeBtn.disabled = true;
		try {
			const r = await runLoop('ssr_preview_batch', {});
			lastPreviewHash = r.planHash;
			lastChangesCount = r.totalChanges;
			executeBtn.disabled = r.totalChanges === 0;
			progressText.textContent = SSR.strings.dryRunDone;
		} catch (e) {
			alert(e.message);
		} finally {
			previewBtn.disabled = false;
			setTimeout(hideProgress, 1500);
		}
	});

	executeBtn.addEventListener('click', async () => {
		if (!validate()) return;
		if (!lastPreviewHash) return;
		const confirmInput = prompt(SSR.strings.confirmPrompt, '');
		if (confirmInput !== SSR.strings.confirmPhrase) return;

		clearResults();
		showProgress('…');
		previewBtn.disabled = true;
		executeBtn.disabled = true;
		try {
			const r = await runLoop('ssr_execute_batch', {
				confirm: 'I UNDERSTAND',
				expected_plan_hash: lastPreviewHash,
			});
			progressText.textContent = SSR.strings.executeDone;
		} catch (e) {
			alert(e.message);
		} finally {
			previewBtn.disabled = false;
			setTimeout(hideProgress, 2000);
		}
	});

	function validate() {
		regexErr.hidden = true;
		const scopes = $$('input[name="scope_ids[]"]:checked');
		if (scopes.length === 0) {
			alert(SSR.strings.noScope);
			return false;
		}
		return true;
	}
})();
