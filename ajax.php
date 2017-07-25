<?php

function print_previous_next_buttons() {
    global $CFG, $COURSE, $DB, $PAGE, $USER;
    include_once($CFG->dirroot . '/question/editlib.php');

    $context = $PAGE->context;


//    $moodleUrl = $this->page->url;
//    $info = $moodleUrl->get_param('info'); //Assignment grading
//    $attempt = $moodleUrl->get_param('attempt'); //Quizzes
//    $cmid = $moodleUrl->get_param('cmid'); //Glossary entry
//    $asid = $moodleUrl->get_param('asid'); //Workshop entry
//    $forum = $moodleUrl->get_param('f'); //Workshop entry
//    $forum2 = $moodleUrl->get_param('d'); //Workshop entry
//
//    $displayButtons = true;
//
//    if (isset($info)) {
//        $displayButtons = false;
//    }
//    if (isset($attempt)) {
//        $displayButtons = false;
//    }
//    if (isset($cmid)) {
//        $displayButtons = false;
//    }
//    if (isset($asid)) {
//        $displayButtons = false;
//    }
//    if (isset($forum)) {
//        $displayButtons = false;
//    }
//    if (isset($forum2)) {
//        $displayButtons = false;
//    }
//    //course admin page
//    if (strstr($moodleUrl->get_path(), 'course/admin.php')) {
//        $displayButtons = false;
//    }
//    //wiki create
//    if (strstr($moodleUrl->get_path(), 'wiki/')) {
//        $displayButtons = false;
//    }
//    //forum posts
//    if (strstr($moodleUrl->get_path(), 'forum/post.php')) {
//        $displayButtons = false;
//    }
//
//    //grade
//    if (strstr($moodleUrl->get_path(), '/grade/')) {
//        $displayButtons = false;
//    }


    if ($displayButtons == true) {
        $id = optional_param('id', 0, PARAM_INT);
        $html = '';
//        Make sure you are within a module
        if ($id != $COURSE->id) {
//            This module
            $mod = get_module_from_cmid($id);
//            Get actual section formation
            $modSection = $DB->get_record('course_sections', array('id' => $mod[1]->section));
//            Get all mods for the course
            $modinfo = get_fast_modinfo($COURSE->id);
            $sectionInfo = $modinfo->get_section_info($modSection->section);
//            Get all mod cmid within this section
            $section = convert_to_array($sectionInfo->getIterator());
            $sequence = explode(',', $section['sequence']);

//            Remove all labels as they cannot be viewed
            $flagForRemoval = '';
            for ($i = 0; $i < count($sequence); $i++) {
                $thisMod = get_module_from_cmid($sequence[$i]);
                if ($thisMod[1]->modname == 'label') {
                    $flagForRemoval .= $i . ',';
                }
            }

            $flagForRemoval = explode(',', rtrim($flagForRemoval, ','));
//            Remove from array
            foreach ($flagForRemoval as $key => $value) {
                unset($sequence[$value]);
            }

//            Reorder the array
            $sequence = array_values($sequence);

            $html .= '<div class="col-md-12 p-a-1">';

            $thisModKey = array_search($id, $sequence);
            if ($thisModKey > 0) {
                $previousKey = $thisModKey - 1;
                $previousMod = get_module_from_cmid($sequence[$previousKey]);

                $previous = '<a href="' . $CFG->wwwroot . '/mod/' . $previousMod[1]->modname . '/view.php?id=' . $previousMod[1]->id . '" class="btn btn-default">' . get_string('previous', 'theme_ease') . '</a>';
            } else {
                $previous = '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id . '&section=' . $modSection->section . '" class="btn btn-default">' . get_string('previous', 'theme_ease') . '</a>';
                ;
            }

            $nextKey = $thisModKey + 1;

            if (isset($sequence[$nextKey])) {
                $nextMod = get_module_from_cmid($sequence[$nextKey]);
                $next = '<a href="' . $CFG->wwwroot . '/mod/' . $nextMod[1]->modname . '/view.php?id=' . $nextMod[1]->id . '" class="btn btn-default">' . get_string('next', 'theme_ease') . '</a>';
            } else {
                $next = '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id . '" class="btn btn-default">' . get_string('next', 'theme_ease') . '</a>';
            }

            $html .= '  <div class="pull-right">' . $previous . ' ' . $next . '</div>';
            $html .= '</div>';
        }
        return $html;
    }
}
