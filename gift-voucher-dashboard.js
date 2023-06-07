jQuery( document ).ready( function( $ ) {

	console.log('Gift Voucher Dashboard JS Loaded');
	//  $('.mselect').multiSelect();
	
	var json_data;
	var original_json;
	var redemptionsChart;
	var salesChart;
	var chartColours = ['#4287f5', '#f54266', '#f5a442', '#42f587', '#f5e042', '#c842f5'];

	var data = {
		action: 'dashboard_get_data', 
		method: "POST",
		dataType: 'JSON',
	};

	$.post( myAjax.ajaxurl, data, function( response ) {

		console.log(response);
		json_data = response;
		original_json = response;

		var to = $('input[name="to-date"]').val();
		var from = $('input[name="from-date"]').val();
		update_data(to, from);

	}).fail(function(xhr, status, error) {
		console.log([xhr, status, error]);
    });

	function create_redemption_chart(){

		$('#outstanding-balance').html(Math.round(json_data.vouchers.balance * 100) / 100);
		$('#total-breakage').html(Math.round(json_data.vouchers.breakage * 100) / 100);
		if(typeof(redemptionsChart) !== 'undefined'){
			redemptionsChart.destroy();
		}

		const redemptionsChartElem = document.getElementById('redemptions-chart');

		datasets = [];
		var count = 0;
		var labelsKey;

		Object.keys(json_data.vouchers.redemptions).forEach(function(key){
			var colour = chartColours[count];
			count++;
			labelsKey = key;
			datasets.push( {
				label: key,
				data: json_data.vouchers.redemptions[key],
				borderWidth: 2,
				borderColor: colour,
				backgroundColor: colour,
				lineTension: 0.2,
				fill: false,
				hoverOffset: 4,
				pointHoverRadius: 7
			} )
		});

		if('Redemptions' in json_data.vouchers.redemptions){
			var labels = Object.keys(json_data.vouchers.redemptions.Redemptions)
		} else {
			var labels = Object.keys(json_data.vouchers.redemptions[labelsKey]);
		}

		redemptionsChart = new Chart(redemptionsChartElem, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: datasets
			},
			options: {
				maintainAspectRatio: false,
				scales: {
					y: {
						beginAtZero: true,
					},
					x: {
						grid: {
							display: false
						}
					}
				},
				plugins: {
					legend: {
						labels: {
							usePointStyle: true,
							pointStyle: 'circle'
						}
					}
				}
			}
		})
	}

	function create_sales_chart(){
		if('sales' in json_data === false){
			return false;
		}
		if(typeof(salesChart) !== 'undefined'){
			salesChart.destroy();
		}
		
		const salesChartElem = document.getElementById('sales-chart');

		datasets = [];
		var count = 0;
		var labelsKey;
		Object.keys(json_data.sales).forEach(function(key){
			labelsKey = key;
			var colour = chartColours[count];
			count++;
			datasets.push( {
				label: key,
				data: json_data.sales[key],
				borderWidth: 2,
				borderColor: colour,
				backgroundColor: colour,
				lineTension: 0.2,
				fill: false,
				hoverOffset: 4,
				pointHoverRadius: 7
			} )
		});

		data = {
			labels: Object.keys(json_data.sales[labelsKey]),
			datasets: datasets
		};

		salesChart = new Chart(salesChartElem, {
			type: 'line',
			data: data,
			options: {
				maintainAspectRatio: false,
				scales: {
					y: {
						beginAtZero: true,
					},
					x: {
						grid: {
							display: false
						}
					}
				},
				plugins: {
					legend: {
						labels: {
							usePointStyle: true,
							pointStyle: 'circle'
						}
					}
				}
			}
		})
	}

	$(document).on('click', '.quick-date-filter .btn', function(e){
		$('.quick-date-filter .active').removeClass('active');
		$(this).addClass('active');
		var to = $(this).data('to');
		var from = $(this).data('from');
		$('input[name="to-date"]').val(to);
		$('input[name="from-date"]').val(from);
		update_data(to, from);
	});

	$(document).on('change', '.date-range-picker', function(e){
		var to = $('input[name="to-date"]').val();
		var from = $('input[name="from-date"]').val();
		update_data(to, from);
	});

	function update_data(to, from){

		var data = {
			action: 'reduce_date_filters', 
			method: "POST",
			dataType: 'JSON',
			data: original_json,
			from: from,
			to: to
		};
	
		$.post( myAjax.ajaxurl, data, function( response ) {
			json_data = response;
			console.log(response);
			create_redemption_chart();
			create_sales_chart();
		});

	}

});
