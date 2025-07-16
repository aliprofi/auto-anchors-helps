<?php
/**
* Plugin Name: AliProfi Auto Anchors
* Description: Создает якорные ссылки для слов в тегах <code> и выводит облако тегов
* Version: 1.0.3
* Author: Али Профи
* Author URI: https://aliprofi.ru
*/

if (!defined('ABSPATH')) {
    exit;
}

class AliprofiAutoAnchors {
    private $found_anchors = array();
    
    public function __construct() {
        add_filter('the_content', array($this, 'add_anchors_to_content'), 10);
        add_shortcode('aliprofi_anchors_cloud', array($this, 'generate_anchors_cloud'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    // Добавляем стили
    public function enqueue_styles() {
        $css = '
        /* Облако тегов */
        .aliprofi-bible-books {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            border-radius: 5px;
        }
        
        .aliprofi-bible-books .aliprofi-bible-book-link {
            border-radius: 12px;
            padding: 3px 7px;
            border: 1px solid #ddd;
            background: #ddd;
            font-size: 16px;
            font-family: system-ui;
            line-height: 20px;
            white-space: nowrap;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            color: #000;
            font-weight: 500;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
            opacity: 0.8;
        }
        
        .aliprofi-bible-books .aliprofi-bible-book-link:hover {
            background: #ccc !important;
            color: #222 !important;
        }
        
        /* Стиль для якорных ссылок в тексте */
        .aliprofi-anchor-target {
            scroll-margin-top: 40px;
            text-decoration: underline;
            cursor: pointer;
        }
        
        /* Стиль для заголовка облака тегов */
        .aliprofi-anchors-title {
            text-align: center;
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: bold;
            width: 100%;
            display: block;
        }

        /* Рамка с заголовком "Основные темы статьи" */
        .aliprofi-topics-frame {
            border: 2px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: #f9f9f9;
        }

        .aliprofi-topics-title {
            color: #666;
            text-align: center;
            margin-top: 0;
            margin-bottom: 15px;
        }
        ';

        // Регистрируем и подключаем стили
        wp_register_style('aliprofi-auto-anchors', false);
        wp_enqueue_style('aliprofi-auto-anchors');
        wp_add_inline_style('aliprofi-auto-anchors', $css);
    }

    // Добавляем якоря к словам в тегах <code>
    public function add_anchors_to_content($content) {
        if (is_singular() && in_the_loop() && is_main_query()) {
            // Сбрасываем найденные якоря для нового контента
            $this->found_anchors = array();
            
            // Регулярное выражение для поиска слов в тегах <code>
            $pattern = '/<code>(.*?)<\/code>/i';
            $content = preg_replace_callback($pattern, array($this, 'create_anchor'), $content);
        }
        return $content;
    }

    // Создаем якорь для найденного слова в <code>
    private function create_anchor($matches) {
        $word = trim($matches[1]);
        $base_slug = sanitize_title($word);
        
        // Проверяем, был ли уже такой якорь
        if (isset($this->found_anchors[$base_slug])) {
            $this->found_anchors[$base_slug]['count']++;
            $slug = $base_slug . '-' . $this->found_anchors[$base_slug]['count'];
        } else {
            $this->found_anchors[$base_slug] = array(
                'text' => $word,
                'count' => 1,
                'order' => count($this->found_anchors) // Добавляем порядковый номер
            );
            $slug = $base_slug;
        }
        
        return sprintf(
            '<span id="%s" class="aliprofi-anchor-target">%s</span>',
            esc_attr($slug),
            esc_html($word)
        );
    }

    // Генерируем облако тегов через шорткод
    public function generate_anchors_cloud($atts) {
        global $post;
        
        if (!is_singular() || !$post) {
            return '';
        }
        
        $defaults = array(
            'title' => '',
            'min_font_size' => '14',
            'max_font_size' => '22'
        );
        
        $atts = shortcode_atts($defaults, $atts);
        
        // Предварительно обрабатываем контент, чтобы найти все якоря
        if (empty($this->found_anchors)) {
            // Если якоря еще не найдены, обрабатываем контент
            $processed_content = apply_filters('the_content', $post->post_content);
        }
        
        // Если нет якорей, возвращаем пустую строку
        if (empty($this->found_anchors)) {
            return '<div class="aliprofi-bible-books"><p>Якорные ссылки не найдены. Используйте теги &lt;code&gt; для создания якорей.</p></div>';
        }
        
        // Определяем диапазон размеров
        $anchors_count = $this->found_anchors;
        $counts = array_column($anchors_count, 'count');
        $min_count = !empty($counts) ? min($counts) : 0;
        $max_count = !empty($counts) ? max($counts) : 0;
        $diff = ($max_count - $min_count) ?: 1;
        
        // Создаем рамку с заголовком
        $output = '<div class="aliprofi-topics-frame">';
        $output .= '<h2 class="aliprofi-topics-title">Основные темы статьи</h2>';
        
        // Внутри рамки размещаем облако тегов
        $output .= '<div class="aliprofi-bible-books">';
        
        if ($atts['title']) {
            $output .= '<h3>' . esc_html($atts['title']) . '</h3>';
        }
        
        // Сортируем якоря по порядку появления в тексте
        uasort($anchors_count, function($a, $b) {
            return $a['order'] - $b['order'];
        });
        
        foreach ($anchors_count as $slug => $data) {
            // Рассчитываем размер шрифта
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
        
        $output .= '</div>'; // Закрываем .aliprofi-bible-books
        $output .= '</div>'; // Закрываем .aliprofi-topics-frame
        
        return $output;
    }
}

// Инициализация плагина
function aliprofi_auto_anchors_init() {
    new AliprofiAutoAnchors();
}
add_action('plugins_loaded', 'aliprofi_auto_anchors_init');
