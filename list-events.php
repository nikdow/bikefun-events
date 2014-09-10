<?php
/*
 * Shortcode for displaying events, paginated
 */
add_action('init', 'short_list_events_script');
add_action('wp_footer', 'enqueue_short_events_script');
function short_list_events_script() {
    wp_register_script( 'angular', "//ajax.googleapis.com/ajax/libs/angularjs/1.2.18/angular.min.js", 'jquery' );
    wp_register_script( 'angular-animate', "//ajax.googleapis.com/ajax/libs/angularjs/1.2.18/angular-animate.min.js", array( 'angular', 'jquery' ) );
    wp_register_script('events',  plugins_url( 'js/eventslist.js' , __FILE__ ), array('jquery', 'angular') );
}
function enqueue_short_events_script() {
	global $add_short_events_script;

	if ( ! $add_short_events_script )
		return;

        wp_enqueue_script('angular');
        wp_enqueue_script('angular-animate');
	wp_enqueue_script('events');
//        wp_enqueue_style('fs-signature-styles');
}
function events_list_short (  ) {
    global $add_short_events_script;
    $add_short_events_script = true;
    
    $rows_per_page = 15;
    $events = get_events( 0, $rows_per_page ); // first lot of sigs are loaded with the page
    ob_start();
    ?>
    <div class="row" ng-app="eventsApp" ng-controller="eventsCtrl">
        <script type="text/javascript">
            _events = <?=json_encode($events)?>;
            <?php
            global $wpdb;
            $now = time() + ( get_option( 'gmt_offset' ) * 3600 );
            $query = $wpdb->prepare('SELECT count(*) FROM ' . $wpdb->posts . ' p' .
                    ' LEFT JOIN ' . $wpdb->postmeta . " pme ON pme.post_id=p.ID AND pme.meta_key='bf_events_enddate'" .
                    ' WHERE post_type="bf_events" AND post_status="publish" AND pme.meta_value > ' . $now, array() );
            $pages = $wpdb->get_col( $query );
            $pages = floor ( ($pages[0] + 0.9999) / $rows_per_page ) + 1;
            if(!$pages) $pages = 1;
            $data = array('pages'=>$pages);
            $data['rows_per_page'] = $rows_per_page;
            ?>
            _data = <?=json_encode($data)?>;
        </script>
        <table id="events" border="0" width="90%" ng-cloak>
            <tbody>
                <tr><th>date</th><th>time</th><th>Title</th>
                <tr ng-repeat="event in events">
                    <td>{{event.startdate}}</td>
                    <td>{{event.starttime}}</td>
                    <td><a href="{{event.permalink}}">{{event.title}}</a></td>
                </tr>
            </tbody>
        </table>
        <div id="ajax-loading" ng-class="{'farleft':!showLoading}"><img src="<?php echo get_site_url();?>/wp-includes/js/thickbox/loadingAnimation.gif" ng-cloak></div>

        <?php
        // pagination adapted from http://sgwordpress.com/teaches/how-to-add-wordpress-pagination-without-a-plugin/                    
        ?>
        <div ng-hide="data.pages===1" class="pagination" ng-cloak>
            <span>Page {{paged}} of {{data.pages}}</span>
            <a ng-show="paged>2 && paged > range+1 && showitems<data.pages" ng-click="gotoPage(1)">&laquo; First</a>
            <a ng-show="paged>1 && showitems<data.pages" ng-click='gotoPage(paged-1)'>&lsaquo; Previous</a>

            <span ng-show='data.pages!==1' ng-repeat='i in pagearray'>
                <span ng-show='paged===i' class="current">{{i}}</span>
                <a ng-hide="paged===i" ng-click="gotoPage(i)" class="inactive">{{i}}</a>
            </span>

            <a ng-show='paged<data.pages && showitems<data.pages' ng-click='gotoPage(paged+1)'>Next &rsaquo;</a>
            <a ng-show='paged<data.pages-1 && paged+range-1<data.pages && showitems < data.pages' ng-click='gotoPage(data.pages)'>Last &raquo;</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('bf-events-short', 'events_list_short' );

/* 
 * showing events to the public - called from ajax wrapper and also when loading page initially
 */
function get_events( $first_event, $rows_per_page ){
    global $wpdb;
    $now = time(); // no adjustment for time zone required, comparison is in UMT on both sides of the inequality
    $query = $wpdb->prepare ( 
        "SELECT p.post_title, p.ID, pms.meta_value AS startdate from " . $wpdb->posts . " p" .
        " LEFT JOIN " . $wpdb->postmeta . " pms ON pms.post_id=p.ID AND pms.meta_key='bf_events_startdate'" . 
        " LEFT JOIN " . $wpdb->postmeta . " pme ON pme.post_id=p.ID AND pme.meta_key='bf_events_enddate'" .
        " WHERE p.post_type='bf_events' AND p.`post_status`='publish' AND pme.meta_value > " . $now .
        " ORDER BY startdate ASC LIMIT %d,%d", $first_event, $rows_per_page 
    );
    $rows = $wpdb->get_results ( $query );
    $output = array();
    $time_format = get_option('time_format');
    foreach ( $rows as $row ) {
        $stime = date($time_format, $row->startdate + get_option( 'gmt_offset' ) * 3600);
        $startout = date("D, M j, Y", $row->startdate + get_option( 'gmt_offset' ) * 3600 );
        $output[] = array(
            'title'=>$row->post_title,
            'startdate'=>$startout,
            'starttime'=>$stime,
            'id'=> $row->ID,
            'permalink' => post_permalink( $row->ID ),
        );
    }
    return $output;
}
/*
 * AJAX wrapper to get sigs
 */
add_action( 'wp_ajax_get_events', 'bf_get_events' );
add_action( 'wp_ajax_nopriv_get_events', 'bf_get_events' );

function bf_get_events() {
    $rows_per_page = $_POST['rows_per_page'];
    $page = $_POST['page'];
    $first_event = ( $page - 1 ) * $rows_per_page;
    echo json_encode( get_events( $first_event, $rows_per_page) );
    die;
}