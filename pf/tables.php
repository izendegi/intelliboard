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

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/gradelib.php');

class intelliboard_pf_users_table extends table_sql {

    function __construct($uniqueid, $search = '', $ids = '', $cohortid = 0, $status = 0) {
        global $CFG, $PAGE, $DB, $USER;

        parent::__construct($uniqueid);

        $headers = array();
        $columns = array();

        $columns[] =  'username';
        $headers[] =  get_string('username');

        $columns[] =  'firstname';
        $headers[] =  get_string('firstname');

        $columns[] =  'lastname';
        $headers[] =  get_string('lastname');

        $columns[] =  'email';
        $headers[] =  get_string('email');

        $columns[] =  'data';
        $headers[] =  'Club Location';

        $columns[] =  'title';
        $headers[] =  'Job Title';

        $columns[] =  'timecreated';
        $headers[] =  'Date Created';

        $columns[] =  'suspended';
        $headers[] =  'Active';

        $columns[] =  'lastlogin';
        $headers[] =  'Last Login';

        if (!optional_param('download', '', PARAM_ALPHA)) {
          $columns[] =  'actions';
          $headers[] =  "View";
        }

        $this->define_headers($headers);
        $this->define_columns($columns);

        $sql = "";
        $params = [];
        if ($status) {
          $sql .= ($status == 2) ? " AND u.suspended = 1" : " AND u.suspended = 0";
        }
        if ($search) {
            $where = [];
            foreach ($columns as $column) {
              $where[] = $DB->sql_like("u." . $column, ":".$column, false, false);
              $params[$column] = "%". $search ."%";
            }
            $sql .= ' AND ('.implode(' OR ',$where).')';
        }
        $sqlfilter = "";
        if ($cohortid) {
          $sqlfilter .= ' AND u.id IN (SELECT userid FROM {cohort_members} WHERE cohortid = :cohortid)';
          $params["cohortid"] = $cohortid;
        }
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
        $fields = "u.*";
        $from = "(SELECT d.id, d.userid, u.username, u.lastlogin, u.firstname, u.lastname, u.email, u.suspended, u.timecreated, d.data,
                  (SELECT d2.data FROM {user_info_field} f2, {user_info_data} d2 WHERE f2.shortname = 'JobTitle' AND d2.fieldid = f2.id AND d2.userid = u.id) as title,
                  '' AS actions
            FROM {user} u, {user_info_field} f,{user_info_data} d
            WHERE u.id = d.userid AND f.id = d.fieldid $sqlfilter) u";
        $where = "id > 0 $sql";

        $this->set_sql($fields, $from, $where, $params);
        $this->define_baseurl($PAGE->url);
    }
    function col_lastlogin($values) {
        return ($values->lastlogin) ? date('m/d/Y', $values->lastlogin) : '-';
    }
    function col_timecreated($values) {
        return date('m/d/Y', $values->timecreated);
    }
    function col_suspended($values) {
      if (!optional_param('download', '', PARAM_ALPHA)) {
        $attr = (!$values->suspended) ? " checked='checked'" : "";
        return "<input type='checkbox' class='userstatus' value='{$values->userid}' $attr />";
      }else{
        return (!$values->suspended) ? "Active" : "Suspended";
      }
    }
    function col_actions($values) {
        global  $PAGE;

        $html = html_writer::start_tag("div",array("style"=>"width:240px; margin: 5px 0;"));
        $html .= html_writer::link(new moodle_url('/user/profile.php', array('id'=>$values->userid)), 'Profile', array('class' =>'btn btn-default', 'target' => '_blank'));
        $html .= "&nbsp";
        $html .= html_writer::link(new moodle_url('transcript.php', array('userid'=>$values->userid)), 'Learner Transcript', array('class' =>'btn btn-default'));
        $html .= html_writer::end_tag("div");
        return $html;
    }
}


class intelliboard_pf_courses_table extends table_sql {

