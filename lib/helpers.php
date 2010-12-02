<?php

require_once dirname(__FILE__).'/vendor/phamlp/haml/HamlParser.php';

// ====== HAML Helpers ======

function render_partial($name) {
  $haml = new HamlParser(array('style' => 'nested', 'ugly' => false));
  require $haml->parse(dirname(__FILE__)."/../src/views/$name.haml", dirname(__FILE__).'/../tmp');
}

function render_view($name) {
  global $current_view;
  $current_view = $name;
  render_partial("template");
}

// ====== Text Helpers ======

function limit_words($string, $word_limit) {
  $words = explode(' ', $string);
  return implode(' ', array_slice($words, 0, $word_limit));
}

function get_the_time_ago($granularity=1) {
  $date = intval(get_the_date('U'));
  $difference = time() - $date;
  $periods = array(
    315360000 => array('decennio', 'decenni'),
    31536000 => array('anno', 'anni'),
    2628000 => array('mese', 'mesi'),
    604800 => array('settimana', 'settimane'),
    86400 => array('giorno', 'giorni'),
    3600 => array('ora', 'ore'),
    60 => array('minuto', 'minuti'),
    1 => array('secondo', 'secondi')
  );

  foreach ($periods as $value => $key) {
    if ($difference >= $value) {
      $time = floor($difference/$value);
      $difference %= $value;
      $retval .= ($retval ? ' ' : '').$time.' ';
      $retval .= (($time > 1) ? $key[1]: $key[0]);
      $granularity--;
    }
    if ($granularity == '0') { break; }
  }
  return $retval.' fa';
}


// ====== HTML Helpers ======

function public_url($path) {
  return get_bloginfo('stylesheet_directory') . "/public/$path";
}

function link_to($text, $link, $class = '') {
  if (!$text) {
    $text = "Testo non disponibile";
  }
  if (!link) {
    $link = "#link_not_available";
  }
  if ($class) {
    $class = " class='$class'";
  }
  return "<a href='$link'$class>$text</a>";
}

function image_tag($img) {
  if (!preg_match("/^http/", $img)) {
    $img = get_bloginfo('stylesheet_directory') . "/" . $img;
  }
  return "<img src='$img' alt=''/>";
}

// ====== The Events Calendar Plugin ======

function upcoming_events($limit, $offset = 0) {
  global $wpdb;
  $query = "SELECT * FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->postmeta.meta_key = '_EventStartDate' AND $wpdb->postmeta.meta_value > CURRENT_DATE() ORDER BY $wpdb->postmeta.meta_value ASC LIMIT $limit OFFSET $offset";
  return $wpdb->get_results($query, OBJECT);
}

function count_upcoming_events($limit = 10000) {
  global $wpdb;
  $query = "SELECT COUNT(*) FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->postmeta.meta_key = '_EventStartDate' AND $wpdb->postmeta.meta_value > CURRENT_DATE() ORDER BY $wpdb->postmeta.meta_value ASC LIMIT $limit";
  return $wpdb->get_var($query);
}

// ====== Attachments plugin ======

function attachment_thumbnail($id, $size) {
  $image = image_downsize($id, $size, false);
  return $image[0];
}

function has_thumbnails() {
  return count(attachments_get_attachments()) || has_post_thumbnail();
}

function thumbnails($size) {

  $attachments = attachments_get_attachments();

  if (!$attachments) {
    $attachments = array();
  }

  if (has_post_thumbnail()) {
    array_unshift($attachments, array(
      'title' => '',
      'caption' => '',
      'id' => get_post_thumbnail_id()
    ));
  }

  foreach ($attachments as $k => $v) {
    $v['thumb'] = attachment_thumbnail($v['id'], $size);
    $v['large'] = attachment_thumbnail($v['id'], 'large');
    $attachments[$k] = $v;
  }

  return $attachments;
}

function get_the_post_thumbnail_image($size) {
  $thumbs = thumbnails($size);
  return image_tag($thumbs[0]['thumb']);
}


// ====== Wordpress helpers ======

function lastest_posts_of_type($type, $limit = -1) {
  return get_posts("numberposts=$limit&post_type=$type");
}

