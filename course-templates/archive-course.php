<?php get_header(); ?>

<div id="main">

  <div class="container">

    <?php if ( have_posts() ) : ?>

      <div id="page-archive">

        <h1>Events</h1>

        <?php while ( have_posts() ) :the_post(); ?>

          <?php $sticky = ''; ?>
          <?php if ( is_sticky() ) : ?>
            <?php $sticky = 'sticky'; ?>
          <?php endif ?>        

          <div class="post-block type-event <?php echo $sticky ?>">

            <?php if ( has_post_thumbnail() ): ?>
              <div class="image-wrapper">
                <?php the_post_thumbnail('full'); ?>
              </div>
            <?php endif; ?>

            <div class="content-wrapper">

              <h2 class="post-title"><a href="<?php the_permalink() ?>"><?php the_title() ?></a></h2>
              
              <p><b>Date: </b>              
                <?php $startdate = get_post_meta( $post->ID, '_start_date', true );
                  $enddate = get_post_meta( $post->ID, '_end_date', true );                
                  if ($startdate) {
                    echo $startdate;
                    if ( $enddate && $enddate !== $startdate ) echo ' - ' . $enddate;
                  } else{
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

              <h3 class="excerpt"><?php the_excerpt() ?></h3>

              <a href="<?php the_permalink() ?>">View full story</a>

            </div>

          </div>

        <?php endwhile; ?>

      </div>

    <?php else: ?>

      <div class="no-content">
        
        <h1>No Events Found</h1>
        <p>No events matched your search criteria. </p>
        <p>Please ammend your search terms or check back later.</p>
        
      </div>

    <?php endif; ?>
    
  </div>

</div>

<?php get_footer(); ?>