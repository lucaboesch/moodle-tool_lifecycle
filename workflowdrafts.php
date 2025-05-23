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
 * Displays the tables inactive workflow definitions (drafts).
 *
 * @package tool_lifecycle
 * @copyright  2025 Thomas Niedermaier University Münster
 * @copyright  2022 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_lifecycle\local\table\workflow_definition_table;
use tool_lifecycle\tabs;
use tool_lifecycle\urls;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

$syscontext = context_system::instance();
$PAGE->set_url(new \moodle_url(urls::WORKFLOW_DRAFTS));
$PAGE->set_context($syscontext);

$action = optional_param('action', null, PARAM_TEXT);
if ($action) {
    $wfid = required_param('workflowid', PARAM_INT);
    \tool_lifecycle\local\manager\workflow_manager::handle_action($action, $wfid);
    redirect($PAGE->url);
}

$PAGE->set_pagetype('admin-setting-' . 'tool_lifecycle');
$PAGE->set_pagelayout('admin');

$renderer = $PAGE->get_renderer('tool_lifecycle');

$heading = get_string('pluginname', 'tool_lifecycle')." / ".get_string('workflow_drafts_header', 'tool_lifecycle');
echo $renderer->header($heading);
$tabrow = tabs::get_tabrow();
$id = optional_param('id', 'settings', PARAM_TEXT);
$renderer->tabs($tabrow, $id);


echo html_writer::link(new \moodle_url(urls::EDIT_WORKFLOW),
    get_string('add_workflow', 'tool_lifecycle'), ['class' => 'btn btn-primary mx-1']);

echo html_writer::link(new \moodle_url(urls::UPLOAD_WORKFLOW),
    get_string('upload_workflow', 'tool_lifecycle'), ['class' => 'btn btn-secondary mx-1']);

echo html_writer::link(new \moodle_url(urls::CREATE_FROM_EXISTING),
    get_string('create_workflow_from_existing', 'tool_lifecycle'), ['class' => 'btn btn-secondary mx-1']);

$table = new workflow_definition_table('tool_lifecycle_workflow_definitions');
echo $OUTPUT->box_start("lifecycle-enable-overflow lifecycle-table");
$table->out(10, false);
echo $OUTPUT->box_end();

echo $renderer->footer();
