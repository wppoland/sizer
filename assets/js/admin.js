/**
 * Sizer – admin chart builder.
 *
 * Dynamically manages chart cards (add/delete) and their tables (add/remove
 * columns and rows). Field names use a per-chart index; new charts are cloned
 * from a <template> with the placeholder token "__i__" replaced by a counter.
 * No dependencies.
 */
(function () {
	'use strict';

	var i18n = (window.sizerAdmin && window.sizerAdmin.i18n) || {};

	var container = document.getElementById('sizer-charts');
	var addChartBtn = document.getElementById('sizer-add-chart');
	var template = document.getElementById('sizer-chart-template');

	if (!container || !addChartBtn || !template) {
		return;
	}

	// Seed the counter above any server-rendered charts to avoid name clashes.
	var counter = container.querySelectorAll('[data-chart]').length + 1;

	function cellInput(name, row, col, value) {
		var input = document.createElement('input');
		input.type = 'text';
		input.name = name + '[rows][' + row + '][' + col + ']';
		input.value = value || '';
		input.setAttribute('aria-label', i18n.cell || 'Cell value');
		return input;
	}

	function chartName(card) {
		var idField = card.querySelector('input[name$="[id]"]');
		if (!idField) {
			return '';
		}
		// "charts[3][id]" -> "charts[3]"
		return idField.name.replace(/\[id\]$/, '');
	}

	function reindexRows(card) {
		var name = chartName(card);
		var rows = card.querySelectorAll('[data-row]');
		rows.forEach(function (tr, r) {
			tr.querySelectorAll('input[type="text"]').forEach(function (input, c) {
				input.name = name + '[rows][' + r + '][' + c + ']';
			});
		});
	}

	function reindexColumns(card) {
		var name = chartName(card);
		card.querySelectorAll('[data-col] input[type="text"]').forEach(function (input, c) {
			input.name = name + '[columns][' + c + ']';
		});
		reindexRows(card);
	}

	function addColumn(card) {
		var name = chartName(card);
		var headRow = card.querySelector('thead tr');
		var addCell = headRow.querySelector('.sizer-col-add');
		var colCount = headRow.querySelectorAll('[data-col]').length;

		var th = document.createElement('th');
		th.setAttribute('data-col', '');
		var input = document.createElement('input');
		input.type = 'text';
		input.name = name + '[columns][' + colCount + ']';
		input.setAttribute('aria-label', i18n.columnLabel || 'Column heading');
		var rm = document.createElement('button');
		rm.type = 'button';
		rm.className = 'sizer-remove-col';
		rm.setAttribute('aria-label', i18n.removeColumn || 'Remove column');
		rm.innerHTML = '&times;';
		th.appendChild(input);
		th.appendChild(rm);
		headRow.insertBefore(th, addCell);

		card.querySelectorAll('[data-row]').forEach(function (tr) {
			var td = document.createElement('td');
			td.appendChild(cellInput(name, 0, 0, ''));
			var removeCell = tr.querySelector('.sizer-row-remove');
			tr.insertBefore(td, removeCell);
		});

		reindexColumns(card);
	}

	function removeColumn(card, th) {
		var index = Array.prototype.indexOf.call(card.querySelectorAll('[data-col]'), th);
		if (index < 0) {
			return;
		}
		th.parentNode.removeChild(th);
		card.querySelectorAll('[data-row]').forEach(function (tr) {
			var cells = tr.querySelectorAll('td:not(.sizer-row-remove)');
			if (cells[index]) {
				cells[index].parentNode.removeChild(cells[index]);
			}
		});
		reindexColumns(card);
		// Restore focus: the removed control is gone, so move to the add-column
		// button of the same card to keep the keyboard user oriented.
		var addCol = card.querySelector('.sizer-add-col');
		if (addCol) {
			addCol.focus();
		}
	}

	function addRow(card) {
		var name = chartName(card);
		var body = card.querySelector('[data-rows]');
		var colCount = card.querySelectorAll('[data-col]').length;
		var rowCount = body.querySelectorAll('[data-row]').length;

		var tr = document.createElement('tr');
		tr.setAttribute('data-row', '');
		for (var c = 0; c < colCount; c++) {
			var td = document.createElement('td');
			td.appendChild(cellInput(name, rowCount, c, ''));
			tr.appendChild(td);
		}
		var rmTd = document.createElement('td');
		rmTd.className = 'sizer-row-remove';
		var rm = document.createElement('button');
		rm.type = 'button';
		rm.className = 'button-link sizer-remove-row';
		rm.setAttribute('aria-label', i18n.removeRow || 'Remove row');
		rm.innerHTML = '&times;';
		rmTd.appendChild(rm);
		tr.appendChild(rmTd);
		body.appendChild(tr);
	}

	function addChart() {
		var empty = document.getElementById('sizer-charts-empty');
		if (empty) {
			empty.parentNode.removeChild(empty);
		}

		var html = template.innerHTML.replace(/__i__/g, 'new' + counter);
		counter++;
		var wrap = document.createElement('div');
		wrap.innerHTML = html.trim();
		var card = wrap.firstElementChild;
		container.appendChild(card);
		var firstInput = card.querySelector('input[name$="[name]"]');
		if (firstInput) {
			firstInput.focus();
		}
	}

	addChartBtn.addEventListener('click', addChart);

	container.addEventListener('click', function (event) {
		var target = event.target;
		var card = target.closest('[data-chart]');
		if (!card) {
			return;
		}

		if (target.closest('.sizer-delete-chart')) {
			var confirmMsg = i18n.confirmDelete || 'Delete this size chart?';
			if (window.confirm(confirmMsg)) {
				card.parentNode.removeChild(card);
				// Restore focus to a stable control after the card is gone.
				if (addChartBtn) {
					addChartBtn.focus();
				}
			}
			return;
		}

		if (target.closest('.sizer-add-col')) {
			addColumn(card);
			return;
		}

		if (target.closest('.sizer-remove-col')) {
			removeColumn(card, target.closest('[data-col]'));
			return;
		}

		if (target.closest('.sizer-add-row')) {
			addRow(card);
			return;
		}

		if (target.closest('.sizer-remove-row')) {
			var tr = target.closest('[data-row]');
			if (tr) {
				tr.parentNode.removeChild(tr);
				reindexRows(card);
				// Restore focus to the add-row button of the same card.
				var addRowBtn = card.querySelector('.sizer-add-row');
				if (addRowBtn) {
					addRowBtn.focus();
				}
			}
		}
	});
})();
