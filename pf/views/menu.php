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
 $id = $id??0;
?>

<ul class="nav nav-tabs">
<li class="nav-item <?php echo ($PAGE->pagetype == 'home')?'active':''; ?>"><a href="<?php echo new moodle_url("/local/intelliboard/pf/index.php", array("id"=>$id)); ?>">Manager Dashboard</a></li>
<li class="nav-item <?php echo ($PAGE->pagetype == 'courses')?'active':''; ?>"><a href="<?php echo new moodle_url("/local/intelliboard/pf/courses.php", array("id"=>$id)); ?>">Course Completion</a></li>
</ul>

<style>
.intelliboard-pf-header{
	background-color: #943193;
	padding:3px 15px;
	color: #fff;
	margin-bottom: 20px;
	font-size: 17px;
}
.intelliboard-pf-content{
	padding:5px 20px;
	margin-bottom: 20px;
}
.intelliboard-pf-head .form-group {
	margin-bottom: 10px;
}
.intelliboard-pf-head .value{
	font-weight: bold;
}
.intelliboard-pf-head select{
  width: 200px;
}
.cids{
  margin-right: 20px;
  max-width: 200px;
}
.intelliboard-pf-head .value,
.intelliboard-pf-head label{
	width: 120px;
	display: inline-block;
}
.intelliboard-pf-widgets .widget{
	min-height: 230px;
	margin-top: 0px;
}
.intelliboard-pf-widgets .widget-wrap{
	height: 200px;
	overflow: hidden;
}
.intelliboard-pf-widgets .left{
	width: 32%;
	float: left;
	border: 1px solid #943193;
	border-radius: 3px;
}
.intelliboard-pf-widgets .middle{
	width: 32%;
	float: left;
	border: 1px solid #943193;
	border-radius: 3px;
  margin: 0 1.7%;
}
.intelliboard-pf-widgets .right{
	width: 32%;
	float: right;
	border: 1px solid #943193;
	border-radius: 3px;
}
.intelliboard-pf-widgets h3{
	background-color: #943193;
	padding:3px 15px;
	color: #fff;
	margin-bottom: 0px;
	font-size: 17px;
	margin-top: 0;
}
.intelliboard-pf-content .ms-drop .actions button{
	margin: 0;
}
.intelliboard-pf-content.pf-table h2{
	clear: both;
}
.intelliboard-pf-content .paging{
	float: right;
	margin-top: 10px;
}
.intelliboard-pf-content .dataformatselector{
	float: left;
}
.intelliboard-pf-content .intelliboard-search{
	float: left;
	margin: 0;
}
</style>
