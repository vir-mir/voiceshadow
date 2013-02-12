<?php  // $Id: uploadmp3.php,v 1.2 2012/03/10 22:00:00 Serafim Panov Exp $


    require_once '../../config.php';
    require_once 'lib.php';

    $id                           = optional_param('p1', 0, PARAM_INT);
    $userid                       = optional_param('p2', 0, PARAM_INT);
    $fid                          = optional_param('p3', 0, PARAM_INT);
    $filename                     = optional_param('p4', NULL, PARAM_TEXT);
    $requestid                    = optional_param('requestid', NULL, PARAM_INT);
    $file                         = optional_param('filedata', NULL, PARAM_TEXT);
    
    $file = base64_decode($file);
    
    $student = $DB->get_record("user", array("id" => $userid));
    
    if (!empty($id))
      $context = get_context_instance(CONTEXT_MODULE, $id);
    else
      $context = get_context_instance(CONTEXT_USER, $userid);
    
    $fs = get_file_storage();
    
///Delete old records
    $fs->delete_area_files($context->id, 'mod_voiceshadow', 'private', $fid);
      
    $file_record = new stdClass;
    
    if (!empty($id)) {
      $file_record->component = 'mod_voiceshadow';
      $file_record->filearea  = 'private';
    } else {
      $file_record->component = 'user';
      $file_record->filearea  = 'public';
    }
    
    $file_record->contextid = $context->id;
    $file_record->userid    = $userid;
    $file_record->filepath  = "/";
    $file_record->itemid    = $fid;
    $file_record->license   = $CFG->sitedefaultlicense;
    $file_record->author    = fullname($student);
    $file_record->source    = '';
    $file_record->filename  = $filename.".mp3";
    

    $to = $CFG->dataroot."/temp/".$filename.".mp3";

    file_put_contents($to, $file);
    
    $itemid = $fs->create_file_from_pathname($file_record, $to);
    
    $json = array("id" => $itemid->get_id());
    
    header("Content-type: text/xml");

    $xml_output = "<?xml version=\"1.0\"?>
    <result requestid='{$requestid}'>success<error>audio.mp3</error></result>";

    echo $xml_output;

    unlink($to);

    