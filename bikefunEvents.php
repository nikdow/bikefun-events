<?php
/**
 * Plugin Name: Bikefun Events
 * Plugin URI: http://www.cbdweb.net
 * Description: Modified from http://www.noeltock.com/web-design/wordpress/how-to-custom-post-types-for-events-pt-2/
 * Version: 1.0
 * Author: Nik Dow, CBDWeb
 * Author URI: http://www.cbdweb.net
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

// 1. Custom Post Type Registration (Events)

add_action( 'init', 'create_event_postype' );
 
function create_event_postype() {
 
    $labels = array(
        'name' => _x('Events', 'post type general name'),
        'singular_name' => _x('Event', 'post type singular name'),
        'add_new' => _x('Add New', 'events'),
        'add_new_item' => __('Add New Event'),
        'edit_item' => __('Edit Event'),
        'new_item' => __('New Event'),
        'view_item' => __('View Event'),
        'search_items' => __('Search Events'),
        'not_found' =>  __('No events found'),
        'not_found_in_trash' => __('No events found in Trash'),
        'parent_item_colon' => '',
    );

    $args = array(
        'label' => __('Events'),
        'labels' => $labels,
        'public' => true,
        'can_export' => true,
        'show_ui' => true,
        '_builtin' => false,
        'capability_type' => 'post',
        'menu_icon' => plugins_url( 'img/calendar.gif', __FILE__ ),
        'hierarchical' => false,
    //    'rewrite' => array( "slug" => "events" ),
        'rewrite' => false,
        'supports'=> array('title', 'thumbnail', 'editor', 'comments' ) ,
        'show_in_nav_menus' => true,
        'taxonomies' => array( 'tf_eventcategory', 'post_tag')
    );

    register_post_type( 'tf_events', $args);
 
}

function create_eventcategory_taxonomy() {
 
$labels = array(
    'name' => _x( 'Categories', 'taxonomy general name' ),
    'singular_name' => _x( 'Category', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Categories' ),
    'popular_items' => __( 'Popular Categories' ),
    'all_items' => __( 'All Categories' ),
    'parent_item' => null,
    'parent_item_colon' => null,
    'edit_item' => __( 'Edit Category' ),
    'update_item' => __( 'Update Category' ),
    'add_new_item' => __( 'Add New Category' ),
    'new_item_name' => __( 'New Category Name' ),
    'separate_items_with_commas' => __( 'Separate categories with commas' ),
    'add_or_remove_items' => __( 'Add or remove categories' ),
    'choose_from_most_used' => __( 'Choose from the most used categories' ),
);
 
register_taxonomy('tf_eventcategory','tf_events', array(
    'label' => __('Event Category'),
    'labels' => $labels,
    'hierarchical' => true,
    'show_ui' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'event-category' ),
));
}
 
add_action( 'init', 'create_eventcategory_taxonomy', 0 );

// 3. Show Columns

add_filter ("manage_edit-tf_events_columns", "tf_events_edit_columns");
add_action ("manage_posts_custom_column", "tf_events_custom_columns");

/* Sort posts in wp_list_table by column in ascending or descending order. 
 * http://wordpress.stackexchange.com/questions/66455/how-to-change-order-of-posts-in-admin
 */
function custom_post_order($query){
    if(!is_admin()) return;
    /* The current post type. */
    $post_type = $query->get('post_type');
    /* Check post types. */
    if($post_type==="tf_events"){
        /* Post Column: e.g. title */
        if($query->get('orderby') == ''){
            $query->set('meta_key', 'tf_events_startdate');
            $query->set('orderby', 'meta_value');
        }
        /* Post Order: ASC / DESC */
//        if($query->get('order') == ''){
            $query->set('order', 'DESC');
//        }
    }
}
if(is_admin()){
    add_action('pre_get_posts', 'custom_post_order');
}
 
