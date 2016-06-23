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
 * Output a TSV showing, for each capability, the permissions each role has in the system context (or in any given context).
 *
 * @package    local_roles
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/capability/locallib.php');

// Get URL parameters.
$systemcontext = context_system::instance();
$contextid = optional_param('context', $systemcontext->id, PARAM_INT);

// Check permissions.
list($context, $course, $cm) = get_context_info_array($contextid);
require_login($course, false, $cm);
require_capability('moodle/role:manage', $context);

// Prepare the list of capabilities for this context.
$capabilities = array();
foreach ($context->get_capabilities() as $cap) {
    $capabilities[] = $cap->name;
}

// Prepare the complete list of roles.
$roles = role_fix_names(get_all_roles($context));

header('Content-Disposition: attachment; filename=capabilities.tsv');
header('Content-Type: text/tsv');

// Output the matrix.
capability_matrix($context->id, $capabilities, $roles);

/**
 * Outputs a matrix of roles and capabilities.
 *
 * @param int $contextid The context we are displaying for.
 * @param array $capabilities An array of capabilities to show.
 * @param array $roles An array of roles to show.
 */
function capability_matrix($contextid, array $capabilities, array $roles) {

    $strpermissions = get_permission_strings();

    if ($contextid === context_system::instance()->id) {
        $strpermissions[CAP_INHERIT] = new lang_string('notset', 'role');
    }
	
    // Start the list item.
    $context = context::instance_by_id($contextid);
	echo "\n" . $context->get_context_name() . "\t";

	// Display the role names as headings.
    foreach ($roles as $role) {
		echo $role->localname . "\t";
    }
	echo "\n\n";

    $matrix = array();
	foreach ($capabilities as $capability) {
        $contexts = tool_capability_calculate_role_data($capability, $roles);
		echo get_capability_string($capability) . "\t";
		
        foreach ($roles as $role) {
            if (isset($contexts[$contextid]->rolecapabilities[$role->id])) {
                $permission = $contexts[$contextid]->rolecapabilities[$role->id];
            } else {
                $permission = CAP_INHERIT;
            }
			echo $strpermissions[$permission] . "\t";
			$matrix[$role][$capability] = $permission; // for comparison matrix !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        }

        echo "\n";
    }
	
    // If there are any child contexts, print them recursively.
    if (!empty($contexts[$contextid]->children)) {
        foreach ($contexts[$contextid]->children as $childcontextid) {
            capability_comparison_table($capabilities, $childcontextid, $roles, true);
        }
    }
	
    return;
}

function get_permission_strings() {
    static $strpermissions;
    if (!$strpermissions) {
        $strpermissions = array(
            CAP_INHERIT => new lang_string('inherit', 'role'),
            CAP_ALLOW => new lang_string('allow', 'role'),
            CAP_PREVENT => new lang_string('prevent', 'role'),
            CAP_PROHIBIT => new lang_string('prohibit', 'role')
        );
    }
    return $strpermissions;
}