function lastest_post_of_type($type) {
  $posts = lastest_posts_of_type($type, 1);
  return $posts[0];
}

function latest_posts_of_category($category, $limit, $offset = 0, $post_type = 'post', $taxonomy = 'category') {
  return query_posts(array(
    'posts_per_page' => $limit,
    'taxonomy' => $taxonomy,
    'term' => $category,
    'offset' => $offset,
    'post_type' => $post_type
  ));
}

function latest_post_of_category($category, $post_type = 'post', $taxonomy = 'category') {
  $posts = latest_posts_of_category($category, 1, 0, $post_type, $taxonomy);
  return $posts[0];
}

function get_the_first_categories_except($limit, $except) {
  global $post;
  $categories = get_the_category();
  $found_categories = false;

  if (count($categories)) {
    $filtered_categories = array();
    foreach ($categories as $category) {
      if ($category->cat_name != $except and count($filtered_categories) < $limit) {
        $filtered_categories[] = link_to($category->cat_name, get_category_link($category->cat_ID));
        $found_categories = true;
      }
    }
  }

  if ($found_categories) {
    return join(", ", $filtered_categories);
  } else {
    return link_to("Articolo", "#");
  }
}

function get_post_type_singular_name() {
  $obj = get_post_type_object(get_post_type());
  return $obj->labels->name;
}

function get_category_id_by_name($cat_name){
  $term = get_term_by('name', $cat_name, 'category');
  return $term->term_id;
}

function is_post_type($type) {
  global $post;
  return $post->post_type == $type;
}

function get_page_id_by_title($title) {
  $page = get_page_by_title($title);
  return $page->ID;
}

function get_formatted_comments_number() {
  $num_comments = get_comments_number();
  if($num_comments == 0){
    $comments ="Nessun Commento";
  } elseif ($num_comments > 1){
    $comments = $num_comments." Commenti";
  }
  else{
    $comments ="1 Commento";
  }
  return $comments;
}

function get_page_title($prefix = "", $separator = "") {
  $title = "";
  if (is_category()) {
    $category = get_category(get_query_var('cat'),false);
    $title = get_cat_name($category->cat_ID);
  }
  if (is_post_type_archive()) {
    $title = get_post_type_singular_name();
  }
  if (is_single()) {
    $title = get_the_title();
  }
  if (is_search()) {
    $title = "Ricerca";
  }
  if (is_front_page()) {
    return $prefix;
  }
  return "$prefix$separator$title";
}

// ====== Flickr helpers ======

function flickrMethod($method, $params) {
  $default_params = array(
    'api_key'  => '2d2940fbdc80a2421666c404c057579d',
    'method'  => $method,
    'format'  => 'php_serial',
  );
  $params = array_merge($default_params, $params);
  $encoded_params = array();
  foreach ($params as $k => $v){
    $encoded_params[] = urlencode($k).'='.urlencode($v);
  }
  $url = "http://api.flickr.com/services/rest/?".implode('&', $encoded_params);

  $cachefile = dirname(__FILE__) . '/../tmp/flickr_'.md5($url);
  $cachetime = 120 * 60;

  $response = false;

  if (file_exists($cachefile) && (time() - $cachetime < filemtime($cachefile))) {
    $response = file_get_contents($cachefile);
  } else {
    $response = file_get_contents($url);
    $fp = fopen($cachefile, 'w');
    fwrite($fp, $response);
    fclose($fp);
  }

  return unserialize($response);
}

function getFlickrPhotosetPhotos($key, $photoset) {

  $photos = flickrMethod('flickr.photosets.getPhotos', array('photoset_id' => $photoset));
  $result = array();

  foreach ($photos['photoset']['photo'] as $photo) {
    $sizes = flickrMethod('flickr.photos.getSizes', array('photo_id' => $photo['id']));
    $sizes_to_return = array();
    foreach ($sizes['sizes']['size'] as $size) {
      $sizes_to_return[strtolower($size['label'])] = $size['source'];
    }
    $result[] = array(
      'title' => $photo['title'],
      'url' => $sizes_to_return
    );
  }

  return $result;
}