    function __construct($uniqueid, $search = '', $ids = '', $cohortid = 0, $status = 0, $cids = '') {
        global $CFG, $PAGE, $DB, $USER;

        parent::__construct($uniqueid);

        $headers = array();
        $columns = array();

        //$columns[] =  'username';
        //$headers[] =  get_string('username');

        $columns[] =  'firstname';
        $headers[] =  get_string('firstname');

        $columns[] =  'lastname';
        $headers[] =  get_string('lastname');

        $columns[] =  'email';
        $headers[] =  get_string('email');

        $columns[] =  'data';
        $headers[] =  'Club Location';

        //$columns[] =  'title';
        //$headers[] =  'Job Title';

        $columns[] =  'course';
        $headers[] =  'Course name';

        $columns[] =  'enrolled';
        $headers[] =  'Enrolled';

        //$columns[] =  'graded';
        //$headers[] =  'Graded';

        $columns[] =  'grade';
        $headers[] =  'Grade';

        $columns[] =  'completed';
        $headers[] =  'Completion status';

        $columns[] =  'timecompleted';
        $headers[] =  'Completion date';

        $columns[] =  'timeaccess';
        $headers[] =  'Last Access';

        if (!optional_param('download', '', PARAM_ALPHA)) {
          //$columns[] =  'actions';
          //$headers[] =  "Certificate";
        }

        $this->define_headers($headers);
        $this->define_columns($columns);

        $sql = "";
        $params = [];
        if ($status) {
          $sql .= ($status == 2) ? " AND u.suspended = 1" : " AND u.suspended = 0";
        }
        if ($search) {
            $where = [];
            foreach (['u.firstname', 'u.lastname', 'u.email', 'course'] as $key=>$column) {
              $where[] = $DB->sql_like($column, ":col".$key, false, false);
              $params["col".$key] = "%". $search ."%";
            }
            $sql .= ' AND ('.implode(' OR ',$where).')';
        }
        $sqlfilter = "";
        if ($cohortid) {
          $sqlfilter .= ' AND u.id IN (SELECT userid FROM {cohort_members} WHERE cohortid = :cohortid)';
          $params["cohortid"] = $cohortid;
        }
        if ($cids) {
          $sql .= " AND c.id IN ($cids)";
        } else {
          $sql .= " AND c.id IN (0)";
        }

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
        $grade_single = intelliboard_grade_sql();

        $fields = "CONCAT(ue.id, '_', u.data) AS id,  (SELECT cm.id FROM {modules} m, {course_modules} cm, {course} c WHERE c.id = cm.course AND cm.visible = 1 AND m.name = 'customcert' AND cm.module = m.id AND cm.course = c.id LIMIT 1) AS certificate,  u.*, ue.timecreated AS enrolled, c.fullname AS course, e.courseid, cc.timecompleted AS completed, cc.timecompleted, ul.timeaccess, $grade_single AS grade, CASE WHEN g.timemodified > 0 THEN g.timemodified ELSE g.timecreated END AS graded";
        $from = "(SELECT d.userid, u.username, u.firstname, u.lastname, u.email, u.suspended, u.timecreated, d.data,
                  (SELECT d2.data FROM {user_info_field} f2, {user_info_data} d2 WHERE f2.shortname = 'JobTitle' AND d2.fieldid = f2.id AND d2.userid = u.id) as title,
                  '' AS actions
            FROM {user} u, {user_info_field} f,{user_info_data} d
            WHERE u.id = d.userid AND f.id = d.fieldid AND u.suspended = 0 AND u.deleted = 0 $sqlfilter) u
            JOIN {user_enrolments} ue ON ue.userid = u.userid
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {course} c ON c.id = e.courseid
            LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = u.userid
            LEFT JOIN {user_lastaccess} ul ON ul.courseid = c.id AND ul.userid = u.userid
            LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
            LEFT JOIN {grade_grades} g ON g.userid = u.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
            ";
        $where = "ue.id > 0 AND ue.status = 0 AND e.status = 0 $sql";

        $this->set_sql($fields, $from, $where, $params);
        $this->define_baseurl($PAGE->url);
    }
    function col_timecompleted($values) {
        return ($values->timecompleted) ? date('m/d/Y', $values->timecompleted) : '-';
    }
    function col_completed($values) {
        return ($values->timecompleted) ? 'Completed ' : 'Not completed';
    }
    function col_timeaccess($values) {
        return ($values->timeaccess) ? date('m/d/Y', $values->timeaccess) : '-';
    }
    function col_graded($values) {
        return ($values->graded) ? date('m/d/Y', $values->graded) : '-';
    }
    function col_enrolled($values) {
        return ($values->enrolled) ? date('m/d/Y', $values->enrolled) : '';
    }
    function col_grade($values) {
        return (int)$values->grade;
    }
    function col_actions($values) {
        global  $PAGE;
        if ($values->certificate) {
          $html = html_writer::start_tag("div");
          $html .= html_writer::link(new moodle_url('/mod/customcert/view.php', ['id' => $values->certificate, 'downloadissue' => $values->userid]), 'View', array('class' =>'btn btn-default'));
          $html .= html_writer::end_tag("div");
          return $html;
      } else{
        return '-';
      }
    }
}


