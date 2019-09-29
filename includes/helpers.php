<?php

if (!defined('WPINC')) {
  die;
}

class ArhaHelpers {
  /**
   * Function checks if passed in $post_type is found in results of passed in $filter.
   *
   * If not, function throw Exception.
   * @param String $filter
   * @param String $post_type
   */
  public static function check_excluded_post_types($filter, $post_type) {
    $excluded_post_types = apply_filters($filter, []);
    if (sizeof($excluded_post_types) === 0) {
      return;
    }

    if (in_array($post_type, $excluded_post_types)) {
      throw new Exception("Post_type '$post_type' is excluded from routes");
    }
  }

  /**
   * Checks if request object has all required params.
   * if not, function throws Exception.
   *
   * @param WP_REST_Request $request
   * @param Array $required_params
   */
  public static function check_required_params($request, $require_params) {
    $params = $request->get_params();
    foreach ($require_params as $required_param) {
      if (!isset($params[$required_param])) {
        throw new Exception("$required_param not specified in GET-params");
      }
    }
  }

  /**
   * Function checks if $orderby param is part of a $orderby_values which is copied
   * from WP documentation and throws exception if it's not.
   *
   * @param String $orderby
   * @see https://codex.wordpress.org/Template_Tags/get_posts
   */
  public static function check_orderby_param(String $orderby) {
    $orderby_values = [
      'none',
      'ID',
      'author',
      'title',
      'date',
      'modified',
      'parent',
      'rand',
      'comment_count',
      'menu_order',
      'meta_value',
      'meta_value_num',
      'post__in',
    ];
    if (!in_array($orderby, $orderby_values)) {
      throw new Exception("orderby=$orderby is not acceptable param");
    }
  }

  /**
   * Function checks if passed in post's language is same as second params value.
   *
   * This is used in page and post-route after content is fetched, while polylang-plugin is active.
   * It is used as extra safety measure.
   *
   * @param WP_Post $post
   * @param String $lang
   */
  public static function check_post_language($post, $lang) {
    $post_type = $post->post_type;
    if (function_exists('pll_get_post_language')) {
      $post_lang = pll_get_post_language($post->ID);
      return $lang === $post_lang;
    } else {
      throw new Exception("System cannot check language");
    }
  }

  /**
   * sets Polylang-plugin's current language model to one passed in as param
   * until end of the process
   *
   * @param String $lang - language code
   */
  public static function set_polylang_curlang($lang) {
    if (!function_exists('pll_languages_list')) {
      throw new Exception('Language settings are not set.');
    }
    $langs = pll_languages_list();
    if (!in_array($lang, $langs)) {
      throw new Exception('Language not found.');
    }

    PLL()->curlang = PLL()->model->get_language($lang);
  }

  /**
   * Gets homepage from Reading settings (/wp-admin/options-reading.php)
   *
   * Remember to call ArhaHelpers::set_polylang_curlang($lang) before this function, if you use polylang.
   *
   */
  public static function get_front_page() {
    $frontpage_id = (int)get_option('page_on_front');
    if ($frontpage_id === 0) {
      throw new Exception("Homepage not set in reading settings");
    }

    if (class_exists('Polylang')) {
      $frontpage_id = pll_get_post($frontpage_id);

      if ($frontpage_id === 0) {
        throw new Exception("Homepage not set in reading settings");
      }
    }
    return get_page($frontpage_id);
  }
}