<?php
/**
 * Add animate.min.css, wow.min.js and new wow().init()
 */
function script_style_custom() {
	wp_enqueue_style('wowjs-animate', get_stylesheet_directory_uri() . '/css/animate.min.css');
    wp_enqueue_script('my-script', get_stylesheet_directory_uri() .'/js/wow.min.js', array('jquery'));
}
add_action( 'wp_enqueue_scripts', 'script_style_custom',10 );

function init_wowjs() {
//  ADD custom code
  echo '<script>
    new WOW().init();
  </script>';
}
add_action('wp_footer', 'init_wowjs');

/**
 * Đổi tên file upload
 */
function slugify($string) {
    $string = iconv('utf-8', 'us-ascii//translit//ignore', $string); // transliterate
    $string = str_replace("'", '', $string);
    $string = preg_replace('~[^\pL\d]+~u', '-', $string); // replace non letter or non digits by "-"
    $string = preg_replace('~[^-\w]+~', '', $string); // remove unwanted characters
    $string = preg_replace('~-+~', '-', $string); // remove duplicate "-"
    $string = trim($string, '-'); // trim "-"
    $string = trim($string); // trim
    $string = mb_strtolower($string, 'utf-8'); // lowercase
    
    return urlencode($string); // safe;
}

function nakovn_filename_hash($filename) {
	$ext = pathinfo($filename, PATHINFO_EXTENSION);
	$name = pathinfo($filename, PATHINFO_FILENAME);
	
    $name = slugify($name);
    $name = str_replace('ngolongnd_', '', $name);
    return 'ngolongnd_' . $name . '.' . $ext;
}
add_filter('sanitize_file_name', 'nakovn_filename_hash', 10);

/**
 * style tinyMCE
 */
function my_admin_custom_css() {
  echo '<style>
    .mce-menubar .mce-container-body { display: block !important; }
  </style>';
}
add_action('admin_head', 'my_admin_custom_css');

/**
 * Add get/set view
 */
/* Đếm lượt xem bài viết */
function getPostViews($postID){
    $count_key = 'post_views_count';
    $count = get_post_meta($postID, $count_key, true);
	$count_text = '<span>'.$count.' Views</span>';
    if($count==''){
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
        return "<span>0 View<span>";
    }
    return $count_text;
}
function setPostViews($postID) {
    $count_key = 'post_views_count';
    $count = get_post_meta($postID, $count_key, true);
    if($count==''){
        $count = 0;
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
    }else{
        $count++;
        update_post_meta($postID, $count_key, $count);
    }
}
// Remove issues with prefetching adding extra views
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

/**
 * Thêm ảnh mặc định vào post khi tạo mới
 * $meta_value = image_id
 */
function wptuts_save_thumbnail( $post_id ) {
    // Get Thumbnail
    $post_thumbnail = get_post_meta( $post_id, $key = '_thumbnail_id', $single = true );
 
    // Verify that post is not a revision
    if ( !wp_is_post_revision( $post_id ) ) {
        // Check if Thumbnail exists
        if ( empty( $post_thumbnail ) ) {
            // Add thumbnail to post
            update_post_meta( $post_id, $meta_key = '_thumbnail_id', $meta_value = '371' );
        }
    }
}
add_action( 'save_post', 'wptuts_save_thumbnail' );

/* Tạo widget hiển thị bài xem nhiều
 * @tham khảo tại http://bit.ly/1tY8TFn
 */
 
function create_topview_widget() {
    register_widget( 'TopView_Widget' );
}
add_action( 'widgets_init', 'create_topview_widget' );
 
class TopView_Widget extends WP_Widget {
 
    /*
     * Thiết lập tên widget và description của nó (Appearance -> Widgets)
     */
    function __construct() {
        $options = array(
           'classname' => 'topview',
            'description' => 'Xem bài viết xem nhiều nhất'
        );
        parent::__construct('topview', 'Top View', $options);
    }
 
    /*
     * Tạo form điền tham số cho widget
     * ở đây ta có 3 form là title, postnum (số lượng bài) và postdate (tuổi của bài
     */
    function form($instance) {
        $default = array(
            'title' => 'Bài xem nhiều nhất',
            'postnum' => 5,
            'postdate' => 30
        );
        $instance = wp_parse_args( (array) $instance, $default );
        $title = esc_attr( $instance['title'] );
        $postnum = esc_attr( $instance['postnum'] );
        $postdate = esc_attr( $instance['postdate'] );
 
        echo "<label>Tiêu đề:</label> <input class='widefat' type='text' name='".$this->get_field_name('title')."' value='".$title."' />";
        echo "<label>Số lượng bài viết:</label> <input class='widefat' type='number' name='".$this->get_field_name('postnum')."' value='".$postnum."' />";
        echo "<label>Độ tuổi của bài viết (ngày)</label> <input class='widefat' type='number' name='".$this->get_field_name('postdate')."' value='".$postdate."' />";
    }
 
    /*
     * Cập nhật dữ liệu nhập vào form tùy chọn trong database
     */
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['postnum'] = strip_tags($new_instance['postnum']);
        $instance['postdate'] = strip_tags($new_instance['postdate']);
        return $instance;
    }
 
    function widget($args, $instance) {
        global $postdate; // Thiết lập biến $postdate là biến toàn cục để dùng ở hàm filter_where
        extract( $args );
        $title = apply_filters( 'widget_title', $instance['title'] );
        $postnum = $instance['postnum'];
        $postdate = $instance['postdate'];
 
        echo $before_widget;
        echo $before_title.$title.$after_title;
 
        $query_args = array(
            'posts_per_page' => $postnum,
            'meta_key' => 'postview_number',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'ignore_sticky_posts' => -1
        );
 
        /*
         * Cách lấy bài viết theo độ tuổi (-30 days = lấy bài được 30 ngày tuổi)
         * @tham khảo tại http://bit.ly/1y7WXFp
         */
        function filter_where( $where = '' ) {
            global $postdate;
            $where .= " AND post_date > '" . date('Y-m-d', strtotime('-'.$postdate.' days')) . "'";
            return $where;
        }
        add_filter( 'posts_where', 'filter_where' );
 
        $postview_query = new WP_Query( $query_args );
 
        remove_filter( 'posts_where', 'filter_where' ); // Xóa filter để tránh ảnh hưởng đến query khác
 
        if ($postview_query->have_posts() ) :
            echo "<ul>";
            while ( $postview_query->have_posts() ) :
                $postview_query->the_post(); ?>
 
                <li>
                    <?php  
                        if ( has_post_thumbnail() )
                            the_post_thumbnail( 'thumbnail' );
                        else
                            echo "</br><img src='https://dummyimage.com/50/000/fff&text=thach'>";       
                    ?>
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </li>
 
            <?php endwhile;
            echo "</ul>";
        endif;
        echo $after_widget;
    }
}