class intelliboard_pf_activities_table extends table_sql {
  public $activities = [];

  function __construct($uniqueid, $search = '', $ids = '', $cohortid = 0, $status = 0, $cids = '') {
      global $CFG, $PAGE, $DB, $USER;

      parent::__construct($uniqueid);

      $headers = array();
      $columns = array();

      //$columns[] =  'username';
      //$headers[] =  get_string('username');

      $columns[] =  'firstname';
      $headers[] =  get_string('firstname');

      $columns[] =  'lastname';
      $headers[] =  get_string('lastname');

      $columns[] =  'email';
      $headers[] =  get_string('email');

      $columns[] =  'data';
      $headers[] =  'Club Location';

      $columns[] =  'course';
      $headers[] =  'Course name';

      $columns[] =  'enrolled';
      $headers[] =  'Enrolled';

      $columns[] =  'title';
      $headers[] =  'Position';

      $sql_activities = "";
      $count_activities = 0;
      $completions = get_config('local_intelliboard', 'completions');
      $completions = ($completions) ? $completions : 1;

      if ($cids) {
        if ($modules = $DB->get_records_sql("SELECT m.id, m.name FROM {modules} m WHERE m.visible = 1 $sql_mods", $this->params)) {
          $sql_columns = "";
          foreach($modules as $module){
              $sql_columns .= " WHEN m.name='{$module->name}' THEN (SELECT name FROM {".$module->name."} WHERE id = cm.instance)";
          }
          if ($this->activities = $DB->get_records_sql("SELECT cm.id, CASE $sql_columns ELSE 'NONE' END AS activity  FROM {course_modules} cm, {modules} m WHERE cm.visible = 1 AND cm.module = m.id AND cm.course IN ($cids)")) {
            foreach ($this->activities as $activity) {
              $activity->name =  "activity" . $activity->id;
              $columns[] =  $activity->name;
              $headers[] =  $activity->activity;
              $sql_activities .= ", (SELECT cmc.timemodified FROM {course_modules_completion} cmc WHERE cmc.completionstate IN($completions) AND cmc.userid = u.userid AND cmc.coursemoduleid = {$activity->id}) AS " . $activity->name;
            }
            $count_activities = count($this->activities);
          }
        }
      }

      $columns[] =  'etc';
      $headers[] =  'ETC';

      $this->define_headers($headers);
      $this->define_columns($columns);

      $sql = "";
      $params = [];
      if ($status) {
        $sql .= ($status == 2) ? " AND u.suspended = 1" : " AND u.suspended = 0";
      }
      if ($search) {
          $where = [];
          foreach (['u.firstname', 'u.lastname', 'u.email', 'course'] as $key=>$column) {
            $where[] = $DB->sql_like($column, ":col".$key, false, false);
            $params["col".$key] = "%". $search ."%";
          }
          $sql .= ' AND ('.implode(' OR ',$where).')';
      }
      $sqlfilter = "";
      if ($cohortid) {
        $sqlfilter .= ' AND u.id IN (SELECT userid FROM {cohort_members} WHERE cohortid = :cohortid)';
        $params["cohortid"] = $cohortid;
      }
      if ($cids) {
        $sql .= " AND c.id = " . intval($cids);
      } else {
        $sql .= " AND c.id IN (0)";
      }

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

      $fields = "CONCAT(ue.id, '_', u.data) AS id, $count_activities AS etc,  u.*, ue.timecreated AS enrolled, c.fullname AS course, e.courseid $sql_activities";
      $from = "(SELECT d.userid, u.username, u.firstname, u.lastname, u.email, u.suspended, u.timecreated, d.data,
                (SELECT d2.data FROM {user_info_field} f2, {user_info_data} d2 WHERE f2.shortname = 'JobTitle' AND d2.fieldid = f2.id AND d2.userid = u.id) as title,
                '' AS actions
          FROM {user} u, {user_info_field} f,{user_info_data} d
          WHERE u.id = d.userid AND f.id = d.fieldid AND u.suspended = 0 AND u.deleted = 0 $sqlfilter) u
          JOIN {user_enrolments} ue ON ue.userid = u.userid
          JOIN {enrol} e ON e.id = ue.enrolid
          JOIN {course} c ON c.id = e.courseid";
      $where = "ue.id > 0 AND ue.status = 0 AND e.status = 0 $sql";

