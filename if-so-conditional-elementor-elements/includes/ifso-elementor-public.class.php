<?php
namespace IfSo\Addons\Elementor\PublicSide;

require_once(IFSO_PLUGIN_BASE_DIR. 'public/models/data-rules/ifso-data-rules-model.class.php');
require_once IFSO_PLUGIN_SERVICES_BASE_DIR . 'standalone-condition-service/standalone-condition-service.class.php';

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    die;
}

class IfsoElementorPublic{
    private $dr_model;

    public function __construct(){}

    public function filter_section_content_before( $section ) {
        if($section->get_type()==='widget') return false;
        ob_start();
    }

    public function filter_section_content_after( $section ) {
        if($section->get_type()==='widget') return false;
        $content = ob_get_clean();
        echo $this->filter_element_through_condition($content,$section);    //The content has already been escaped in elementor
    }

    public function filter_element_through_condition($content,$el){
        $is_editor =  \Elementor\Plugin::$instance->editor->is_edit_mode();
        $settings = $el->get_settings_for_display();
        $rules = $this->settings_to_data_rules($settings);
        if($rules){
            if($is_editor){
                $condition_marker = "<span class='ifso-has-standalone-marker'>If<span style='color: rgb(253, 91, 86);'>‣</span>So active</span>";
                $content = $condition_marker . $content;
            }
            else{
                $default_content = isset($settings['ifso_standalone_condition_default_content']) ? $settings['ifso_standalone_condition_default_content'] : '';
                $params = [
                    'content'=>$content,
                    'default'=>$default_content,
                    'rule'=>$rules
                ];

                $pluginSettingsService = \IfSo\Services\PluginSettingsService\PluginSettingsService::get_instance();
                if(isset($pluginSettingsService->renderStandaloneViaAjax) && (empty($settings['ifso_condition_isAjax']) || $settings['ifso_condition_isAjax']==='same-as-global'))
                    $isAjax = $pluginSettingsService::get_instance()->renderStandaloneViaAjax->get();
                else
                    $isAjax = ($settings['ifso_condition_isAjax']==='1' || $settings['ifso_condition_isAjax']==='yes');

                if(!empty($settings['ifso_loader_type']) && $settings['ifso_loader_type']!=='same-as-global')
                    $params['loader'] = $settings['ifso_loader_type'];
                return \IfSo\PublicFace\Services\StandaloneConditionService\StandaloneConditionService::get_instance()->render($params,$isAjax);
            }
        }
        return $content;
    }

    private function settings_to_data_rules($settings){
        $ret = [];
        if(empty($this->dr_model))
            $this->dr_model =  new \IfSo\PublicFace\Models\DataRulesModel\DataRulesModel();

        $condition_type = (isset($settings['ifso_condition_type'])) ? $settings['ifso_condition_type'] : false;
        if($condition_type){
            $ret['trigger_type'] = $condition_type;
            $fields = $this->dr_model->get_condition_fields($condition_type);

            if($condition_type && $fields){
                foreach($fields as $field){
                    $settings_field_name = $condition_type . '-' .$field;
                    if(isset($settings[$settings_field_name]))
                        $ret[$field] = $settings[$settings_field_name];
                }
                if(!empty($settings['ifso_add_to_grp'])) $ret['add_to_group'] = $settings['ifso_add_to_grp'];
                if(!empty($settings['ifso_rm_from_grp'])) $ret['remove_from_group'] = $settings['ifso_rm_from_grp'];
                return $ret;
            }
        }

        return false;
    }
}
