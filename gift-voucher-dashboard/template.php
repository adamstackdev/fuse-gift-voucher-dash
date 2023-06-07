<?php 
include_once WP_PLUGIN_DIR . '/fuse-reports/reports/gift-voucher-dashboard/controller.php'; //include controller 
$controller = new GiftVoucherDashboard();

?>

<?php

wp_enqueue_script( 'gift-vouchers-dashboard-js', '/wp-content/plugins/fuse-reports/assets/js/gift-voucher-dashboard.js', array('jquery'), 2, true );
wp_register_script('gift-vouchers-dashboard-js', '/wp-content/plugins/fuse-reports/assets/js/gift-voucher-dashboard.js', array('jquery'), 2, true);
wp_localize_script('gift-vouchers-dashboard-js', 'myAjax', array(  'ajaxurl'       => admin_url( 'admin-ajax.php' )) );
wp_enqueue_script( 'multiselect', '/wp-content/plugins/fuse-reports/assets/js/multi-select/jquery.multi-select.js', array('jquery'), 1, true );
wp_enqueue_style( 'multiselect-styles', '/wp-content/plugins/fuse-reports/assets/js/multi-select/multi-select.css');
?>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
	
	.db-sect {
		background-color:white!important;
		padding: 20px 25px!important;
		width:100%;
		border-radius: 0.428rem;
		height:100%;
		-webkit-box-shadow: 0 4px 24px 0 rgb(34 41 47 / 10%);
    	box-shadow: 0 4px 24px 0 rgb(34 41 47 / 10%);
	}

	.db-head {
		background-color:white!important;
		padding:10px!important;
		width:100%;
		display:flex;
		border-radius: 0.428rem;
		-webkit-box-shadow: 0 4px 24px 0 rgb(34 41 47 / 10%);
    	box-shadow: 0 4px 24px 0 rgb(34 41 47 / 10%);
	}

	.col-4, .col-9, .col{
		padding:6px!important;

	}

	h3{
		font-weight: 800!important;
    	font-size: 40px!important;
	}

	h3 small{
		font-weight: 800!important;
	}


	p{
		font-size:15px!important;
	}
	

	.no-data-found{
		text-align: left;
		font-weight: 600;
		color: #aaa;
	}

	.switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
}

.switch input { 
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: .4s;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

input:focus + .slider {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
}

/* Rounded sliders */
.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}

.btn{
	white-space: nowrap;
   text-align: center;
}

.date-range-picker{
	border:none!important;
	border-radius:0!important;
}

.input-group-text, .location_select{
	background: transparent;
    border: none;
}

.input-group{
	border: 1px solid #bfbfbf;
	border-radius: 3px;
}

.location_select:focus, input:focus{
	border:none!important;
	box-shadow:none!important;
}

.btn{
	font-size: 14px;
	background-color: white!important;
    color: #2271B1!important;
	border: 1px solid #2271B1!important;
}

.btn.active{
	font-size: 14px;
	color: white!important;
	background-color: #2271B1!important;
}

::-webkit-calendar-picker-indicator {
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='20' height='20' stroke='%23bfbfbf' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round' class='css-i6dzq1'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E");
}

#top-bottom .table th{
	border:none!important;
}

.sellers-position{
	background-color: #2271B1;
    color: #fff;
    width: 20px;
    height: 20px;
    border-radius: 50px;
    display: block;
    text-align: center;
    font-size: 14px;
    font-weight: 900;
}

.fuse-dash{
	margin-left: -6px;
    margin-right: 5px;
    width: 101%;
}

.loading-indicator #progress {
  width: 100%;
  background-color: white;
  border-radius: 0.5rem;
  overflow: hidden;
}

.loading-indicator #bar {
  width: 1%;
  height: 30px;
  background-color: #0062cc;
  transition: width 1s ease-in-out;
}

.db-sect .isLoading{
	webkit-filter: blur(3px);
    -moz-filter: blur(3px);
    -o-filter: blur(3px);
    -ms-filter: blur(3px);
    filter: blur(3px);
}

.db-sect .data-container{
	transition: 0.5s filter ease-in-out;
}
.action-required{
	color: orange;
	border-color: orange;
}

h1{
	margin-top: 0!important;
}

.dates-input-group{
	justify-content: end;
}

.dates-input-group input{
	width: 43%;
}

.update-location{
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='24' height='24' stroke='%232271B1' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round' class='css-i6dzq1'%3E%3Cpolyline points='23 4 23 10 17 10'%3E%3C/polyline%3E%3Cpolyline points='1 20 1 14 7 14'%3E%3C/polyline%3E%3Cpath d='M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15'%3E%3C/path%3E%3C/svg%3E");
    background-position: center;
    background-size: contain;
    padding: 0.7em;
    background-repeat: no-repeat;
    margin-left: 13px;
	cursor: pointer;
	position: absolute;
    left: -36px;
    top: 7px;
    z-index: 99;
}

