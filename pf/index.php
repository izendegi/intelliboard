<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 *
 * @package    local_intelliboard
 * @copyright  2017 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    https://intelliboard.net/
 */
require('../../../config.php');
require_once($CFG->dirroot .'/local/intelliboard/locallib.php');
require_once($CFG->dirroot .'/local/intelliboard/pf/lib.php');
require_once($CFG->dirroot .'/local/intelliboard/pf/tables.php');


$id = optional_param('id', 0, PARAM_SEQUENCE);
$search = clean_raw(optional_param('search', '', PARAM_RAW));
$download = optional_param('download', '', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);
$status = optional_param('status', 0, PARAM_INT);
$sitecontext = context_system::instance();

require_login();
require_capability('local/intelliboard:pf', $sitecontext);

if ($action == 'userstatus') {
	require_once($CFG->dirroot.'/user/lib.php');
	require_capability('moodle/user:update', $sitecontext);

	$status = 0;

	if ($user = $DB->get_record('user', array('id'=>$id, 'deleted'=>0))) {
			if (!is_siteadmin($user) and $USER->id != $user->id) {
					$status = ($user->suspended) ? 0 : 1;
					$user->suspended = $status;
					// Force logout.
					\core\session\manager::kill_user_sessions($user->id);
					user_update_user($user, false);
			}
	}
	$json = json_encode(['status' => !$status]);
	die($json);
}
if ($id) {
	$USER->pfid = $id;
} else {
	$id = $USER->pfid??0;
}

$params = array(
	'do'=>'pf',
	'mode'=> 3
);
$intelliboard = intelliboard($params);
if (!isset($intelliboard) || !$intelliboard->token) {
		throw new moodle_exception('invalidaccess', 'error');
}

$PAGE->set_url(new moodle_url("/local/intelliboard/pf/index.php", array("id"=>$id, "search"=>$search, "status"=>$status)));
$PAGE->set_pagetype('home');
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->jquery();
$PAGE->requires->js('/local/intelliboard/assets/js/jquery.multiple.select.js');
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');
$PAGE->requires->css('/local/intelliboard/assets/css/multiple-select.css');

$ids = explode(",", $id);
$cohort = intelliboard_pf_cohort();
$fields = intelliboard_pf_fields($cohort->id);
$widgets = intelliboard_pf_widgets($id, $cohort->id);


$fieldsMenu = [];
foreach ($fields as $field) {
	$fieldsMenu[$field->fieldid] = ["name" => $field->name];
}
foreach ($fields as $field) {
	$fieldsMenu[$field->fieldid]["items"][] = $field;
}


$table = new intelliboard_pf_users_table('table', $search, $id, $cohort->id, $status);
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->is_downloadable(true);
$table->is_downloading($download, 'report', get_string('report'));

if ($download) {
	$table->out(10, true);
	exit;
}


echo $OUTPUT->header();
?>
<?php if(!$cohort): ?>
	<div class="alert alert-error alert-block fade in " role="alert"<?php echo get_string('nofranchisegroup', 'local_intelliboard');?></div>
<?php else: ?>
<div class="intelliboard-page intelliboard-pf">
	<?php include("views/menu.php"); ?>



	<div class="intelliboard-pf-content">
		<form class="intelliboard-pf-head" id="fields" action="" method="get">
		  <div class="form-group">
		    <label>Franchise Group:</label>
		    <div class="value"><?php echo $cohort->name; ?></div>
		  </div>

			<div class="form-group">
		    <label for="fieldid">Select a Club:</label>
		    <div class="value">
					<select class="form-control" name="id" id="fieldid" multiple="multiple">
						<?php foreach($fieldsMenu as $item): ?>
							<optgroup label="<?php echo $item['name']; ?>">
							<?php foreach($item['items'] as $field): ?>
								<option value="<?php echo $field->id; ?>" <?php echo (in_array($field->id, $ids))?'selected="selected"':''; ?>><?php echo $field->data; ?></option>
							<?php endforeach; ?>
							</optgroup>
						<?php endforeach; ?>
					</select>
				</div>
		  </div>
		</form>
	</div>
	<?php if($id): ?>
		<?php if($widgets): ?>
		<div class="intelliboard-pf-widgets clearfix">
			<div class="left">
				<h3>Recently Created</h3>
				<div class="widget-wrap">
				<div class="widget" id="widget1"></div>
				</div>
			</div>
			<div class="middle">
				<h3>Users Activity</h3>
				<div class="widget-wrap">
				<div class="widget" id="widget2"></div>
				</div>
			</div>

			<div class="right">
				<h3>Most active users </h3>
				<div class="widget-wrap">
				<div class="widget" id="widget3"></div>
				</div>
			</div>
		</div>
	<?php endif; ?>


	<h2 class="intelliboard-pf-header">User Details</h2>
	<div class="intelliboard-pf-content pf-table">
		<div class="intelliboard-search clearfix">
			<form action="" method="GET">
					<select name="status" class="pull-left form-control" onchange="this.form.submit()" style="margin-right:3px;">
						<option value="0">All</option>
						<option value="1" <?php echo ($status == 1)?'selected="selected"':''; ?>>Active</option>
						<option value="2" <?php echo ($status == 2)?'selected="selected"':''; ?>>Suspended</option>
					</select>
				<input type="hidden" name="sesskey" value="<?php p(sesskey()); ?>" />
				<input name="id" type="hidden" value="<?php echo $id; ?>" />
				<span class="pull-left"><input class="form-control" name="search" type="text" value="<?php echo format_string($search); ?>" placeholder="<?php echo get_string('type_here', 'local_intelliboard');?>" /></span>
				<button class="btn btn-default"><?php echo get_string('search');?></button>
			</form>
		</div>
		<?php $table->out(10, true); ?>
		<div class="clear"></div>
	</div>
	<?php else: ?>
		<div class="alert alert-info alert-block fade in " role="alert">Please select CLUB</div>
	<?php endif; ?>

	<?php include("../views/footer.php"); ?>
