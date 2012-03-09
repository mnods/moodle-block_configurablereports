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

/** Configurable Reports
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plugin.class.php');
 
abstract class component_base {
    var $report;     // Report configuration (DB record)
    var $config;     // Component configuration (DB record if exists)
	var $plugins;    // Plugin objects
	
	var $help = '';
	
	static function get($report, $component, $classname){
	    global $CFG;
	    
	    $comppath = self::get_path($report, $component);
	    require_once("$comppath/component.class.php");
	    
	    return new $classname($report);
	}
	
	static function get_path($report, $component){
	    global $CFG;
	    
	    $basedir = "$CFG->dirroot/blocks/configurable_reports";
	    $custompath = "report/$report->type";
	    $filepath = "components/$component";
        $file = "component.class.php";
	    
	    if (file_exists("$basedir/$custompath/$filepath/$file")) {
	        return "$basedir/$custompath/$filepath";
	    }
	    
	    return "$basedir/$filepath";
	}
	
	function __construct($report) {
		global $DB, $CFG;
		
		$this->report = $report;
		$search = array('reportid' => $report->id, 'component' => $this->get_name());
		$configdata = $DB->get_field('block_configurable_reports_component', 'configdata', $search);
		$this->config = cr_unserialize($configdata);
	}
	
	function __toString(){
	    return get_string($this->get_name(), 'block_configurable_reports');
	}
	
	function get_name(){
	    $pieces = explode('component_', get_class($this));
	    return $pieces[1];
	}
	
	function plugin_classes(){
	    return array();
	}
	
	function _load_plugins(){
	    $this->plugins = array();
	    foreach($this->plugin_classes() as $plug => $classname){
	        $this->plugins[$plug] = plugin_base::get($this->report, $this->get_name(), $plug, $classname);
	    }
	}
	
	function get_plugins(){
	    if (!isset($this->plugins)) {
	        $this->_load_plugins();
	    }
	    
	    return $this->plugins;
	}
	
	function get_all_instances(){
	    global $DB;
	    
	    $instances = array();
	    
	    $search = array('reportid' => $this->report->id, 'component' => $this->get_name());
	    $records = $DB->get_records('block_configurable_reports_plugin', $search, 'sortorder');
	    foreach($records as $record){
	        $instances[$record->sortorder] = $record;
	    }
	    
	    return $instances;
	}
	
	function has_plugin($plugname){
	    return array_key_exists($plugname, $this->plugin_classes());
	}
	
	function get_plugin($plugname){
	    if (!$this->has_plugin($plugname)) {
	        return null;
	    }
	    $plugins = $this->get_plugins();
	     
	    return $plugins[$plugname];
	}
	
	function get_plugin_options(){
	    $plugins = $this->get_plugins();
	     
	    $pluginoptions = array();
	    foreach($plugins as $plugin => $pluginclass){
	        if ($pluginclass->can_create_instance()) {
	            $pluginoptions[$plugin] = get_string($pluginclass->get_name(), 'block_configurable_reports');
	        }
	    }
	    asort($pluginoptions);
	     
	    return $pluginoptions;
	}
	
	function has_ordering(){
	    return false;
	}
	
	function get_help_icon(){
	    global $OUTPUT;
	    
	    return $OUTPUT->help_icon('comp_'.$this->get_name(), 'block_configurable_reports');
	}
	
	function has_form(){
	    return false;
	}
	
	function get_form($action = null, $customdata = array()){
	    if (!$this->has_form()) {
	        return null;
	    }
	    
	    global $CFG;
	    $component = $this->get_name();
	    $comppath = self::get_path($this->report, $component);
	    require_once("$comppath/form.php");
	    
	    $formclassname = $component.'_form';
	    $customdata['compclass'] = $this;
	    return new $formclassname($action, $customdata);
	}
}

?>