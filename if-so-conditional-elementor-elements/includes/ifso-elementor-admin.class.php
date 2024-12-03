<?php
namespace IfSo\Addons\Elementor\Admin;
use Elementor\Controls_Manager;
use IfSo\Addons\Elementor\Controls\Group_Control_Standalone_Ifso_Condition;
use IfSo\PublicFace\Models\DataRulesModel;
use IfSo\Services\LicenseService\LicenseService;

require_once(IFSO_PLUGIN_BASE_DIR. 'public/models/data-rules/ifso-data-rules-ui-model.class.php');
require_once(IFSO_PLUGIN_BASE_DIR . 'services/license-service/license-service.class.php');
require_once(IFSO_PLUGIN_BASE_DIR . 'services/plugin-settings-service/plugin-settings-service.class.php');
//require_once( IFSO_ELEMENTOR_DIR . '/includes/controls/ifso-standalone-condition-control.class.php' );

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    die;
}

class IfsoElementorAdmin{
    private $ui_model;
    private $removed_conditions = ['AB-Testing'];

    private function get_model(){
        if($this->ui_model===null){
            $data_rules_model  = new DataRulesModel\DataRulesUiModel();
            $this->ui_model = $data_rules_model->get_ui_model();
            foreach($this->removed_conditions as $condition)
                unset($this->ui_model->$condition);       //Remove unuseable conditions
        }
        return $this->ui_model;
    }
    public function add_ifso_standalone_condition_ui($element, $section_id = null, $args = null){
        $ui_model = $this->get_model();
        $condition_types = [''=>"Select a Condition"];
        $has_valid_license = LicenseService::get_instance()->is_license_valid();
        $ifso_tab_key = 'ifso_tab';
        foreach ($ui_model as $key=>$condition){
            $condition_types[$key] = $condition->name;
            if(!$has_valid_license && !in_array($key,DataRulesModel\DataRulesModel::get_free_conditions()))
                $condition_types[$key].='*';
        }

        Controls_Manager::add_tab($ifso_tab_key, 'Conditions');

        $element->start_controls_section(
            'ifso_standalone_condition_section',
            [
                'tab' => $ifso_tab_key,
                'label' => 'If-So Dynamic Condition',
            ]
        );

        $element->start_controls_tabs("ifso_condition_fields_tabs");
        $element->start_controls_tab('ifso_standalone_condition_condition_tab', ['label' => "Condition"]);

        $element->add_control(
            'ifso_condition_type',
            [
                'label'=>'Condition',
                'type'=>Controls_Manager::SELECT2,
                'options'=>$condition_types,
            ]
        );

        /*$element->add_group_control(
            Group_Control_Standalone_Ifso_Condition::get_type(),
            [
                'name'=>'ifso_condition_fields',
                'label'=>'Condition Fields',
                'ui_model'=>$ui_model,
                'popover'=>false,
                //'selector'=>'{{WRAPPER}} .if-so-condition-fields'
            ],
            [
                'popover'=>false
            ]
        );*/

        $element->add_control(
            'ifso_multibox_wrapper',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => "<div class='ifso-multibox-wrapper'><div class='ifso-multibox-description'>Targeted Locations:</div><div class='ifso-multibox-versions'></div></div>",
            ]
        );

        if(!$has_valid_license)
            $element->add_control('ifso_license_notice', ['type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => '<p>This condition is only available upon license activation. <a href=\'https://www.if-so.com/plans/?utm_source=Plugin&utm_medium=direct&utm_campaign=getFree&utm_term=lockedConditon&utm_content=Elementor\' target="_blank"> Click here</a> to get a license if you do not have one.</p>', 'content_classes' => 'ifso-standalone-error-message ifso-license-error nodisplay']);

        foreach($ui_model as $key=>$condition){
            if(!empty($condition->fields)){
                foreach($condition->fields as $field){
                    $this->add_control_form_model_field($element,$field,$key);
                }
            }
        }

        $element->add_control('hr', ['type' => \Elementor\Controls_Manager::DIVIDER]);
        $ajax_question_mark = '<a href="https://www.if-so.com/help/documentation/ajax-loading-page-caching/?utm_source=Plugin&utm_medium=Elementor&utm_campaign=inlineHelp" target="_blank" class="ifso-question-mark">?</a>';
        $element->add_control(
            'ifso_condition_isAjax',
            [
                'label'=>"Render with Ajax {$ajax_question_mark}",
                'type'=>Controls_Manager::SELECT,
                'options'=>['same-as-global'=>'Same as global','yes'=>'Yes','no'=>'No'],
                'default'=>'same-as-global'
            ]
        );

        if(method_exists(\IfSo\PublicFace\Services\AjaxTriggersService\AjaxTriggersService::get_instance(),'get_ajax_loader_list')){
            $ajax_loaders = \IfSo\PublicFace\Services\AjaxTriggersService\AjaxTriggersService::get_instance()->get_ajax_loader_list('prettynames');
            $loaders_options = array_merge(['same-as-global'=>"Same as global"],$ajax_loaders);
            $element->add_control(
                'ifso_loader_type',
                [
                    'label'=>'Loader type',
                    'type'=>Controls_Manager::SELECT,
                    'options'=>$loaders_options,
                    'default'=>'same-as-global'
                ]
            );
        }



        $element->end_controls_tab();

        $element->start_controls_tab('ifso_standalone_condition_default_tab', ['label' => "Default"]);
        $element->add_control(
            'ifso_standalone_condition_default_content',
            [
                'label'=>'Default Content',
                'type'=>Controls_Manager::WYSIWYG,
                'default'=>'',
            ]
        );
        $element->end_controls_tab();

