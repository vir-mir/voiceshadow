<?php  // $Id: view.php,v 1.2 2012/03/10 22:00:00 Serafim Panov Exp $


require_once '../../config.php';
require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once 'lib.php';
require_once ($CFG->libdir.'/gradelib.php');


$id                     = optional_param('id', 0, PARAM_INT); 
$ids                    = optional_param('ids', 0, PARAM_INT); 
$a                      = optional_param('a', 'list', PARAM_TEXT);  
$summary                = optional_param_array('summary', NULL, PARAM_TEXT);  
$filename               = optional_param('filename', NULL, PARAM_TEXT);  
$fileid                 = optional_param('fileid', 0, PARAM_INT);
$submitfile             = optional_param('submitfile', 0, PARAM_INT); 
$commentid              = optional_param('commentid', 0, PARAM_INT); 
$selectaudiomodel       = optional_param('selectaudiomodel', 0, PARAM_INT); 
$act                    = optional_param('act', NULL, PARAM_CLEAN); 
$delfilename            = optional_param('delfilename', NULL, PARAM_TEXT); 
    
    
if (is_array($summary)) $summary = $summary['text'];
    
if ($id) {
    if (! $cm = get_coursemodule_from_id('voiceshadow', $id)) {
        error('Course Module ID was incorrect');
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        error('Course is misconfigured');
    }

    if (! $voiceshadow = $DB->get_record('voiceshadow', array('id' => $cm->instance))) {
        error('Course module is incorrect');
    }

} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

add_to_log($course->id, "voiceshadow", "view", "view.php?id=$cm->id", "$voiceshadow->id");


if (!empty($delfilename)) {
  $DB->delete_records("voiceshadow_files", array("filename"=>$delfilename));
}


//if (voiceshadow_is_ios() && is_dir($CFG->dirroot.'/theme/mymobile')) {} else
  $PAGE->requires->js('/mod/voiceshadow/js/jquery.min.js', true);

//$PAGE->requires->js_function_call('M.util.load_flowplayer'); 
//$PAGE->requires->js('/mod/voiceshadow/js/ajax.js', true);

$PAGE->requires->js('/mod/voiceshadow/js/flowplayer.min.js', true);
$PAGE->requires->js('/mod/voiceshadow/js/swfobject.js', true);


if ($a == 'add' && $act == 'newinstance') {
    $data                = new stdClass;
    $data->instance      = $id;
    $data->userid        = $USER->id;
    $data->summary       = $summary;
    $data->filename      = $filename;
    $data->var           = $selectaudiomodel;
    $data->time          = time();
        
    
    if(!empty($submitfile)) {
      if ($file = voiceshadow_getfile($submitfile)){
        if (mimeinfo('type', $file->filename) == 'audio/wav') {
          $data->itemoldid = $file->id;
        
          $add         = new stdClass;
          $add->itemid = $file->id;
          $add->type   = mimeinfo('type', $file->filename);
          $add->status = 'open';
          $add->name   = md5($CFG->wwwroot.'_'.time());
          $add->time   = time();
          
          $DB->insert_record("voiceshadow_process", $add);
        } else if (mimeinfo('type', $file->filename) == 'audio/mp3') {
          $data->itemid = $file->id;
        } else {
          echo "Incorrect Audio format ".mimeinfo('type', $file->filename);
          die();
        }
      }
    }
    

    if (!empty($fileid)) {
      $data->id = $fileid;
      $ids = $DB->update_record("voiceshadow_files", $data);
    } else
      $ids = $DB->insert_record("voiceshadow_files", $data);
      
    $DB->set_field("voiceshadow_files", "var", $selectaudiomodel, array("id"=>$ids));
      
    redirect("view.php?id={$id}", get_string('postsubmited', 'voiceshadow'));
}

