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
//        $thissection = $modinfo->get_section_info(0);
//        if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
//            echo $this->start_section_list();
//            echo $this->section_header($thissection, $course, true, $displaysection);
//            echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
//            echo $this->courserenderer->course_section_add_cm_control($course, 0, $displaysection);
//            echo $this->section_footer();
//            echo $this->end_section_list();
//        }
        // Start single-section div
        echo html_writer::start_tag('div', array('class' => 'single-section'));

        // The requested section page.
        $thissection = $modinfo->get_section_info($displaysection);

        // Title with section navigation links.
        $sectionnavlinks = $this->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);
        $sectiontitle = '';
        $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation navigationtitle'));
        //$sectiontitle .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left'));
        //$sectiontitle .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right'));
        // Title attributes
        $classes = 'sectionname';
        if (!$thissection->visible) {
            $classes .= ' dimmed_text';
        }
        $sectionname = html_writer::tag('span', get_section_name($course, $displaysection));
        $sectiontitle .= $this->output->heading($sectionname, 3, $classes);

        $sectiontitle .= html_writer::end_tag('div');
        echo $sectiontitle;

        // Now the list of sections..
        echo $this->start_section_list();

        echo $this->section_header($thissection, $course, true, $displaysection);
        // Show completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();

        echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
        echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
        echo '<a href="'. $CFG->wwwroot . '/course/view.php?id=' . $course->id . '">Return</a>';
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
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();
        $context = context_course::instance($course->id);

        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_div();

        $numberOfSections = $course->numsections;
        $numberOfColumns = $course->numcolumns;
        $numberOfRows = ceil($numberOfSections / $numberOfColumns);
        $bootstrapVersion = $course->bootstrapversion;

        //Print section 0 also known as start here
        echo $this->print_section_row_start($bootstrapVersion);
        echo $this->print_start_here($bootstrapVersion, $course);
        echo $this->print_section_row_end();

        //Print all other sections
        for ($i = 0; $i < $numberOfRows; $i++) {
            echo $this->print_section_row_start($bootstrapVersion);
            echo $this->print_section_columns($bootstrapVersion, $numberOfSections, $numberOfColumns, $i, $course);
            echo $this->print_section_row_end();
        }

        echo $this->end_section_div();
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
    public function print_glendon_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0) {
                // 0-section is displayed a little different then the others
                if ($thissection->summary or ! empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
                    echo $this->section_header($thissection, $course, false, 0);
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, 0, 0);
                    echo $this->section_footer();
                }
                continue;
            }
            if ($section > $course->numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
                continue;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available &&
                    !empty($thissection->availableinfo));
            if (!$showsection) {
                // If the hiddensections option is set to 'show hidden sections in collapsed
                // form', then display the hidden section message - UNLESS the section is
                // hidden by the availability system, which is set to hide the reason.
                if (!$course->hiddensections && $thissection->available) {
                    echo $this->section_hidden($section, $course->id);
                }

                continue;
            }

            if (!$PAGE->user_is_editing() && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                // Display section summary only.
                echo $this->section_summary($thissection, $course, null);
            } else {
                echo $this->section_header($thissection, $course, false, 0);
                if ($thissection->uservisible) {
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, $section, 0);
                }
                echo $this->section_footer();
            }
        }

        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $course->numsections or empty($modinfo->sections[$section])) {
                    // this is not stealth section or it is empty
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            echo html_writer::start_tag('div', array('id' => 'changenumsections', 'class' => 'mdl-right'));

            // Increase number of sections.
            $straddsection = get_string('increasesections', 'moodle');
            $url = new moodle_url('/course/changenumsections.php', array('courseid' => $course->id,
                'increase' => true,
                'sesskey' => sesskey()));
            $icon = $this->output->pix_icon('t/switch_plus', $straddsection);
            echo html_writer::link($url, $icon . get_accesshide($straddsection), array('class' => 'increase-sections'));

            if ($course->numsections > 0) {
                // Reduce number of sections sections.
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php', array('courseid' => $course->id,
                    'increase' => false,
                    'sesskey' => sesskey()));
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                echo html_writer::link($url, $icon . get_accesshide($strremovesection), array('class' => 'reduce-sections'));
            }

            echo html_writer::end_tag('div');
        } else {
            echo $this->end_section_list();
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
    protected function print_section_columns($bootstrapVersion, $numberOfSections, $numberOfColumns, $rowNumber, $course) {
        global $CFG, $PAGE;

        $rowStartSectionNumber = ($rowNumber * $numberOfColumns) + 1;
        $bootstrapColumnNumber = 12 / $numberOfColumns;
        $modinfo = get_fast_modinfo($course);
        $html = '';



        if ($bootstrapVersion == 3) {
            $columnClass = 'col-md-' . $bootstrapColumnNumber;
            $btnClass = 'btn btn-lg btn-warning';
        } else {
            $columnClass = 'span' . $bootstrapColumnNumber;
            $btnClass = 'btn btn-large';
        }

        for ($i = 0; $i < $numberOfColumns; $i++) {
            $html .= html_writer::start_tag('div', array('class' => $columnClass));

            $thisSection = ($rowStartSectionNumber + $i);
            
            if ($sectionInfo = $modinfo->get_section_info($thisSection)) {
                $html .= html_writer::start_tag('div', array('class' => 'well well-lg', 'style' => 'text-align: center'));
                $html .= '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '&section=' . $thisSection . '"'
                        . ' class="' . $btnClass . '" title="' . get_section_name($course, $sectionInfo) . '">'
                        . get_section_name($course, $sectionInfo) . '</a>';
                $html .= html_writer::end_tag('div');
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
        if($collapsed == 1) {
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
        if($collapsed == 1) {
            $in = '';
        }
        
        $html = '<div class="accordion" id="accordion2">';
        $html .= '  <div class="accordion-group">';
        $html .= '    <div class="accordion-heading">';
        $html .= '      <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseOne">';
        $html .= '        ' . $sectionName;
        $html .= '      </a>';
        $html .= '    </div>';
        $html .= '    <div id="collapseOne" class="accordion-body collapse '. $in . '">';
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
    
    protected function get_bootstrap3_botton_style() {
        $styles = array(
            'btn-success' => 'btn-success',
            'btn-warning' => 'btn-warning',
            'btn-default' => 'btn-default',
            'btn-danger' => 'btn-danger',
        );
        
        return array_rand($styles);
    }

}
