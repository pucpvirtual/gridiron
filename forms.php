<?php
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->libdir}/formslib.php");

class grid_icon_form extends moodleform {
    public function definition() {
        global $DB;
        $mform = $this->_form;
        $instance = $this->_customdata;

        $sql = "SELECT cm.id, cm.instance, m.name
                FROM {course_modules} cm 
                INNER JOIN {modules} m ON m.id = cm.module
                WHERE cm.course = ? 
                ORDER BY cm.section ASC
                ";

        $params = array($instance['courseid'],$instance['courseid']);

        $modules = $DB->get_records_sql($sql, $params);

        $options = array();

        foreach($modules as $m){
           $options[$m->id] = $DB->get_field($m->name,'name',array('id'=>$m->instance), MUST_EXIST);
        }

        if(!is_null($instance['item'])){
            $item = $instance['item'];
            if(!empty($item->activityid)){

                $sql = "SELECT cm.id, cm.instance, m.name
                FROM {course_modules} cm 
                INNER JOIN {modules} m ON m.id = cm.module
                WHERE cm.id = ? ";

                $params = array($item->activityid);

                $module = $DB->get_record_sql($sql, $params);         

                $options[$item->activityid]  = $DB->get_field($module->name,'name',array('id'=>$module->instance), MUST_EXIST);
            }        
        }


        $options[0] = get_string('other');

        $mform->addElement('text', 'icon',get_string('imageurl','format_gridiron'));
        $mform->setType('icon', PARAM_TEXT );
        $mform->addHelpButton('icon','imageurl', 'format_gridiron');

        $mform->addElement('select', 'activity',get_string('activity','format_gridiron'),$options);
        $mform->addHelpButton('activity','activity', 'format_gridiron');

        $mform->addElement('text', 'url',get_string('url'));
        $mform->addHelpButton('url','url', 'format_gridiron');

        $mform->setType('url', PARAM_TEXT );

        // Defaults
        if(isset($item)){
            $mform->setDefault('activity', $item->activityid);    
            $mform->setDefault('icon',$item->imagepath);   
            if($item->url){
                $mform->setDefault('url',$item->url);   
            }
        }
        

        //Conditions:...
        $mform->disabledIf('url','activity','neq',0);
        // Buttons:...
        $this->add_action_buttons(true, get_string('savechanges', 'admin'));

        
    }
}


class grid_link_form extends moodleform {
    public function definition() {
        global $DB;
        $mform = $this->_form;
        $instance = $this->_customdata;

        $sql = "SELECT id, fullname
                FROM {course}  
                WHERE category IN (SELECT category FROM {course} WHERE id = ?)
                AND id NOT IN (SELECT link FROM {format_grid_link} WHERE courseid = ?)
                ORDER BY fullname ASC
                ";

        $params = array($instance['courseid'],$instance['courseid']);

        $options = $DB->get_records_sql_menu($sql, $params); 

        if(!is_null($instance['item'])){
            $item = $instance['item'];     
            $options[$item->link]  = $DB->get_field('course','fullname',array('id'=>$item->link), MUST_EXIST);
        }

        $mform->addElement('text', 'name',get_string('name'));
        $mform->setType('name', PARAM_TEXT );
        $mform->addElement('select', 'course',get_string('course'),$options);     

        // Defaults
        if(isset($item)){
            $mform->setDefault('name', $item->name);    
            $mform->setDefault('course',$item->link);   
        }
        // Buttons:...
        $this->add_action_buttons(true, get_string('savechanges', 'admin'));

        if(count($options) == 0){
            $mform->disabledIf('submitbutton', 'course', 'neq', 0);
        }

        $mform->addRule('name', get_string('required'), 'required', null, 'client', false, false);
        $mform->addRule('course', get_string('required'), 'required', null, 'client', false, false);
    }
}


class grid_icon_edit_form extends moodleform{

    public function definition(){
        global $DB;

        $mform = $this->_form;
        $instance = $this->_customdata;
        $icons = $DB->get_records('format_grid_icon',array('courseid'=>$instance['courseid']),'position');

        $total = count($icons);

        $position = array();

        for($i = 0; $i<$total; $i++){
            $position[] = $i;
        }

        foreach($icons as $i){

            $mform->addElement('html','<div>');
            $mform->addElement('html','<img src="'.$i->imagepath.'"/>');
            $mform->addElement('select',"order[{$i->id}]",get_string('order','format_gridiron'),$position);
            $mform->setDefault("order[{$i->id}]",$i->position);
            $mform->addHelpButton("order[{$i->id}]",'order', 'format_gridiron');
            $mform->addElement('html','</div>');           
        }

        if($total > 0){
            $this->add_action_buttons(true, get_string('savechanges', 'admin'));
        }else{
            $mform->addElement('html','<span>'.get_string('norecords','format_gridiron').'</span>');
            $mform->addElement('cancel');
        }

    }



}

class grid_link_edit_form extends moodleform{

    public function definition(){
        global $DB;

        $mform = $this->_form;
        $instance = $this->_customdata;
        $links = $DB->get_records('format_grid_link',array('courseid'=>$instance['courseid']),'position');

        $total = count($links);

        $position = array();

        for($i = 0; $i<$total; $i++){
            $position[] = $i;
        }


        foreach($links as $l){

            $mform->addElement('html','<div>');
            $mform->addElement('text',"name[{$l->id}]",get_string('name'));
            $mform->setDefault("name[{$l->id}]",$l->name);
            $mform->setType("name[{$l->id}]", PARAM_TEXT );
            $mform->addRule("name[{$l->id}]", get_string('required'), 'required', null, 'client', false, false);
            $mform->addElement('select',"order[{$l->id}]",get_string('order','format_gridiron'),$position);
            $mform->addHelpButton("order[{$l->id}]",'order', 'format_gridiron');
            $mform->setDefault("order[{$l->id}]",$l->position);
            $mform->addElement('checkbox',"delete[{$l->id}]",get_string('delete','format_gridiron'));
            $mform->addHelpButton("delete[{$l->id}]",'delete', 'format_gridiron');
            $mform->addElement('html','</div>');           
        }

        if(count($links) > 0){
            $this->add_action_buttons(true, get_string('savechanges', 'admin'));
        }else{
            $mform->addElement('html','<span>'.get_string('norecords','format_gridiron').'</span>');
            $mform->addElement('cancel');
        }

    }

}

