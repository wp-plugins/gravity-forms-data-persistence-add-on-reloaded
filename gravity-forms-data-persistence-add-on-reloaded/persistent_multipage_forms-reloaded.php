<?php
/*
  Plugin Name: Gravity Forms Data Persistence Add-On Reloaded
  Plugin URI: http://asthait.com
  Description: This is a <a href="http://www.gravityforms.com/" target="_blank">Gravity Form</a> plugin. A big limitation with Gravity Form is, in case of big multipage forms, if you close or refresh the page during somewhere midle of some step. all the steps data will loose. this plugin solves that problem. This is an updated version of asthait's plugin.
  Author: Robert Iseley
  Version: 3.1
  Author URI: http://www.robertiseley.com
  Orginal Plugin by: asthait
 */



add_action("gform_post_paging", "ri_page_changed", 10, 3);

function ri_page_changed($form, $coming_from_page, $current_page) {
    if ($form['isPersistent']) {
        if (is_user_logged_in()) {
            $option_key = ri_getFormOptionKeyForGF($form);
            update_option($option_key, json_encode($_POST));
        }
    }
}

add_filter("gform_pre_render", "ri_pre_populate_the_form");

function ri_pre_populate_the_form($form) {
    if ($form['isPersistent']) {
        $option_key = ri_getFormOptionKeyForGF($form);
        if (get_option($option_key)) {
            $_POST = json_decode(get_option($option_key), true);
        }
    }


    return $form;
}

add_action("gform_post_submission", "ri_set_post_content", 10, 2);

function ri_set_post_content($entry, $form) {
    if ($form['isPersistent']) {
        //Update form data in wp_options table
        if (is_user_logged_in()) {
            $option_key = ri_getFormOptionKeyForGF($form);
			
			if($form['isEnablePersistentClear'])
				delete_option($option_key);
			else
	            update_option($option_key, json_encode($_POST));

            $entry_option_key = ri_getEntryOptionKeyForGF($form);
            if (get_option($entry_option_key)) {
                //Delete old entry from GF tables
                if (!$form['isEnableMulipleEntry']) {
                   RGFormsModel::delete_lead(get_option($entry_option_key));
                }
                
            }
        }

        //Update entry in wp_options table

        update_option($entry_option_key, $entry['id']);
    }
}

function ri_getFormOptionKeyForGF($form) {

    global $current_user;
    get_currentuserinfo();

    $option_key = $current_user->user_login . '_GF_' . $form['id'];

    return $option_key;
}

function ri_getEntryOptionKeyForGF($form) {

    global $current_user;
    get_currentuserinfo();

    $option_key = $current_user->user_login . '_GF_' . $form['id'] . '_entry';

    return $option_key;
}

//Add persistent checkbox to the form settings
add_filter("gform_form_settings", "ri_persistency_settings", 50, 2);

function ri_persistency_settings($form_settings, $form) {
	
	

    //create settings on position 50 (right after Admin Label)
	$tr_persistent = '
        	<tr>
            	<th>Persistent</th>
        	<td>
            <input type="checkbox" id="form_persist_value" onclick="SetFormPersistency();" /> Enable form persistence
            <label for="form_persist_value">              
                <?php gform_tooltip("form_persist_tooltip") ?>
            </label>
			</td>
        </tr>
        <tr>
        	<th></th>
        	<td>
            <input type="checkbox" id="form_enable_multiple_entry_entry" onclick="SetFormMultipleEntry();" /> Enable multi entry from same user while form is persistent
            <label for="form_enable_multiple_entry">              
                <?php gform_tooltip("form_enable_multiple_entry_tooltip") ?>
            </label>
			</td>
        </tr>
        <tr>
        <th></th>
        	<td>
            <input type="checkbox" id="form_enable_persistent_clear" onclick="SetFormPersistentClear();" /> Clear persistence on submit
            <label for="form_enable_multiple_entry">              
                <?php gform_tooltip("form_enable_persistent_clear_tooltip") ?>
            </label>
			</td>
        </tr>';
		
		$form_settings["Form Options"]['persistent'] = $tr_persistent;
		
		return $form_settings;
}

//Action to inject supporting script to the form editor page
add_action("gform_advanced_settings", "ri_editor_script_persistency");
function ri_editor_script_persistency() {
    ?>
    <script type='text/javascript'>
                
        function SetFormPersistency(){
            form.isPersistent = jQuery("#form_persist_value").is(":checked");
        }
        function SetFormMultipleEntry(){
            form.isEnableMulipleEntry = jQuery("#form_enable_multiple_entry_entry").is(":checked");
        }
        function SetFormPersistentClear(){
            form.isEnablePersistentClear = jQuery("#form_enable_persistent_clear").is(":checked");
        }
                
        jQuery("#form_persist_value").attr("checked", form.isPersistent);       
        jQuery("#form_enable_multiple_entry_entry").attr("checked", form.isEnableMulipleEntry);
        jQuery("#form_enable_persistent_clear").attr("checked", form.isEnablePersistentClear);    
        
    </script>
    <?php
}

//Filter to add a new tooltip
add_filter('gform_tooltips', 'ri_add_persistency_tooltips');

function ri_add_persistency_tooltips($tooltips) {
    $tooltips["form_persist_tooltip"] = "<h6>Persistency</h6>Check this box to make this form persistant";
    $tooltips["form_enable_multiple_entry_tooltip"] = "<h6>Persistency</h6>This will allow multiple entry from same user but, user can't edit their last";
    return $tooltips;
}
