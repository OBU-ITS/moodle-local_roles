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
 * changerole - for a given list of users, change role assignments as directed (for all contexts)
 *
 * @package    local_roles
 * @author     Peter Welham
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./change_form.php');

require_login();
$context = context_system::instance();
require_capability('moodle/role:assign', $context);

$PAGE->set_url('/local/roles/changerole.php');
$PAGE->set_context($context);

$message = '';

// Save the array of roles
$allroles = role_fix_names(get_all_roles());
$roles = array();
foreach ($allroles as $ar) {
	$roles[$ar->id] = $ar->localname;
}

$mform_change = new change_form(null, array('roles'=>$roles));    

if ($mform_change->is_cancelled()) {
    redirect(get_login_url());
} 
else if ($form_data = $mform_change->get_data()) {
     
    // Sets values in form_data for 'role_from' (old role), 'role_to' (new role), 'users' and 'usersfile'
    // So now we need to:
    // - build the users list
    // - loop through the users changing their role for all contexts
    
    $role_from = $form_data->role_from;
    $role_to = $form_data->role_to;
    $users = $mform_change->get_file_content('usersfile');
    
    if (is_null($role_from) || is_null($role_to) || ($role_from === $role_to)) {
        $message = get_string('invalidrole', 'local_roles');
   }
    
    if (!$users) {
        $users = $form_data->users;
    }
    
    if (!$message) {
    
        $user_array = split_form_data($users);

        // Here is where the work gets done
        $successes = change_role($role_from, $role_to, $user_array);
        $numberChanged = count($successes);

        $message = "Role changed for $numberSent user" . ($numberChanged == 1 ? "" : "s") . ".";
    }
}

$nav = get_string('change_role_nav', 'local_roles');
$title = get_string('change_role_title', 'local_roles');

$PAGE->navbar->add($nav);

$PAGE->set_title($title);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

if ($message) {
    notice($message, "$CFG->wwwroot/local/roles/changerole.php");    
}
else {
    $mform_change->display();
}

echo $OUTPUT->footer();

/** 
 * Change roles for the given users
 * 
 * @param type $role_from
 * @param type $role_to
 * @param type $users
 * @return array of changed users
 */

function change_role($role_from, $role_to, $user_array) {
    global $DB;
    
    $count = 0;
    
    $successes = array();
/*    
    foreach ($user_array as $user) {
        $recipientUser = new stdClass();
        $recipientUser->email = $recipient;
        $recipientUser->firstname = '';
        $recipientUser->lastname = '';
        $recipientUser->maildisplay = true;
        $recipientUser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
        $recipientUser->id = -99; // Moodle User ID. If it is for someone who is not a Moodle user, use an invalid ID like -99.
        $recipientUser->firstnamephonetic = '';
        $recipientUser->lastnamephonetic = '';
        $recipientUser->middlename = '';
        $recipientUser->alternatename = '';        
        
        if ($count < $max) {
            $ok = email_to_user($recipientUser, $supportuser, $email_subject, $email_text, $email_text_html);
                        
            if ($ok) {
                $successes[$count] = $recipient;
                $count++; 
                
                // and update the database with details of the invitation sent
                insert_invitation($cid, $recipient, $id_approver);        
            }
        }    
    } 
*/    
    return $successes;
}

/** 
 * Split incoming data into an array on newline
 * 
 * @param string $rawcsv
 * @return array 
 */

function split_form_data($rawdata) {
    // Remove Windows \r\n new lines
    $rawdata = str_replace("\r\n", "\n", $rawdata);
    $datarows = array();
    $lines = explode("\n", $rawdata);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $datarows[] = $line;
        }
    }

    return $datarows;
}

