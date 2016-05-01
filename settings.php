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
 * Settings for format_glendon
 *
 * @package    format_glendon
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    //Bootstrap version being used in the Moodle theme. Default 2
    $name = 'format_glendon/bootstrapversion';
    $title = get_string('bootstrap_version', 'format_glendon');
    $description = get_string('bootstrap_version_help', 'format_glendon');
    $default = 2;
    $choices = array(
        2 => '2.x.x',
        3 => '3.x.x'
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));
    //Number of columns
    $name = 'format_glendon/numcolumns';
    $title = get_string('numcolumns', 'format_glendon');
    $description = get_string('numcolumns_help', 'format_glendon');
    $default = 3;
    $choices = array(
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        6 => '6',
        12 => '12'
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));
    //First section (Section 0). Should it be collapsed when students enter the course.
    $name = 'format_glendon/collapsed';
    $title = get_string('collapsed', 'format_glendon');
    $description = get_string('collapsed_help', 'format_glendon');
    $default = 1;
    $choices = array(
        1 => get_string('yes'),
        0 => get_string('no'),
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));
    //Length of default label
    $name = 'format_glendon/tablabel';
    $title = get_string('tab_label', 'format_glendon');
    $description = get_string('tab_label_help', 'format_glendon');
    $default = 25;
    $settings->add(new admin_setting_configtext($name, $title, $description, $default));
    //Cover image
    $name = 'format_glendon/coverimage';
    $title = get_string('cover_image', 'format_glendon');
    $description = get_string('cover_image_help', 'format_glendon');
    $filearea =  '/format_glendon/';
    $options = array(
        'maxfiles' => 1,
        'accepted_types' => 'jpg,gif,png,svg'
    );
    $settings->add(new admin_setting_configstoredfile($name, $title, $description, $filearea, 0, $options));
    //Course title
    $name = 'format_glendon/course_title';
    $title = get_string('course_title', 'format_glendon');
    $description = get_string('course_title_help', 'format_glendon');
    $default = 1;
    $choices = array(
        1 => get_string('yes'),
        0 => get_string('no'),
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));
}