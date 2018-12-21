<?php defined( 'ABSPATH' ) || exit;?>
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>模块编辑</title>
    <?php wpcom::modules_setting_head();?>
</head>
<body>
<div class="wrap clearfix" ng-app="panel" ng-controller="modules as Mod" style="display: none;">
    <div class="mod-wrap" ng-if="options[0]['tab-name']">
        <div class="mod-tab"><div class="mod-tab-item" ng-repeat="(i, o) in options" ng-class="{active:i==0}">{{o['tab-name']}}</div></div>
        <div class="mod-content" ng-repeat="(i, option) in options" ng-class="{active:i==0}"><div class="mod-item clearfix" ng-if="v.type" ng-repeat="(k, v) in option" k="k" v="v"></div></div>
    </div>
    <div class="mod-item clearfix" ng-if="!options[0]['tab-name']" ng-repeat="(k, v) in options" k="k" v="v"></div>
</div>
<script type="text/html" id="j-tpl-editor">
    <?php wp_editor( '', 'mod-editor-test', wpcom::editor_settings(array('textarea_name'=>'mod-editor-test', 'textarea_rows'=>4)) );?>
</script>
<?php wpcom::modules_setting_foot(); ?>
</body>
</html>