if ($a == 'add' && $act == 'addcomment' && isset($summary)) {
    $data                = new object;
    $data->instance      = $id;
    $data->userid        = $USER->id;
    $data->summary       = $summary;
    $data->filename      = $filename;
    $data->fileid        = $fileid;
    $data->time          = time();
    

    if(!empty($submitfile)) {
      if ($file = voiceshadow_getfile($submitfile)){
        if (mimeinfo('type', $file->filename) == 'audio/wav') {
          $data->itemoldid = $file->id;
        
          $add         = new stdClass;
          $add->itemid = $file->id;
          $add->type   = mimeinfo('type', $file->filename);
          $add->status = 'open';
          $add->name   = md5($CFG->wwwroot.'_'.time());
          
          $DB->insert_record("voiceshadow_process", $add);
        } else if (mimeinfo('type', $file->filename) == 'audio/mp3')
          $data->itemid = $file->id;
      }
    }
    
    
    if (!empty($commentid)) {
      $data->id          = $commentid;
      $DB->update_record("voiceshadow_comments", $data);
    } else
      $DB->insert_record("voiceshadow_comments", $data);
    
      
    redirect("view.php?id={$id}", get_string('commentsubmited', 'voiceshadow'));
}

    
if ($act == "deleteentry" && !empty($fileid)) {
    if (has_capability('mod/voiceshadow:teacher', $context)) 
      $DB->delete_records("voiceshadow_files", array("id" => $fileid));
    else
      $DB->delete_records("voiceshadow_files", array("id" => $fileid, "userid" => $USER->id));
}

if ($act == "deleteentry" && !empty($filename)) {
    $filename = end(explode("/", $filename));
    list($filename) = explode(".", $filename);
    $DB->delete_records("voiceshadow_files", array("filename" => $filename, "userid" => $USER->id));
}

if ($act == "deletecomment" && !empty($fileid)) {
    if (has_capability('mod/voiceshadow:teacher', $context)) 
      $DB->delete_records("voiceshadow_comments", array("id" => $fileid));
    else
      $DB->delete_records("voiceshadow_comments", array("id" => $fileid, "userid" => $USER->id));
}



/// Print the page header
$strvoiceshadows = get_string('modulenameplural', 'voiceshadow');
$strvoiceshadow  = get_string('modulename', 'voiceshadow');

$PAGE->set_url('/mod/voiceshadow/view.php', array('id' => $id));
    
