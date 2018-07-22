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
 * Glendon Course Format: Displays course front page in a grid format
 * and the content in a tabbed format
 * @package format_glendon
 * @copyright 2018 Glendon - York University
 * @author Patrick Thibaudeau
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/renderer.php');

/**
 * Basic renderer for glendon format.
 *
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_glendon_renderer extends format_section_renderer_base {

    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        global $PAGE;
        parent::__construct($page, $target);
        // Since format_glendon_renderer::section_edit_controls() only displays the 'Set current section' control when editing mode is on
        // we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', array('class' => 'glendon'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_div() {
//        return html_writer::start_tag('div', array('class' => 'container-fluid'));
        return html_writer::start_tag('div', []);
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_div() {
        return html_writer::end_tag('div');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('topicoutline');
    }

    /**
     * Generate the edit control items of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false) {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $isstealth = $section->section > $course->numsections;
        $controls = array();
        if (!$isstealth && $section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $markedthistopic = get_string('markedthistopic');
                $highlightoff = get_string('highlightoff');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marked',
                    'name' => $highlightoff,
                    'pixattr' => array('class' => '', 'alt' => $markedthistopic),
                    'attr' => array('class' => 'editing_highlight', 'title' => $markedthistopic));
            } else {
                $url->param('marker', $section->section);
                $markthistopic = get_string('markthistopic');
                $highlight = get_string('highlight');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marker',
                    'name' => $highlight,
                    'pixattr' => array('class' => '', 'alt' => $markthistopic),
                    'attr' => array('class' => 'editing_highlight', 'title' => $markthistopic));
            }
        }

        $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

        // If the edit key exists, we are going to insert our controls after it.
        if (array_key_exists("edit", $parentcontrols)) {
            $merged = array();
            // We can't use splice because we are using associative arrays.
            // Step through the array and merge the arrays.
            foreach ($parentcontrols as $key => $action) {
                $merged[$key] = $action;
                if ($key == "edit") {
                    // If we have come to the edit key, merge these controls here.
                    $merged = array_merge($merged, $controls);
                }
            }

            return $merged;
        } else {
            return array_merge($controls, $parentcontrols);
        }
    }

    /**
     * Output the html for a single section page .
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     * @param int $displaysection The section number in the course which is being displayed
     */
    public function print_glendon_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
        global $CFG, $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        // Can we view the section in question?
        if (!($sectioninfo = $modinfo->get_section_info($displaysection))) {
            // This section doesn't exist
            print_error('unknowncoursesection', 'error', null, $course->fullname);
            return;
        }

        if (!$sectioninfo->uservisible) {
            if (!$course->hiddensections) {
                echo $this->start_section_list();
                echo $this->section_hidden($displaysection, $course->id);
                echo $this->end_section_list();
            }
            // Can't view this section.
            return;
        }

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, $displaysection);
        // Start single-section div
        echo html_writer::start_tag('div', array('class' => 'single-section'));

        // The requested section page.
        $thissection = $modinfo->get_section_info($displaysection);

        // Title 
        $sectiontitle = '';
        $sectiontitle .= html_writer::start_tag('div', array('class' => 'topic-text'));
        // Title attributes
        $classes = 'sectionname';
        if (!$thissection->visible) {
            $classes .= ' dimmed_text';
        }
        $sectiontitle .= '<a href="javascript:void(0)" title="' . get_string('toggle_course_menu', 'format_glendon') . '" id="course-menu-toggle" class="pull-left active"><img id="course-menu-toogle-image" src="' . $CFG->wwwroot . '/pix/t/switch_minus.png"></a>';
        $sectiontitle .= get_section_name($course, $displaysection);

        $sectiontitle .= html_writer::end_tag('div');
        echo $sectiontitle;
        //If there is a section summary, print it here
