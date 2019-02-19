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
$cids = optional_param('cids', 0, PARAM_SEQUENCE);
$search = clean_raw(optional_param('search', '', PARAM_RAW));
$download = optional_param('download', '', PARAM_ALPHA);
$status = optional_param('status', 0, PARAM_INT);
$redirect = optional_param('redirect', 0, PARAM_INT);
$sitecontext = context_system::instance();

require_login();
require_capability('local/intelliboard:pf', $sitecontext);

if ($id) {
	$USER->pfid = $id;
} else {
	$$id = $USER->pfid??0;
}

$params = array(
	'do'=>'pf',
	'mode'=> 1
);
$intelliboard = intelliboard($params);
if (!isset($intelliboard) || !$intelliboard->token) {
		throw new moodle_exception('invalidaccess', 'error');
}

$PAGE->set_url(new moodle_url("/local/intelliboard/pf/courses.php", array("id"=>$id, "cids" => $cids, "search"=>$search, "status"=>$status)));
$PAGE->set_pagetype('courses');
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
$courses = intelliboard_pf_courses();
$fields = intelliboard_pf_fields($cohort->id);
$widgets = intelliboard_pf_widgets($id, $cohort->id);



$categories = [];
foreach ($courses as $course) {
	$categories[$course->category][] = $course;
}
//echo "<pre>"; print_r($categories); exit;


$fieldsMenu = [];
foreach ($fields as $field) {
	$fieldsMenu[$field->fieldid] = ["name" => $field->name];
}
foreach ($fields as $field) {
	$fieldsMenu[$field->fieldid]["items"][] = $field;
}

$table = new intelliboard_pf_courses_table('table', $search, $id, $cohort->id, $status, $cids);
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->is_downloadable(true);
$table->is_downloading($download, 'report', get_string('report'));

if ($download) {
	$table->out(10, true);
	exit;
}
$cids = explode(",", $cids);

echo $OUTPUT->header();
?>
<?php if(!$cohort): ?>
	<div class="alert alert-error alert-block fade in " role="alert"><?php echo get_string('nofranchisegroup', 'local_intelliboard');?></div>
<?php else: ?>
<div class="intelliboard-page intelliboard-pf">
	<?php include("views/menu.php"); ?>

	<div class="intelliboard-pf-content">
		<form class="intelliboard-pf-head" id="fields" action="" method="get">
		  <div class="form-group">
		    <label><?php echo get_string('franchisegroup', 'local_intelliboard');?>:</label>
		    <div class="value"><?php echo $cohort->name; ?></div>
		  </div>

			<div class="form-group">
		    <label for="fieldid"><?php echo get_string('selectaclub', 'local_intelliboard');?>:</label>
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
	<div class="intelliboard-pf-content pf-table">
		<div class="intelliboard-search clearfix">

			<form action="" method="GET" id="filterform">
				<select id="cids" class="pull-left cids coursesdropdown form-control" style="margin-right:20px; width: 200px;" multiple="multiple">
					<?php foreach($categories as $key=>$category): $cat = reset($category); ?>
						<?php if ($cat->category_visible or has_capability('moodle/category:viewhiddencategories', context_coursecat::instance($key))): ?>
						<optgroup label="<?php echo $cat->name; ?>">
						<?php foreach($category as $course): ?>
							<?php if ($course->visible or has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id))): ?>
								<option value="<?php echo $course->id; ?>" <?php echo (in_array($course->id, $cids))?'selected="selected"':''; ?>><?php echo format_string($course->fullname); ?></option>
							<?php endif; ?>
						<?php endforeach; ?>
						</optgroup>
					<?php endif; ?>
					<?php endforeach; ?>
				</select>


				<input type="hidden" name="cids" id="cidsid" value="" />
				<input type="hidden" name="sesskey" value="<?php p(sesskey()); ?>" />
				<input name="id" type="hidden" value="<?php echo $id; ?>" />
				<span class="pull-left"><input class="form-control" name="search" type="text" value="<?php echo format_string($search); ?>" placeholder="<?php echo get_string('type_here', 'local_intelliboard');?>" /></span>
				<button id="search" class="btn btn-default"><?php echo get_string('search');?></button>
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
<style>
.ms-drop input[type="checkbox"] {
    margin-top: 7px;
}
</style>
<script type="text/javascript">
	jQuery(document).ready(function(){
		$("#fieldid").multipleSelect({
			minimumCountSelected:1,
			filter:true,
			placeholder:"<?php echo get_string('select', 'local_intelliboard') ?>",
			selectAllText:"<?php echo get_string('selectall', 'local_intelliboard') ?>",
			single:false,
			onClose: function() {

			}
		});
		$("#fields .ms-drop").append('<div class="actions"><button type="button" id="intelliboard-close"><?php echo get_string('ok', 'local_intelliboard') ?></button></div>');
		$("#fields  .ms-drop").on('click', 'button', function(){
			var id = jQuery('#fieldid').val();
			location = "<?php echo new moodle_url("/local/intelliboard/pf/courses.php"); ?>?id="+id;
		});


		$("#filterform").submit(function(e){
			 e.preventDefault();

			var cids = jQuery('#cids').val();
			$("#cidsid").val(cids);
			$("#filterform").submit();
		});

		$("#cids").multipleSelect({
			minimumCountSelected:1,
			filter:true,
			placeholder:"Select a Course",
			selectAllText:"<?php echo get_string('selectall', 'local_intelliboard') ?>",
			single:false,
			onClose: function() {

			}
		});

		$("#filterform .ms-drop").append('<div class="actions"><button type="button" id="intelliboard-close"><?php echo get_string('ok', 'local_intelliboard') ?></button></div>');
		$("#filterform  .ms-drop").on('click', 'button', function(){
			var cids = jQuery('#cids').val();
			var id = jQuery('#fieldid').val();
			location = "<?php echo new moodle_url("/local/intelliboard/pf/courses.php"); ?>?id="+id + "&cids="+cids;
		});

		var cids = jQuery('#cids').val();
		var redirect = <?php echo $redirect; ?>;

		if (cids == '' && redirect == 0) {
			$('#cids option').prop('selected', true);

			var cids = jQuery('#cids').val();
			var id = jQuery('#fieldid').val();
			location = "<?php echo new moodle_url("/local/intelliboard/pf/courses.php"); ?>?id="+id + "&cids="+cids+"&redirect=1";
		}

	});
</script>
<?php endif; ?>
<?php echo $OUTPUT->footer();
