<?php global $options; ?>
</div>
<footer class="footer">
    <div class="container">
        <div class="clearfix">
            <?php if(isset($options['footer_logo']) && trim($options['footer_logo'])){ ?>
            <div class="footer-col footer-col-logo">
                <img src="<?php echo esc_url(trim($options['footer_logo'])); ?>" alt="<?php echo esc_attr(get_bloginfo("name")); ?>">
            </div>
            <?php } ?>
            <div class="footer-col footer-col-copy">
                <?php wp_nav_menu( array( 'container' => false, 'depth'=> 1, 'theme_location' => 'footer', 'items_wrap' => '<ul class="footer-nav hidden-xs">%3$s</ul>', 'fallback_cb' => 'WPCOM_Nav_Walker::fallback' ) ); ?>
                <div class="copyright">
                    <?php echo ($copyright=isset($options['copyright'])?$options['copyright']:'')?$copyright:'Copyright © 2018 '.get_bloginfo("name").' 版权所有  Powered by <a href="http://www.wpcom.cn" target="_blank">WordPress</a>'?> <?php echo (isset($options['tongji'])?$options['tongji']:'');?>
                </div>
            </div>
            <div class="footer-col footer-col-sns">
                <div class="footer-sns">
                    <?php if(isset($options['ios']) && trim($options['ios'])){ ?>
                        <a class="sns-wx" href="javascript:;">
                            <i class="fa fa-apple"></i>
                            <span style="background-image:url(<?php echo trim($options['ios']); ?>);"></span>
                        </a>
                    <?php } ?>
                    <?php if(isset($options['android']) && trim($options['android'])){ ?>
                        <a class="sns-wx" href="javascript:;">
                            <i class="fa fa-android"></i>
                            <span style="background-image:url(<?php echo trim($options['android']); ?>);"></span>
                        </a>
                    <?php } ?>
                    <?php if(isset($options['weixin']) && trim($options['weixin'])){ ?>
                        <a class="sns-wx" href="javascript:;">
                            <i class="fa fa-weixin"></i>
                            <span style="background-image:url(<?php echo trim($options['weixin']); ?>);"></span>
                        </a>
                    <?php } ?>
                    <?php if(isset($options['weibo']) && trim($options['weibo'])){ ?>
                        <a target="_blank" href="<?php echo trim($options['weibo'])?>" rel="nofollow"><i class="fa fa-weibo"></i></a>
                    <?php } ?>
                    <?php if(isset($options['qq_weibo']) && trim($options['qq_weibo'])){ ?>
                        <a target="_blank" href="<?php echo trim($options['qq_weibo'])?>" rel="nofollow"><i class="fa fa-tencent-weibo"></i></a>
                    <?php } ?>
                    <?php if(isset($options['facebook']) && trim($options['facebook'])){ ?>
                        <a target="_blank" href="<?php echo trim($options['facebook'])?>" rel="nofollow"><i class="fa fa-facebook"></i></a>
                    <?php } ?>
                    <?php if(isset($options['twitter']) && trim($options['twitter'])){ ?>
                        <a target="_blank" href="<?php echo trim($options['twitter'])?>" rel="nofollow"><i class="fa fa-twitter"></i></a>
                    <?php } ?>
                    <?php if(isset($options['google_plus']) && trim($options['google_plus'])){ ?>
                        <a target="_blank" href="<?php echo trim($options['google_plus'])?>" rel="nofollow"><i class="fa fa-google-plus"></i></a>
                    <?php } ?>
                    <?php if(isset($options['linkedin']) && trim($options['linkedin'])){ ?>
                        <a target="_blank" href="<?php echo trim($options['linkedin'])?>" rel="nofollow"><i class="fa fa-linkedin"></i></a>
                    <?php } ?>
                    <?php if(isset($options['youtube']) && trim($options['youtube'])){ ?>
                        <a target="_blank" href="<?php echo trim($options['youtube'])?>" rel="nofollow"><i class="fa fa-youtube"></i></a>
                    <?php } ?>
                    <?php if(isset($options['instagram']) && trim($options['instagram'])){ ?>
                        <a target="_blank" href="<?php echo $options['instagram']?>" rel="nofollow"><i class="fa fa-instagram"></i></a>
                    <?php } ?>
                    <?php if(isset($options['pinterest']) && trim($options['pinterest'])){ ?>
                        <a target="_blank" href="<?php echo trim($options['pinterest'])?>" rel="nofollow"><i class="fa fa-pinterest"></i></a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</footer>
<div class="action"<?php echo isset($options['action_top'])?' style="top:'.$options['action_top'].';"':''?>>
    <?php if(isset($options['contact_text']) && trim($options['contact_text'])){ ?>
        <div class="a-box contact">
            <div class="contact-wrap">
                <h3 class="contact-title"><?php _e('Contact Us', 'wpcom');?></h3>
                <?php echo wpautop($options['contact_text']);?>
            </div>
        </div>
    <?php } ?>
    <?php if(isset($options['wechat'])&&$options['wechat']){ ?>
        <div class="a-box wechat">
            <div class="wechat-wrap">
                <img src="<?php echo $options['wechat']?>" alt="QR code">
            </div>
        </div>
    <?php } ?>
    <?php if(isset($options['share'])&&$options['share']=='1'){ ?>
        <div class="bdsharebuttonbox" data-tag="global"><a href="#" class="a-box share<?php echo get_locale()=='zh_CN'?' bds_more':'';?>" data-cmd="more"></a></div>
    <?php } ?>
    <div class="a-box gotop" id="j-top" style="display: none;"></div>
</div>
<?php
if(isset($options['footer_bar_icon']) && !empty($options['footer_bar_icon']) && $options['footer_bar_icon'][0]){
    ?>
    <div class="footer-bar">
        <?php $i = 0; foreach($options['footer_bar_icon'] as $fb){
            $target = isset($options['footer_bar_target']) && $options['footer_bar_target'][$i]=='0' ? '' : ' target="_blank"'; ?>
            <div class="fb-item">
                <a href="<?php echo $options['footer_bar_url'][$i];?>"<?php echo $target;?>>
                    <i class="fa fa-<?php echo $fb;?>"></i>
                    <span><?php echo $options['footer_bar_title'][$i];?></span>
                </a>
            </div>
            <?php $i++; } ?>
    </div>
<?php }else{ echo '<style>.footer{padding-bottom: 35px;}</style>';} wp_footer();?>
<?php if(get_locale()=='zh_CN'){ ?>
    <script>var $imageEl=document.querySelector('meta[property="og:image"]');window._bd_share_config={"common":{"bdSnsKey":{},"bdText":"","bdMini":"2","bdMiniList":["mshare","tsina","weixin","qzone","sqq","douban","fbook","twi","bdhome","tqq","tieba","mail","youdao","print"],"bdPic":$imageEl?$imageEl.getAttribute('content'):"","bdStyle":"1","bdSize":"16"},"share":[{"tag" : "single", "bdSize" : 16}, {"tag" : "global","bdSize" : 16,bdPopupOffsetLeft:-227}],url:'<?php echo get_template_directory_uri()?>'};with(document)0[(getElementsByTagName('head')[0]||body).appendChild(createElement('script')).src='<?php echo get_template_directory_uri()?>/js/share.js?v=89860593.js?cdnversion='+~(-new Date()/36e5)];</script>
<?php } else { ?>
    <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-542188574c8ebd62"></script>
    <script>setup_share();</script>
<?php } ?>
</body>
</html>