//        if (($this->format_summary_text($thissection)) && ($this->format_summary_text($thissection) != '<div class="no-overflow"><br></div>')) {
//            echo '<div class="summary alert alert-default" style="margin-top: 10px;">';
//            echo $this->format_summary_text($thissection);
//            echo '</div>';
//        }


        echo html_writer::start_tag('div', array('class' => 'row', 'style' => 'margin-top: 15px;'));


        //$cmList = $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
        //This following code is required to find out if there are any modules within the section
        $section = convert_to_array($sectioninfo->getIterator());
        $courseModules = explode(',', $section['sequence']);
        //Start two column container. Left for menu, right for content
        //************ RIGHT MENU***********************
        echo html_writer::start_tag('div', array('id' => 'format-glendon-content-left', 'class' => 'col-md-4'));
        echo $this->print_course_menu($course, $displaysection);
        echo html_writer::end_tag('div');
        //****************** END RIGHT MENU**************
        //****************** CONTENTS ******************
        echo html_writer::start_tag('div', array('id' => 'format-glendon-content-right', 'class' => 'col-md-8'));

        //Only print tabs if there are labels
        if ($courseModules != null) {
            echo @$this->print_bootstrap_tab_list($course, $displaysection);
            echo @$this->print_bootstrap_tab_divs($course, $displaysection);
        }

        echo html_writer::end_tag('div');

        echo html_writer::end_tag('div'); //Row
        //*******************END CONTENTS ****************
        // Now the list of sections..
        echo $this->start_section_list();

        echo $this->section_header($thissection, $course, true, $displaysection);
        // Show completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->section_footer();
        echo $this->end_section_list();

        // Close single-section div.
        echo html_writer::end_tag('div');
    }

    /**
     * Renders HTML to display a list of course modules in a course section
     * Also displays "move here" controls in Javascript-disabled mode
     *
     * This function calls {@link core_course_renderer::course_section_cm()}
     *
     * @param stdClass $course course object
     * @param int|stdClass|section_info $section relative section number or section object
     * @param int $sectionreturn section number to return to
     * @param int $displayoptions
     * @return void
     */
    public function course_section_cm_list($course, $section, $sectionreturn = null, $displayoptions = array()) {
        global $USER;

        $output = '';
        $modinfo = get_fast_modinfo($course);
        if (is_object($section)) {
            $section = $modinfo->get_section_info($section->section);
        } else {
            $section = $modinfo->get_section_info($section);
        }
        $completioninfo = new completion_info($course);

        // check if we are currently in the process of moving a module with JavaScript disabled
        $ismoving = $this->page->user_is_editing() && ismoving($course->id);
        if ($ismoving) {
            $movingpix = new pix_icon('movehere', get_string('movehere'), 'moodle', array('class' => 'movetarget'));
            $strmovefull = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
        }

        // Get the list of modules visible to user (excluding the module being moved if there is one)
        $moduleshtml = array();
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];

                if ($ismoving and $mod->id == $USER->activitycopy) {
                    // do not display moving mod
                    continue;
                }

                if ($modulehtml = $this->course_section_cm_list_item($course, $completioninfo, $mod, $sectionreturn, $displayoptions)) {
                    $moduleshtml[$modnumber] = $modulehtml;
                }
            }
        }

        $sectionoutput = '';
        if (!empty($moduleshtml) || $ismoving) {
            foreach ($moduleshtml as $modnumber => $modulehtml) {
                if ($ismoving) {
                    $movingurl = new moodle_url('/course/mod.php', array('moveto' => $modnumber, 'sesskey' => sesskey()));
                    $sectionoutput .= html_writer::tag('li', html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)), array('class' => 'movehere'));
                }

                $sectionoutput .= $modulehtml;
            }

            if ($ismoving) {
                $movingurl = new moodle_url('/course/mod.php', array('movetosection' => $section->id, 'sesskey' => sesskey()));
                $sectionoutput .= html_writer::tag('li', html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)), array('class' => 'movehere'));
            }
        }

        // Always output the section module list.
        $output .= html_writer::tag('ul', $sectionoutput, array('class' => 'section img-text'));

        return $output;
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_course_front_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $CFG, $PAGE, $DB;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();
        $context = context_course::instance($course->id);
        //Redirect to highlighted section
        @$this->redirect_highlighted_section($course);

        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_div();

        //*********************Print image if there is one **********************
        $out = array();
        $context = context_course::instance($course->id);
        require_once($CFG->libdir . '/coursecatlib.php');
        $course = new course_in_list($course);
        foreach ($course->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                    $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
            if ($isimage) {
                $image = '<img class="img-fluid course-front-page" src="' . $url . '" alt="Image ' . $course->fullname . '">';
                break;
            }
        }

        if (isset($image)) {
            echo html_writer::start_tag('div', array('align' => 'center', 'style' => 'margin-bottom: 5px;'));
            echo $image;
            echo html_writer::end_tag('div');
        }
        //***************** Print section 0 also known as start here ************
