<?php

if (!defined('WPINC')) {
  die;
}

class ArhaBuilder {

  /**
   * - gets post's ACF-fields with get_fields() and appends them to 'acf'-key of
   *   passed in post
   * - gets post's taxonomies and appends them in 'taxonomies'-key
   *
   * @param   Object $post - WP Post
   * @return  Object $post - New and better post
   */
  public function build_post($post) {
    $post = is_int($post) ? get_post($post) : $post;

    if (function_exists('get_fields')) {
      $id        = $post->ID;
      $post->acf = get_fields($id);
    }

    $post->taxonomies = $this->__build_taxonomies($post);

    return $post;
  }

  /**
   * - Gets all taxonomies of passed in post
   * - Apply 'arha_routes/format_taxonomy'-filter, so each taxonomy object can be formatted
   *   by user
   * - Get post's terms of each taxonomies
   * - Apply 'arha_route/format_term'-filter, so each term can be formatted by user
   * - Append terms to each taxonomy's 'terms'-index
   * - return taxonomy list
   *
   * @param Object $post - WP Post
   * @return Array $taxonomies - post's terms organized under each taxonomy
   */
  private function __build_taxonomies($post) {
    $post_taxonomies = get_object_taxonomies($post);
    if (sizeof($post_taxonomies) === 0) {
      return [];
    }

    $id         = $post->ID;
    $taxonomies = [];
    foreach ($post_taxonomies as $tax) {
      $tax_object        = get_taxonomy($tax);
      $tax_object        = apply_filters('arha_routes/format_taxonomy', $tax_object);
      $tax_object->terms = $this->__build_terms($id, $tax);
      $taxonomies[$tax]  = $tax_object;
    }

    return $taxonomies;
  }

  private function __build_terms($id, $taxonomy) {
    $terms = wp_get_post_terms($id, $taxonomy);
    if (!$terms) {
      return [];
    }

    $formatted_terms = array_map(function ($term) {
      return apply_filters('arha_routes/format_term', $term);
    }, $terms);

    return $formatted_terms;
  }

  /**
   * - Gets all registered ACF option pages with acf_get_options_pages()
   * - Iterates through them and call get_fields() to get all fields for
   *   each option page
   * - Appends all option pages with their fields to 'acf'-index
   *
   * @return Object $options - object that has all option field values
   */
  public function build_options() {
    $options = new stdClass();

    if (function_exists('acf_get_options_pages') && function_exists("get_fields")) {
      $acf_option_pages = acf_get_options_pages();

      $acf = array_map(function ($options_page) {
        $id = $options_page['post_id'];
        return get_fields($id);
      }, $acf_option_pages);

      $options->acf = $acf;
    }

    return $options;
  }
}
