<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';


/* TODO
 * Use SOAP "future" instead for parallell config call
 * Support "outbound" aka more listeners
 */
if (!$settings->getDisplayStats()) die("The setting display-stats isn't enabled");

if (isset($_GET['ajax-rrd'])) {
	if (!Session::Get()->checkAccessDomain($_GET['ajax-rrd']))
		die('access denied');
	$listener = 'mailserver:1';
	$listener = str_replace(':', '-', $listener);
	$domain = $_GET['ajax-rrd'];
	$data = array();
	foreach ($settings->getNodes() as $node) {
		try {
			$data[] = base64_encode($node->soap()->graphFile(array('name' => 'mail-stat-'.$listener.'-'.$domain))->result);
		} catch (SoapFault $e) {
		}
	}
	header('Content-type: application/json');
	die(json_encode($data));
}

if (isset($_GET['ajax-pie'])) {
	if (!Session::Get()->checkAccessDomain($_GET['ajax-pie']))
		die('access denied');
	$listener = 'mailserver:1';
	$keyname = 'mail:action:';
	$stats = array();
	$since = null;
	foreach ($settings->getNodes() as $node) {
		try {
			$ss = $node->soap()->statList(array('key1' => $keyname.'%', 'key2' => $inbound, 'key3' => $_GET['ajax-pie'], 'offset' => 0, 'limit' => 10))->result->item;
			if (!is_array($ss))
				continue;
			foreach ($ss as $s) {
				$k = str_replace($keyname, '', $s->key1);
				if (!$stats[$k]) $stats[$k] = 0;
				$stats[$k] += $s->count;
				if ($since === null || $s->created < $since) $since = $s->created;
			}
		} catch (SoapFault $e) {
		}
	}
	$flot = array();
	foreach ($stats as $k => $v) {
		$p = array('label' => $k, 'data' => $v);
		$color = null;
		if ($k == 'delete') $color = '#666';
		if ($k == 'deliver') $color = '#7d6';
		if ($k == 'allow') $color = '#9cf';
		if ($k == 'reject') $color = '#d44';
		if ($k == 'block') $color = '#622';
		if ($k == 'defer') $color = '#ed4';
		if ($k == 'quarantine') $color = '#e96';
		if ($color) $p['color'] = $color;
		$flot[] = $p;
	}
	header('Content-type: application/json');
	die(json_encode(array('since' => $since, 'flot' => $flot)));
}

