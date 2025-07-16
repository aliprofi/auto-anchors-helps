<?php
/**
 * Plugin Name: Auto Anchors Helps
 * Description: Creates anchor links for words inside <code> tags within DW Question & Answer and displays a tag cloud.
 * Version: 1.0.11
 * Author: Али Профи
 * License: GPL-2.0+
 * Text Domain: auto-anchors-helps
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoAnchorsHelps {
    private $found_anchors = array();
    private static $instance = null;
    private $content_processed = false;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Load text domain for translations
        add_action('init', array($this, 'load_textdomain'));

        // Register shortcode
        add_shortcode('dwqa_anchors_cloud', array($this, 'generate_anchors_cloud'));

        // Process content to add anchors - используем очень ранний приоритет
        add_filter('the_content', array($this, 'add_anchors_to_content'), 5);
        add_filter('dwqa_answer_content', array($this, 'add_anchors_to_content'), 5);

        // Allow <code> and <span> tags in content
        add_filter('wp_kses_allowed_html', array($this, 'allow_code_tag'), 10, 2);

        // Output tag cloud after question content
        add_action('dwqa_after_question_content', array($this, 'output_anchors_cloud'), 10);
        add_action('dwqa_after_single_question_content', array($this, 'output_anchors_cloud'), 10);

        // Enqueue styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Обрабатываем контент на более раннем этапе
        add_action('wp', array($this, 'pre_process_content'));
    }

    public function pre_process_content() {
        global $post;
        
        if (!$this->is_dwqa_page()) {
            return;
        }

        $question_id = $post->ID;
        
        // Если это ответ, получаем ID вопроса
        if ($post->post_type === 'dwqa-answer') {
            $question_id = $post->post_parent;
        }

        // Обрабатываем контент вопроса
        $question_post = get_post($question_id);
        if ($question_post) {
            $this->scan_content_for_anchors($question_post->post_content);
        }

        // Получаем все ответы для этого вопроса
        $answers = get_posts(array(
            'post_type' => 'dwqa-answer',
            'post_parent' => $question_id,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        foreach ($answers as $answer) {
            $this->scan_content_for_anchors($answer->post_content);
        }
    }

    private function scan_content_for_anchors($content) {
        if (empty($content)) {
            return;
        }

        $pattern = '/<code>(.*?)<\/code>/is';
        preg_replace_callback($pattern, array($this, 'collect_anchor'), $content);
    }

    private function collect_anchor($matches) {
        $word = trim($matches[1]);
        if (empty($word)) {
            return $matches[0];
        }

        $base_slug = sanitize_title($word);
        
        if (isset($this->found_anchors[$base_slug])) {
            $this->found_anchors[$base_slug]['count']++;
        } else {
            $this->found_anchors[$base_slug] = array(
                'text' => $word,
                'count' => 1,
                'order' => count($this->found_anchors),
            );
        }

        return $matches[0];
    }

    public function load_textdomain() {
        load_plugin_textdomain('auto-anchors-helps', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_assets() {
        if (!$this->is_dwqa_page()) {
            return;
        }

        // Enqueue styles
        $css = '
            .aliprofi-topics-frame {
                border: 2px solid #ddd;
                padding: 15px;
                margin: 20px 0;
                border-radius: 8px;
                background: #f9f9f9;
            }
            .aliprofi-topics-title {
                color: #666;
                text-align: center;
                margin: 0 0 15px 0;
                font-size: 1.2em;
            }
            .aliprofi-bible-books {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }
            .aliprofi-bible-book-link {
                border-radius: 12px;
                padding: 3px 7px;
                border: 1px solid #ddd;
                background: #ddd;
                font-size: 16px;
                font-family: system-ui, -apple-system, sans-serif;
                line-height: 20px;
                white-space: nowrap;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
                color: #000;
                font-weight: 500;
                text-decoration: none;
                transition: all 0.2s ease;
            }
            .aliprofi-bible-book-link:hover {
                background: #ccc;
                color: #222;
            }
            .aliprofi-anchor-target {
                padding: 2px 5px;
                border-radius: 3px;
                display: inline-block;
            }
            html {
                scroll-behavior: smooth;
            }
        ';

        wp_register_style('auto-anchors-helps', false, array(), '1.0.11');
        wp_enqueue_style('auto-anchors-helps');
        wp_add_inline_style('auto-anchors-helps', $css);

        // Enqueue script for smooth scrolling
        $js = '
            jQuery(document).ready(function($) {
                $("a.aliprofi-bible-book-link").on("click", function(e) {
                    e.preventDefault();
                    var target = $(this.hash);
                    if (target.length) {
                        $("html, body").animate({
                            scrollTop: target.offset().top - 50
                        }, 500);
                    }
                });
            });
        ';
        wp_register_script('auto-anchors-helps', false, array('jquery'), '1.0.11', true);
        wp_enqueue_script('auto-anchors-helps');
        wp_add_inline_script('auto-anchors-helps', $js);
    }

    public function allow_code_tag($allowed, $context) {
        if ($context === 'post') {
            $allowed['code'] = array();
            $allowed['span'] = array(
                'id' => true,
                'class' => true,
            );
        }
        return $allowed;
    }

    public function is_dwqa_page() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        return $post->post_type === 'dwqa-question' || 
               (function_exists('dwqa_is_single_question') && dwqa_is_single_question());
    }

    public function add_anchors_to_content($content) {
        if (empty($content)) {
            return $content;
        }

        $pattern = '/<code>(.*?)<\/code>/is';
        $content = preg_replace_callback($pattern, array($this, 'create_anchor'), $content);
        
        return $content;
    }

    private function create_anchor($matches) {
        $word = trim($matches[1]);
        if (empty($word)) {
            return $matches[0];
        }

        $base_slug = sanitize_title($word);
        
        // Генерируем уникальный ID для каждого вхождения
        $counter = 1;
        $slug = $base_slug;
        
        // Проверяем, есть ли уже такой ID на странице
        static $used_ids = array();
        while (isset($used_ids[$slug])) {
            $counter++;
            $slug = $base_slug . '-' . $counter;
        }
        $used_ids[$slug] = true;

        return sprintf(
            '<span id="%s" class="aliprofi-anchor-target">%s</span>',
            esc_attr($slug),
            esc_html($word)
        );
    }

    public function generate_anchors_cloud($atts) {
        $defaults = array(
            'title' => __('Темы обсудения', 'auto-anchors-helps'),
            'min_font_size' => 14,
            'max_font_size' => 22,
        );

        $atts = shortcode_atts($defaults, $atts, 'dwqa_anchors_cloud');

        // Если якоря не найдены, попробуем найти их сейчас
        if (empty($this->found_anchors)) {
            global $post;
            if ($post) {
                $question_id = $post->ID;
                
                // Если это ответ, получаем ID вопроса
                if ($post->post_type === 'dwqa-answer') {
                    $question_id = $post->post_parent;
                }

                // Сканируем вопрос
                $question_post = get_post($question_id);
                if ($question_post) {
                    $this->scan_content_for_anchors($question_post->post_content);
                }
                
                // Сканируем все ответы
                $answers = get_posts(array(
                    'post_type' => 'dwqa-answer',
                    'post_parent' => $question_id,
                    'posts_per_page' => -1,
                    'post_status' => 'publish'
                ));

                foreach ($answers as $answer) {
                    $this->scan_content_for_anchors($answer->post_content);
                }
            }
        }

        if (empty($this->found_anchors)) {
            return '<div class="aliprofi-topics-frame"><p>' . esc_html__('No anchor links found.', 'auto-anchors-helps') . '</p></div>';
        }

        $output = '<div class="aliprofi-topics-frame">';
        $output .= '<h2 class="aliprofi-topics-title">' . esc_html($atts['title']) . '</h2>';
        $output .= '<div class="aliprofi-bible-books">';

        $counts = array_column($this->found_anchors, 'count');
        $min_count = min($counts) ?: 1;
        $max_count = max($counts) ?: 1;
        $diff = ($max_count - $min_count) ?: 1;

        // Сортируем по порядку появления
        uasort($this->found_anchors, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        foreach ($this->found_anchors as $slug => $data) {
            $font_size = (int)$atts['min_font_size'] +
                (($data['count'] - $min_count) *
                ((int)$atts['max_font_size'] - (int)$atts['min_font_size'])) / $diff;

            $output .= sprintf(
                '<a href="#%s" class="aliprofi-bible-book-link" style="font-size:%dpx">%s</a>',
                esc_attr($slug),
                (int)$font_size,
                esc_html($data['text'])
            );
        }

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    public function output_anchors_cloud() {
        if ($this->is_dwqa_page()) {
            echo do_shortcode('[dwqa_anchors_cloud]');
        }
    }
}

// Инициализация плагина
function auto_anchors_helps_init() {
    AutoAnchorsHelps::get_instance();
}
add_action('plugins_loaded', 'auto_anchors_helps_init');

// Функция для отладки - показывает найденные якоря
function debug_anchors_info() {
    if (current_user_can('manage_options')) {
        global $post;
        $instance = AutoAnchorsHelps::get_instance();
        
        echo '<div style="background: #fff; border: 1px solid #ccc; padding: 10px; margin: 10px;">';
        echo '<h3>Debug: Found Anchors</h3>';
        echo '<p>Current post ID: ' . ($post ? $post->ID : 'none') . '</p>';
        echo '<p>Post type: ' . ($post ? $post->post_type : 'none') . '</p>';
        
        if ($post && $post->post_type === 'dwqa-answer') {
            echo '<p>Question ID: ' . $post->post_parent . '</p>';
            
            // Покажем все ответы для этого вопроса
            $answers = get_posts(array(
                'post_type' => 'dwqa-answer',
                'post_parent' => $post->post_parent,
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
            
            echo '<p>Found answers: ' . count($answers) . '</p>';
            foreach ($answers as $answer) {
                echo '<p>Answer ID: ' . $answer->ID . ' - ' . strlen($answer->post_content) . ' chars</p>';
            }
        }
        
        echo '<pre>' . print_r($instance->found_anchors, true) . '</pre>';
        echo '</div>';
    }
}
add_shortcode('debug_anchors', 'debug_anchors_info');