.loading-spinner{
	margin: auto;
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    width: 50px;
	opacity: 0.5;
}

.db-sect:has(.isLoading) .loading-spinner{
	display: block!important;
}

.chart-sect{
	height: 400px;
	margin: 15px 0;
	padding-bottom: 2em!important;
}

.icon{
	background-size: 100%;
    background-position: center;
    background-repeat: no-repeat;
    width: 20px;
    height: 20px;
    display: inline-block;
}

.help{
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='24' height='24' stroke='%23b8b8b8' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round' class='css-i6dzq1'%3E%3Ccircle cx='12' cy='12' r='10'%3E%3C/circle%3E%3Cpath d='M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3'%3E%3C/path%3E%3Cline x1='12' y1='17' x2='12.01' y2='17'%3E%3C/line%3E%3C/svg%3E");
    margin-bottom: -3px;
}

.help-tooltip{
	cursor:help;
}

.help-tooltip:hover .help-content{
	opacity:1;
}

.help-content{
	position: absolute;
    background-color: white;
    -webkit-box-shadow: 0 4px 24px 0 rgb(34 41 47 / 10%);
    box-shadow: 0 4px 24px 0 rgb(34 41 47 / 10%);
    padding: 1em;
    width: 400px;
    margin-top: 5px;
    left: 35px;
    top: -14px;
	opacity: 0;
	transition: opacity 0.3s ease-in-out;
}
.help-content:before{
    width: 15px;
    height: 15px;
    transform: rotate(45deg);
    content: '';
    display: block;
    position: absolute;
    top: 13px;
    left: -6px;
    background: white;
	-webkit-box-shadow: 0 4px 24px 0 rgb(34 41 47 / 10%);
    box-shadow: 0 4px 24px 0 rgb(34 41 47 / 10%);
}
.help-tooltip{
	display: inline-block;
    position: relative;
}

</style>

<div class="wrap">

	<div class="container-fluid fuse-dash">
		<div class="row align-items-end">

			<div class="col-12">
				<div class="db-head">
					<div class="row w-100">
						<div class="col-2 my-auto">
						<h1 class="wp-heading-inline fuse-dashboard"><?php _e( 'Dashboard' ); ?></h1>
					</div> 
					<div class="col-7 text-right my-auto" style="padding-right:4%;">
						<div class="btn-group quick-date-filter" role="group" aria-label="Date Filter">
							<button type="button" data-from='<?=date('Y-m-d', strtotime('first day of this month'))?>' data-to='<?=date('Y-m-d')?>' class="active btn btn-primary">This Month</button>
							<button type="button" data-from='<?=date('Y-m-d', strtotime('first day of last month'))?>' data-to='<?=date('Y-m-d', strtotime('last day of last month'))?>'class="btn btn-primary">Last Month</button>
							<button type="button" data-from='<?=date('Y-m-d', strtotime(date('Y-m-d').'-6 months'))?>' data-to='<?=date('Y-m-d')?>'class="btn btn-primary">Last 6 Months</button>
							<button type="button" data-from='<?=date('Y-m-d', strtotime(date('Y-m-d').'-1 year'))?>' data-to='<?=date('Y-m-d')?>'class="btn btn-primary">Last 12 Months</button>
						</div>
					</div>
					<div class="col-3 text-right my-auto">
						<div class="input-group dates-input-group">
							<input type="date" name="from-date" value="<?=date('Y-m-01');?>" max="???" class="from-date date-range-picker"/>
							<div class="input-group-prepend">
								<span class="input-group-text">to</span>
							</div>
							<input type="date" class="to-date date-range-picker" name="to-date" min="???" value="<?=date('Y-m-d');?>"/>
						</div>
					</div>
				</div>
			</div>


		</div>
	</div>

	<div class="row">
		<div class="col-12">
			<div class="db-sect chart-sect">
			<h6>Sales</h6>
				<canvas id="sales-chart"></canvas>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-6">
			<div class="db-sect">
				<h5>Outstanding Balance</h5>
				<h3><span class="currency_symbol">£</span><span class="count-to" id="outstanding-balance"></span></h3>
			</div>
		</div>
		<div class="col-6">
			<div class="db-sect">
				
				<h5>Total Breakage 
					<div class="help-tooltip">
						<i class="icon help"></i>
							<div class="help-content">
								<p>Breakage is revenue gained by retailers through unredeemed gift cards or other prepaid services that are never claimed.</p>
							</div>
					</div>
				</h5>

				<h3><span class="currency_symbol">£</span><span class="count-to" id="total-breakage"></span></h3>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-12">
			<div class="db-sect chart-sect">
				<h6>Redemptions</h6>
				<canvas id="redemptions-chart"></canvas>
			</div>
		</div>
	</div>







</div>