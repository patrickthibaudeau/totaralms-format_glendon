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
 * Renderer for outputting the glendon course format.
 *
 * @package format_glendon
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
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
        return html_writer::start_tag('div', array('class' => 'container-fluid'));
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
        $bootstrapVersion = $course->bootstrapversion;

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
        $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation navigationtitle alert alert-info'));
        // Title attributes
        $classes = 'sectionname';
        if (!$thissection->visible) {
            $classes .= ' dimmed_text';
        }
        $sectionname = html_writer::tag('span', get_section_name($course, $displaysection));
        $sectiontitle .= $this->output->heading($sectionname, 3, $classes);
        $sectiontitle .= html_writer::end_tag('div');
        echo $sectiontitle;
        //If there is a section summary, print it here
        if (($this->format_summary_text($thissection)) && ($this->format_summary_text($thissection) != '<div class="no-overflow"><br></div>')) {
            echo html_writer::start_tag('div', array('class' => 'summary alert alert-warning', 'style' => 'margin-top: 10px;'));
            echo $this->format_summary_text($thissection);
            echo html_writer::end_tag('div');
        }

        if ($course->bootstrapversion == 2) {
            echo html_writer::start_tag('div', array('class' => 'row-fluid', 'style' => 'margin-top: 15px;'));
        } else {
            echo html_writer::start_tag('div', array('class' => 'row', 'style' => 'margin-top: 15px;'));
        }

        //$cmList = $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
        //This following code is required to find out if there are any modules within the section
        $section = convert_to_array($sectioninfo->getIterator());
        $courseModules = explode(',', $section['sequence']);
        //Start two column container. Left for menu, right for content
        //
        //************ LEFT MENU***********************
        if ($course->bootstrapversion == 2) {
            echo html_writer::start_tag('div', array('class' => 'span4'));
        } else {
            echo html_writer::start_tag('div', array('class' => 'col-md-4'));
        }

        echo $this->print_course_menu($course, $displaysection);

        echo html_writer::end_tag('div');
        //****************** END LEFT MENU**************
        //
        //****************** CONTENTS ******************
        if ($course->bootstrapversion == 2) {
            echo html_writer::start_tag('div', array('class' => 'span8'));
        } else {
            echo html_writer::start_tag('div', array('class' => 'col-md-8'));
        }
        //Only print tabs if there are labels
        if ($courseModules != null) {
            echo @$this->print_bootstrap3_tab_list($course, $displaysection);
            echo @$this->print_bootstrap3_tab_divs($course, $displaysection);
        }
        echo html_writer::end_tag('div'); //Row
        echo html_writer::end_tag('div');
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
        global $CFG, $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();
        $context = context_course::instance($course->id);
        $bootstrapVersion = $course->bootstrapversion;
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
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'format_glendon', 'cover_image', 1);

        foreach ($files as $file) {
            $filename = $file->get_filename();
            $out[] = '<img class="img-responsive" src="' . $CFG->wwwroot . '/pluginfile.php/' . $file->get_contextid() . '/format_glendon/cover_image/' . $file->get_itemid() . '/' . $filename . '" alt="' . $filename . '">';
        }
        if (isset($out[1])) {
            echo $this->print_section_row_start($bootstrapVersion);
            echo html_writer::start_tag('div', array('class' => 'col-md-12', 'align' => 'center', 'style' => 'margin-bottom: 5px;'));
            echo $out[1];
            echo html_writer::end_tag('div');
            echo $this->print_section_row_end();
        }
        //***************** Print section 0 also known as start here ************
        echo $this->print_section_row_start($bootstrapVersion);
        echo $this->print_start_here($bootstrapVersion, $course);
        echo $this->print_section_row_end();

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
            echo $this->print_section_row_start($bootstrapVersion);
            echo $this->print_section_columns($bootstrapVersion, $numberOfSections, $numberOfColumns, $i, $course, $printableSections);
            echo $this->print_section_row_end();
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
    protected function print_section_columns($bootstrapVersion, $numberOfSections, $numberOfColumns, $rowNumber, $course, $printableSections) {
        global $CFG, $PAGE;

        $rowStartSectionNumber = ($rowNumber * $numberOfColumns) + 1;
        $bootstrapColumnNumber = 12 / $numberOfColumns;
        $modinfo = get_fast_modinfo($course);
        $html = '';

        if ($bootstrapVersion == 3) {
            $columnClass = 'col-md-' . $bootstrapColumnNumber;
        } else {
            $columnClass = 'span' . $bootstrapColumnNumber;
        }
        //Get section number according to row start;
        $thisSection = $rowStartSectionNumber;
        for ($i = 1; $i <= $numberOfColumns; $i++) {
            $html .= html_writer::start_tag('div', array('class' => $columnClass));
            if (isset($printableSections[$thisSection])) {
                if ($sectionInfo = $modinfo->get_section_info($printableSections[$thisSection])) {

                    $html .= html_writer::start_tag('div', array('class' => 'well well-lg', 'style' => 'text-align: center; word-wrap: break-word;'));
                    $html .= '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '&section=' . $thisSection . '"'
                            . '  title="' . get_section_name($course, $sectionInfo) . '">'
                            . get_section_name($course, $sectionInfo) . '</a>';
                    $html .= html_writer::end_tag('div');
                    $thisSection++;
                }
            }
            $html .= html_writer::end_tag('div');
        }

        return $html;
    }

    protected function print_section_row_start($bootstrapVersion) {

        $rowClass = 'row-fluid';
        if ($bootstrapVersion == 3) {
            $rowClass = 'row';
        }

        $html = html_writer::start_tag('div', array('class' => $rowClass));

        echo $html;
    }

    protected function print_section_row_end() {

        $html = html_writer::end_tag('div');

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
    protected function print_start_here($bootstrapVersion, $course) {
        global $CFG, $PAGE;

        $modinfo = get_fast_modinfo($course);

        $sectionInfo = $modinfo->get_section_info(0);
        $summary = $this->format_summary_text($sectionInfo);
        $modList = $this->courserenderer->course_section_cm_list($course, $sectionInfo, 0);
        $sectionName = get_section_name($course, $sectionInfo);
        $collapsed = $course->collapsed;

        if ($bootstrapVersion == 3) {
            $columnClass = 'col-md-12';
            $btnClass = 'btn btn-lg btn-success';
            $collapse = $this->print_bootstrap3_collapse($sectionName, $summary, $modList, $collapsed);
        } else {
            $columnClass = 'span12';
            $btnClass = 'btn btn-large';
            $collapse = $this->print_bootstrap2_collapse($sectionName, $summary, $modList, $collapsed);
        }
        //Only need one column
        $html = html_writer::start_tag('div', array('class' => $columnClass));
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
    protected function print_bootstrap3_collapse($sectionName, $summary, $modListing, $collapsed) {

        $in = 'in';
        if ($collapsed == 1) {
            $in = '';
        }

        $html = '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">';
        $html .= '  <div class="panel panel-success">';
        $html .= '    <div class="panel-heading" role="tab" id="headingOne">';
        $html .= '      <h4 class="panel-title">';
        $html .= '        <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">';
        $html .= '          ' . $sectionName;
        $html .= '        </a>';
        $html .= '      </h4>';
        $html .= '    </div>';
        $html .= '    <div id="collapseOne" class="panel-collapse collapse ' . $in . '" role="tabpanel" aria-labelledby="headingOne">';
        $html .= '      <div class="panel-body">';
        $html .= '        <div class="well well-lg">';
        $html .= '          ' . $summary;
        $html .= '        </div>';
        $html .= '        <div>';
        $html .= '          ' . $modListing;
        $html .= '        </div>';
        $html .= '      </div>';
        $html .= '    </div>';
        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * return string HTML for Bootstrap 2 collapsable
     * @param string $sectionName
     * @param string $summary
     * @param string $modListing
     * @return string
     */
    protected function print_bootstrap2_collapse($sectionName, $summary, $modListing, $collapsed) {

        $in = 'in';
        if ($collapsed == 1) {
            $in = '';
        }

        $html = '<div class="accordion" id="accordion2">';
        $html .= '  <div class="accordion-group">';
        $html .= '    <div class="accordion-heading">';
        $html .= '      <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseOne">';
        $html .= '        ' . $sectionName;
        $html .= '      </a>';
        $html .= '    </div>';
        $html .= '    <div id="collapseOne" class="accordion-body collapse ' . $in . '">';
        $html .= '      <div class="accordion-inner">';
        $html .= '        <div class="well">';
        $html .= '          ' . $summary;
        $html .= '        </div>';
        $html .= '        ' . $modListing;
        $html .= '      </div>';
        $html .= '    </div>';
        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }

    protected function get_bootstrap3_button_style() {
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
    protected function print_bootstrap3_tab_list($course, $displaysection, $class = 'glendon_format') {
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
                if ($thisModuleArray['modname'] == 'label') {
                    $labelName = $thisModule->get_formatted_name();
                    //If lentgh is more than 25 Characters cut the label
                    if (strlen($labelName) < $course->tablabel) {
                        if ($i != 0) {
                            $activeClass = 'class="' . $class . '"';
                        } else {
                            $activeClass = 'class="' . $class . ' active' . '"';
                        }
                        $label .= '    <li role="presentation" ' . $activeClass . '><a href="#tab' . $i . '" aria-controls="tab' . $i . '" role="tab" data-toggle="tab">' . $labelName . '</a></li> ';

                        $i++;
                    }
                }
            }
        }
        //Setup HTML
        $html = '<div>';
        $html .= ' <!-- Nav tabs --> ';
        $html .= '  <ul class="nav nav-tabs" role="tablist"> ';
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
    protected function print_bootstrap3_tab_divs($course, $displaysection) {
        global $CFG, $DB, $OUTPUT;
        include_once($CFG->dirroot . '/question/editlib.php');

        $modinfo = get_fast_modinfo($course->id);
        $sectionInfo = $modinfo->get_section_info($displaysection);
        $section = convert_to_array($sectionInfo->getIterator());
        $courseModules = explode(',', $section['sequence']);
        //Get the config data for the module
        

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

                if (strlen($thisModule->get_formatted_name()) >= $course->tablabel) {
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
            $html .= '              <div class="format_glendon_tab_content">';


            for ($x = 1; $x < count($courseModulesByLabel[$z]); $x++) {
                //Get the module
                $module = get_module_from_cmid($courseModulesByLabel[$z][$x]);
                //Get simple module info
                $thisModule = $modinfo->get_cm($courseModulesByLabel[$z][$x]);
                //Convert to array. Required to be able to get all information as not all get methods exist for the object
                $thisModuleArray = convert_to_array($thisModule);
                //Keep this commented print_object. you will need it when you integrate completion info.
//                print_object($thisModuleArray);
                //Add note so that teacher knows it is hidden for students
                if ($thisModuleArray['visible'] == true) {
                    $hiddenFromStudents = '';
                } else {
                    $hiddenFromStudents = ' <span class="badge">' . get_string('hidden', 'format_glendon') . '</span>';
                }

                //Completion information
                if ($thisModuleArray['completionview'] == true) {
                    if ($thisModuleArray['completion'] == true) {
                        $completion = '<i class="glyphicon glyphicon-check format_glendon_complete"></i>';
                    } else {
                        $completion = '';
                    }
                } else {
                    $completion = '';
                }
                print_object($thisModuleArray);
                //Only display if it is visible to the user
                if ($thisModuleArray['uservisible'] == true) {
                    if ($thisModuleArray['modname'] == 'label') {
                        $image = '';
                        $link = $thisModule->get_formatted_content();
                    } else {
                        $image = '<img src="' . $thisModule->get_icon_url() . '" />';
                        $link = '<a href="' . $CFG->wwwroot . '/mod/' . $thisModuleArray['modname'] . '/view.php?id=' . $thisModuleArray['id'] . '">'
                                . $thisModule->get_formatted_name() . '</a>';
                    }

                    $html .= '          <div class="format_glendon_content_span">' . $image . ' ' . $link . $hiddenFromStudents . $completion . '</div>';
                }
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
        $html = '<div class="block format_glendon_menu">' . "\n";
        $html .= '   <div class="header format_glendon_menu_header">' . "\n";
        $html .= '       <h2>' . get_string('main_menu', 'format_glendon') . '</h2>' . "\n";
        $html .= '   </div>' . "\n";
        $html .= '   <div class="content format_glendon_menu_content">' . "\n";
        $html .= '      <ul class="format_glendon_ul">' . "\n";
        $html .= '      <li class="format_glendon_li"><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '" ><i class="fa fa-home"></i> ' . get_string('return', 'format_glendon') . '</a></li>' . "\n";
        foreach ($sections as $key => $thisSection) {
            $sectionInfo = $modinfo->get_section_info($thisSection);
            if ($displaysection == $thisSection) {
                $classActive = 'active';
            } else {
                $classActive = '';
            }
            if ($thisSection != 0) {
                $html .= '      <li class="format_glendon_li ' . $classActive . '"><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '&section=' . $thisSection . '"'
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

        $o.= html_writer::start_tag('li', array('id' => 'section-' . $section->section,
                    'class' => 'section main clearfix' . $sectionstyle, 'role' => 'region',
                    'aria-label' => get_section_name($course, $section)));

        // Create a span that contains the section title to be used to create the keyboard section move menu.
        $o .= html_writer::tag('span', $this->section_title($section, $course), array('class' => 'hidden sectionname'));

        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o.= html_writer::start_tag('div', array('class' => 'content'));

        // When not on a section page, we display the section titles except the general section if null
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }
        $sectionname = html_writer::tag('span', $this->section_title($section, $course));
        $o.= $this->output->heading($sectionname, 3, 'sectionname' . $classes);

        $context = context_course::instance($course->id);
        $o .= $this->section_availability_message($section, has_capability('moodle/course:viewhiddensections', $context));

        return $o;
    }

}
