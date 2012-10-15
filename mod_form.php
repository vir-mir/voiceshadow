<?php //$Id: mod_form.php,v 1.2 2012/03/10 22:00:00 Serafim Panov Exp $

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');  
}

require_once($CFG->dirroot . '/course/moodleform_mod.php');

$PAGE->requires->js('/mod/voiceshadow/js/jquery.min.js', true);


class mod_voiceshadow_mod_form extends moodleform_mod {
    function definition() {
        global $COURSE, $CFG, $form, $USER;
        $mform    =& $this->_form;

        $fmstime = time();

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->add_intro_editor(true, get_string('intro', 'voiceshadow'));

        $mform->addElement('textarea', 'embedvideo', get_string("embedvideo", "voiceshadow"), 'wrap="virtual" rows="10" cols="80"');
        //$mform->addElement('editor', 'embedvideo', get_string('embedvideo', 'voiceshadow'));
        //$mform->setType('embedvideo', PARAM_RAW);
        
        $mform->addElement('date_time_selector', 'timeavailable', get_string('availabledate', 'assignment'), array('optional'=>true));
        $mform->setDefault('timeavailable', time());
        $mform->addElement('date_time_selector', 'timedue', get_string('duedate', 'assignment'), array('optional'=>true));
        $mform->setDefault('timedue', time()+7*24*3600);

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $mform->addElement('select', 'preventlate', get_string('preventlate', 'assignment'), $ynoptions);
        $mform->setDefault('preventlate', 0);
        
        
        $mform->addElement('select', 'grade', get_string('grade'), array('1'=>'1', '2'=>'2', '3'=>'3', '4'=>'4', '5'=>'5'));
        $mform->setDefault('grade', 5);
        
        $mform->addElement('select', 'grademethod', get_string('grademethod', "voiceshadow"), array('default'=>get_string('default', "voiceshadow"), 'rubrics'=>get_string('rubrics', "voiceshadow")));
        $mform->setDefault('grademethod', 'default');
        
        $filepickeroptions = array();
        $filepickeroptions['maxbytes']  = get_max_upload_file_size($CFG->maxbytes);
        $mform->addElement('header', 'mp3upload', get_string('mp3upload', 'voiceshadow')); 
        $mform->addElement('filepicker', 'submitfile', get_string('uploadmp3', 'voiceshadow'), null, $filepickeroptions);
        
        $time = time();
        $filename = str_replace(" ", "_", $USER->username)."_".date("Ymd_Hi", $time);
        
        $mediadata = "";
        $mediadata .= html_writer::start_tag("applet", array("id" => "nanogong", "archive" => new moodle_url("/mod/voiceshadow/nanogong.jar"), "code" => "gong.NanoGong", "width" => "180", "height" => "40"));
        $mediadata .= html_writer::empty_tag("param", array("name" => "Color", "value" => "#ffffff"));
        $mediadata .= html_writer::empty_tag("param", array("name" => "AudioFormat", "value" => "ImaADPCM"));
        $mediadata .= html_writer::end_tag('applet');
        
        $ngurl = str_replace("&amp;", "&", new moodle_url("/mod/voiceshadow/nanogong.php", array("userid"=>$USER->id, "id"=>$id, "filename"=>$filename)));
        
        $mediadata .= html_writer::script('
$(document).ready(function() {
  $(\'#id_submitbutton\').click(function() {$(\'.loaderlayer\').show();});
  $(\'#mform1\').live("submit", function(){$(\'.loaderlayer\').show();var applet = document.getElementById("nanogong");var ret = applet.sendGongRequest("PostToForm", "'.$ngurl.'&fid="+$(\'#id_submitfile\').attr(\'value\'), "voicefile","", "temp");});
});');
        
        $mediadata .= html_writer::start_tag("div", array("class" => "loaderlayer", "style" => "display:none;background-color:#FF0000;position:fixed;right:0px;top:0px"));
        $mediadata .= html_writer::empty_tag("img", array("src" => new moodle_url('/mod/voiceshadow/img/ajax-record-save.gif'), "alt" => get_string("recordsaved", "voiceshadow")));
        $mediadata .= html_writer::end_tag('div');
        
        //$mform->addElement('header', 'Recording', get_string('recordvoice', 'voiceshadow')); 
        $mform->addelEment('hidden', 'filename', $filename);
        $mform->addelEment('hidden', 'iphonelink', '');
        $mform->addElement('static', 'description', '', $mediadata);
        
//-------------------------------------------------------------------------------
        $mform->addElement('header', 'typedesc', get_string("typeupload", 'voiceshadow'));
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $mform->addElement('select', 'maxbytes', get_string('maximumsize', 'assignment'), $choices);
        $mform->setDefault('maxbytes', $CFG->assignment_maxbytes);

        $mform->addElement('select', 'resubmit', get_string('allowdeleting', 'assignment'), $ynoptions);
        $mform->addHelpButton('resubmit', 'allowdeleting', 'assignment');
        $mform->setDefault('resubmit', 0);
        $mform->setDefault('maxbytes', 10485760);
//-------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        $this->add_action_buttons();
    }
}
