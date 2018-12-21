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

defined('MOODLE_INTERNAL') || die();


function intelliboard_pf_user($userid)
{
    global $DB;

    return $DB->get_record_sql("SELECT u.id, u.firstname, u.lastname
      FROM {user} u
        WHERE u.id = :userid", ['userid' => $userid]);
}

function intelliboard_pf_profile($userid, $courseid)
{
    global $DB;

    return $DB->get_record_sql("SELECT u.id, u.firstname, u.lastname, c.fullname
      FROM {user} u, {course} c
        WHERE u.id = :userid AND c.id = :courseid", ['userid' => $userid, 'courseid' => $courseid]);
}
function intelliboard_pf_cohort()
{
    global $DB, $USER;
    if (!is_siteadmin()) {

    }
    return $DB->get_record_sql("SELECT c.id, c.name
      FROM {cohort_members} m, {cohort} c
        WHERE idnumber <> 'ClubManager' AND m.userid = :userid AND c.id = m.cohortid LIMIT 1", ['userid' => $USER->id]);
}
function intelliboard_pf_fields($id)
{
    global $DB;

    return $DB->get_records_sql("SELECT MAX(d.id) AS id, MAX(d.fieldid) AS fieldid, f.name, d.data
      FROM {local_profilecohort} p, {user_info_field} f, {user_info_data} d
        WHERE p.value = :id AND f.id = p.fieldid AND d.fieldid = f.id GROUP BY d.data, f.name", ['id' => $id]);
}

function intelliboard_pf_courses()
{
  global $DB;

  return $DB->get_records_sql("SELECT c.id, c.fullname FROM {course} c WHERE c.visible = 1");
}
function intelliboard_pf_widgets($ids, $cohortid)
{
    global $DB;

    return null;

    $params = [];
    $sqlfilter = "";
    if ($ids) {
      $data = $DB->get_records_sql("SELECT * FROM {user_info_data} WHERE id IN ($ids)");
      $where = [];
      foreach ($data as $item) {
          $where[] = "(" . $DB->sql_like("d.data", ":col".$item->id, false, false) . " AND d.fieldid = $item->fieldid)";
          $params["col".$item->id] = "%". $item->data ."%";
      }
      $sqlfilter = ' AND ('.implode(' OR ',$where).')';
    } else {
      $sqlfilter = ' AND u.id = 0';
    }


    if ($cohortid) {
      //$sqlfilter .= ' AND u.id IN (SELECT userid FROM {cohort_members} WHERE cohortid = :cohortid)';
      //$params["cohortid"] = $cohortid;
    }

    $data = new stdClass();
    $data->widget1 = $DB->get_records_sql("SELECT MAX(u.id) AS id, COUNT(DISTINCT u.id) AS users,
      (SELECT d2.data FROM {user_info_field} f2, {user_info_data} d2 WHERE f2.shortname = 'JobTitle' AND d2.fieldid = f2.id AND d2.userid = u.id) as title
      FROM {user} u, {user_info_field} f,{user_info_data} d
      WHERE u.suspended = 0 AND u.timecreated > " . (time() - 604800) . " AND u.id = d.userid AND f.id = d.fieldid $sqlfilter GROUP BY title", $params);


    $data->widget3 = $DB->get_records_sql("SELECT t.* FROM (SELECT u.*, SUM(t.timespend) AS timespend, SUM(t.visits) AS visits FROM (SELECT DISTINCT u.id, u.firstname, u.lastname
            FROM {user} u, {user_info_field} f,{user_info_data} d
            WHERE u.suspended = 0 AND u.id = d.userid AND f.id = d.fieldid $sqlfilter GROUP BY u.id) u, {local_intelliboard_tracking} t WHERE t.userid = u.id GROUP BY u.id ORDER BY timespend DESC) t LIMIT 3", $params);

    $data->time30 = $DB->get_field_sql("SELECT COUNT(DISTINCT u.id) FROM {user} u, {user_info_field} f,{user_info_data} d,{local_intelliboard_tracking} t,{local_intelliboard_logs} l WHERE t.userid = u.id AND l.trackid = t.id AND u.suspended = 0 AND l.timepoint > ".strtotime("-30 days")." AND u.id = d.userid AND f.id = d.fieldid $sqlfilter", $params);
    $data->time60 = $DB->get_field_sql("SELECT COUNT(DISTINCT u.id) FROM {user} u, {user_info_field} f,{user_info_data} d,{local_intelliboard_tracking} t,{local_intelliboard_logs} l WHERE t.userid = u.id AND l.trackid = t.id AND u.suspended = 0 AND l.timepoint BETWEEN ".strtotime("-30 days")." AND ".strtotime("-60 days")." AND u.id = d.userid AND f.id = d.fieldid $sqlfilter", $params);
    $data->time90 = $DB->get_field_sql("SELECT COUNT(DISTINCT u.id) FROM {user} u, {user_info_field} f,{user_info_data} d,{local_intelliboard_tracking} t,{local_intelliboard_logs} l WHERE t.userid = u.id AND l.trackid = t.id AND u.suspended = 0 AND l.timepoint BETWEEN ".strtotime("-60 days")." AND ".strtotime("-90 days")." AND u.id = d.userid AND f.id = d.fieldid $sqlfilter", $params);
    $data->time100 = $DB->get_field_sql("SELECT COUNT(DISTINCT u.id) FROM {user} u, {user_info_field} f,{user_info_data} d,{local_intelliboard_tracking} t,{local_intelliboard_logs} l WHERE t.userid = u.id AND l.trackid = t.id AND u.suspended = 0 AND l.timepoint < ".strtotime("-90 days")." AND u.id = d.userid AND f.id = d.fieldid $sqlfilter", $params);

    return $data;
}
