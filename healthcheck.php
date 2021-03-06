<?php
// This file is part of Stack - http://stack.bham.ac.uk/
//
// Stack is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stack is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stack.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This script helps  that the stack is installed correctly, and that
 * all the parts are working properly, including the conection to the CAS,
 * graph plotting, and equation rendering.
 *
 * @copyright  2012 University of Birmingham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/stack/utils.class.php');
require_once(dirname(__FILE__) . '/stack/options.class.php');
require_once(dirname(__FILE__) . '/stack/cas/castext.class.php');
require_once(dirname(__FILE__) . '/stack/cas/casstring.class.php');
require_once(dirname(__FILE__) . '/stack/cas/cassession.class.php');
require_once(dirname(__FILE__) . '/stack/cas/connector.dbcache.class.php');
require_once(dirname(__FILE__) . '/stack/cas/installhelper.class.php');


// Check permissions.
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Set up page.
$PAGE->set_context($context);
$PAGE->set_url('/question/type/stack/healthcheck.php');
$title = stack_string('healthcheck');
$PAGE->set_title($title);

// Clear the cache if requested.
if (data_submitted() && optional_param('clearcache', false, PARAM_BOOL)) {
    require_sesskey();
    stack_cas_connection_db_cache::clear_cache($DB);
    redirect($PAGE->url);
}

$config = stack_utils::get_config();

// Start output.
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// LaTeX.
echo $OUTPUT->heading(stack_string('healthchecklatex'), 3);
echo html_writer::tag('p', stack_string('healthcheckmathsdisplaymethod',
        stack_maths::configured_output_name()));
echo html_writer::tag('p', stack_string('healthchecklatexintro'));

echo html_writer::tag('dt', stack_string('texdisplaystyle'));
echo html_writer::tag('dd', stack_string('healthchecksampledisplaytex'));

echo html_writer::tag('dt', stack_string('texinlinestyle'));
echo html_writer::tag('dd', stack_string('healthchecksampleinlinetex'));

if ($config->mathsdisplay === 'mathjax') {
    $settingsurl = new moodle_url('/admin/settings.php', array('section' => 'additionalhtml'));
    echo html_writer::tag('p', stack_string('healthchecklatexmathjax',
            $settingsurl->out()));
    echo html_writer::tag('textarea', s(stack_maths_output_mathjax::get_mathjax_code()),
            array('readonly' => 'readonly', 'wrap' => 'virtual', 'rows'=>'12', 'cols'=>'100'));
} else {
    $settingsurl = new moodle_url('/admin/filters.php');
    echo html_writer::tag('p', stack_string('healthcheckfilters',
            array('filter' => stack_maths::configured_output_name(), 'url' => $settingsurl->out())));
}

// Maxima config.
echo $OUTPUT->heading(stack_string('healthcheckconfig'), 3);

// Check for location of Maxima.
$maxima_location = stack_cas_configuration::confirm_maxima_win_location();
if ('' != $maxima_location) {
    echo html_writer::tag('p', stack_string('healthcheckconfigintro1').' '.html_writer::tag('tt', $maxima_location));
}

// Try to connect to create maxima local.
echo html_writer::tag('p', stack_string('healthcheckconfigintro2'));
stack_cas_configuration::create_maximalocal();

echo html_writer::tag('textarea', stack_cas_configuration::generate_maximalocal_contents(),
        array('readonly' => 'readonly', 'wrap' => 'virtual', 'rows'=>'32', 'cols'=>'100'));

// Maxima config.
if (stack_cas_configuration::maxima_bat_is_missing()) {
    echo $OUTPUT->heading(stack_string('healthcheckmaximabat'), 3);
    echo html_writer::tag('p', stack_string('healthcheckmaximabatinfo', $CFG->dataroot));
}

// Test Maxima connection.
// Intentionally use get_string for the sample CAS and plots, so we don't render
// the maths too soon.
output_cas_text(stack_string('healthcheckconnect'),
        stack_string('healthcheckconnectintro'), get_string('healthchecksamplecas', 'qtype_stack'));

// Test the version of the STACK libraries that Maxima is using.
// When Maxima is being run pre-compiled (maxima-optimise) or on a server,
// it is possible for the version of the Maxima libraries to get out of synch
// with the qtype_stack code.

echo $OUTPUT->heading(stack_string('healthchecksstackmaximaversion'), 3);
list($message, $details) = stack_connection_helper::stackmaxima_version_healthcheck();
echo html_writer::tag('p', stack_string($message, $details));

// Test plots.
output_cas_text(stack_string('healthcheckplots'),
        stack_string('healthcheckplotsintro'), get_string('healthchecksampleplots', 'qtype_stack'));

// State of the cache.
echo $OUTPUT->heading(stack_string('settingcasresultscache'), 3);
echo html_writer::tag('p', stack_string('healthcheckcache_' . $config->casresultscache));
if ('db' == $config->casresultscache) {
    echo html_writer::tag('p', stack_string('healthcheckcachestatus',
            stack_cas_connection_db_cache::entries_count($DB)));
    echo $OUTPUT->single_button(
            new moodle_url($PAGE->url, array('clearcache' => 1, 'sesskey' => sesskey())),
            stack_string('clearthecache'));
}

echo $OUTPUT->footer();

function output_cas_text($title, $intro, $castext) {
    global $OUTPUT;

    echo $OUTPUT->heading($title, 3);
    echo html_writer::tag('p', $intro);
    echo html_writer::tag('pre', s($castext));

    $ct = new stack_cas_text($castext, null, 0);

    echo html_writer::tag('p', stack_ouput_castext($ct->get_display_castext()));
    echo output_debug(stack_string('errors'), $ct->get_errors());
    echo output_debug(stack_string('debuginfo'), $ct->get_debuginfo());
}


function output_debug($title, $message) {
    global $OUTPUT;

    if (!$message) {
        return;
    }

    return $OUTPUT->box($OUTPUT->heading($title) . $message);
}