      $this->set_sql($fields, $from, $where, $params);
      $this->define_baseurl($PAGE->url);
  }
  function other_cols($colname, $attempt)
  {
    if ($this->activities) {
      foreach ($this->activities as $activity) {
        if ($activity->name == $colname) {
          return ($attempt->{$colname}) ? date('m/d/Y', $attempt->{$colname}) : 'incomplete';
        }
      }
    }
  }
  function col_enrolled($values) {
      return ($values->enrolled) ? date('m/d/Y', $values->enrolled) : '-';
  }
}



class intelliboard_pf_transcript_table extends table_sql {

    function __construct($uniqueid, $userid = 0, $search = '') {
        global $PAGE, $DB;

        parent::__construct($uniqueid);

        $headers = array('Course Name');
        $columns = array('course');

        //$columns[] =  'startdate';
        //$headers[] =  get_string('course_start_date', 'local_intelliboard');

        $columns[] =  'timemodified';
        $headers[] =  get_string('enrolled_date', 'local_intelliboard');

        $columns[] =  'grade';
        $headers[] =  get_string('score', 'local_intelliboard');

        $columns[] =  'timecompleted';
        $headers[] =  get_string('course_completion_status', 'local_intelliboard');

        if (!optional_param('download', '', PARAM_ALPHA)) {
          //$columns[] =  'actions';
          //$headers[] =  'Certificate';
        }

        $this->define_headers($headers);
        $this->define_columns($columns);

        $params = array('userid'=>$userid);
        $sql = "";
        if($search){
            $sql .= " AND " . $DB->sql_like('c.fullname', ":fullname", false, false);
            $params['fullname'] = "%$search%";
        }
        $grade_single = intelliboard_grade_sql(false, null, 'g.',0, 'gi.',true);

        $fields = "c.id, c.fullname as course,  (SELECT cm.id FROM {modules} m, {course_modules} cm, {course} c WHERE c.id = cm.course AND cm.visible = 1 AND c.visible = 1 AND m.name = 'customcert' AND cm.module = m.id AND cm.course = c.id LIMIT 1) AS certificate, c.timemodified, c.startdate, c.userid, c.enablecompletion, cri.gradepass, $grade_single AS grade, cc.timecompleted, '' as actions";

        $from = "(SELECT DISTINCT c.id, c.fullname, c.startdate, c.enablecompletion, MIN(ue.timemodified) AS timemodified, ue.userid FROM {user_enrolments} ue, {enrol} e, {course} c WHERE ue.userid = :userid  AND ue.status = 0 AND e.id = ue.enrolid AND e.status = 0 AND c.id = e.courseid AND c.visible = 1 GROUP BY c.id, ue.userid) c

            LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = c.userid
            LEFT JOIN {course_completion_criteria} as cri ON cri.course = c.id AND cri.criteriatype = 6
            LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
            LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = c.userid";
        $where = "c.id > 0 $sql";
        $this->set_sql($fields, $from, $where, $params);
        $this->define_baseurl($PAGE->url);
    }


    function col_startdate($values) {
        return  ($values->startdate) ? date('m/d/Y', $values->startdate) : "";
    }
    function col_timecompleted($values) {
        if(!$values->enablecompletion){
            return get_string('completion_is_not_enabled', 'local_intelliboard');
        }
        return  ($values->timecompleted) ? get_string('completed_on', 'local_intelliboard', date('m/d/Y', $values->timemodified)) : get_string('incomplete', 'local_intelliboard');
    }
    function col_grade($values) {
        return (int)$values->grade;
    }
    function col_completedmodules($values) {
        return intval($values->completedmodules)."/".intval($values->modules);
    }

    function col_timemodified($values) {
      return ($values->timemodified) ? date('m/d/Y', $values->timemodified) : '';
    }
    function col_actions($values) {
        global  $PAGE;
        if ($values->certificate) {
          $html = html_writer::start_tag("div");
          $html .= html_writer::link(new moodle_url('/mod/customcert/view.php', ['id' => $values->certificate, 'downloadissue' => $values->userid]), 'View', array('class' =>'btn btn-default'));
          $html .= html_writer::end_tag("div");
          return $html;
      } else{
        return '-';
      }
    }
}
