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
 * Offers the possibility to add or modify a step instance.
 *
 * @package    tool_lifecycle
 * @copyright  2017 Tobias Reischmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_lifecycle\form;

use tool_lifecycle\entity\trigger_subplugin;
use tool_lifecycle\manager\lib_manager;
use tool_lifecycle\manager\trigger_manager;
use tool_lifecycle\manager\workflow_manager;
use tool_lifecycle\trigger\base;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Provides a form to modify a step instance
 */
class form_trigger_instance extends \moodleform {


    /**
     * @var trigger_subplugin
     */
    public $trigger;

    /**
     * @var string
     */
    public $subpluginname;

    /**
     * @var array/null local settings of the trigger instance
     */
    public $settings;

    /**
     * @var base name of the subplugin to be created
     */
    public $lib;

    /**
     * @var int id of the workflow
     */
    private $workflowid;

    /**
     * Constructor
     * @param \moodle_url $url.
     * @param int $workflowid if of the workflow.
     * @param trigger_subplugin $trigger step entity.
     * @param string $subpluginname name of the trigger subplugin.
     * @param array $settings settings of the step.
     * @throws \moodle_exception if neither step nor subpluginname are set.
     */
    public function __construct($url, $workflowid, $trigger = null, $subpluginname = null, $settings = null) {
        $this->trigger = $trigger;
        $this->workflowid = $workflowid;
        $this->settings = $settings;
        // Subpluginname has priority over trigger, since it could have been overriden by the form.
        if ($subpluginname) {
            $this->subpluginname = $subpluginname;
        } else if ($trigger) {
            $this->subpluginname = $trigger->subpluginname;
        } else {
            $triggertypes = trigger_manager::get_trigger_types();
            $this->subpluginname = array_pop($triggertypes);
        }
        if ($this->subpluginname) {
            $this->lib = lib_manager::get_trigger_lib($this->subpluginname);
        }

        parent::__construct($url);
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id'); // Save the record's id.
        $mform->setType('id', PARAM_TEXT);

        $mform->addElement('hidden', 'workflowid'); // Save the record's id.
        $mform->setType('workflowid', PARAM_INT);

        $mform->addElement('hidden', 'action'); // Save the current action.
        $mform->setType('action', PARAM_TEXT);
        $mform->setDefault('action', ACTION_TRIGGER_INSTANCE_FORM);

        $mform->addElement('header', 'general_settings_header', get_string('general_settings_header', 'tool_lifecycle'));

        $elementname = 'instancename';
        $mform->addElement('text', $elementname, get_string('trigger_instancename', 'tool_lifecycle'));
        $mform->addHelpButton($elementname, 'trigger_instancename', 'tool_lifecycle');
        $mform->setType($elementname, PARAM_TEXT);

        // If workflow is active, then all trigger types have to be used to also show the preset triggers.
        if ($this->workflowid && workflow_manager::is_active($this->workflowid)) {
            $triggers = trigger_manager::get_trigger_types();
        } else {
            $triggers = trigger_manager::get_chooseable_trigger_types();
        }

        $elementname = 'subpluginname';
        $mform->addElement('select', $elementname,
            get_string('trigger_subpluginname', 'tool_lifecycle'),
            $triggers);
        $mform->addHelpButton($elementname, 'trigger_subpluginname', 'tool_lifecycle');
        $mform->setType($elementname, PARAM_TEXT);

        // Insert the subplugin specific settings.
        if (isset($this->lib) && !empty($this->lib->instance_settings())) {
            $mform->addElement('header', 'trigger_settings_header', get_string('trigger_settings_header', 'tool_lifecycle'));
            $this->lib->extend_add_instance_form_definition($mform);
        }

        $mform->addElement('submit', 'reload', 'reload');
        $mform->registerNoSubmitButton('reload');

        // For active workflows, we do not want the form to be editable.
        if ($this->workflowid && workflow_manager::is_active($this->workflowid)) {
            $this->add_cancel_button();
        } else {
            $this->add_action_buttons();
        }
    }

    /**
     * In case of read only mode only the cancel button is rendered.
     */
    private function add_cancel_button() {
        $mform =& $this->_form;

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Defines forms elements
     */
    public function definition_after_data() {
        $mform = $this->_form;

        $mform->setDefault('workflowid', $this->workflowid);

        if ($this->trigger) {
            $mform->setDefault('id', $this->trigger->id);
            $mform->setDefault('instancename', $this->trigger->instancename);
        }
        $mform->setDefault('subpluginname', $this->subpluginname);

        // Setting the default values for the local trigger settings.
        if ($this->settings) {
            foreach ($this->settings as $key => $value) {
                $mform->setDefault($key, $value);
            }
        }

        // Insert the subplugin specific settings.
        if (isset($this->lib) && !empty($this->lib->instance_settings())) {
            $this->lib->extend_add_instance_form_definition_after_data($mform, $this->settings);
        }

        // For active workflows, we do not want the form to be editable.
        if ($this->workflowid && workflow_manager::is_active($this->workflowid)) {
            // The group buttonar is the array of submit buttons. For inactive workflows this is only a cancel button.
            $mform->hardFreezeAllVisibleExcept(array('buttonar'));
        }
    }

    public function validation($data, $files) {
        $error = parent::validation($data, $files);
        if (empty($data['instancename'])) {
            $error['instancename'] = get_string('required');
        }

        $requiredsettings = $this->lib->instance_settings();
        foreach ($requiredsettings as $setting) {
            if (!array_key_exists($setting->name, $data) || empty($data[$setting->name])) {
                $error[$setting->name] = get_string('required');
            }
        }
        return $error;
    }

}