$title = $course->shortname . ': ' . format_string($voiceshadow->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

/// Print the main part of the page

require_once ('tabs.php');

if ($a == "list") {
    voiceshadow_view_dates();

    $table = new html_table();
    $table->width = "100%";
    
    if (!voiceshadow_is_ios()) {
      $table->head  = array(get_string("cell1::student", "voiceshadow"), get_string("cell2::", "voiceshadow"), get_string("cell3::peer", "voiceshadow"), get_string("cell4::teacher", "voiceshadow"));
      $table->align = array ("left", "center", "center", "center");
    }
    
    
        
    $lists = $DB->get_records ("voiceshadow_files", array("instance" => $id), 'time DESC');
            
    foreach ($lists as $list) {
        $name = "var".$list->var."text";
        
        $userdata  = $DB->get_record("user", array("id" => $list->userid));
        $picture   = $OUTPUT->user_picture($userdata, array('popup' => true));
                
        $own = $DB->get_record("voiceshadow_ratings", array("fileid" => $list->id, "userid" => $list->userid));
            
        if (@empty($own->ratingrhythm)) @$own->ratingrhythm = get_string('norateyet', 'voiceshadow');
        if (empty($own->ratingclear))  $own->ratingclear = get_string('norateyet', 'voiceshadow');
        if (empty($own->ratingintonation)) $own->ratingintonation = get_string('norateyet', 'voiceshadow');
        if (empty($own->ratingspeed)) $own->ratingspeed = get_string('norateyet', 'voiceshadow');
        if (empty($own->ratingreproduction)) $own->ratingreproduction = get_string('norateyet', 'voiceshadow');
            
        //1-cell
        $o = "";
        $o .= html_writer::start_tag('div', array("style" => "text-align:left;margin:10px 0;"));
        $o .= html_writer::tag('span', $picture);
        $o .= html_writer::start_tag('span', array("style" => "margin: 8px;position: absolute;"));
        $o .= html_writer::link(new moodle_url('/user/view.php', array("id" => $userdata->id, "course" => $cm->course)), fullname($userdata));
        $o .= html_writer::end_tag('span');
        $o .= html_writer::end_tag('div');
        
        $o .= html_writer::tag('div', $list->summary, array('style'=>'margin:10px 0;'));
        
        $o .= html_writer::tag('div', voiceshadow_player($list->id));
        
        if (!empty($voiceshadow->{$name}))
          $o .= html_writer::tag('div', "(".$voiceshadow->{$name}.")");
        
        $o .= html_writer::tag('div', html_writer::tag('small', date(get_string("timeformat1", "voiceshadow"), $list->time)), array("style" => "float:left;"));
        
        if ($list->userid == $USER->id || has_capability('mod/voiceshadow:teacher', $context)) {
          if ($list->userid == $USER->id)
            $editlink   = html_writer::link(new moodle_url('/mod/voiceshadow/view.php', array("id" => $id, "a" => "add", "fileid" => $list->id)), get_string("editlink", "voiceshadow"))." ";
          else
            $editlink   = "";
            
          if (has_capability('mod/voiceshadow:teacher', $context) || ($voiceshadow->resubmit == 1 && $list->userid == $USER->id)) 
            $deletelink = html_writer::link(new moodle_url('/mod/voiceshadow/view.php', array("id" => $id, "act" => "deleteentry", "fileid" => $list->id)), get_string("delete", "voiceshadow"), array("onclick"=>"return confirm('".get_string("confim", "voiceshadow")."')"));
          else
            $deletelink = "";
           
          $o .= html_writer::tag('div', html_writer::tag('small', $editlink.$deletelink, array("style" => "margin: 2px 0 0 10px;")));
        }
        
        $cell1 = new html_table_cell($o);
        
        //2-cell
        $table2 = new html_table();
        $table2->width = "100%";
        
        if (voiceshadow_is_ios()) {
          $table2->data[] = new html_table_row(array ( new html_table_cell(get_string("table2::cell1::pronunciation", "voiceshadow")), new html_table_cell(voiceshadow_set_rait($list->id, 1))));
          $table2->data[] = new html_table_row(array ( new html_table_cell(get_string("table2::cell2::fluency", "voiceshadow")), new html_table_cell(voiceshadow_set_rait($list->id, 2))));
          $table2->data[] = new html_table_row(array ( new html_table_cell(get_string("table2::cell3::content", "voiceshadow")), new html_table_cell(voiceshadow_set_rait($list->id, 3))));
          $table2->data[] = new html_table_row(array ( new html_table_cell(get_string("table2::cell4::organization", "voiceshadow", "voiceshadow")), new html_table_cell(voiceshadow_set_rait($list->id, 4))));
          $table2->data[] = new html_table_row(array ( new html_table_cell(get_string("table2::cell5::eye", "voiceshadow")), new html_table_cell(voiceshadow_set_rait($list->id, 5))));
        } else {
          $table2->head  = array(get_string("table2::cell1::pronunciation", "voiceshadow"), get_string("table2::cell2::fluency", "voiceshadow"), get_string("table2::cell3::content", "voiceshadow"), get_string("table2::cell4::organization", "voiceshadow"), get_string("table2::cell5::eye", "voiceshadow"));
          //$table2->align = array ("center", "center", "center", "center", "center");
          $table2->align = array ("center".get_string("table2::style", "voiceshadow"), "center".get_string("table2::style", "voiceshadow"), "center".get_string("table2::style", "voiceshadow"), "center".get_string("table2::style", "voiceshadow"), "center".get_string("table2::style", "voiceshadow"));
          
          $table2->data[] = array (voiceshadow_set_rait($list->id, 1),
                                   voiceshadow_set_rait($list->id, 2),
                                   voiceshadow_set_rait($list->id, 3),
                                   voiceshadow_set_rait($list->id, 4),
                                   voiceshadow_set_rait($list->id, 5));
        }
        
        //----Comment Box-----/
        //if ($list->userid == $USER->id){
        $chtml = "";
        if($comments = $DB->get_records("voiceshadow_comments", array("fileid" => $list->id))){
          foreach($comments as $comment){
            $chtml .= html_writer::start_tag('div', array("style"=>"border:1px solid #333;margin:5px;text-align:left;padding:5px;"));
            
            $chtml .= html_writer::tag('div', $comment->summary, array('style'=>'margin:10px 0;'));
            
            //if (!empty($comment->itemid)) {
            $chtml .= html_writer::tag('div', voiceshadow_player($comment->id, "voiceshadow_comments"));
            //}
            
            $chtml .= html_writer::tag('div', html_writer::tag('small', date(get_string("timeformat1", "voiceshadow"), $comment->time)), array("style" => "float:left;"));
            
            $student = $DB->get_record("user", array("id" => $comment->userid));
            $studentlink = html_writer::link(new moodle_url('/user/view.php', array("id" => $student->id, "course" => $cm->course)), fullname($student));
            
            //if ($comment->userid == $USER->id || has_capability('mod/voiceshadow:teacher', $context)) {
              if (has_capability('mod/voiceshadow:teacher', $context) || ($voiceshadow->resubmit == 1 && $comment->userid == $USER->id)) {
                $deletelink = html_writer::link(new moodle_url('/mod/voiceshadow/view.php', array("id" => $id, "act" => "deletecomment", "fileid" => $comment->id)), get_string("delete", "voiceshadow"), array("onclick"=>"return confirm('".get_string("confim", "voiceshadow")."')"));
              } else {
                $deletelink = "";
              }
              
              if (has_capability('mod/voiceshadow:teacher', $context) && $comment->userid == $USER->id) {
                $editlink = html_writer::link(new moodle_url('/mod/voiceshadow/view.php', array("id" => $id, "a" => "add", "act" => "addcomment", "fileid" => $list->id, "commentid" => $comment->id)), get_string("editlink", "voiceshadow"));
              } else {
                $editlink = "";
              }
           // }
            
            $chtml .= html_writer::tag('div', html_writer::tag('small', $studentlink . " " . $editlink . " " . $deletelink, array("style" => "margin: 2px 0 0 10px;")));
            
            $chtml .= html_writer::tag('div', NULL, array("style" => "clear:both"));
            
            $chtml .= html_writer::end_tag('div');
          }
        }
        
        if (has_capability('mod/voiceshadow:teacher', $context)) {
          $addcommentlink = html_writer::tag('div', html_writer::link(new moodle_url('/mod/voiceshadow/view.php', array("id" => $id, "a" => "add", "act" => "addcomment", "fileid" => $list->id)), get_string("addcomment", "voiceshadow")));
        } else {
          $addcommentlink = "";
        }
          
          /*
        } else
          $addcomment = "";*/
        //--------------------/
        
        if (voiceshadow_is_ios()) {
          //if ($list->userid != $USER->id){
          //  unset($table2->data);
          //}
          
          $table2->data[] = new html_table_row(array ( new html_table_cell(get_string("cell3::peer", "voiceshadow")), new html_table_cell(voiceshadow_set_rait($list->id, 6))));
          $table2->data[] = new html_table_row(array ( new html_table_cell(get_string("cell4::teacher", "voiceshadow")), new html_table_cell(voiceshadow_set_rait($list->id, 7))));
        
          $row = new html_table_row(array($cell1));
          $table->data[] = $row;
          
          $cell2 = new html_table_cell(html_writer::table($table2) . $chtml . $addcommentlink);
          $row = new html_table_row(array($cell2));
          $table->data[] = $row;
        } else {
          //if ($list->userid == $USER->id)
            $cell2 = new html_table_cell(html_writer::table($table2) . $chtml . $addcommentlink);
          //else
          //  $cell2 = new html_table_cell($chtml . $addcommentlink);
          
          //3-cell
          $cell3 = new html_table_cell(voiceshadow_set_rait($list->id, 6));
          
          //4-cell
          $cell4 = new html_table_cell(voiceshadow_set_rait($list->id, 7));
          
          
          $cells = array($cell1, $cell2, $cell3, $cell4);
          
          $row = new html_table_row($cells);
          
          $table->data[] = $row;
        }
    }
    
    if ($voiceshadow->grademethod == "rubrics") {
      echo html_writer::start_tag('div');
      echo html_writer::link(new moodle_url('/mod/voiceshadow/submissions.php', array("id" => $id)), get_string("rubrics", "voiceshadow"));
      echo html_writer::end_tag('div');
    }
   
    echo html_writer::table($table);
        
    echo html_writer::script('
 $(document).ready(function() {
  $(".voiceshadow_rate_box").change(function() {
    var value = $(this).val();
    var data  = $(this).attr("data-url");
    
    var e = $(this).parent();
    e.html(\'<img src="img/ajax-loader.gif" />\');
    
    $.get("ajax.php", {id: '.$id.', act: "setrating", data: data, value: value}, function(data) {
      e.html(data); 
    });
  });
 });
    ');
        
        /*
    if (is_object($table)) {
        list($totalcount, $table->data, $startrec, $finishrec, $options["page"]) = voiceshadow_get_pages($table->data, $page, $perpage);
        print_paging_bar($totalcount, $page, $perpage, "view.php?a=list&id={$id}&sort={$sort}&orderby={$orderby}&amp;");
        print_table($table);
        print_paging_bar($totalcount, $page, $perpage, "view.php?a=list&id={$id}&sort={$sort}&orderby={$orderby}&amp;");
    }
    */
}


    if ($a == "add") {
        class voiceshadow_comment_form extends moodleform {
            function definition() {
                global $CFG, $USER, $DB, $course, $fileid, $id, $act, $commentid, $voiceshadow;
                
                $time = time();
                $filename = str_replace(" ", "_", $USER->username)."_".date("Ymd_Hi", $time);
                
                $mform    =& $this->_form;
                
                //--------------Checking Embed code---------//
                if (!empty($voiceshadow->embedvideo)) {
                  $mform->addElement('header', 'Embed', get_string('embedcode', 'voiceshadow')); 
                  $mform->addElement('static', 'description', '', $voiceshadow->embedvideo);
                }
                //------------------------------------------//

                //--------------Uploadd MP3 ----------------//
                if (!voiceshadow_is_ios()) {
                  $filepickeroptions = array();
                  //$filepickeroptions['filetypes'] = array('.mp3','.mov','.mp4','.m4a');
                  $filepickeroptions['maxbytes']  = get_max_upload_file_size($voiceshadow->maxbytes);
                  $mform->addElement('header', 'mp3upload', get_string('mp3upload', 'voiceshadow')); 
                  $mform->addElement('filepicker', 'submitfile', get_string('uploadmp3', 'voiceshadow'), null, $filepickeroptions);
                }
                
                
                //-------------- Listen to recorded audio ----------------//
                $mform->addElement('header', 'listentorecordedaudio', get_string('listentorecordedaudio', 'voiceshadow')); 
                
                for ($i=1;$i<=5;$i++) {
                  $name = "var{$i}";
                  $nametext = "var{$i}text";
                  if (!empty($voiceshadow->{$name})){
                    if ($item = $DB->get_record("files", array("id" => $voiceshadow->{$name}))) {
                      $link = new moodle_url("/mod/voiceshadow/file.php?file=".$voiceshadow->{$name});
                      
                      if ($i == 1)
                        $checked = 'checked="checked"';
                      else
                        $checked = '';
                      
                      $o  = '<div style="margin:10px 0">
                      <input type="radio" name="selectaudiomodel" value="'.$i.'" class="selectaudiomodel" id="id_selectaudiomodel_'.$i.'" style="float: left;margin: 0 20px 0 0;" '.$checked.' data-url="voiceshadow://?link='.$CFG->wwwroot.'&id='.$id.'&uid='.$USER->id.'&time='.$time.'&var='.$i.'&type=voiceshadow" />
                      ';
                      
                      if (!voiceshadow_is_ios()) {
                        $o .= html_writer::script('var fn = function() {var att = { data:"'.(new moodle_url("/mod/voiceshadow/js/mp3player.swf")).'", width:"90", height:"15" };var par = { flashvars:"src='.$link.'" };var id = "audio_'.$voiceshadow->{$name}.'";var myObject = swfobject.createSWF(att, par, id);};swfobject.addDomLoadEvent(fn);');
                        $o .= '<div style="float:left;"><div id="audio_'.$voiceshadow->{$name}.'"><a href="'.$link.'">audio</a></div></div><label for="id_selectaudiomodel_'.$i.'" style="float: left;margin-left: 20px;font-size: 15px;">'.$voiceshadow->{$nametext}.'</label><div style="clear:both;"></div>';
                      } else {
                        $o .= '<div style="float:left;"><audio src="'.$link.'" id="audio_'.$voiceshadow->{$name}.'" controls="controls"><a href="'.$link.'">audio</a></audio></div><label for="id_selectaudiomodel_'.$i.'" style="float: left;margin-left: 20px;font-size: 15px;">'.$voiceshadow->{$nametext}.'</label><div style="clear:both;"></div>';
                      }
                      
                      $o  .= '</div>';
                      
                      $mform->addElement('static', 'description', '', $o);
                    }
                  }
                }
                //-------------- END -------------------------------------//
                
                
                //-------------- Record ----------------//
                $mediadata = "";
                
                if (voiceshadow_is_ios()) {
                  $mediadata .= html_writer::start_tag("h3", array("style" => "padding: 0 20px;"));
                  $mediadata .= html_writer::start_tag("a", array("href" => 'voiceshadow://?link='.$CFG->wwwroot.'&id='.$id.'&uid='.$USER->id.'&time='.$time.'&var=1&type=voiceshadow', "id"=>"id_recoring_link",
                                                                  "onclick" => 'formsubmit(this.href)'));
                                                                  //voiceshadow://?link='.$CFG->wwwroot.'&id='.$id.'&cid='.$course->id.'&filename='.$filename.'&type=audio
                  $mediadata .= get_string('recordvoice', 'voiceshadow');
                  $mediadata .= html_writer::end_tag('a');
                  $mediadata .= html_writer::end_tag('h3');
                  
                  $mediadata .= html_writer::script('function formsubmit(link) {$(\'input[name=iphonelink]\').val(link);$(\'#mform1\').submit();}');
                } else {
                  $filename = str_replace(" ", "_", $USER->username)."_".date("Ymd_Hi", $time);
                  
                  $mediadata  = html_writer::script('var fn = function() {var att = { data:"'.(new moodle_url("/mod/voiceshadow/js/recorder.swf")).'", width:"350", height:"200"};var par = { flashvars:"rate=44&gain=50&prefdevice=&loopback=no&echosupression=yes&silencelevel=0&updatecontrol=poodll_recorded_file&callbackjs=poodllcallback&posturl='.(new moodle_url("/mod/voiceshadow/uploadmp3.php")).'&p1='.$id.'&p2='.$USER->id.'&p3="+$(\'#id_submitfile\').attr(\'value\')+"&p4='.$filename.'&autosubmit=true&debug=false&lzproxied=false" };var id = "mp3_flash_recorder";var myObject = swfobject.createSWF(att, par, id);};swfobject.addDomLoadEvent(fn);function poodllcallback(args){console.log(args);}');
                  $mediadata .= '<div id="mp3_flash_recorder"></div>';
                }
                
                $mform->addElement('header', 'Recording', get_string('recordvoice', 'voiceshadow')); 
                $mform->addelEment('hidden', 'filename', $filename);
                $mform->addelEment('hidden', 'iphonelink', '');
                $mform->addElement('static', 'description', '', $mediadata);
                
                if (!empty($fileid) && empty($act)) {
                  $mform->setDefault("filename", $data->filename);
                  $mform->addelEment('hidden', 'fileid', $fileid);
                }
                
                if (!empty($act)) {
                  $mform->addelEment('hidden', 'act', $act);
                  $mform->addelEment('hidden', 'fileid', $fileid);
                } else
                  $mform->addelEment('hidden', 'act', 'newinstance');
                //-------------- Record -------END------//
                

                $mform->addElement('header', 'addcomment', get_string('addcomment', 'voiceshadow')); 
                
                if (!empty($fileid) && empty($act)) {
                  $data = $DB->get_record("voiceshadow_files", array("id" => $fileid, "userid" => $USER->id));
                  $mform->addElement('editor', 'summary', '')->setValue( array('text' => $data->summary) );
                } else {
                  if (!empty($act) && !empty($commentid)) {
                    $data = $DB->get_record("voiceshadow_comments", array("id" => $commentid, "userid" => $USER->id));
                    $mform->addElement('editor', 'summary', '')->setValue( array('text' => $data->summary) );
                    $mform->addelEment('hidden', 'commentid', $commentid);
                  } else
                    $mform->addElement('editor', 'summary', '');
                }
                
                $mform->addElement('html', '<script language="JavaScript">
            $(document).ready(function() {
              $(".selectaudiomodel").click(function(){
                $("#id_recoring_link").attr("href", $(this).attr("data-url"));
              });
            });
            </script>');
                
                
                $this->add_action_buttons(false, $submitlabel = get_string("saverecording", "voiceshadow"));
            }
        }
        
        $mform = new voiceshadow_comment_form('view.php?a='.$a.'&id='.$id);
        
        $mform->display();
    }

/// Finish the page
echo $OUTPUT->footer();



