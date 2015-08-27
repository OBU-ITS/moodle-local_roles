<?php

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
 * changerole - display the role change form
 *
 * @package    local_roles
 * @author     Peter Welham
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class change_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
        $data->roles = $this->_customdata['roles'];
		
        $mform->addElement('header', '', 'Change Role');
        $mform->addElement('html', '<br>' . get_string('formheader', 'local_roles') . '<p>');        

		$mform->addElement('select', 'role_from', get_string('role_from', 'local_roles'), $data->roles, null);
		$mform->addElement('select', 'role_to', get_string('role_to', 'local_roles'), $data->roles, null);
        $mform->addElement('textarea', 'users', get_string('users_label_1', 'local_roles'), 'rows="10" cols="80"');
        $mform->addElement('filepicker', 'usersfile', get_string('users_label_2', 'local_roles'));

        $this->add_action_buttons(false, get_string('change_role_button', 'local_roles'));
    }

}