        $element->start_controls_tab('ifso_standalone_condition_audiences_tab', ['label' => "Audiences"]);
        $groups_service = \IfSo\PublicFace\Services\GroupsService\GroupsService::get_instance();
        $groups_list = $groups_service->get_groups();
        $groups_options = array_combine($groups_list,$groups_list);
        $element->add_control(
            'ifso_addrm_groups_heading',
            [
                'label'=>'Add or remove users from Audiences if the conditon is met:',
                'type'=>Controls_Manager::HEADING
            ]
        );
        $element->add_control(
            'ifso_add_to_grp',
            [
                'label'=>'Add to Audience',
                'type'=>Controls_Manager::SELECT2,
                'multiple'=>true,
                'options'=>$groups_options,
            ]
        );

        $element->add_control(
            'ifso_rm_from_grp',
            [
                'label'=>'Remove from Audience',
                'type'=>Controls_Manager::SELECT2,
                'multiple'=>true,
                'options'=>$groups_options,
            ]
        );

        if(empty($groups_options)){
            $create_audience_url = admin_url('admin.php?page=' . EDD_IFSO_PLUGIN_GROUPS_PAGE);
            $element->add_control(
                'ifso-no-audiences-noticebox',
                [
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => "<div class='ifso-standalone-condition-noticebox' style='color: #dba617;background-color: #fff9e9;border: 1px solid #dba617;'>You havent't created any Audiences <br> <a class='linkBtn' target='_blank' href='{$create_audience_url}'>+ Create an audience</a></div>",
                ]
            );
        }

        $element->end_controls_tab();

        $element->end_controls_tabs();
        $element->end_controls_section();
    }

    private function add_control_form_model_field($element,$field,$condition){
        $type = $field->type;
        $name = $condition . '-' . $field->name;
        $extraClasses = !empty($field->extraClasses) ? $field->extraClasses : '';
        if($type==='noticebox'){
            $closing_X_html = $field->closeable ? "<span class='closingX' onclick='(function(e){console.log(e);return false;})'>X</span>" : "";
            $element->add_control(
                $name,
                [
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => "<div class='ifso-standalone-condition-noticebox {$extraClasses}' style='color:{$field->color};background-color:{$field->bgcolor};border: 1px solid {$field->color}'>{$closing_X_html}{$field->content}</div>",
                    //'content_classes' => "",
                ]
            );
            return;
        }
        $label = $field->prettyName;
        $title = !empty($field->symbol) ? 'Press Enter to add the item to the target list' : '';    //For multibox fields
        $placeholder = !empty($field->placeholder) ? $field->placeholder : '';
        if($type==='text'){
            $element->add_control(
                $name,
                [
                    'label'=>$label,
                    'type'=>Controls_Manager::TEXT,
                    'label_block'=>true,
                    'title'=>$title,
                    'classes'=>$extraClasses,
                    'placeholder'=>$placeholder
                ]
            );
        }
        if($type==='select'){
            $options = [];
            $first_option = null;
            if(!empty($field->options)){
                foreach ($field->options as $option){
                    if($first_option===null)
                        $first_option = $option->value;
                    $options[$option->value] = $option->display_value;
                }
            }

            $element->add_control(
                $name,
                [
                    'label'=>$label,
                    'type'=>Controls_Manager::SELECT,
                    'options'=>$options,
                    'default'=>$first_option,
                    'label_block'=>true,
                    'classes'=>$extraClasses
                ]
            );
        }
        if($type==='checkbox'){
            $element->add_control(
                $name,
                [
                    'label'=>$label,
                    'type'=>Controls_Manager::SWITCHER,
                    'label_on'=>"Yes",
                    'label_off'=>"No",
                    'return_value'=>'1',
                    'classes'=>$extraClasses
                ]
            );
        }
        if($type==='multi'){
            $element->add_control(
                $name,
                [
                    'label'=>$name,
                    'type'=>Controls_Manager::TEXT,
                    'classes'=>"ifso-multibox {$extraClasses}"
                ]
            );
        }
    }

    public function enqueue_scripts(){
        $admin_script_name = 'ifso-elementor-admin';
        $dr_model = $this->get_model();
        $data_rules_model_json = json_encode($dr_model);
        $license_data_json = json_encode(['free_conditions'=>DataRulesModel\DataRulesModel::get_free_conditions(),'is_license_valid'=>LicenseService::get_instance()->is_license_valid()]);
        wp_enqueue_script($admin_script_name,plugin_dir_url(IFSO_ELEMENTOR_PLUGIN_FILE) . 'assets/js/ifso-elementor-admin.js',array('jquery'),IFSO_ELEMENTOR_VERSION,false);
        if(function_exists('wp_add_inline_script')){
            $data_scr = "var data_rules_model_json = {$data_rules_model_json};
                var license_data_json = {$license_data_json};";
            wp_add_inline_script($admin_script_name,$data_scr,'before');
        }
        else{
            wp_localize_script($admin_script_name,'data_rules_model_json',$data_rules_model_json);
            wp_localize_script($admin_script_name,'license_data_json',$license_data_json);
        }

    }

    public function enqueue_styles(){
        wp_enqueue_style('ifso-elementor-admin',plugin_dir_url(IFSO_ELEMENTOR_PLUGIN_FILE) . 'assets/css/ifso-elementor-admin.css',[],IFSO_ELEMENTOR_VERSION);
    }

    public function remove_ifso_triggers_cpt_from_elementor_types($types) {
        if(isset($types['ifso_triggers']))
            unset($types['ifso_triggers']);
        return $types;
    }
}


?>
