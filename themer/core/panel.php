<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Panel{
    private $settings;
    private $updateName;
    protected $automaticCheckDone = false;

    public function __construct() {
        $this->options = $this->get_theme_options();
        $GLOBALS['options'] = $this->options;
        $this->init_hooks();
    }

    private function init_hooks(){
        add_action('admin_init', array($this, 'panel_init'));
        add_action('admin_menu', array($this, 'panel_menu'));
        add_action('wp_ajax_wpcom_panel', array($this, 'panel_callback'));
        add_action('wp_ajax_wpcom_check_version', array($this, 'check_version'));
        add_action('wp_ajax_wpcom_demo_export', array($this, 'theme_options_demo_export'));

        $this->updateName = 'theme_update_'.THEME_ID;

        add_action('delete_site_transient_update_themes', array($this, 'updated'));
    }

    public function panel_menu() {
        if(function_exists('add_menu_page')) {
			$this->settings = $this->get_panel_settings();
            add_menu_page('主题设置', '主题设置', 'edit_theme_options', 'wpcom-panel', array( $this, 'panel_admin' ), 'dashicons-art');
        }
    }

    public function panel_init() {

        if ( (isset($this->options['auto_check_update']) && $this->options['auto_check_update']=='1' ) || !isset($this->options['auto_check_update'])){
            add_filter('pre_set_site_transient_update_themes', array($this, 'check_update'));
        }else{
            delete_option($this->updateName);
        }

        if (is_admin() && isset($_GET['page']) && ( $_GET['page'] == 'wpcom-panel' ) ){

            // Load CSS
            wp_enqueue_style("panel", FRAMEWORK_URI."/assets/css/panel.css", false, FRAMEWORK_VERSION, "all");
            wp_enqueue_style( 'wp-color-picker' );

            // Load JS
            wp_enqueue_script("panel", FRAMEWORK_URI."/assets/js/panel.js", array('jquery', 'jquery-ui-core', 'wp-color-picker'), FRAMEWORK_VERSION, true);
            wp_enqueue_media();
        }
    }

    public function panel_admin(){


?>
        <div class="wrap" id="wpcom-panel">
            <form class="form-horizontal" id="wpcom-panel-form" method="post" action="">
                <?php wp_nonce_field( 'wpcom_theme_options', 'wpcom_theme_options_nonce' ); ?>
                <div id="wpcom-panel-header" class="clearfix">
                    <div class="logo pull-left">
                        <h3 class="panel-title">主题设置<small><?php echo $this->get_current_theme(1);?></small></h3>
                    </div>
                    <div class="pull-right">
                        <?php echo apply_filters('wpcom_panel_docs_link', '<a class="button" target="_blank" href="https://www.wpcom.cn/docs"><i class="fa fa-file-text-o"></i> 使用文档</a>'); ?>
                    </div>
                </div><!--#wpcom-panel-header-->

                <div id="wpcom-panel-main">
                    <ul id="wpcom-panel-nav" class="nav nav-pills nav-stacked" role="tablist">
                        <?php $this->get_menu();?>
                    </ul>

                    <div id="wpcom-panel-content" class="tab-content">
                        <?php $this->get_tab();?>
                    </div>
                </div><!--#wpcom-panel-main-->

                <div class="wpcom-panel-save clearfix">
                    <div class="col-xs-7" id="alert-info"></div>
                    <div class="col-xs-5 wpcom-panel-btn">
                        <button id="wpcom-panel-reset" type="button" data-loading-text="正在重置..."class="button submit-button reset-button">重置设置</button>
                        <button id="wpcom-panel-submit" type="button"  data-loading-text="正在保存..." class="button button-primary">保存设置</button>
                    </div>
                </div><!--.wpcom-panel-save-->
            </form>
        </div><!--.wrap-->
    <?php }
    public function panel_callback(){
        $post = isset($_POST['data']) ? $_POST['data'] : '';
        wp_parse_str($post, $data);

        if ( ! isset( $data['wpcom_theme_options_nonce'] ) )
            return ;

        $nonce = $data['wpcom_theme_options_nonce'];

        if ( ! wp_verify_nonce( $nonce, 'wpcom_theme_options' ) )
            return ;

        unset($data['wpcom_theme_options_nonce']);
        unset($data['_wp_http_referer']);

        // Delete theme options
        if(isset($data['reset'])&&$data['reset']==true){

            // Delete `reset` from array
            unset($data['reset']);

            // Return html
            if($this->remove_theme_options( $data )){
                $output = array(
                    'errcode' => 0,
                    'errmsg' => '重置成功，主题设置信息已恢复初始状态~'
                );
            }else{
                $save = false;
                foreach($data as $key => $value){
                    if( isset($this->options[$key]) && $this->options[$key]!=$value ){
                        $save = true;
                    }
                }
                if($save==false){
                    $output = array(
                        'errcode' => 1,
                        'errmsg' => '已经是初始状态了，不需要重置了~'
                    );
                }else{
                    $output = array(
                        'errcode' => 2,
                        'errmsg' => '重置失败，请稍后再试！'
                    );
                }
            }
            echo wp_json_encode($output);
            exit;
        }

        if($this->set_theme_options( $data )){
            $output = array(
                'errcode' => 0,
                'errmsg' => '设置保存成功~'
            );
            do_action( 'wpcom_options_updated' );
        }else{
            $save = false;
            foreach($data as $key => $value){
                if( isset($this->options[$key]) && $this->options[$key]!=$value ){
                    $save = true;
                }
            }
            if($save==false){
                $output = array(
                    'errcode' => 1,
                    'errmsg' => '额，你好像什么也没改呢？'
                );
            }else{
                $output = array(
                    'errcode' => 2,
                    'errmsg' => 'Sorry~ 提交失败了，可以再提交一次试试~'
                );
            }
        }
        echo wp_json_encode($output);
        exit;
    }
    private function _get_settings(){
        if( !isset($this->_settings) ) {
            $ops = base64_decode(get_option('izt_' . THEME_ID . '_panel'));
            //$token = '$P$BxxE.GsHMPKOUW.a3AHabXYCs1K41r0';
            //$ops = base64_decode(str_replace(md5(THEME_ID) . md5($token), '', $ops));
            $this->_settings = json_decode($ops);
        }
        return $this->_settings;
    }

    private function get_panel_settings(){
        $settings = $this->_get_settings();
        if( $settings && $settings->theme ) return $settings->theme;
    }

    public function get_meta_settings(){
        $res = new stdClass();
        if( $mos = $this->_get_settings() ) {
            foreach ($mos as $k => $v) {
                if (!in_array($k, array('theme', 'plugin', 'taxonomy', 'category_tpl', 'seo', 'demo'))) {
                    $res->{$k} = $v;
                }
            }
        }
        return $res;
    }

    public function get_seo_settings(){
        $settings = $this->_get_settings();
        if( $settings && $settings->seo) return $settings->seo;
    }

    public function get_required_plugin(){
        $settings = $this->_get_settings();
        if( $settings && isset($settings->plugin) ) return $settings->plugin;
    }

    public function get_taxonomy_settings(){
        $settings = $this->_get_settings();
        if( $settings && isset($settings->taxonomy) ) return $settings->taxonomy;
    }

    public function get_category_tpl(){
        $settings = $this->_get_settings();
        if( $settings && isset($settings->taxonomy) && isset($settings->taxonomy->category) ){
            foreach ( $settings->taxonomy->category as $item ){
                if( $item->name == 'tpl' ) return $item->options;
            }
        }else{
            if( isset($settings->category_tpl) ) return $settings->category_tpl;
        }
    }

    public function get_demo_config(){
        $settings = $this->_get_settings();
        if( $settings && isset($settings->demo) ) return $settings->demo;
    }

    public function check_version(){
        $body = array('version'=>THEME_VERSION,'email' => get_option('izt_theme_email'),'home' => get_option('siteurl'),'themer' => FRAMEWORK_VERSION);
        echo $this->send_request('check', $body);
        if(isset($this->options['auto_check_update']) && $this->options['auto_check_update']=='1')
            $this->check_update(0);
        exit;
    }

    private function theme_update(){
        global $theme_updated;
        if(isset($theme_updated) && $theme_updated){ // 防多次请求
            return false;
        }else{
            $theme_updated = 1;
        }
        $settings = $this->_get_settings();
        if($settings && isset($settings->theme)){
            $count = count($settings->theme)-1;
            $version = $settings->theme[$count]->version;
            $current_ver = $this->theme_version();
            if( version_compare($version, $current_ver) < 0 ) {
                $body = array('email'=>get_option('izt_theme_email'), 'token'=>get_option('izt_theme_token'), 'version'=>$current_ver, 'home'=>get_option('siteurl'), 'themer' => $this->framework_version());
                $this->send_request('update', $body);
            }
        }
    }

    private function theme_version(){
        if( function_exists('file_get_contents') ){
            $files = @file_get_contents( get_template_directory() . '/functions.php' );
            preg_match('/define\s*?\(\s*?[\'|"]THEME_VERSION[\'|"],\s*?[\'|"](.*)[\'|"].*?\)/i', $files, $matches);
            if( isset($matches[1]) && $matches[1] ){
                return trim($matches[1]);
            }
        }
        return THEME_VERSION;
    }

    private function framework_version(){
        if( function_exists('file_get_contents') ){
            $files = @file_get_contents( FRAMEWORK_PATH . '/load.php' );
            preg_match('/define\s*?\(\s*?[\'|"]FRAMEWORK_VERSION[\'|"],\s*?[\'|"](.*)[\'|"].*?\)/i', $files, $matches);
            if( isset($matches[1]) && $matches[1] ){
                return trim($matches[1]);
            }
        }
        return FRAMEWORK_VERSION;
    }

    private function send_request($type, $body, $method='POST'){
        $url = 'http://www.wpcom.cn/authentication/'.$type.'/'.THEME_ID;
        $result = wp_remote_request($url, array('method' => $method, 'timeout' => 30, 'body'=>$body));
        if(is_array($result)){
            return $result['body'];
        }
    }

    public function get_theme_options() {
        return get_option( 'izt_theme_options' );
    }

    public function set_theme_options( $data ) {
        foreach($data as $key => $value){
            $this->options[$key] = $value;
        }
        return update_option( "izt_theme_options", $this->options );
    }

    public function remove_theme_options( $data ) {
        foreach($data as $key => $value){
            unset($this->options[$key]);
        }
        return update_option( "izt_theme_options", $this->options );
    }

    private function get_menu(){
        $output = '';
        $i=-1;
        $active = 0;
        if(isset($_COOKIE['wpcom_panel_nav']) && $_COOKIE['wpcom_panel_nav'])
            $active = $_COOKIE['wpcom_panel_nav'];
        foreach($this->settings as $index => $menu){
            if(isset($menu->require) && $menu->require && !function_exists($menu->require)) {
                continue;
            }else{
                $i++;
            }
            $output .= '<li';
            $output .= $i==$active ? ' class="active"' : '';
            $output .= '><a href="#tab'.$index.'" role="tab" data-toggle="tab"><i class="fa fa-'.$menu->icon.'"></i> '.$menu->title.'</a></li>';
        }
        echo $output;
    }

    private function get_tab(){
        $i=-1;
        $active = 0;
        if(isset($_COOKIE['wpcom_panel_nav']) && $_COOKIE['wpcom_panel_nav'])
            $active = $_COOKIE['wpcom_panel_nav'];
        foreach($this->settings as $index => $tab){
            if(isset($tab->require) && $tab->require && !function_exists($tab->require)) {
                continue;
            }else{
                $i++;
            }
            echo '<div class="tab-pane fade in';
            echo $i==$active?' active':'';
            echo '" id="tab'.$index.'">';
            $this->get_tab_html($index);
            echo '</div>';
        }
    }

    private function get_tab_html($i){
        $j = 0;
        foreach ($this->settings[$i]->option as $option) {
            $this->get_type_html($option,$j);
            $j++;
        }
    }

    private function get_type_html($option, $i, $repeat='-1'){
        $type = $option->type;
        $title = isset($option->title)?$option->title:'';
        $desc = isset($option->desc)?$option->desc:'';
        $name = isset($option->name)?$option->name:'';
        $id = isset($option->id)?$option->id:$name;
        $rows = isset($option->rows)?$option->rows:3;
        $tax = isset($option->tax)?$option->tax:'category';
        $value = isset($option->std)?$option->std:'';
        if($repeat>-1){
            $value = isset($this->options[$option->oname]) && isset($this->options[$option->oname][$repeat]) ? $this->options[$option->oname][$repeat] : $value;
        }else{
            $value = isset($this->options[$name]) ? $this->options[$name] : $value;
        }
        $notice = $desc?'<small class="input-notice">'.$desc.'</small>':'';

        switch ($type) {
            case 'title':
                $first = $i==0?' section-hd-first':'';
                echo '<div class="section-hd'.$first.'"><h3 class="section-title">'.$title.' <small>'.$desc.'</small></h3></div>';
                break;

            case 'text':
                echo '<div class="form-group clearfix"><label for="wpcom_'.$id.'" class="col-sm-2 control-label">'.$title.'</label><div class="col-sm-8"><input type="text" class="form-control" id="wpcom_'.$id.'" name="'.$name.'" value="'.esc_attr($value).'">'.$notice.'</div></div>';
                break;

            case 'radio':
                $html = '';
                foreach ($option->options as $opk=>$opv) {
                    $opk = $opk==='_empty_'?'':$opk;
                    $opk = $opk===0?'0':$opk;
                    $html.=$opk==$value?'<label class="radio-inline"><input type="radio" name="'.$name.'" checked value="'.$opk.'">'.$opv.'</label>':'<label class="radio-inline"><input type="radio" name="'.$name.'" value="'.$opk.'">'.$opv.'</label>';
                }
                echo '<div class="form-group clearfix"><label for="wpcom_'.$id.'" class="col-sm-2 control-label">'.$title.'</label><div class="col-sm-8">'.$html . $notice.'</div></div>';
                break;

            case 'checkbox':
                $html = '';
                foreach ($option->options as $opk=>$opv) {
                    $checked = '';
                    $opk = $opk==='_empty_'?'':$opk;
                    $opk = $opk===0?'0':$opk;

                    if(is_array($value)){
                        foreach($value as $v){
                            if($opk==$v) $checked = ' checked';
                        }
                    }else{
                        if($opk==$value) $checked = ' checked';
                    }
                    $html .= '<label class="checkbox-inline"><input type="checkbox" name="'.$name.'[]"'.$checked.' value="'.$opk.'">'.$opv.'</label>';
                }
                echo '<div class="form-group clearfix"><label for="wpcom_'.$id.'" class="col-sm-2 control-label">'.$title.'</label><div class="col-sm-8">'.$html . $notice.'</div></div>';
                break;

            case 'checkbox_sort':
                $html = '';
                $value = $value ? $value : array();
                $option->options = (array) $option->options;
                foreach ($value as $item) {
                    $html.='<label class="checkbox-inline"><input name="'.$name.'[]" checked type="checkbox" value="'.$item.'"> '.$option->options[$item].'</label>';
                }
                foreach ($option->options as $opk => $opv) {
                    $opk = $opk==='_empty_'?'':$opk;
                    $opk = $opk===0?'0':$opk;
                    if(!in_array($opk, $value)){
                        $html.='<label class="checkbox-inline"><input name="'.$name.'[]" type="checkbox" value="'.$opk.'"> '.$opv.'</label>';
                    }
                }
                echo '<div class="form-group clearfix"><label for="wpcom_'.$id.'" class="col-sm-2 control-label">'.$title.'</label><div class="col-sm-8"><div class="cat-checkbox-list j-cat-sort" data-name="'.$name.'">'.$html.'</div><div>'.$notice.'</div></div></div>';
                break;

            case 'info':
                echo '<div class="form-group clearfix"><label class="col-sm-2 control-label">'.$title.'</label><div class="col-sm-8" style="padding-top:7px;">'.$value . $notice.'</div></div>';
                break;

            case 'alert':
                echo '<div class="clearfix"><div class="alert alert-warning" style="margin:0 0 10px;">'.$value.'</div></div>';
                break;

            case 'version':
                echo '<div class="form-group clearfix"><label class="col-sm-2 control-label">'.$title.'</label><div class="col-sm-8" style="padding-top:7px;">'.THEME_VERSION.' <a class="check-version" id="j-check-version" href="javascript:;">检查更新</a>'.$notice.'</div></div>';
                break;

            case 'select':
                $html = '<option value="">--请选择--</option>';
                foreach ($option->options as $opk=>$opv) {
                    $opk = $opk==='_empty_'?'':$opk;
                    $opk = $opk===0?'0':$opk;
                    $html.=$opk==$value?'<option selected value="'.$opk.'">'.$opv.'</option>':'<option value="'.$opk.'">'.$opv.'</option>';
                }
                echo '<div class="form-group clearfix"><label for="wpcom_'.$id.'" class="col-sm-2 control-label">'.$title.'</label><div class="col-sm-8"><select class="form-control" id="wpcom_'.$id.'" name="'.$name.'">'.$html.'</select>'.$notice.'</div></div>';
                break;

            case 'textarea':
                echo '<div class="form-group clearfix"><label for="wpcom_'.$id.'" class="col-sm-2 control-label">'.$title.'</label><div class="col-sm-8"><textarea class="form-control" rows="'.$rows.'" id="wpcom_'.$id.'" name="'.$name.'">'.esc_html($value).'</textarea>'.$notice.'</div></div>';
                break;

            case 'editor':
                echo '<div class="form-group clearfix"><label for="wpcom_'.$id.'" class="col-sm-2 control-label">'.$title.'</label><div class="col-sm-10">';
                wp_editor( wpautop( $value ), 'wpcom_'.$id, WPCOM::editor_settings(array('textarea_name'=>$name, 'textarea_rows'=>$rows)) );
                echo $notice.'</div></div>';
                break;

            case 'upload':
                echo '<div class="form-group clearfix"><label for="wpcom_'.$id.'" class="col-sm-2 control-label">'.$title.'</label><div class="col-sm-8"><input type="text" class="form-control" id="wpcom_'.$id.'" name="'.$name.'" value="'.esc_attr($value).'">'.$notice.'</div><div class="col-sm-2"><button id="wpcom_'.$id.'_upload" type="button" class="button upload-btn"><i class="fa fa-image"></i> 上传</button></div></div>';
                break;

            case 'color':
                $value = WPCOM::color($value);
                echo '<div class="form-group clearfix"><label for="wpcom_'.$id.'" class="col-sm-2 control-label">'.$title.'</label><div class="col-sm-8"><input class="color-picker" type="text"  name="'.$name.'" value="'.esc_attr($value).'">'.$notice.'</div></div>';
                break;

            case 'page':
                $html = '<option value="">--请选择--</option>';
                $pages = $this->get_all_pages();
                foreach ($pages as $page) {
                    $html.=$page['ID']==$value?'<option selected value="'.$page['ID'].'">'.$page['title'].'</option>':'<option value="'.$page['ID'].'">'.$page['title'].'</option>';
                }
                echo '<div class="form-group clearfix"><label for="wpcom_'.$id.'" class="col-sm-2 control-label">'.$title.'</label><div class="col-sm-8"><select class="form-control" id="wpcom_'.$id.'" name="'.$name.'">'.$html.'</select>'.$notice.'</div></div>';
                break;

            case 'cat_single':
                $option->options = WPCOM::category($tax);
                $option->type = 'select';
                $this->get_type_html($option, $i);
                break;

            case 'cat_multi':
                $option->options = WPCOM::category($tax);
                $option->type = 'checkbox';
                $this->get_type_html($option, $i);
                break;

            case 'cat_multi_sort':
                $option->options = WPCOM::category($tax);
                $option->type = 'checkbox_sort';
                $this->get_type_html($option, $i);
                break;

            case 'toggle':
                echo '<div class="form-group clearfix"><label for="wpcom_'.$id.'" class="col-sm-2 control-label">'.$title.'</label><div class="col-sm-8 toggle-wrap">';
                if($value=='1'){
                    echo '<div class="toggle active"></div>';
                }else{
                    echo '<div class="toggle"></div>';
                }
                echo '<input type="hidden" id="wpcom_'.$id.'" name="'.$name.'" value="'.esc_attr($value).'">'.$notice.'</div></div>';
                break;

            case 'repeat':
                /*
                 * $this->options 保存的数据
                 * $this->options[$option->options[0]->name] 重复数据的第一个属性保持的值
                 * 每个属性根据添加个数会有多个，以数组形式保存
                 */
                $len = count(isset($this->options[$option->options[0]->name])?$this->options[$option->options[0]->name]:array());
                $len = $len ? $len : 1;
                $index = array();
                if(isset($this->options[$option->options[0]->name])){
                    foreach ($this->options[$option->options[0]->name] as $a=>$b) {
                        $index[] = $a;
                    }
                }
                echo '<div class="wpcom-panel-repeat">';
                for($i=0; $i<$len; $i++) {
                    $j = isset($index[$i]) ? $index[$i] : $i;
                    echo '<div class="repeat-wrap" data-id="'.$i.'">';
                    $x = 0;
                    $arg = new stdClass();
                    foreach ($option->options as $o) {
                        foreach($o as $k=>$v){
                            $arg->{$k} = $v;
                        }
                        $arg->id = $o->name . '_' . $i;
                        $arg->name = $o->name . '['.$i.']';
                        $arg->oname = $o->name;
                        $this->get_type_html($arg, 1, $j);
                        $x++;
                    }
                    echo $i==0? '</div>':'<div class="repeat-action"><div class="repeat-item repeat-up j-repeat-up"><i class="dashicons dashicons-arrow-up-alt"></i></div><div class="repeat-item repeat-down j-repeat-down"><i class="dashicons dashicons-arrow-down-alt"></i></div><div class="repeat-item repeat-del j-repeat-del"><i class="dashicons dashicons-no-alt"></i></div></div></div>';
                }
                echo '<div class="repeat-btn-wrap"><button type="button" class="button j-repeat-add" id="wpcom_'.$name.'"><i class="dashicons dashicons-plus"></i> 添加选项</button></div></div>';
                break;
            default:
                break;
        }
    }

    function get_all_pages(){
        $pages = get_pages(array('post_type' => 'page','post_status' => 'publish'));
        $res = array();
        if($pages){
            foreach ($pages as $page) {
                $p = array(
                    'ID' => $page->ID,
                    'title' => $page->post_title
                );
                $res[] = $p;
            }
        }
        return $res;
    }

    public function check_update($value){

        if ($value && empty( $value->checked ) )
            return $value;

        if ( !current_user_can('update_themes' ) )
            return $value;

        if ( !$this->automaticCheckDone ) {
            $body = array('email' => get_option('izt_theme_email'), 'token' => get_option('izt_theme_token'), 'version' => THEME_VERSION, 'home' => get_option('siteurl'), 'themer' => FRAMEWORK_VERSION);
            $req = $this->send_request('notify', $body);
            $this->automaticCheckDone = true;

            $this->theme_update();
        }

        if ( !$value ) { // 手动点击更新
            $last_update = get_site_transient( 'update_themes' );
            if ( ! is_object($last_update) ) $last_update = new stdClass;
            if ( !isset($last_update->checked) || !$last_update->checked ) {
                $installed_themes = wp_get_themes();
                $checked = array();
                foreach ( $installed_themes as $theme ) {
                    $checked[ $theme->get_stylesheet() ] = $theme->get('Version');
                }
                $last_update->checked = $checked;
                if(!isset($last_update->last_checked)) $last_update->last_checked = time();
            }

            $this->theme_update();

            return set_site_transient( 'update_themes', $last_update, 3 * HOUR_IN_SECONDS );
        }

        global $theme_update_state;
        if(!isset($theme_update_state)) $theme_update_state = get_option($this->updateName);

        if ( !empty($theme_update_state) && isset($theme_update_state->update) && !empty($theme_update_state->update) ){
            $update = $theme_update_state->update;
            $value->response[$this->get_current_theme()] = array(
                'new_version' => $update->version,
                'url' => $update->url,
                'package' => $update->package
            );
        }

        return $value;
    }

    public function updated(){
        delete_option($this->updateName);
        $this->theme_update();
    }

    private function get_current_theme( $name=false ){
        $theme = wp_get_theme();
        if($theme->get('Template')){
            return $name ? $theme->parent()->get('Name') : $theme->template;
        }else{
            return $name ? $theme->get('Name') : $theme->stylesheet;
        }
    }

    public function theme_options_demo_export(){
        if(current_user_can( 'edit_theme_options' )){
            header( "Content-type:  application/json" );
            header( 'Content-Disposition: attachment; filename="demo-options.json"' );
            $res = array();

            $nav_menu_locations = get_theme_mod('nav_menu_locations');
            $res['menu'] = array();
            if($nav_menu_locations){
                foreach($nav_menu_locations as $k => $nav){
                    if($term = get_term($nav, 'nav_menu')) $res['menu'][$k] = $term->slug;
                }
            }

            $sidebars_widgets = get_option('sidebars_widgets');
            $res['widgets'] = array();
            if($sidebars_widgets){
                $widgets = array();
                foreach($sidebars_widgets as $k => $wgts){
                    if($k!='wp_inactive_widgets' && $k!='array_version' && !empty($wgts)){
                        $res['widgets'][$k] = array();
                        foreach($wgts as $w){
                            preg_match('/(.*)-(\d+)$/i', $w, $matches);
                            if(!isset($widgets[$matches[1]])) $widgets[$matches[1]] = get_option('widget_'.$matches[1]);
                            $res['widgets'][$k][$w] = $widgets[$matches[1]][$matches[2]];
                            if($matches[1]=='nav_menu'){
                                $mid = $widgets['nav_menu'][$matches[2]]['nav_menu'];
                                if($term2 = get_term($mid, 'nav_menu')){
                                    $res['widgets'][$k][$w]['nav_menu'] = $term2->slug;
                                }
                            }
                        }
                    }
                }
            }

            // 其他信息，比如分类、首页
            $res['show_on_front'] = get_option( 'show_on_front' );
            if($res['show_on_front']=='page'){
                $page = get_post(get_option( 'page_on_front' ));
                $res['page_on_front'] = $page->post_name;
            }

            $res['options'] = $this->options;
            echo json_encode($res);
            exit;
        }
    }
}

$wpcom_panel = new WPCOM_Panel();