function tf_events_edit_columns($columns) {
 
$columns = array(
    "cb" => "<input type=\"checkbox\" />",
    "tf_col_ev_cat" => "Category",
    "tf_col_ev_date" => "Dates",
    "tf_col_ev_times" => "Times",
//    "tf_col_ev_thumb" => "Thumbnail",
    "title" => "Event",
    "tf_col_ev_desc" => "Description",
    "date" => __( 'Date' ),
    );
return $columns;
}
 
function tf_events_custom_columns($column)
{
global $post;
$custom = get_post_custom();

$class = "class=\"$column column-$column\"";
$style = '';
$attributes = "$class$style";

switch ($column)
{
case "tf_col_ev_cat":
    // - show taxonomy terms -
    $eventcats = get_the_terms($post->ID, "tf_eventcategory");
    $eventcats_html = array();
    if ($eventcats) {
    foreach ($eventcats as $eventcat)
    array_push($eventcats_html, $eventcat->name);
    echo implode($eventcats_html, ", ");
    } else {
    _e('None', 'themeforce');;
    }
break;
case "tf_col_ev_date":
    // - show dates -
    $startd = $custom["tf_events_startdate"][0] + get_option( 'gmt_offset' ) * 3600;
    $endd = $custom["tf_events_enddate"][0] + get_option( 'gmt_offset' ) * 3600;
    $startdate = date("j F Y", $startd);
    $enddate = date("j F Y", $endd);
    echo $startdate . '<br /><em>' . $enddate . '</em>';
break;
case "tf_col_ev_times":
    // - show times -
    $startt = $custom["tf_events_startdate"][0] + get_option( 'gmt_offset' ) * 3600;
    $endt = $custom["tf_events_enddate"][0] + get_option( 'gmt_offset' ) * 3600;
    $time_format = get_option('time_format');
    $starttime = date($time_format, $startt);
    $endtime = date($time_format, $endt);
    echo $starttime . ' - ' .$endtime;
break;
case 'date':
    if ( '0000-00-00 00:00:00' == $post->post_date ) {
            $t_time = $h_time = __( 'Unpublished' );
            $time_diff = 0;
    } else {
            $t_time = get_the_time( __( 'Y/m/d g:i:s A' ) );
            $m_time = $post->post_date;
            $time = get_post_time( 'G', true, $post );

            $time_diff = time() - $time;

            if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS )
                    $h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
            else
                    $h_time = mysql2date( __( 'Y/m/d' ), $m_time );
    }

    echo '<td ' . $attributes . '>';
    if ( 'excerpt' == $mode ) {

            /**
             * Filter the published time of the post.
             *
             * If $mode equals 'excerpt', the published time and date are both displayed.
             * If $mode equals 'list' (default), the publish date is displayed, with the
             * time and date together available as an abbreviation definition.
             *
             * @since 2.5.1
             *
             * @param array   $t_time      The published time.
             * @param WP_Post $post        Post object.
             * @param string  $column_name The column name.
             * @param string  $mode        The list display mode ('excerpt' or 'list').
             */
            echo apply_filters( 'post_date_column_time', $t_time, $post, $column_name, $mode );
    } else {

            /** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
            echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, $column_name, $mode ) . '</abbr>';
    }
    echo '<br />';
    if ( 'publish' == $post->post_status ) {
            _e( 'Published' );
    } elseif ( 'future' == $post->post_status ) {
            if ( $time_diff > 0 )
                    echo '<strong class="attention">' . __( 'Missed schedule' ) . '</strong>';
            else
                    _e( 'Scheduled' );
    } else {
            _e( 'Last Modified' );
    }
    echo '</td>';
break;
case "tf_col_ev_thumb":
    // - show thumb -
    $post_image_id = get_post_thumbnail_id(get_the_ID());
    if ($post_image_id) {
    $thumbnail = wp_get_attachment_image_src( $post_image_id, 'post-thumbnail', false);
    if ($thumbnail) (string)$thumbnail = $thumbnail[0];
    echo '<img src="';
    echo bloginfo('template_url');
    echo '/timthumb/timthumb.php?src=';
    echo $thumbnail;
    echo '&h=60&w=60&zc=1" alt="" />';
}
break;
case "tf_col_ev_desc";
    the_excerpt();
break;
 
}
}

// 4. Show Meta-Box
 
add_action( 'admin_init', 'tf_events_create' );
 
function tf_events_create() {
    add_meta_box('tf_events_meta', 'Events', 'tf_events_meta', 'tf_events');
}
 
function tf_events_meta () {
 
    // - grab data -

    global $post;
    $custom = get_post_custom($post->ID);
    $meta_sd = $custom["tf_events_startdate"][0] + get_option( 'gmt_offset' ) * 3600;
    $meta_ed = $custom["tf_events_enddate"][0] + get_option( 'gmt_offset' ) * 3600;
    $meta_st = $meta_sd;
    $meta_et = $meta_ed;
    $meta_email = $custom["tf_events_email"][0];
    $meta_place = $custom["tf_events_place"][0];
    $meta_url = $custom["tf_events_url"][0];

    // - grab wp time format -

    $date_format = get_option('date_format'); // Not required in my code
    $time_format = get_option('time_format');

    // - populate today if empty, 00:00 for time -

    if ($meta_sd == null) { $meta_sd = time(); $meta_ed = $meta_sd; $meta_st = 0; $meta_et = 0;}

    // - convert to pretty formats -

    $clean_sd = date("D, d M Y", $meta_sd);
    $clean_ed = date("D, d M Y", $meta_ed);
    $clean_st = date($time_format, $meta_st);
    $clean_et = date($time_format, $meta_et);

    // - security -

    echo '<input type="hidden" name="tf-events-nonce" id="tf-events-nonce" value="' .
    wp_create_nonce( 'tf-events-nonce' ) . '" />';

    // - output -

    ?>
    <div class="tf-meta">
    <ul>
        <li><label>Start Date</label><input name="tf_events_startdate" class="tfdate" value="<?php echo $clean_sd; ?>" /></li>
        <li><label>Start Time</label><input name="tf_events_starttime" value="<?php echo $clean_st; ?>" /><em>Use 24h format (7pm = 19:00)</em></li>
        <li><label>End Date</label><input name="tf_events_enddate" class="tfdate" value="<?php echo $clean_ed; ?>" /></li>
        <li><label>End Time</label><input name="tf_events_endtime" value="<?php echo $clean_et; ?>" /><em>Use 24h format (7pm = 19:00)</em></li>
        <li><label>Your Email</label><input type="email" name="tf_events_email" value="<?php echo $meta_email; ?>" /><em>(not for publication)</em></li>
        <li><label>Meeting Place</label><input class="wide" name="tf_events_place" value="<?php echo $meta_place; ?>" /></li>
        <li><label>Web Page</label><input class="wide" type="url" name="tf_events_url" value="<?php echo $meta_url; ?>" /><em>(if any)</em></li>
    </ul>
    </div>
    <?php
}

// 5. Save Data
 
add_action ('save_post', 'save_tf_events');
 
function save_tf_events(){
 
    global $post;

    // - still require nonce

    if (  isset( $_POST['tf-events-nonce'] ) && !wp_verify_nonce( $_POST['tf-events-nonce'], 'tf-events-nonce' )) {
        return $post->ID;
    }

    if ( !current_user_can( 'edit_posts' ) )
        return $post->ID;

    // - convert back to unix & update post

    if(!isset($_POST["tf_events_startdate"])):
        return $post;
    endif;
    $updatestartd = strtotime ( $_POST["tf_events_startdate"] . $_POST["tf_events_starttime"] ) - get_option( 'gmt_offset' ) * 3600;
    update_post_meta($post->ID, "tf_events_startdate", $updatestartd );

    if( ! isset( $_POST[ "tf_events_enddate" ] ) ) :
        return $post;
    endif;
    
    $updateendd = strtotime ( $_POST["tf_events_enddate"] . $_POST["tf_events_endtime"] ) - get_option( 'gmt_offset' ) * 3600;
    update_post_meta($post->ID, "tf_events_enddate", $updateendd );
    
    update_post_meta($post->ID, "tf_events_email", $_POST["tf_events_email"] );
    update_post_meta($post->ID, "tf_events_place", $_POST["tf_events_place"] );
    update_post_meta($post->ID, "tf_events_url", $_POST["tf_events_url"] );
    
}

// 6. Customize Update Messages
 
add_filter('post_updated_messages', 'events_updated_messages');
 
function events_updated_messages( $messages ) {
 
  global $post, $post_ID;
 
  $messages['tf_events'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Event updated. <a href="%s">View item</a>'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('Event updated.'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Event restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Event published. <a href="%s">View event</a>'), esc_url( get_permalink($post_ID) ) ),
    7 => __('Event saved.'),
    8 => sprintf( __('Event submitted. <a target="_blank" href="%s">Preview event</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Event draft updated. <a target="_blank" href="%s">Preview event</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );
 
  return $messages;
}

// 7. JS Datepicker UI

function events_styles() {
    global $post_type;
    if( 'tf_events' != $post_type )
        return;
    wp_enqueue_style('ui-datepicker', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css');
}
 
function events_scripts() {
    global $post_type;
    if( 'tf_events' != $post_type )
        return;
    wp_enqueue_script('jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js', array( 'jquery') );
//    wp_enqueue_script('ui-datepicker', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/jquery-ui.min.js', array( 'jquery', 'jquery-ui' ) );
    wp_register_script('admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery', 'jquery-ui' ) );
    wp_localize_script('admin', 'stylesheetUri', plugin_dir_url( __FILE__ ) );
    wp_enqueue_script('admin' );
}
 
add_action( 'admin_print_styles-post.php', 'events_styles', 1000 );
add_action( 'admin_print_styles-post-new.php', 'events_styles', 1000 );
 
add_action( 'admin_print_scripts-post.php', 'events_scripts', 1000 );
add_action( 'admin_print_scripts-post-new.php', 'events_scripts', 1000 );

function scripts() {
    wp_enqueue_script('ui', plugins_url( 'js/ui.js', __FILE__ ), array( 'jquery' ) );
    wp_enqueue_style('event-style', plugins_url( 'style.css', __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'scripts' );

function add_admin_styles() {
    wp_enqueue_style( 'admin-style', plugins_url( 'css/admin-style.css', __FILE__ ) );
}
add_action('admin_init', 'add_admin_styles' );

/*
 *  Shortcode to display events
 */

/*
 * EVENTS SHORTCODES (CUSTOM POST TYPE)
 * http://www.noeltock.com/web-design/wordpress/how-to-custom-post-types-for-events-pt-2/
 */

// 1) FULL EVENTS
//***********************************************************************************

function tf_events_full ( $atts ) {

// - define arguments -
extract(shortcode_atts(array(
    'limit' => '10', // # of events to show
 ), $atts));

// ===== OUTPUT FUNCTION =====

ob_start();

// ===== LOOP: FULL EVENTS SECTION =====

// - hide events that are older than 6am today (because some parties go past your bedtime) -

// $today6am = strtotime('today 6:00') + ( get_option( 'gmt_offset' ) * 3600 );
$now = time() + ( get_option( 'gmt_offset' ) * 3600 );

// - query -
global $wpdb;
$querystr = "
    SELECT *
    FROM $wpdb->posts wposts, $wpdb->postmeta metastart, $wpdb->postmeta metaend
    WHERE (wposts.ID = metastart.post_id AND wposts.ID = metaend.post_id)
    AND (metaend.meta_key = 'tf_events_enddate' AND metaend.meta_value > $now )
    AND metastart.meta_key = 'tf_events_startdate'
    AND wposts.post_type = 'tf_events'
    AND wposts.post_status = 'publish'
    ORDER BY metastart.meta_value ASC LIMIT $limit
 ";

$events = $wpdb->get_results($querystr, OBJECT);

// - declare fresh day -
$daycheck = null;

// - loop -
if ($events):
global $post;
foreach ($events as $post):
setup_postdata($post);

// - custom variables -
$custom = get_post_custom(get_the_ID());
$sd = $custom["tf_events_startdate"][0] + get_option( 'gmt_offset' ) * 3600;
$ed = $custom["tf_events_enddate"][0] + get_option( 'gmt_offset' ) * 3600;

// - determine if it's a new day -
$longdate = date("l, F j, Y", $sd);
if ($daycheck == null) { echo '<h2 class="full-events">' . $longdate . '</h2>'; }
if ($daycheck != $longdate && $daycheck != null) { echo '<h2 class="full-events">' . $longdate . '</h2>'; }

// - local time format -
$time_format = get_option('time_format');
$stime = date($time_format, $sd);
$etime = date($time_format, $ed);

// - output - ?>
<div class="full-events">
    <div class="text">
        <div class="title">
            <div class="eventtext"><?php the_title(); ?></div>
            <div class="time"><?php echo $stime . ' - ' . $etime; ?></div>
            <div class="time">Meet at: <?php echo $custom["tf_events_place"][0]?></div>
            <?php if($custom["tf_events_url"][0] !== "" ) { ?>
            <div class="time"><a href="<?php echo $custom["tf_events_url"][0]?>" target="_blank">Web page</a></div>
            <?php }
            if( current_user_can( 'edit_posts' ) ) {
                echo "<span class='edit-link'>\n";
                echo edit_post_link();
            } ?>
        </div>
    </div>
     <div class="desc"><?php if (strlen($post->post_content) > 150) { echo substr($post->post_content, 0, 150) . '...'; } else { echo $post->post_content; } ?></div>
</div>
<?php

// - fill daycheck with the current day -
$daycheck = $longdate;

endforeach;
else :
endif;

// ===== RETURN: FULL EVENTS SECTION =====

$output = ob_get_contents();
ob_end_clean();
return $output;
}

add_shortcode('tf-events-full', 'tf_events_full'); // You can now call this shortcode with [tf-events-full limit='20']

/*
 * Add event details before the body of the post
 */

function event_details($content) {
    global $post;
    if($post->post_type !== 'tf_events' ) return $content;
    
    $output = "";
    $sd = get_post_meta( $post->ID, 'tf_events_startdate', true);
    $ed = get_post_meta( $post->ID, 'tf_events_enddate', true);
    $time_format = get_option('time_format');
    $stime = date($time_format, $sd + get_option( 'gmt_offset' ) * 3600);
    $etime = date($time_format, $ed + get_option( 'gmt_offset' ) * 3600);
    $startout = date("l, F j, Y", $sd + get_option( 'gmt_offset' ) * 3600 );
    $endout = date("l, F j, Y", $ed + get_option( 'gmt_offset' ) * 3600 );
    $output .= "<div>Start: " . $startout . " " . $stime . "</div>";
    $output .= "<div>Finish: " . ($endout===$startout ? "" : $endout . " ") . $etime . "</div>";
    $output .= "<div>Meet at: " . get_post_meta( $post->ID, 'tf_events_place', true ) . "</div>";
    $url = get_post_meta( $post->ID, 'tf_events_url', true);
    if( $url ) $output .= "<div>More information: <a href='" . $url . "' target='_blank'>" . $url . "</a></div>";
    $postput = "";
    $postput .= "<div>iCal download  - coming soon</div>";
    $now = time();
    if( $ed < $now ) {
        $output .= "<h2>Warning - you are viewing a past event</h2>";
    }
    return $output . $content . $postput;
}
add_filter( 'the_content', 'event_details' );

/*
 *  one line listing of events for home page
 */
require plugin_dir_path( __FILE__ ) . "list-events.php";
/*
 * RSS feed
 */
remove_all_actions( 'do_feed_rss2' );
add_action( 'do_feed_rss2', 'events_feed_rss2', 10, 1 );
function events_feed_rss2( ) {
    $rss_template = plugin_dir_path( __FILE__ ) . 'feed-rss2.php';
    if( get_query_var( 'post_type' ) == 'tf_events' and file_exists( $rss_template ) )
        load_template( $rss_template );
}

function myfeed_request($qv) {
    if (isset($qv['feed'])) {
        $qv['post_type'] = "tf_events";
    }
    return $qv;
}
add_filter('request', 'myfeed_request');
/**
 * Add function to widgets_init that'll load our widget.
 */
add_action( 'widgets_init', 'load_RSS_widget' );

/**
 * Register widget.
 */
function load_RSS_widget() {
	register_widget( 'Events_RSS' );
}

class Events_RSS extends WP_Widget {
    function __construct() {
		$widget_ops = array('classname' => 'widget_event_feed', 'description' => __( 'RSS feed of events.') );
		parent::__construct('RSS', __('RSS'), $widget_ops);
	}

	public function widget( $args, $instance ) {
		extract($args);
                $title = apply_filters( 'widget_title', $instance['title'] );

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		echo $before_widget;
                    
                ?>
                <a href="<?=bloginfo('rss2_url') . "&post_type=tf_events";?>"><?=$title?></a>
                <?php
                
                echo $after_widget;
        }
        
        public function form ( $instance ) {
            if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'New title', 'text_domain' );
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
                <?php
        }
}

/*
 * AJAX call to add a new event
 */
add_action( 'wp_ajax_newEvent', 'bf_newEvent' );
add_action( 'wp_ajax_nopriv_newEvent', 'bf_newEvent' );

function bf_newEvent() {
    $title = $_POST['title'];
    $tf_events_email = $_POST['tf_events_email'];
    $tf_events_place = $_POST['tf_events_place'];
    $tf_events_url = $_POST['tf_events_url'];
    $tf_description = $_POST['tf_description'];
   
    $areYouThere = $_POST['areYouThere'];
    if (
        ! isset( $_POST['fs_nonce'] ) 
        || ! wp_verify_nonce( $_POST['fs_nonce'], 'bf_new_event' ) 
    ) {

       echo json_encode( array( 'error'=>'Sorry, your nonce did not verify.' ) );
       die;
    }
    
    if($tf_events_email==="") {
        echo json_encode( array( 'error'=>'Please supply an email address' ) );
        die;
    }
    global $wpdb;
    
    if( $areYouThere !== "y" ) {
        echo json_encode( array('error'=>'Please tick the box to show you are not a robot') );
        die;
    }
        
    $post_id = wp_insert_post(array(
            'post_title'=>$title,
            'post_status'=>'draft',
            'post_type'=>'tf_events',
            'ping_status'=>false,
            'post_content'=>$tf_description,
        ),
        true
    );
    if(is_wp_error($post_id)) {
        echo json_encode( array( 'error'=>$post_id->get_error_message() ) );
        die;
    }
    $updatestartd = strtotime ( $_POST["tf_events_startdate"] . $_POST["tf_events_starttime"] ) - get_option( 'gmt_offset' ) * 3600;
    update_post_meta($post->ID, "tf_events_startdate", $updatestartd );

    if( isset( $_POST[ "tf_events_enddate" ] ) ) :
        $updateendd = strtotime ( $_POST["tf_events_enddate"] . $_POST["tf_events_endtime"] ) - get_option( 'gmt_offset' ) * 3600;
        update_post_meta($post->ID, "tf_events_enddate", $updateendd );
    endif;
   
    update_post_meta($post->ID, "tf_events_email", $_POST["tf_events_email"] );
    update_post_meta($post->ID, "tf_events_place", $_POST["tf_events_place"] );
    update_post_meta($post->ID, "tf_events_url", $_POST["tf_events_url"] );
        
    $secret = generateRandomString();
    update_post_meta ( $post_id, "tf_events_secret", $secret );
    
    $subject = "Thanks for listing with Bikefun"; // use blog title in subject!
    $headers = array();
    $headers[] = 'From: Bikefun <info@bikefun.org>';
    $headers[] = "Content-type: text/html";
    $message = "<P>Thanks for listing with Bikefun. Your event will be visible once a moderator has approved it.</P>";
    $message .= "<P>Below is a link you can use to edit the event you have listed.</P>";
    $message .= "<P><a href='http://www.freestylecyclists.org/confirm?secret=" . $secret . "'>Click here to edit your event</a></P>";
    wp_mail( $fs_signature_email, $subject, $message, $headers );
    
    echo json_encode( array( 'success'=>'You have successfully registered your support. Look for an email from us and click on the link to confirm your email address - until then we can\'t count you.' ) );
    die();
}
/*
 * used in secret key for email login link
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}
/* 
 * Shortcode for event submission form
 */
function register_event_script() {
    wp_register_script('jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js', array( 'jquery') );
    wp_register_style('ui-datepicker', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css');
    wp_register_script('event', plugins_url( 'js/event.js' , __FILE__ ), array('jquery', 'jquery-ui' ) );
    wp_register_style(' bf-event_styles', plugins_url( 'css/style.css', __FILE__ ) );	
}
function enqueue_event_register_script() {	// support for signup form, which appears on two pages and in a popup
    global $add_event_register_script;
    if( ! $add_event_register_script ) return;
    wp_enqueue_script('jquery-ui');
    wp_enqueue_style('ui-datepicker');
    wp_localize_script('event', 'data', array('stylesheetUri' => plugin_dir_url( __FILE__ ), 'ajaxUrl'=> admin_url('admin-ajax.php') ) );
    wp_enqueue_script('event');
    wp_enqueue_style(' bf-event_styles');
}
add_action('init', 'register_event_script' );
add_action( 'wp_footer', 'enqueue_event_register_script' );
function bf_event_register ( $atts ) { 
    global $add_event_register_script;
    $add_event_register_script = true;
    
    $a = shortcode_atts( array(
        'narrow' => '0',
        'popup' => '0',
    ), $atts );
    $narrow = $a['narrow']==='1';
    $popup = $a['popup']==='1';
    
    ob_start() ?>

    <form name="register<?=($popup ? "_popup" : "");?>">
        <div class="tf-meta">
            <ul>            
                <li><label>Event title</label><input <?=($narrow || $popup) ? " class='smallinput'" : "class='wide'";?> type="text" name="title" id="name<?=($popup ? "_popup" : "");?>"></li>
                <li><label>Start Date</label><input name="tf_events_startdate" class="tfdate" value="<?php echo $clean_sd; ?>" /></li>
                <li><label>Start Time</label><input name="tf_events_starttime" value="<?php echo $clean_st; ?>" /></li>
                <li><label>End Date</label><input name="tf_events_enddate" class="tfdate" value="<?php echo $clean_ed; ?>" /></li>
                <li><label>End Time</label><input name="tf_events_endtime" value="<?php echo $clean_et; ?>" /></li>
                <li><label>Your Email</label><input type="email" id="email" name="tf_events_email" value="<?php echo $meta_email; ?>" /><em>(not for publication)</em></li>
                <li><label>Meeting Place</label><input class="wide" name="tf_events_place" value="<?php echo $meta_place; ?>" /></li>
                <li><label>Web Page</label><input type="url" name="tf_events_url" value="<?php echo $meta_url; ?>" /><em>(if any)</em></li>
                <li><label>Description</label></li>
                <li class="tall"><?php wp_editor( "&nbsp;", "editcontent", array("media_buttons"=>false, "textarea_name"=>"tf_description" ) ); ?></li>
                <li><input id="simpleTuring<?=($popup ? "_popup" : "");?>" name="areYouThere" type="checkbox" value="y" class="inputc"></td><td class="medfont">Tick this box to show you are not a robot</li>
                <li><button type="button" id="saveButton<?=($popup ? "_popup" : "");?>">Save</button></li>
                <li><div id="ajax-loading<?=($popup ? "_popup" : "");?>" class="farleft"><img src="<?php echo get_site_url();?>/wp-includes/js/thickbox/loadingAnimation.gif"></div></li>
                <li><div id="returnMessage<?=($popup ? "_popup" : "");?>"></div></li>
            </ul> 
        <input name="action" value="newEvent" type="hidden">
        <?php wp_nonce_field( "bf_new_event", "fs_nonce");?>
    </form>
<?php return ob_get_clean();
}
add_shortcode('event', bf_event_register );
