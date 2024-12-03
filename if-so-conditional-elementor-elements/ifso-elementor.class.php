<?php
namespace IfSo\Addons\Elementor;
use IfSo\Addons\Elementor\Admin\IfsoElementorAdmin;
use IfSo\Addons\Elementor\PublicSide\IfsoElementorPublic;

include_once IFSO_PLUGIN_BASE_DIR . 'extensions/extension-base/ifso-extension-include.php';
require_once(__DIR__ . '/includes/ifso-elementor-admin.class.php');
require_once(__DIR__ . '/includes/ifso-elementor-public.class.php');

if(class_exists('\IfSo\Addons\Base\ExtensionMain')){
    class ElementorExtension extends \IfSo\Addons\Base\ExtensionMain{
        protected $addon_settings;
        protected function __construct(){
            add_action( 'elementor/init', [ $this, 'init' ] );
        }

        public function init(){
            $this->register_admin_hooks();
            $this->register_public_hooks();
        }

        public function register_admin_hooks(){
            $admin = new IfsoElementorAdmin();
            add_action( 'elementor/element/column/section_advanced/after_section_end', [$admin,'add_ifso_standalone_condition_ui'], 10, 3 );
            add_action( 'elementor/element/section/section_advanced/after_section_end', [$admin,'add_ifso_standalone_condition_ui'], 10, 3 );
            add_action( 'elementor/element/common/_section_style/after_section_end', [$admin,'add_ifso_standalone_condition_ui'], 10, 3 );
            add_action( 'elementor/element/container/section_layout/after_section_end', [$admin,'add_ifso_standalone_condition_ui'], 10, 3 );
            add_action( 'elementor/element/popup/section_advanced/after_section_end', [$admin,'add_ifso_standalone_condition_ui'], 10, 3 );

            add_filter('elementor/settings/controls/checkbox_list_cpt/post_type_objects',[$admin,'remove_ifso_triggers_cpt_from_elementor_types'],10,1);

            add_action('elementor/editor/before_enqueue_scripts',[$admin,'enqueue_scripts']);
            add_action('elementor/editor/before_enqueue_styles',[$admin,'enqueue_styles']);
            add_action('elementor/preview/enqueue_styles',[$admin,'enqueue_styles']);
        }

        public function register_public_hooks(){
            $public = new IfsoElementorPublic();

            add_action( "elementor/frontend/section/before_render", [$public, 'filter_section_content_before'], 10, 1 );
            add_action( "elementor/frontend/section/after_render", [$public, 'filter_section_content_after'], 10, 1 );
            add_action( "elementor/frontend/column/before_render", [$public, 'filter_section_content_before'], 10, 1 );
            add_action( "elementor/frontend/column/after_render", [$public, 'filter_section_content_after'], 10, 1 );
            add_action( "elementor/frontend/container/before_render", [$public, 'filter_section_content_before'], 10, 1 );
            add_action( "elementor/frontend/container/after_render", [$public, 'filter_section_content_after'], 10, 1 );

            add_filter('elementor/widget/render_content',[$public, 'filter_element_through_condition'],10,2);

            //add_action( "elementor/frontend/widget/before_render", [$public, 'filter_section_content_before'], 10, 1);  //Widgets filtered through render_content
            //add_action( "elementor/frontend/widget/after_render", [$public, 'filter_section_content_after'], 10, 1 );
        }
    }
}