//        echo $this->print_section_row_start();
        echo $this->print_start_here($course);
//        echo $this->print_section_row_end();
        //********************* Print out course sections ***********************
        //Get printable sections
        $printableSections = $this->get_printable_sections($course);
        //Make array start at 1
        $printableSections = array_combine(range(1, count($printableSections)), array_values($printableSections));
//        $numberOfSections = $course->numsections;
        $numberOfSections = count($printableSections);
        $numberOfColumns = $course->numcolumns;
        $numberOfRows = ceil($numberOfSections / $numberOfColumns);


        //Print all other sections
        for ($i = 0; $i < $numberOfRows; $i++) {
//            echo $this->print_section_row_start();
            echo $this->print_section_columns($numberOfSections, $numberOfColumns, $i, $course, $printableSections);
//            echo $this->print_section_row_end();
        }

        echo $this->end_section_div();
    }

    /**
     * Returns an array with the sections that can be printed
     * @global stdClass $CFG
     * @global moodle_page $PAGE
     * @param stdClass $course
     * @return array
     */
    protected function get_printable_sections($course) {
        global $CFG, $PAGE;

        $modinfo = get_fast_modinfo($course);
        //Sections with contents
        $courseSections = $modinfo->get_sections();
        unset($courseSections[0]);
        $sections = array();
        foreach ($courseSections as $thisSection => $sectionId) {
            $sectionInfo = $modinfo->get_section_info($thisSection);
            $sectionInfo = convert_to_array($sectionInfo);

            if ($sectionInfo['visible'] == true) {
                $sections[] = $thisSection;
            }
        }

        return $sections;
    }

    /**
     * Returns an array with the sections that can be printed
     * @global stdClass $CFG
     * @global moodle_page $PAGE
     * @param stdClass $course
     * @return array
     */
    protected function redirect_highlighted_section($course) {
        global $CFG, $PAGE;

        $modinfo = get_fast_modinfo($course);
        //Sections with contents
        $courseSections = $modinfo->get_sections();
        unset($courseSections[0]);
        foreach ($courseSections as $thisSection => $sectionId) {
            $sectionInfo = $modinfo->get_section_info($thisSection);
            $sectionInfoArray = convert_to_array($sectionInfo);
            if ($sectionInfoArray['visible'] == true) {
                if (course_get_format($course)->is_section_current($sectionInfo)) {
                    redirect($CFG->wwwroot . '/course/view.php?id=' . $course->id . '&section=' . $thisSection, '', 0);
                }
            }
        }
    }

    /**
     * 
     * @global stdClass $CFG
     * @global moodle_page $PAGE
     * @param int $bootstrapVersion
     * @param int $numberOfSections
     * @param int $numberOfColumns
     * @param int $rowNumber Current row
     * @param stdClass $course
     * @return string HTML
     */
    protected function print_section_columns($numberOfSections, $numberOfColumns, $rowNumber, $course, $printableSections) {
        global $CFG, $PAGE;

        $rowStartSectionNumber = ($rowNumber * $numberOfColumns) + 1;
        $bootstrapColumnNumber = 12 / $numberOfColumns;
        $modinfo = get_fast_modinfo($course);
        $html = '';

        $columnClass = 'col-md-' . $bootstrapColumnNumber;

        //Get section number according to row start;
        $thisSection = $rowStartSectionNumber;
        $html .= html_writer::start_tag('div', array('class' => 'card-deck', 'style' => 'margin-top: 15px;'));
        for ($i = 1; $i <= $numberOfColumns; $i++) {

            if (isset($printableSections[$thisSection])) {
                if ($sectionInfo = $modinfo->get_section_info($printableSections[$thisSection])) {
                    $summary = $this->format_summary_text($sectionInfo);
                    preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', $summary, $result);
                    if (isset($result[0])) {
                        $image = $result[0] . ' class="card-image-top"  style="height: 160px; width: 100%; object-fit: cover;" alt="Image"/>';
                    } else {
                        $image = '';
                    }
                    $html .= '<div class="card">';
                    if (isset($result[0])) {
                        $html .= '          <a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '&section=' . $thisSection . '"'
                                . '  title="' . get_section_name($course, $sectionInfo) . '">';
                        $html .= $image;
                        $html .= '</a>';
                    }
                    $html .= '  <div class="card-body">';
                    $html .= '      <h5 class="card-title">';
                    $html .= '          <a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '&section=' . $thisSection . '"'
                            . '  title="' . get_section_name($course, $sectionInfo) . '">'
                            . get_section_name($course, $sectionInfo) . '</a>';
                    $html .= '      </h5>';
                    $summary = preg_replace("/<img[^>]+\>/i", "", $summary);
                    ;
                    $html .= '<p class="card-text">' . $summary . '</p>';
                    $html .= '  </div>';

                    $html .= '</div>';
                    $thisSection++;
                }
            }
        }
        $html .= html_writer::end_tag('div');



        return $html;
    }

    protected function print_section_row_start() {

        $html = html_writer::start_tag('div', array('class' => 'row'));

        echo $html;
    }

    protected function print_section_row_end() {
        $html = html_writer::end_tag('div'); //Row

        echo $html;
    }

    /**
     * returns string HTML for Section 0
     * @global stdClass $CFG
     * @global moodle_page $PAGE
     * @param int $bootstrapVersion
     * @param stdClass $course
     * @return type
     */
    protected function print_start_here($course) {
        global $CFG, $PAGE;

        $modinfo = get_fast_modinfo($course);

        $sectionInfo = $modinfo->get_section_info(0);
        $summary = $this->format_summary_text($sectionInfo);
        $modList = $this->courserenderer->course_section_cm_list($course, $sectionInfo, 0);
        $sectionName = get_section_name($course, $sectionInfo);
        $collapsed = $course->collapsed;

        $columnClass = 'col-md-12';
        $btnClass = 'btn btn-lg btn-success';
        $collapse = $this->print_bootstrap_collapse($sectionName, $summary, $modList, $collapsed);

        //Only need one column
        $html = html_writer::start_tag('div', []);
        $html .= $collapse;
        $html .= html_writer::end_tag('div');

        return $html;
    }

    /**
     * return string HTML for Bootstrap 3 collapsable
     * @param string $sectionName
     * @param string $summary
     * @param string $modListing
     * @return string
     */
    protected function print_bootstrap_collapse($sectionName, $summary, $modListing, $collapsed) {

        $in = 'show in';
        if ($collapsed == 1) {
            $in = '';
        }

        $html = '<div id="accordion" role="tablist" aria-multiselectable="true">';
        $html .= '  <div class="card">';
        $html .= '    <div class="card-header  bg-green" id="headingOne">';
        $html .= '      <h5 class="mb-0">';
        $html .= '          <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">';
        $html .= '          ' . $sectionName;
        $html .= '        </button>';
        $html .= '      </h5>';
        $html .= '    </div>';
        $html .= '    <div id="collapseOne" class="collapse ' . $in . '" data-parent="#accordion" aria-labelledby="headingOne">';
        $html .= '      <div class="card-block">';
        if (!empty($summary)) {
            $html .= '        <div class="well well-lg">';
            $html .= '          ' . $summary;
            $html .= '        </div>';
        }
        $html .= '        <div>';
        $html .= '          ' . $modListing;
        $html .= '        </div>';
        $html .= '      </div>';
        $html .= '    </div>';
        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }

    protected function get_bootstrap_button_style() {
        $styles = array(
            'btn-success' => 'btn-success',
            'btn-warning' => 'btn-warning',
            'btn-default' => 'btn-default',
            'btn-danger' => 'btn-danger',
        );

        return array_rand($styles);
    }

    /**
     * 
     * @global stdClass $CFG
     * @global moodle_database $DB
     * @param moodle_course $course
     * @param int $displaysection
     * @param strings $class
     * @return string
     */
    protected function print_bootstrap_tab_list($course, $displaysection, $class = '') {
        global $CFG, $DB;

        //Get course modules
        $modinfo = get_fast_modinfo($course->id);
        //Get this section info
        $sectionInfo = $modinfo->get_section_info($displaysection);
        //Convert sectin info into an array
        $section = convert_to_array($sectionInfo->getIterator());
        //get all course modules for this section in the order that they appear in the section
        $courseModules = explode(',', $section['sequence']);
        //Get the config data for the module

        $i = 0;

        $label = '';
        if ($courseModules[0] != null) {

            foreach ($courseModules as $key => $value) {
                $thisModule = $modinfo->cms[$value];
                $thisModuleArray = convert_to_array($thisModule);
                if ($thisModuleArray['modname'] == 'label' && $thisModuleArray['deletioninprogress'] == 0) {
//                    $labelName = $thisModule->get_formatted_name();
                    preg_match("#<\s*?h2\b[^>]*>(.*?)</h2\b[^>]*>#s", $thisModule->get_formatted_content(), $matches);
                    $labelName = strip_tags($matches[1]);
                    //If lentgh is more than 25 Characters cut the label
                    if ($labelName != '') {
                        if ($i != 0) {
                            $activeClass = 'class="nav-link ' . $class . '"';
                        } else {
                            $activeClass = 'class="nav-link ' . $class . ' active' . '"';
                        }
                        $label .= '    <li class="nav-item"><a href="#tab' . $i . '" aria-controls="tab' . $i . '" role="tab" data-toggle="tab" ' . $activeClass . '>' . $labelName . '</a></li> ';

                        $i++;
                    }
                }
            }
        }
        //Setup HTML
        $html = '<div>';
        $html .= ' <!-- Nav tabs --> ';
        $html .= '  <ul class="nav nav-tabs"> ';
        $html .= $label;
        $html .= '  </ul> ';
        $html .= ' ';



        return $html;
    }

    /**
     * 
     * @global moodle_database $DB
     * @param type $labels
     * @param type $course
     * @param type $displaysection
     * @return string
     */
    protected function print_bootstrap_tab_divs($course, $displaysection) {
        global $CFG, $DB, $OUTPUT, $PAGE;
        include_once($CFG->dirroot . '/question/editlib.php');
        include_once($CFG->dirroot . '/course/renderer.php');

        $courseRenderer = new core_course_renderer($PAGE, '');

        $modinfo = get_fast_modinfo($course->id);
        $sectionInfo = $modinfo->get_section_info($displaysection);
        $section = convert_to_array($sectionInfo->getIterator());
        $courseModules = explode(',', $section['sequence']);
        //Get the config data for the module
        $completioninfo = new completion_info($course);

        $labels = array();

        $i = 0;
        foreach ($courseModules as $key => $value) {
            $thisModule = $modinfo->cms[$value];
            $thisModuleArray = convert_to_array($thisModule);
            if ($thisModuleArray['modname'] == 'label') {
                $labels[$i] = $i;
                $i++;
            }
        }


        $i = 0;

        $courseModulesByLabel = array();
        foreach ($courseModules as $key => $value) {
            $thisModule = $modinfo->cms[$value];
            $thisModuleInfo = $thisModule->get_course_module_record();
            $thisModuleArray = convert_to_array($thisModule);
            if ($thisModuleArray['modname'] == 'label') {
                preg_match("#<\s*?h2\b[^>]*>(.*?)</h2\b[^>]*>#s", $thisModule->get_formatted_content(), $matches);
                if (!isset($matches[1])) {
                    $x++;
                    $courseModulesByLabel[$i][$x] = $thisModuleInfo->id;
                } else {
                    $i++;
                    $x = 0;
                    $courseModulesByLabel[$i][$x] = 'Label_' . $i;
                }
            } else {
                $x++;
                $courseModulesByLabel[$i][$x] = $thisModuleInfo->id;
            }
        }

        $html = ' <!-- Tab panes --> ';
        $html .= '  <div class="tab-content"> ';
        $i = 0;
        $z = 1;
        foreach ($labels as $l) {
            if ($i == 0) {
                $class = 'active';
            } else {
                $class = '';
            }
            $html .= '    <div role="tabpanel" class="tab-pane ' . $class . '" id="tab' . $i . '">';
            $html .= '      <div class="container-fluid">';
            $html .= '          <div class="col-md-12" style="margin-top: 10px;">';
            $html .= '              <div class="section img-text">';


            for ($x = 1; $x < count($courseModulesByLabel[$z]); $x++) {
                //Get the module
                $module = get_module_from_cmid($courseModulesByLabel[$z][$x]);
                //Get simple module info
                $thisModule = $modinfo->get_cm($courseModulesByLabel[$z][$x]);


                //Convert to array. Required to be able to get all information as not all get methods exist for the object
                $thisModuleArray = convert_to_array($thisModule);
                $mod = $modinfo->cms[$thisModuleArray[id]];

                $html .= $courseRenderer->course_section_cm_list_item($course, $completioninfo, $mod, null, null);
            }

            $html .= '              </div> ';
            $html .= '          </div> ';
            $html .= '      </div> ';
            $html .= '     </div> ';
            $i++;
            $z++;
        }
        $html .= '  </div> ';
        $html .= '</div>';

        return $html;
    }

    /**
     * This function returns an array of cmids containing the top level label and all
     * modules underneath that label
     * @global stdClass $CFG
     * @global moodle_database $DB
     * @global moodle_output $OUTPUT
     * @param stdClass $course
     * @param int $displaysection
     * @return array containing
     */
    private function print_course_menu($course, $displaysection) {
        global $CFG, $DB, $OUTPUT;

        //Get course modules
        $modinfo = get_fast_modinfo($course->id);
        $sections = $this->get_printable_sections($course);
        $html = '<div >' . "\n";
        $html .= '   <div >' . "\n";
        $html .= '      <ul class="list-group">' . "\n";
        $html .= '      <li class="list-group-item"><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '" ><i class="fa fa-home"></i> ' . get_string('return', 'format_glendon') . '</a></li>' . "\n";
        foreach ($sections as $key => $thisSection) {
            $sectionInfo = $modinfo->get_section_info($thisSection);
            if ($displaysection == $thisSection) {
                $classActive = 'active';
            } else {
                $classActive = '';
            }
            if ($thisSection != 0) {
                $html .= '      <li class="list-group-item ' . $classActive . '"><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '&section=' . $thisSection . '"'
                        . '  title="' . get_section_name($course, $sectionInfo) . '">'
                        . get_section_name($course, $sectionInfo) . '</a></li>';
            }
        }

        $html .= '      </ul>' . "\n";

        $html .= '  </div>' . "\n";
        $html .= '</div>' . "\n";
        return $html;
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn = null) {
        global $PAGE;

        $o = '';
        $currenttext = '';
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        $o .= html_writer::start_tag('li', array('id' => 'section-' . $section->section,
                    'class' => 'section main clearfix' . $sectionstyle, 'role' => 'region',
                    'aria-label' => get_section_name($course, $section)));

        // Create a span that contains the section title to be used to create the keyboard section move menu.
        $o .= html_writer::tag('span', $this->section_title($section, $course), array('class' => 'hidden sectionname'));

        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $o .= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o .= html_writer::start_tag('div', array('class' => 'content'));

        // When not on a section page, we display the section titles except the general section if null
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }
        $sectionname = html_writer::tag('span', $this->section_title($section, $course));
        $o .= $this->output->heading($sectionname, 3, 'sectionname' . $classes);

        $context = context_course::instance($course->id);
        $o .= $this->section_availability_message($section, has_capability('moodle/course:viewhiddensections', $context));

        return $o;
    }

    /**
     * Generate html for a section summary text
     *
     * @param stdClass $section The course_section entry from DB
     * @return string HTML to output.
     */
    protected function format_summary_text($section) {
        $context = context_course::instance($section->course);
        $summarytext = file_rewrite_pluginfile_urls($section->summary, 'pluginfile.php', $context->id, 'course', 'section', $section->id);

        $options = new stdClass();
        $options->noclean = true;
        $options->overflowdiv = true;
        return format_text($summarytext, $section->summaryformat, $options);
    }

}