$title = 'Statistics';
$javascript[] = 'static/js/javascriptrrd.js';
$javascript[] = 'static/js/jquery.flot.min.js';
$javascript[] = 'static/js/jquery.flot.pie.min.js';
$javascript[] = 'static/js/jquery.flot.resize.min.js';
$javascript[] = 'static/js/jquery.flot.time.min.js';
$javascript[] = 'static/js/jquery.flot.selection.min.js';
$javascript[] = 'static/js/jquery.flot.stack.min.js';
require_once BASE.'/partials/header.php';
?>
		<div class="container" id="panel-container">
			<div class="btn-group">
				<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
					Add chart <span class="caret"></span>
				</button>
				<ul class="dropdown-menu" role="menu">
					<li><a href="#" class="add-all">All</a></li>
					<li class="divider"></li>
					<?php foreach (Session::Get()->getAccess('domain') as $d) { $id = uniqid(); ?>
					<li><a href="#" data-domain="<?php p($d) ?>" class="add-domain"><?php p($d) ?></a></li>
					<?php } ?>
				</ul>
			</div>
			<span class="text-muted pull-right many-domains" style="display:none">Because you have more than 5 domains, you need to choose them specifically.</span>
			<br><br>
			<div class="panel panel-default template" style="display:none">
				<div class="panel-heading">
					<h3 class="panel-title">
						<span></span>
						<button type="button" class="close"><span>&times;</span></button>
					</h3>
				</div>
				<div class="panel-body draw-charts" iid="<?php p($id) ?>" ddata-domain="<?php p($d) ?>">
					<div class="row"><div class="col-md-6">
						<div class="rrd-id" style="height:200px;display:none;"></div>
						<div class="realrrd" style="height:200px"></div>
					</div><div class="col-md-6">
						<div class="pie" style="height:200px"></div>
						<div class="since text-muted pull-right">Loading...</div>
					</div></div>
				</div>
			</div>
		</div>
	<script>
	var chartid = 0;
	$(document).ready(function() {
		$(".add-all").click(function() {
			$(".add-domain").click();
		});
		$(".add-domain").click(function() {
			var that = this;
			var panel = $(".template").clone();
			panel.removeClass("template");
			panel.attr("id", "chart" + chartid++);
			panel.appendTo("#panel-container");
			panel.show();
			panel.find(".panel-title > span").text($(this).data("domain"));
			panel.find(".rrd-id").attr("id", "rrd-" + panel.attr("id"));
			panel.find("button.close").click(function() {
				$(this).parent().parent().parent().remove();
			});
			$.ajax({
				url: "?page=stats",
				dataType: "json",
				data: {"ajax-pie": $(this).data("domain")}
			}).done(function(data) {
				panel.find(".since").text("Since " + new Date(data.since * 1000).toDateString());
				$.plot(panel.find(".pie"), data.flot, {
					series: {
						pie: {
							show: true,
							innerRadius: 0.5,
						}
					},
					legend: {
						show: true,
						labelFormatter: function(label, series) {
							var num = parseInt(series.data[0][1], 10);
							return "&nbsp;" + label + " " + Math.round(series.percent) + "% (" + num + ")";
						}
					}
				});
			});
			$.ajax({
				url: "?page=stats",
				dataType: "json",
				data: {"ajax-rrd": $(this).data("domain")}
			}).done(function(data) {
				var binary = new Array();
				for (i = 0; i < data.length; i++)
					binary.push(new RRDFile(new BinaryFile(atob(data[i]))));
				var rrd = new RRDFileSum(binary);
				// Setup lines and colors
				var dss = rrd.getDSNames();
				var ds_opt = new Array();
				for (i = 0; i < dss.length; i++) {
					ds_opt[dss[i]] = {
						stack: true,
						lines: { show: true, fill: 1, lineWidth: 0 }
					};
					var color = null;
					if (dss[i] == "quarantine") color = "#e96";
					if (dss[i] == "deliver") color = "#7d6";
					if (dss[i] == "delete") color = "#666";
					if (dss[i] == "reject") color = "#d44";
					if (dss[i] == "allow") color = "#9cf";
					if (dss[i] == "block") color = "#622";
					if (dss[i] == "defer") color = "#ed4";
					if (color) ds_opt[dss[i]].color = color;
				}
				var flot_opts = {
					grid: { borderWidth: 1 },
					series: { stack: true },
					yaxis: { tickFormatter: function(v) { return Math.round(v * 3600) + " /h"; }},
					legend: {
						labelFormatter: function(label, series) {
							var hasValue = false;
							for (var i = 0; i < series.data.length; ++i) {
								if (series.data[i][1] != 0) {
									hasValue = true;
									break;
								}
							}
							return hasValue ? label : null;
						}
					}
				};
				var rrd_opts = {
					checked_DSs: ["reject", "deliver", "quarantine", "defer"],
					use_checked_DSs: true,
					graph_width: "100%",
					graph_height: "150px",
					scale_width: "70%",
					scale_height: "50px"
				};
				var rrdid = "rrd-" + panel.attr("id");
				new rrdFlot(rrdid, rrd, flot_opts, ds_opt, rrd_opts);

				// Move the elements that we like to a div
				$("#" + rrdid + "_graph").appendTo(panel.find(".realrrd"));
				$("#" + rrdid + "_scale").appendTo(panel.find(".realrrd")).css("width", "70%").css("float", "right");
				$("#" + rrdid + "_res").appendTo(panel.find(".realrrd")).addClass("form-control").css("width", "30%").css("float", "left");
				$("#" + rrdid + "_res").trigger("change"); // We need to .draw() the flot again to make axis fit, etc
			});
		});
		if ($(".add-domain").length < 6) $(".add-domain").click();
		else $(".many-domains").show();

	});
	</script>
<?php require_once BASE.'/partials/footer.php'; ?>
