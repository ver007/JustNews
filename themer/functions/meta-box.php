<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Meta {
    private $settings;

    public function __construct() {
        global $wpcom_panel;
        $this->settings = $wpcom_panel->get_meta_settings();
        if(!$this->settings) $this->settings = new stdClass();
        $wpcom_metas = apply_filters( 'wpcom_post_metas', array() );

        if(!empty($wpcom_metas)) {
            foreach ($wpcom_metas as $k => $v) {
                if (isset($this->settings->{$k})) { // 已存在，push进去
                    if (!is_array($this->settings->{$k})) { // 是否是数组，不是数组的话则只有一个数据组，需转为数组再插入数据
                        $this->settings->{$k} = array($this->settings->{$k});
                    }
                    if(isset($v['option'])){
                        array_push($this->settings->{$k}, json_decode(json_encode($v)));
                    }else if(is_array($v)){
                        foreach($v as $i){
                            array_push($this->settings->{$k}, json_decode(json_encode($i)));
                        }
                    }
                } else { // 不存在，新增
                    $this->settings->{$k} = json_decode(json_encode($v));
                }
            }
        }

        if($this->settings){
            $this->init_hooks();
        }
    }

    private function init_hooks(){
        add_action( 'load-post.php', array( $this, 'call_meta' ));
        add_action( 'load-post-new.php', array( $this, 'call_meta' ));
        add_action( 'add_meta_boxes', array( $this, 'set_metabox' ) );
        add_action( 'save_post', array( $this, 'save_metabox' ) );
    }

    public function call_meta() {
        wp_enqueue_style("meta", FRAMEWORK_URI."/assets/css/meta.css", false, FRAMEWORK_VERSION, "all");
        wp_enqueue_style( 'wp-color-picker' );

        wp_enqueue_script("meta", FRAMEWORK_URI."/assets/js/meta.js", array('jquery', 'wp-color-picker'), FRAMEWORK_VERSION, true);
    }

    /**
     * Add meta box for all post type if options exist.
     *
     * @uses add_meta_box
     */
    public function set_metabox(){
        foreach($this->settings as $type => $box){
            if(isset($box->title)) $box = array($box);

            if(is_array($box) && $box) add_meta_box('wpcom-metas', '主题自定义选项', array($this, 'metabox_html'), $type, 'normal', 'high', array('option' => $box));
        }
    }

    public function metabox_html( $post, $options ){
        $metas = $options['args']['option'];

        // Add an nonce field
        wp_nonce_field( 'wpcom_meta_box', 'wpcom_meta_box_nonce' );

        $tab = '<ul class="wpcom-meta-tab">';
        $content = '';
        $i = 0;
        foreach($metas as $meta){
            $tab .= '<li'.($i==0?' class="active"':'').'>'.$meta->title.'</li>';
            $content .= '<div class="wpcom-meta-box'.($i==0?' active':'').' clearfix">';
            foreach( $meta->option as $o){
                $content .= $this->get_type_html( $post->ID, $o );
            }
            $content .='</div>';
            $i++;
        }

        echo $tab.'</ul><div class="wpcom-meta-content">'.$content.'</div>';
    }

    private function get_type_html($post_id, $meta, $repeat='-1'){
        $type = $meta->type;
        $name = isset($meta->name) ? $meta->name : '';
        $id = isset($meta->id) ? $meta->id : $name;
        $title = isset($meta->title) ? $meta->title : '';
        $desc = isset($meta->desc) ? $meta->desc : '';
        $rows = isset($meta->rows)?$meta->rows:3;

        if(preg_match('/^_/', $name)){
            $value = get_post_meta( $post_id, $name, true );
        }else{
            if($repeat>-1){
                $val = get_post_meta( $post_id, 'wpcom_'.$meta->oname, true );
                $value = $val && isset($val[$repeat]) ? $val[$repeat] : '';
            }else{
                $value = get_post_meta( $post_id, 'wpcom_'.$name, true );
            }
        }

        $value = $value!='' ? $value : (isset($meta->value)?$meta->value:'');

        $output = '';
        switch ($type) {
            case 'title':
                $output .='<div class="form-group clearfix"><h3 class="meta-title">'.$title.' <small>'.$desc.'</small></h3></div>';
                break;

            case 'text':
                $output .= '<div class="form-group clearfix"><div class="form-label col-md-2"><label for="wpcom_'.$name.'">'.$title.'</label></div><div class="col-md-7"><input class="form-control" type="text" id="wpcom_'.$id.'" name="wpcom_'.$name.'" value="'.esc_attr($value).'"></div><div class="col-md-7 col-md-offset-2 meta-desc">'.$desc.'</div></div>';
                break;

            case 'textarea':
                $output .= '<div class="form-group clearfix"><div class="form-label col-md-2"><label for="wpcom_'.$name.'">'.$title.'</label></div><div class="col-md-7"><textarea class="form-control" id="wpcom_'.$id.'" name="wpcom_'.$name.'" rows="'.$rows.'">'.esc_html( $value, 1 ).'</textarea></div><div class="col-md-7 col-md-offset-2 meta-desc">'.$desc.'</div></div>';
                break;

            case 'upload':
                $output .= '<div class="form-group clearfix"><div class="form-label col-md-2"><label for="wpcom_'.$name.'">'.$title.'</label></div><div class="col-md-7"><input class="form-control" type="text" id="wpcom_'.$id.'" name="wpcom_'.$name.'" value="'.esc_attr($value).'"></div><div class="col-md-3"><button id="wpcom_'.$id.'_upload" type="button" class="button btn-upload"><i class="dashicons dashicons-admin-media"></i> 添加媒体</button></div><div class="col-md-7 col-md-offset-2 meta-desc">'.$desc.'</div></div>';
                break;

            case 'color':
                $value = wpcom::color($value);
                $output .= '<div class="form-group clearfix"><div class="form-label col-md-2"><label for="wpcom_'.$name.'">'.$title.'</label></div><div class="col-md-7"><input class="color-picker" type="text"  name="wpcom_'.$name.'" value="'.esc_attr($value).'"></div>'.$desc.'</div>';
                break;

            case 'toggle':
                $output .= '<div class="form-group clearfix"><div class="form-label col-md-2"><label for="wpcom_'.$name.'">'.$title.'</label></div><div class="col-md-7 toggle-wrap">';
                if($value=='1'){
                    $output .= '<div class="toggle active"></div>';
                }else{
                    $output .= '<div class="toggle"></div>';
                }
                $output .= '<input type="hidden" id="wpcom_'.$id.'" name="wpcom_'.$name.'" value="'.esc_attr($value).'"></div><div class="col-md-7 col-md-offset-2 meta-desc">'.$desc.'</div></div>';
                break;

            case 'radio':
                $html = '';
                foreach ($meta->options as $opk=>$opv) {
                    $opk = $opk==='_empty_'?'':$opk;
                    $opk = $opk===0?'0':$opk;
                    $html.=$opk==$value?'<label class="radio-inline"><input type="radio" name="wpcom_'.$name.'" checked value="'.$opk.'">'.$opv.'</label>':'<label class="radio-inline"><input type="radio" name="wpcom_'.$name.'" value="'.$opk.'">'.$opv.'</label>';
                }
                $output .= '<div class="form-group clearfix"><div class="form-label col-md-2"><label for="wpcom_'.$name.'">'.$title.'</label></div><div class="col-md-7">'.$html.'</div><div class="col-md-7 col-md-offset-2 meta-desc">'.$desc.'</div></div>';
                break;

            case 'checkbox':
                $html = '';
                foreach ($meta->options as $opk=>$opv) {
                    $checked = '';
                    if(is_array($value)){
                        foreach($value as $v){
                            if($opk==$v) $checked = ' checked';
                        }
                    }else{
                        if($opk==$value) $checked = ' checked';
                    }
                    $html .= '<label class="checkbox-inline"><input type="checkbox" name="wpcom_'.$name.'[]"'.$checked.' value="'.$opk.'">'.$opv.'</label>';
                }
                $output .= '<div class="form-group clearfix"><div class="form-label col-md-2"><label for="wpcom_'.$name.'">'.$title.'</label></div><div class="col-md-7">'.$html.'</div><div class="col-md-7 col-md-offset-2 meta-desc">'.$desc.'</div></div>';
                break;

            case 'select':
                $html = '';
                foreach ($meta->options as $opk=>$opv) {
                    $opk = $opk==='_empty_'?'':$opk;
                    $opk = $opk===0?'0':$opk;
                    $html.=$opk==$value?'<option selected value="'.$opk.'">'.$opv.'</option>':'<option value="'.$opk.'">'.$opv.'</option>';
                }
                $output .= '<div class="form-group clearfix"><div class="form-label col-md-2"><label for="wpcom_'.$name.'">'.$title.'</label></div><div class="col-md-7"><select class="form-control" id="wpcom_'.$id.'" name="wpcom_'.$name.'">'.$html.'</select></div><div class="col-md-7 col-md-offset-2 meta-desc">'.$desc.'</div></div>';
                break;
            case 'theme_settings':
                global $options;
                $html = '';
                $settings = isset($options[$meta->id_key]) ? $options[$meta->id_key] : array();
                $meta->options = isset($meta->options) ? json_decode( json_encode($meta->options), true ) : array();
                if($settings) {
                    foreach ($settings as $i => $val) {
                        if($val && $settings[$i]) {
                            $meta->options[$val] = $options[$meta->value_key][$i];
                        }
                    }
                }

                foreach ($meta->options as $opk=>$opv) {
                    $opk = $opk==='_empty_'?'':$opk;
                    $opk = $opk===0?'0':$opk;
                    $html.=($opk==$value)?'<option selected value="'.$opk.'">'.$opv.'</option>':'<option value="'.$opk.'">'.$opv.'</option>';
                }
                $output .= '<div class="form-group clearfix"><div class="form-label col-md-2"><label for="wpcom_'.$name.'">'.$title.'</label></div><div class="col-md-7"><select class="form-control" id="wpcom_'.$id.'" name="wpcom_'.$name.'">'.$html.'</select></div><div class="col-md-7 col-md-offset-2 meta-desc">'.$desc.'</div></div>';
                break;
            case 'repeat':
                $html = '<div class="wpcom-meta-repeat">';
                $html .= $title ? ('<div class="form-group clearfix"><h3 class="meta-title">'.$title.' <small>'.$desc.'</small></h3></div>') :'';
                $val = get_post_meta( $post_id, 'wpcom_'.$meta->options[0]->name, true );
                $len = count(isset($val) && !empty($val) ? $val : array());
                $len = $len ? $len : 1;
                if($val){
                    foreach ($val as $a=>$b) {
                        $index[] = $a;
                    }
                }

                for($i=0; $i<$len; $i++) {
                    $j = isset($index[$i]) ? $index[$i] : $i;
                    $html .= '<div class="repeat-wrap" data-id="'.$i.'">';
                    $x = 0;
                    $arg = new stdClass();
                    foreach ($meta->options as $o) {
                        foreach($o as $k=>$v){
                            $arg->{$k} = $v;
                        }
                        $arg->id = $o->name . '_' . $i;
                        $arg->name = $o->name . '['.$i.']';
                        $arg->oname = $o->name;
                        $html .= $this->get_type_html($post_id, $arg, $j);
                        $x++;
                    }
                    $html .= $i==0? '</div>':'<div class="repeat-action"><div class="repeat-item repeat-up j-repeat-up"><i class="dashicons dashicons-arrow-up-alt"></i></div><div class="repeat-item repeat-down j-repeat-down"><i class="dashicons dashicons-arrow-down-alt"></i></div><div class="repeat-item repeat-del j-repeat-del"><i class="dashicons dashicons-no-alt"></i></div></div></div>';
                }
                $html .= '<div class="repeat-btn-wrap"><button type="button" class="button j-repeat-add"><i class="dashicons dashicons-plus"></i> 添加选项</button></div></div>';
                $output .= $html;
                break;
            default:
                break;
        }
        return $output;
    }

    /**
     * Save the meta when the post is saved.
     */
    public function save_metabox($post_id){
        global $post;
        if($post && $post->ID!=$post_id) return false;

        if(isset($_POST['post_type'])){
            if(isset($this->settings->{$_POST['post_type']}->title)){
                $meta_boxes = $this->settings->{$_POST['post_type']}->option;
            }else{
                $meta_boxes = array();
                foreach($this->settings->{$_POST['post_type']} as $metas){
                    foreach($metas->option as $m){
                        $meta_boxes[] = $m;
                    }
                }
            }
        }
        if(!isset($meta_boxes)||!$meta_boxes) return false;

        // Check if our nonce is set.
        if ( ! isset( $_POST['wpcom_meta_box_nonce'] ) )
            return $post_id;

        $nonce = $_POST['wpcom_meta_box_nonce'];

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'wpcom_meta_box' ) )
            return $post_id;

        // If this is an autosave, our form has not been submitted,
        // so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return $post_id;

        // Check the user's permissions.
        if ( 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) )
                return $post_id;
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) )
                return $post_id;
        }

        $metas = get_post_meta( $post_id, '_wpcom_metas', true);
        $metas = is_array($metas) ? $metas : array();
        foreach ($meta_boxes as $meta) {
            if(preg_match('/^_/', $meta->name)){
                update_post_meta($post_id, $meta->name, stripslashes_deep( $_POST['wpcom_'.$meta->name] ) );
            }else{
                if($meta->type == 'repeat') {
                    foreach($meta->options as $m){
                        $value = stripslashes_deep( $_POST['wpcom_'.$m->name] );

                        if ( $value!='' )
                            $metas[$m->name] = $value;
                        else if ( isset($metas[$m->name]) )
                            unset($metas[$meta->name]);
                    }
                }else{
                    $value = stripslashes_deep( $_POST['wpcom_'.$meta->name] );

                    if ( $value!='' )
                        $metas[$meta->name] = $value;
                    else if ( isset($metas[$meta->name]) )
                        unset($metas[$meta->name]);
                }
                update_post_meta($post_id, '_wpcom_metas', $metas );
            }
        }
    }
}