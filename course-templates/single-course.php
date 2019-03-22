<?php get_header(); ?>

<?php if ( have_posts() ) : ?>

  <div class="container">
    
    <?php while ( have_posts() ) : the_post(); ?>

      <div id="single-post">

        <div class="row page-heading">

          <div class="col-xs-12">

            <h1 class="page-title"><?php the_title(); ?></h1>

            <br><p style="margin-bottom:12px; font-size:16px; color:#595D63;"><b>Date: </b>
                    
              <?php $startdate = get_post_meta( $post->ID, '_start_date', true );
                $enddate = get_post_meta( $post->ID, '_end_date', true );
                $starttime = get_post_meta( $post->ID, '_start_time', true );
                $endtime = get_post_meta( $post->ID, '_end_time', true );
              
                if ($startdate) {
                  echo $startdate;
                  if ($starttime) echo ' (from ' . $starttime . ')';
                  if ( $enddate && $enddate !== $startdate ) echo ' - ' . $enddate;
                  if ($endtime) echo ' (till ' . $endtime . ')';
                } else {
                  echo 'N/A';
                }
                
              ?>
              
              <b>Location: </b>
              
              <?php $terms = get_the_terms( $post->ID, 'event_location' );

                $loc = array();

                if ($terms){
                  foreach ( $terms as $term ) {
                    $loc = $term->name;
                  }
                }
                
                if ($loc) {
                  echo $loc;
                } else{
                  echo 'N/A';
                }
                
              ?>
            
            </p>

          </div>

          <div class="clearfix"></div>

        </div>

        <div class="row">

          <div class="col-xs-12">

            <p><?php if (has_excerpt()) the_excerpt() ?></p>
            <?php //Import from old database has leading and trailing quotes, trim to fix this
            echo wpautop(trim(get_the_content(), '"')); ?>

            <!--<p><a href="<?php echo get_post_meta( $post->ID, 'more_details', true ); ?>"><?php echo get_post_meta( $post->ID, 'more_details', true ); ?></a></p>-->
          </div>

          <div class="clearfix"></div>

        </div>

      </div>

    <?php endwhile; ?>
    
  </div>

<?php endif; ?>

<?php get_footer();