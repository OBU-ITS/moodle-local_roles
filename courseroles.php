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
 * courseroles - for all users (or just those with the given role(s)) output all explicitly assigned category/course roles (bar any given exclusion(s))
 *
 * @package    local_roles
 * @author     Peter Welham
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once(__DIR__ . '/../../config.php');

$usercontext = context_user::instance($USER->id);

// Check login and permissions
require_login();
$canview = has_any_capability(array('moodle/role:assign', 'moodle/role:safeoverride', 'moodle/role:override', 'moodle/role:manage'), $usercontext);
if (!$canview) {
    print_error('nopermissions', 'error', '', get_string('checkpermissions', 'local_roles'));
}

// We'll provide a downloadable tab-separated-values file (can't use csv because text may contain commas) 
header('Content-Disposition: attachment; filename=courseroles.tsv');
header('Content-Type: text/tab-separated-values');

// Output the column headings
print('User ID' . "\t" . 'Username' . "\t" . 'First Name' . "\t" . 'Last Name' . "\t");
print('Context ID' . "\t" . 'Context Instance' . "\t" . 'Context Level' . "\t" . 'Context Name' . "\t");
print('RA ID' . "\t" . 'RA Component' . "\t" . 'Role ID' . "\t" . 'Role Name' . "\n");

$allroles = role_fix_names(get_all_roles());

// Compose a list of the role id's to exclude from the output (if any)
$exclude = '';
if (isset($_REQUEST['exclude'])) {
	$exclude_array = explode(',', $_REQUEST['exclude']);
	foreach ($exclude_array as $e) {
		$e = trim($e);
		if (is_numeric($e)) {
			// We have been given the role id and not the name
			add_role($exclude, $e);
		} else {
			// Find the role id from the given name
			foreach ($allroles as $ar) {
				if ($e == $ar->localname) {
					add_role($exclude, $ar->id);
					break;
				}
			}
		}
	}
}

if (isset($_REQUEST['roles'])) {
	if (isset($_REQUEST['exclusive']) && ($_REQUEST['roles'] == 'no')) {
		$exclusive = false;
	} else {
		$exclusive = true; // The default
	}
	// Only output users with the the given role(s)
	$role_array = explode(',', $_REQUEST['roles']);
	$roles = '';
	foreach ($role_array as $r) {
		$r = trim($r);
		if (is_numeric($r)) {
			// We have been given the role id and not the name
			add_role($roles, $r);
		} else {
			// Find the role id from the given name
			foreach ($allroles as $ar) {
				if ($r == $ar->localname) {
					add_role($roles, $ar->id);
					break;
				}
			}
		}
	}
	$sql = "SELECT ra.id, ra.userid
		FROM {role_assignments} ra
		WHERE ra.roleid IN (?)
		ORDER BY ra.userid ASC";
	$roleassignments = $DB->get_records_sql($sql, array($roles));

	// Compose an array of id's for users with at least one of the given roles in at least one context
	$userid = '';
	$userids = array();
	foreach ($roleassignments as $ra) {
		if ($ra->userid != $userid) {
			$userid = $ra->userid;
			$userids[] = $userid;
		}
	}
	
	// Output all the roles for the selected users (bar exclusions)
	foreach ($userids as $userid) {
		$sql = "SELECT user.id, user.username, user.firstname, user.lastname
		FROM {user} user
		WHERE user.id = ? AND user.deleted = '0'";
		$user = $DB->get_record_sql($sql, array($userid));
		output($user, $allroles, $exclude);
	}
} else {
	// Get all current users (in batches of 5000 to avoid any memory problems)
	$count = 0;
	do {
		$sql = "SELECT user.id, user.username, user.firstname, user.lastname
			FROM {user} user
			WHERE user.deleted = '0'
			LIMIT " . $count . ", " . ($count + 5000);
		$users = $DB->get_records_sql($sql, array());
		if (!$users) {
			break;
		}

		foreach ($users as $user) {
			output($user, $allroles, $exclude);
		}

		$count += 5000;
	
	} while(true);
}

function add_role(&$list, $role) {
	if ($list != '') {
		$list = $list . ',';
	}
	$list = $list . $role;
}

function output($user, $allroles, $exclude) {
	global $DB;
	
	// Get the role assignments for this user (barring exclusions)
	$sql = "SELECT ra.id, ra.userid, ra.contextid, ra.roleid, ra.component, ra.itemid, c.path
		FROM {role_assignments} ra
		JOIN {context} c ON ra.contextid = c.id
		JOIN {role} r ON ra.roleid = r.id
		WHERE ra.userid = ? AND ra.roleid NOT IN (?)
		ORDER BY contextlevel DESC, contextid ASC, r.sortorder ASC";
	$roleassignments = $DB->get_records_sql($sql, array($user->id, $exclude));
	if (!$roleassignments) {
		return;
	}
	
	print("\n"); // Spacer

	foreach ($roleassignments as $ra) {
		$context = context::instance_by_id($ra->contextid);
		if (($context->contextlevel >= CONTEXT_COURSECAT) && ($context->contextlevel <= CONTEXT_MODULE)) {
			$role = $allroles[$ra->roleid];
			print($user->id . "\t" . $user->username . "\t" . $user->firstname . "\t" . $user->lastname . "\t");
			print($ra->contextid . "\t" . $context->instanceid . "\t" . $context->get_level_name() . "\t" . $context->get_context_name(false, true) . "\t");
			print($ra->id . "\t" . $ra->component . "\t" . $ra->roleid . "\t" . $role->localname . "\n");
		}
	}
}
