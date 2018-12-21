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

$search = clean_raw(optional_param('search', '', PARAM_RAW));
$download = optional_param('download', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$sitecontext = context_system::instance();

require_login();
require_capability('local/intelliboard:pf', $sitecontext);

$params = array(
	'do'=>'pf',
	'mode'=> 4
);
$intelliboard = intelliboard($params);
if (!isset($intelliboard) || !$intelliboard->token) {
		throw new moodle_exception('invalidaccess', 'error');
}

$PAGE->set_url(new moodle_url("/local/intelliboard/pf/activities.php", array("userid" => $userid, "courseid" => $courseid, "search" => $search)));
$PAGE->set_pagetype('courses');
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->jquery();
$PAGE->requires->js('/local/intelliboard/assets/js/jquery.multiple.select.js');
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');
$PAGE->requires->css('/local/intelliboard/assets/css/multiple-select.css');

$cohort = intelliboard_pf_cohort();
$profile = intelliboard_pf_profile($userid, $courseid);

$table = new intelliboard_pf_activities_table('table', $userid, $courseid, $search);
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->is_downloadable(true);
$table->is_downloading($download, 'learner_transcript', 'transcript');

if ($download) {
	$table->out(10, true);
	exit;
}

echo $OUTPUT->header();
?>
<?php if(!$cohort): ?>
	<div class="alert alert-error alert-block fade in " role="alert">Franchise Group NOT found!</div>
<?php else: ?>
<div class="intelliboard-page intelliboard-pf">
	<?php include("views/menu.php"); ?>

	<div class="intelliboard-pf-content">
		<form class="intelliboard-pf-head" id="fields" action="" method="get">
		  <div class="form-group">
		    <label>User:</label>
		    <div class="value"><?php echo "$profile->firstname $profile->lastname"; ?></div>
		  </div>

			<div class="form-group">
		    <label for="fieldid">Course:</label>
		    <div class="value"><?php echo $profile->fullname; ?></div>
		  </div>
		</form>
	</div>
	<?php if($profile): ?>
	<div class="intelliboard-pf-content pf-table">
		<div class="intelliboard-search clearfix">
			<form action="" method="GET">
				<input type="hidden" name="sesskey" value="<?php p(sesskey()); ?>" />
				<input name="userid" type="hidden" value="<?php echo $userid; ?>" />
				<input name="courseid" type="hidden" value="<?php echo $courseid; ?>" />
				<span class="pull-left"><input class="form-control" name="search" type="text" value="<?php echo format_string($search); ?>" placeholder="<?php echo get_string('type_here', 'local_intelliboard');?>" /></span>
				<button class="btn btn-default"><?php echo get_string('search');?></button>
			</form>
		</div>
		<?php $table->out(10, true); ?>
		<div class="clear"></div>
	</div>
	<?php else: ?>
		<div class="alert alert-info alert-block fade in " role="alert">Please select USER and COURSE</div>
	<?php endif; ?>

	<?php include("../views/footer.php"); ?>
</div>
<?php endif; ?>

<?php echo $OUTPUT->footer();
