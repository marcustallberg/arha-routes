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
   * This is used in post-route after post is fetched with slug,
   * post_type and lang using get_posts(). Sometimes, if get_posts() doesn't
   * find post with these parameters, it tries to find similar posts in other
   * languages. So, this is used as extra safety measure.
   *
   * @param WP_Post $post
   * @param String $lang
   */
  public static function check_post_language($post, $lang) {
    if (function_exists('pll_get_post_language')) {
      $post_lang = pll_get_post_language($post->ID);
      if ($lang !== $post_lang) {
        throw new Exception("System didn't find post with params");
      }
    } else {
      throw new Exception("System cannot check post's language");
    }
  }
}