</div>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
	jQuery(document).ready(function(){
		$(".userstatus").click(function(){
			var id = jQuery(this).val();
			var obj = jQuery(this);
			jQuery.ajax({
					url: "<?php echo $CFG->wwwroot; ?>/local/intelliboard/pf/index.php?action=userstatus&id="+id,
					dataType: "json"
			}).done(function( response ) {
					obj.prop("checked", response.status);
			});
		});

		$("#fieldid").multipleSelect({
			minimumCountSelected:1,
			filter:true,
			placeholder:"<?php echo get_string('select', 'local_intelliboard') ?>",
			selectAllText:"<?php echo get_string('selectall', 'local_intelliboard') ?>",
			single:false,
			onClose: function() {

			}
		});
		$(".ms-drop").append('<div class="actions"><button type="button" id="intelliboard-close"><?php echo get_string('ok', 'local_intelliboard') ?></button></div>');
		$(".ms-drop").on('click', 'button', function(){
			var id = jQuery('#fieldid').val();
			location = "<?php echo new moodle_url("/local/intelliboard/pf/index.php"); ?>?id="+id;
		});
	});


<?php if($widgets): ?>
	google.charts.load("current", {packages:["corechart"]});
	google.charts.setOnLoadCallback(function() {
		var chart = new google.visualization.PieChart(document.getElementById('widget1'));
		chart.draw(google.visualization.arrayToDataTable([
			['Name', 'Accounts'],
			<?php $accounts = 0; foreach($widgets->widget1 as $item): $accounts = (int)$item->users + $accounts; ?>
			['<?php echo $item->title; ?>', <?php echo (int)$item->users; ?>],
			<?php endforeach; ?>
		]), {
			legent:{
				position: 'top'
			},
			pieSliceTextStyle: {
            color: 'black',
          },
			title: 'New Users: <?php echo (int)$accounts; ?>',
			pieHole: 0.4,
		});
	});
	google.charts.setOnLoadCallback(function() {
		var chart = new google.visualization.PieChart(document.getElementById('widget2'));
		chart.draw(google.visualization.arrayToDataTable([
			['Name', 'Accounts'],
			['30 days',<?php echo (int)$widgets->time30; ?>],
			['60 days',<?php echo (int)$widgets->time60; ?>],
			['90 days',<?php echo (int)$widgets->time90; ?>],
			['>90 days',<?php echo (int)$widgets->time100; ?>]
		]), {
			title: 'Total Users:  <?php echo $widgets->time30+$widgets->time60+$widgets->time90+$widgets->time100; ?>',
			pieHole: 0.4,
		});
	});

	google.charts.setOnLoadCallback(function() {
		var chart = new google.visualization.PieChart(document.getElementById('widget3'));
		var dataTable = new google.visualization.DataTable();
        dataTable.addColumn('string', 'User');
        dataTable.addColumn('number', 'Time');
        // A column for custom tooltip content
        dataTable.addColumn({type: 'string', role: 'tooltip'});
        dataTable.addRows([
					<?php foreach($widgets->widget3 as $item): ?>
					['<?php echo "$item->firstname $item->lastname"; ?>', <?php echo (int)$item->timespend; ?>, 'Time spent: <?php echo seconds_to_time($item->timespend); ?>'],
					<?php endforeach; ?>
      ]);

		chart.draw(dataTable, {
			tooltip: {isHtml: true}
		});
	});
	<?php endif; ?>

</script>
<?php endif; ?>

<?php echo $OUTPUT->footer();
