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
   * Function iterates $key_array param and tries to remove each iteration's
   * value from $obj param's keys.
   *
   * @param   Array   $key_array  - list of strings
   * @param   Object  $obj
   * @return  Object  $obj        - stripped down object 👀
   */
  public static function remove_keys_from_object($key_array, $obj) {
    foreach ($key_array as $key) {
      if (isset($obj->$key)) {
        unset($obj->$key);
      }
    }
    return $obj;
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
   * This function checks if $orderby param is part of a $orderby_values-list which is copied
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
}