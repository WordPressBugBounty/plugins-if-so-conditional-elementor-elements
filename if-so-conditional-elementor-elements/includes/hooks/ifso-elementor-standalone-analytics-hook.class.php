<?php
namespace IfSo\Addons\Elementor\Hooks;

use IfSo\PublicFace\Services\TriggersService\Filters\Hooks\IHook;

require_once( IFSO_PLUGIN_SERVICES_BASE_DIR . '/triggers-service/filters/hooks/hook.interface.php');

class ElementorStandaloneAnalyticsHook implements IHook {
    public function apply($text, $rule_data){
    }
}