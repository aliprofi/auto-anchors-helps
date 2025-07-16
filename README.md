# auto-anchors-helps

Auto Anchors Helps

📝 Описание (на русском)Auto Anchors Helps — это плагин для WordPress, созданный для интеграции с плагином DW Question & Answer. Он автоматически создаёт якорные ссылки для слов, выделенных тегом <code> в вопросах и ответах, и отображает их в виде облака тегов через шорткод [dwqa_anchors_cloud]. Это удобно для улучшения навигации по контенту и выделения ключевых терминов на страницах вопросов. Плагин разработан для сайта Алиэкспресс для профессионалов aliprofi.ru.
🔧 Возможности  

Автоматическое создание якорей для слов внутри тегов <code> в вопросах и ответах DW Question & Answer.  
Генерация облака тегов с якорными ссылками через шорткод [dwqa_anchors_cloud].  
Автоматический вывод облака тегов после контента вопроса с использованием хука dwqa_after_question_content.  
Настраиваемый размер шрифта тегов в облаке (атрибуты min_font_size и max_font_size).  
Плавная прокрутка к якорям при клике на ссылки в облаке тегов.  
Лёгкий и оптимизированный код, совместимый с PHP 8.0+ и WordPress 6.7.0.  
Поддержка переводов через текстовый домен auto-anchors-helps.

📦 Установка  

Скачайте или клонируйте репозиторий в папку wp-content/plugins/auto-anchors-helps.  
Убедитесь, что в папке находится файл auto-anchors-helps.php.  
Перейдите в админку WordPress → Плагины → Активируйте плагин Auto Anchors Helps.  
Убедитесь, что плагин DW Question & Answer установлен и активирован.

⚙️ Использование  

Шорткод: Вставьте [dwqa_anchors_cloud] в контент страницы, поста или вопроса DW Question & Answer для отображения облака тегов.Пример:  
[dwqa_anchors_cloud title="Основные темы" min_font_size="12" max_font_size="20"]


title: Заголовок облака тегов (по умолчанию: "Main discussion topics").  
min_font_size: Минимальный размер шрифта тегов (по умолчанию: 14).  
max_font_size: Максимальный размер шрифта тегов (по умолчанию: 22).


Автоматический вывод: Облако тегов автоматически отображается после контента вопроса, если включён хук dwqa_after_question_content.  

Якорные ссылки: Слова внутри тегов <code> в вопросах и ответах автоматически преобразуются в якорные ссылки с уникальными ID.Пример контента:  
This is a test with <code>example</code> and <code>another</code>.

Результат:  
This is a test with <span id="example" class="aliprofi-anchor-target">example</span> and <span id="another" class="aliprofi-anchor-target">another</span>.

🛠 Требования  

WordPress 6.0 или выше.  
Плагин DW Question & Answer (бесплатная или Pro версия).  
PHP 8.0 или выше.

📋 Отладка  

Включите отладку в WordPress, добавив в wp-config.php:  define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);


Логи плагина записываются в wp-content/debug.log с префиксом Auto Anchors Helps.

🌐 СовместимостьПлагин протестирован с DW Question & Answer и WordPress 6.7.0. Для корректной работы убедитесь, что DW Question & Answer обновлён до последней версии, чтобы избежать проблем с устаревшими вызовами add_option или загрузкой переводов.
📜 ЛицензияGPL-2.0+
👨‍💻 АвторАли ПрофиСайт: aliprofi.ru
