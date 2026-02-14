<?php
/**
 * Plugin Name: Crypto News Auto Publisher Ultimate
 * Plugin URI: https://yoursite.com
 * Description: Публикует РЕАЛЬНЫЕ новости о криптовалютах НЕМЕДЛЕННО с изображениями (цены, события, биржи, NFT)
 * Version: 3.0
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

class CryptoNewsAutoPublisherUltimate {

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Крон задачи для автоматических постов
        add_action('crypto_hourly_news_event', array($this, 'publish_all_news'));

        add_action('wp_ajax_manual_publish', array($this, 'manual_publish'));
        add_action('wp_ajax_test_price_news', array($this, 'test_price_news'));
    }

    public function activate() {
        // Публикуем новости каждые 3 часа по времени США
        if (!wp_next_scheduled('crypto_hourly_news_event')) {
            wp_schedule_event(time(), 'twicedaily', 'crypto_hourly_news_event');
        }

        // Публикуем первую партию сразу при активации
        $this->publish_all_news();
    }

    public function deactivate() {
        wp_clear_scheduled_hook('crypto_hourly_news_event');
    }

    public function add_admin_menu() {
        add_menu_page(
            'Crypto News Ultimate',
            'Crypto News',
            'manage_options',
            'crypto-news-ultimate',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>🚀 Crypto News Auto Publisher Ultimate</h1>
            <p><strong>Публикует РЕАЛЬНЫЕ новости о криптовалютах НЕМЕДЛЕННО!</strong></p>

            <div class="card" style="max-width: 900px;">
                <h2>📰 Типы публикуемых новостей:</h2>
                <ul style="font-size: 16px; line-height: 1.8;">
                    <li>💰 <strong>Цены монет</strong> - актуальные цены и изменения (публикуются СРАЗУ)</li>
                    <li>📊 <strong>Рыночные события</strong> - памп/дамп монет, рекорды</li>
                    <li>🏢 <strong>Новости бирж</strong> - листинги, обновления, события</li>
                    <li>🎨 <strong>NFT новости</strong> - тренды, коллекции, продажи</li>
                    <li>⚡ <strong>Блокчейн события</strong> - обновления, хардфорки, новые проекты</li>
                    <li>₿ <strong>Bitcoin анализ</strong> - детальный отчет о главной монете</li>
                </ul>
            </div>

            <div class="card" style="max-width: 900px; margin-top: 20px;">
                <h2>⚙️ Статус</h2>
                <table class="form-table">
                    <tr>
                        <th>Автопубликация:</th>
                        <td><?php echo wp_next_scheduled('crypto_hourly_news_event') ? '<span style="color: green;">✅ Активно (каждые 3 часа)</span>' : '<span style="color: red;">❌ Неактивно</span>'; ?></td>
                    </tr>
                    <tr>
                        <th>Следующая публикация:</th>
                        <td><?php 
                        $next = wp_next_scheduled('crypto_hourly_news_event');
                        echo $next ? date('d.m.Y H:i:s', $next) . ' (UTC)' : 'Не запланировано'; 
                        ?></td>
                    </tr>
                    <tr>
                        <th>Режим публикации:</th>
                        <td><strong style="color: #d63384;">НЕМЕДЛЕННЫЙ</strong> (без планирования)</td>
                    </tr>
                </table>
            </div>

            <div class="card" style="max-width: 900px; margin-top: 20px; background: #e7f3ff;">
                <h2>🎯 Ручная публикация</h2>
                <p>Опубликовать новости прямо сейчас:</p>
                <button class="button button-primary button-hero" onclick="publishNow()" style="font-size: 18px; padding: 10px 30px;">
                    🚀 Опубликовать ВСЕ новости СЕЙЧАС
                </button>
                <div id="result" style="margin-top: 20px;"></div>
            </div>

            <div class="card" style="max-width: 900px; margin-top: 20px;">
                <h2>📋 Последние опубликованные посты</h2>
                <?php
                $recent_posts = get_posts(array(
                    'numberposts' => 5,
                    'category_name' => 'криптовалюты,bitcoin,nft,биржи',
                    'post_status' => 'publish'
                ));

                if ($recent_posts) {
                    echo '<ul style="list-style: none; padding: 0;">';
                    foreach ($recent_posts as $post) {
                        echo '<li style="padding: 10px; border-bottom: 1px solid #eee;">';
                        echo '<strong>' . esc_html($post->post_title) . '</strong><br>';
                        echo '<small style="color: #666;">' . get_the_date('d.m.Y H:i', $post->ID) . '</small>';
                        echo ' | <a href="' . get_edit_post_link($post->ID) . '">Редактировать</a>';
                        echo ' | <a href="' . get_permalink($post->ID) . '" target="_blank">Просмотр</a>';
                        echo '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>Пока нет опубликованных постов. Нажмите кнопку выше!</p>';
                }
                ?>
            </div>

            <script>
            function publishNow() {
                var btn = event.target;
                btn.disabled = true;
                btn.innerHTML = "⏳ Публикуем...";
                document.getElementById("result").innerHTML = "<div class='notice notice-info'><p>⏳ Загружаем новости и создаем посты...</p></div>";

                jQuery.post(ajaxurl, {action: "manual_publish"}, function(response) {
                    document.getElementById("result").innerHTML = "<div class='notice notice-success'><p>✅ " + response + "</p></div>";
                    btn.disabled = false;
                    btn.innerHTML = "🚀 Опубликовать ВСЕ новости СЕЙЧАС";
                    setTimeout(function() { location.reload(); }, 2000);
                }).fail(function() {
                    document.getElementById("result").innerHTML = "<div class='notice notice-error'><p>❌ Ошибка при публикации</p></div>";
                    btn.disabled = false;
                    btn.innerHTML = "🚀 Опубликовать ВСЕ новости СЕЙЧАС";
                });
            }
            </script>
        </div>
        <?php
    }

    // Главная функция - публикует ВСЕ типы новостей НЕМЕДЛЕННО
    public function publish_all_news() {
        $total = 0;

        // 1. Посты о ценах топ монет (СРАЗУ публикуем)
        $total += $this->publish_price_updates();

        // 2. Новости о рыночных событиях
        $total += $this->publish_market_events();

        // 3. Bitcoin отчет
        $total += $this->publish_bitcoin_report();

        // 4. NFT новости (если есть активность)
        $total += $this->publish_nft_news();

        return $total;
    }

    // 1. Публикация постов о ЦЕНАХ (НЕМЕДЛЕННО, без планирования)
    private function publish_price_updates() {
        $response = wp_remote_get('https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_rank&per_page=15&page=1&sparkline=false&price_change_percentage=1h,24h,7d');

        if (is_wp_error($response)) {
            return 0;
        }

        $coins = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($coins)) {
            return 0;
        }

        // Сортируем по абсолютному изменению за 24ч
        usort($coins, function($a, $b) {
            return abs($b['price_change_percentage_24h']) - abs($a['price_change_percentage_24h']);
        });

        // Берем топ-2 монеты с наибольшими изменениями
        $selected = array_slice($coins, 0, 2);
        $created = 0;

        foreach ($selected as $coin) {
            // ПУБЛИКУЕМ СРАЗУ (не планируем)
            if ($this->create_price_post($coin)) {
                $created++;
            }
        }

        return $created;
    }

    // 1. Publish instant price update posts
    private function create_price_post($coin) {
        $existing = get_posts(array(
            'meta_query' => array(
                array(
                    'key' => 'crypto_coin_id',
                    'value' => $coin['id'],
                    'compare' => '='
                )
            ),
            'date_query' => array(array('after' => '6 hours ago')),
            'post_status' => 'any',
            'numberposts' => 1
        ));

        if (!empty($existing)) {
            return false;
        }

        $name = $coin['name'];
        $symbol = strtoupper($coin['symbol']);
        $price = $coin['current_price'];
        $change_24h = $coin['price_change_percentage_24h'];
        $change_1h = isset($coin['price_change_percentage_1h_in_currency']) ? $coin['price_change_percentage_1h_in_currency'] : 0;
        $change_7d = isset($coin['price_change_percentage_7d_in_currency']) ? $coin['price_change_percentage_7d_in_currency'] : null;

        $emoji = $change_24h >= 0 ? '📈' : '📉';
        $trend = $change_24h >= 0 ? 'is climbing' : 'is pulling back';

        $title = $emoji . ' ' . $name . ' (' . $symbol . ') ' . $trend . ' — $' . number_format($price, $price < 1 ? 4 : 2);

        $hero_image = !empty($coin['image']) ? $coin['image'] : 'https://images.unsplash.com/photo-1621761191319-c6fb62004040?auto=format&fit=crop&w=1200&h=675&q=80';

        $content = $this->build_inline_image($hero_image, $name . ' crypto chart');
        $content .= '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 12px; color: white; margin-bottom: 25px;">';
        $content .= '<h2 style="color: white; margin: 0 0 15px 0;">💰 ' . $name . ' Price Breakdown</h2>';
        $content .= '<p style="font-size: 36px; font-weight: bold; margin: 0;">$' . number_format($price, $price < 1 ? 6 : 2) . '</p>';
        $content .= '<p style="font-size: 20px; margin: 10px 0 0 0;">24h move: <strong style="' . ($change_24h >= 0 ? 'color: #4ade80;' : 'color: #f87171;') . '">' . ($change_24h >= 0 ? '+' : '') . number_format($change_24h, 2) . '%</strong></p>';
        $content .= '</div>';

        $content .= '<h3>📊 Current Metrics</h3>';
        $content .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
        $content .= '<tr style="background: #f8f9fa;"><td style="padding: 15px; border: 1px solid #dee2e6;"><strong>💵 Spot Price</strong></td><td style="padding: 15px; border: 1px solid #dee2e6; font-size: 18px;">$' . number_format($price, $price < 1 ? 6 : 2) . '</td></tr>';
        if ($change_1h != 0) {
            $content .= '<tr><td style="padding: 15px; border: 1px solid #dee2e6;"><strong>⚡ 1 Hour</strong></td><td style="padding: 15px; border: 1px solid #dee2e6; color: ' . ($change_1h >= 0 ? 'green' : 'red') . '; font-weight: bold;">' . ($change_1h >= 0 ? '+' : '') . number_format($change_1h, 2) . '%</td></tr>';
        }
        $content .= '<tr style="background: #f8f9fa;"><td style="padding: 15px; border: 1px solid #dee2e6;"><strong>📅 24 Hours</strong></td><td style="padding: 15px; border: 1px solid #dee2e6; color: ' . ($change_24h >= 0 ? 'green' : 'red') . '; font-weight: bold; font-size: 18px;">' . ($change_24h >= 0 ? '+' : '') . number_format($change_24h, 2) . '%</td></tr>';
        if ($change_7d !== null) {
            $content .= '<tr><td style="padding: 15px; border: 1px solid #dee2e6;"><strong>📈 7 Days</strong></td><td style="padding: 15px; border: 1px solid #dee2e6; color: ' . ($change_7d >= 0 ? 'green' : 'red') . '; font-weight: bold;">' . ($change_7d >= 0 ? '+' : '') . number_format($change_7d, 2) . '%</td></tr>';
        }
        $content .= '<tr style="background: #f8f9fa;"><td style="padding: 15px; border: 1px solid #dee2e6;"><strong>🏆 Market Rank</strong></td><td style="padding: 15px; border: 1px solid #dee2e6;">#' . $coin['market_cap_rank'] . '</td></tr>';
        $content .= '<tr><td style="padding: 15px; border: 1px solid #dee2e6;"><strong>💎 Market Cap</strong></td><td style="padding: 15px; border: 1px solid #dee2e6;">$' . number_format($coin['market_cap'], 0, '.', ',') . '</td></tr>';
        $content .= '<tr style="background: #f8f9fa;"><td style="padding: 15px; border: 1px solid #dee2e6;"><strong>📊 24h Volume</strong></td><td style="padding: 15px; border: 1px solid #dee2e6;">$' . number_format($coin['total_volume'], 0, '.', ',') . '</td></tr>';
        if (isset($coin['high_24h']) && isset($coin['low_24h'])) {
            $content .= '<tr><td style="padding: 15px; border: 1px solid #dee2e6;"><strong>📈 24h High</strong></td><td style="padding: 15px; border: 1px solid #dee2e6;">$' . number_format($coin['high_24h'], $price < 1 ? 6 : 2) . '</td></tr>';
            $content .= '<tr style="background: #f8f9fa;"><td style="padding: 15px; border: 1px solid #dee2e6;"><strong>📉 24h Low</strong></td><td style="padding: 15px; border: 1px solid #dee2e6;">$' . number_format($coin['low_24h'], $price < 1 ? 6 : 2) . '</td></tr>';
        }
        $content .= '</table>';
        $content .= '<p style="color: #666; font-size: 14px;"><em>📅 Published: ' . gmdate('Y-m-d H:i') . ' UTC • Source: CoinGecko</em></p>';

        $post_data = array(
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_category' => array($this->get_or_create_category('Cryptocurrency')),
            'tags_input'    => 'crypto, ' . strtolower($symbol) . ', ' . strtolower($name) . ', coin price, market data'
        );

        $post_id = wp_insert_post($post_data);
        if ($post_id) {
            update_post_meta($post_id, 'crypto_coin_id', $coin['id']);
            update_post_meta($post_id, 'crypto_price', $price);
            if (!empty($coin['image'])) {
                $this->set_featured_image_from_url($post_id, $coin['image'], $name . ' logo');
            }
            return true;
        }

        return false;
    }

    // 2. Новости о рыночных событиях
    // 2. Market overview post
    private function publish_market_events() {
        $response = wp_remote_get('https://api.coingecko.com/api/v3/global');
        if (is_wp_error($response)) {
            return 0;
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['data'])) {
            return 0;
        }

        $market_data = $data['data'];
        $btc_dominance = $market_data['market_cap_percentage']['btc'];
        $eth_dominance = $market_data['market_cap_percentage']['eth'];
        $total_market_cap = $market_data['total_market_cap']['usd'];
        $market_change_24h = $market_data['market_cap_change_percentage_24h_usd'];

        $today_posts = get_posts(array('meta_key' => 'post_type_crypto', 'meta_value' => 'market_overview', 'date_query' => array(array('after' => 'today')), 'post_status' => 'any', 'numberposts' => 1));
        if (!empty($today_posts)) {
            return 0;
        }

        $emoji = $market_change_24h >= 0 ? '📈' : '📉';
        $title = $emoji . ' Crypto Market Overview: Total Cap at $' . number_format($total_market_cap / 1000000000000, 2) . 'T';

        $content = $this->build_inline_image('https://images.unsplash.com/photo-1642543492481-44e81e3914a7?auto=format&fit=crop&w=1200&h=675&q=80', 'crypto market dashboard');
        $content .= '<h3>📊 Key Indicators</h3>';
        $content .= '<p>Total market cap: <strong>$' . number_format($total_market_cap, 0, '.', ',') . '</strong><br>24h change: <strong>' . ($market_change_24h >= 0 ? '+' : '') . number_format($market_change_24h, 2) . '%</strong><br>BTC dominance: <strong>' . number_format($btc_dominance, 2) . '%</strong><br>ETH dominance: <strong>' . number_format($eth_dominance, 2) . '%</strong></p>';
        $content .= '<p style="color: #666; font-size: 14px;"><em>📅 ' . gmdate('Y-m-d H:i') . ' UTC • Source: CoinGecko</em></p>';

        $post_id = wp_insert_post(array('post_title' => $title, 'post_content' => $content, 'post_status' => 'publish', 'post_author' => 1, 'post_category' => array($this->get_or_create_category('Cryptocurrency')), 'tags_input' => 'crypto, market overview, market cap, bitcoin dominance, ethereum dominance'));
        if ($post_id) {
            update_post_meta($post_id, 'post_type_crypto', 'market_overview');
            $this->set_featured_image_from_url($post_id, 'https://images.unsplash.com/photo-1640161704729-cbe966a08476?auto=format&fit=crop&w=1200&h=675&q=80', 'Crypto market overview');
            return 1;
        }
        return 0;
    }

    // 3. Bitcoin отчет
    // 3. Bitcoin daily report
    private function publish_bitcoin_report() {
        $response = wp_remote_get('https://api.coingecko.com/api/v3/coins/bitcoin?localization=false&tickers=false&community_data=false&developer_data=false');
        if (is_wp_error($response)) {
            return 0;
        }
        $btc_data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($btc_data)) {
            return 0;
        }

        $today_btc = get_posts(array('meta_key' => 'post_type_crypto', 'meta_value' => 'bitcoin_daily', 'date_query' => array(array('after' => '18 hours ago')), 'post_status' => 'any', 'numberposts' => 1));
        if (!empty($today_btc)) {
            return 0;
        }

        $price = $btc_data['market_data']['current_price']['usd'];
        $change_24h = $btc_data['market_data']['price_change_percentage_24h'];
        $emoji = $change_24h >= 0 ? '📈' : '📉';
        $title = $emoji . ' Bitcoin at $' . number_format($price, 0, '.', ',') . ' | Daily Analysis ' . gmdate('Y-m-d');

        $hero_btc = !empty($btc_data['image']['large']) ? $btc_data['image']['large'] : 'https://images.unsplash.com/photo-1518546305927-5a555bb7020d?auto=format&fit=crop&w=1200&h=675&q=80';
        $content = $this->build_inline_image($hero_btc, 'Bitcoin visual');
        $content .= '<p><strong>Price:</strong> $' . number_format($price, 2) . ' • <strong>24h:</strong> ' . ($change_24h >= 0 ? '+' : '') . number_format($change_24h, 2) . '%</p>';
        $content .= '<p style="color: #666; font-size: 14px;"><em>📅 ' . gmdate('Y-m-d H:i') . ' UTC • Source: CoinGecko</em></p>';

        $post_id = wp_insert_post(array('post_title' => $title, 'post_content' => $content, 'post_status' => 'publish', 'post_author' => 1, 'post_category' => array($this->get_or_create_category('Bitcoin')), 'tags_input' => 'bitcoin, btc, bitcoin price, crypto analysis, market update'));
        if ($post_id) {
            update_post_meta($post_id, 'post_type_crypto', 'bitcoin_daily');
            if (!empty($btc_data['image']['large'])) {
                $this->set_featured_image_from_url($post_id, $btc_data['image']['large'], 'Bitcoin');
            }
            return 1;
        }
        return 0;
    }

    // 4. NFT новости (базовая версия)
    // 4. NFT trend post
    private function publish_nft_news() {
        $today_nft = get_posts(array('meta_key' => 'post_type_crypto', 'meta_value' => 'nft_news', 'date_query' => array(array('after' => '2 days ago')), 'post_status' => 'any', 'numberposts' => 1));
        if (!empty($today_nft)) {
            return 0;
        }

        $title = '🎨 NFT Market Update: Current Trends and Leading Collections';
        $content = $this->build_inline_image('https://images.unsplash.com/photo-1639762681485-074b7f938ba0?auto=format&fit=crop&w=1200&h=675&q=80', 'NFT digital art display');
        $content .= '<p>The NFT sector continues to evolve with digital art, gaming assets, music drops, and sports collectibles.</p>';
        $content .= '<p style="color: #666; font-size: 14px;"><em>📅 ' . gmdate('Y-m-d') . ' • Sector update</em></p>';

        $post_id = wp_insert_post(array('post_title' => $title, 'post_content' => $content, 'post_status' => 'publish', 'post_author' => 1, 'post_category' => array($this->get_or_create_category('NFT')), 'tags_input' => 'nft, digital art, opensea, blur, magic eden, web3'));
        if ($post_id) {
            update_post_meta($post_id, 'post_type_crypto', 'nft_news');
            $this->set_featured_image_from_url($post_id, 'https://images.unsplash.com/photo-1642052501778-1e04f3f6f6f0?auto=format&fit=crop&w=1200&h=675&q=80', 'NFT market');
            return 1;
        }
        return 0;
    }

    // Helper to render a fixed-size inline image in posts
    private function build_inline_image($image_url, $alt_text) {
        return '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($alt_text) . '" width="1200" height="675" style="display:block;width:100%;max-width:1200px;height:auto;aspect-ratio:1200 / 675;object-fit:cover;border-radius:12px;margin:0 0 20px 0;" />';
    }

    // Загрузка изображения из URL
    // Загрузка изображения из URL
    private function set_featured_image_from_url($post_id, $image_url, $image_name) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($image_url);

        if (is_wp_error($tmp)) {
            return false;
        }

        $file_array = array(
            'name'     => basename($image_url),
            'tmp_name' => $tmp
        );

        $attachment_id = media_handle_sideload($file_array, $post_id, $image_name);

        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }

        set_post_thumbnail($post_id, $attachment_id);

        return true;
    }

    private function get_or_create_category($category_name) {
        $category = get_term_by('name', $category_name, 'category');

        if (!$category) {
            $category_id = wp_create_category($category_name);
            return $category_id;
        }

        return $category->term_id;
    }

    // AJAX обработчики
    public function manual_publish() {
        $total = $this->publish_all_news();
        echo 'Успешно опубликовано постов: ' . $total . '! Перезагружаем страницу...';
        wp_die();
    }

    public function test_price_news() {
        $result = $this->publish_price_updates();
        echo 'Создано постов о ценах: ' . $result;
        wp_die();
    }
}

new CryptoNewsAutoPublisherUltimate();
