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
 * Internal library of functions for module qpractice
 *
 * All the qpractice specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_qpractice
 * @copyright  2013 Jayesh Anandani
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$excludedquestions=null;
$excludedqtypes=null;

/**
 * Create a qpractice attempt.
 * @param mixed $attempt an integer attempt id or an attempt object
 *      (row of the quiz_attempts table).
 * @param object $quiz the quiz object.
 */
function qpractice_session_create($fromform, $context) {
    global $DB, $USER;

    $qpractice = new stdClass();
    $value = $fromform->optiontype;

    if ($value == 1) {
        $qpractice->time = null;
        $qpractice->goalpercentage = null;
        $qpractice->noofquestions = null;

    }

    if ($value == 2) {
        $qpractice->goalpercentage = null;
        $qpractice->noofquestions = null;
        $qpractice->time = $fromform->timelimit;

    }

    if ($value == 3) {
        $qpractice->time = null;
        $qpractice->goalpercentage = $fromform->goal;
        $qpractice->noofquestions = $fromform->noofquestions;

    }

    $quba = question_engine::make_questions_usage_by_activity('mod_qpractice', $context);

    $qpractice->typeofpractice = $value;
    $qpractice->categoryid = $fromform->categories;
    $behaviour = $fromform->behaviour;
    $qpractice->userid = $USER->id;
    $quba->set_preferred_behaviour($behaviour);
    $qpractice->qpracticeid = $fromform->instanceid;
    $qpractice->sessionnumber = 3;
    question_engine::save_questions_usage_by_activity($quba);
    $qpractice->questionusageid = $quba->get_id();
    $sessionid=$DB->insert_record('qpractice_session', $qpractice);

    return $sessionid;
}

/**
 * Delete a qpractice attempt.
 * @param mixed $attempt an integer attempt id or an attempt object
 *      (row of the quiz_attempts table).
 * @param object $quiz the quiz object.
 */
function qpractice_delete_attempt($sessionid) {
    global $DB;

    if (is_numeric($sessionid)) {
        if (!$session = $DB->get_record('qpractice_session', array('id' => $sessionid))) {
            return;
        }
    }

    question_engine::delete_questions_usage_by_activity($session->questionusageid);
    $DB->delete_records('qpractice_session', array('id' => $session->id));

}

function get_available_questions_from_category($categoryid, $subcategories) {

    if ($subcategories) {
         $categoryids = question_categorylist($categoryid);
    } else {
         $categoryids = array($categoryid);
    }
        $excludedqtypes=null;
        $questionids = question_bank::get_finder()->get_questions_from_categories($categoryids, $excludedqtypes);

        return $questionids;
}

function choose_other_question($categoryid, $subcategories, $excludedquestions, $allowshuffle = true) {
    $available = get_available_questions_from_category($categoryid, $subcategories);
    shuffle($available);

    foreach ($available as $questionid) {
        if (in_array($questionid, $excludedquestions)) {
             continue;
        }
        $question = question_bank::load_question($questionid, $allowshuffle);
        return $question;
    }

    return null;
}

function get_options_behaviour($cm) {
    global $DB, $CFG;
    $behaviour=$DB->get_record('qpractice', array('id'=>$cm->instance), 'behaviour');
    $comma=explode(",", $behaviour->behaviour);
    $currentbehaviour='';
    $behaviours = question_engine::get_behaviour_options($currentbehaviour);
    $showbehaviour=array();
    foreach ($comma as $id => $values) {
        foreach ($behaviours as $key => $langstring) {
            if ($values == $key) {
                $showbehaviour[$key] = $langstring;
            }
        }
    }
    return $showbehaviour;
}