(function(){
	function ready(fn){ if(document.readyState!='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
	ready(function(){
		var el = document.getElementById('fmpReportsData');
		if(!el){ return; }
		try {
			var data = JSON.parse(el.textContent);
			var monthly = data.monthly || {year: new Date().getFullYear(), collected: []};
			var yearly = data.yearly || [];

			var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

			var ctx1 = document.getElementById('fmpMonthlyChart');
			if (ctx1 && window.Chart) {
				new Chart(ctx1, {
					type: 'bar',
					data: {
						labels: months,
						datasets: [{
							label: 'Collected (' + monthly.year + ')',
							data: monthly.collected,
							backgroundColor: 'rgba(54, 162, 235, 0.5)'
						}]
					},
					options: { responsive: true, plugins: { legend: { position: 'top' } } }
				});
			}

			var ctx2 = document.getElementById('fmpYearlyChart');
			if (ctx2 && window.Chart) {
				var labels = yearly.map(function(r){ return r.year; });
				var values = yearly.map(function(r){ return r.total; });
				new Chart(ctx2, {
					type: 'line',
					data: {
						labels: labels,
						datasets: [{
							label: 'Yearly Total',
							data: values,
							borderColor: 'rgba(75, 192, 192, 1)',
							backgroundColor: 'rgba(75, 192, 192, 0.2)'
						}]
					},
					options: { responsive: true, plugins: { legend: { position: 'top' } } }
				});
			}
		} catch(e) {}
	});
})();