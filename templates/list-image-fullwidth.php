<li class="col-xs-6 col-md-3 col-sm-6 p-item">
    <div class="p-item-wrap">
        <a class="thumb" href="<?php echo esc_url( get_permalink() )?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank">
            <?php the_post_thumbnail();?>
        </a>
        <h2 class="title">
            <a href="<?php echo esc_url( get_permalink() )?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank"><?php the_title();?></a>
        </h2>
    </div>
</li>