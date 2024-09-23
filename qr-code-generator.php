<?php
/*
Plugin Name: QR Code Generator
Description: Basit bir QR kod oluşturucu WordPress eklentisi.
Version: 1.0
Author: Salih Lüleci
*/

if (!defined('ABSPATH')) {
    exit; // Doğrudan erişimi engelle
}

// CSS ve JS dosyalarını yükle
function qr_code_generator_enqueue_scripts() {
    wp_enqueue_style('qr-code-style', plugin_dir_url(__FILE__) . 'assets/qr-style.css');
    wp_enqueue_script('qr-code-script', plugin_dir_url(__FILE__) . 'assets/qr-script.js', array('jquery'), null, true);

    // Ajax için gerekli bilgileri sağla
    wp_localize_script('qr-code-script', 'qr_code_generator_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}
add_action('wp_enqueue_scripts', 'qr_code_generator_enqueue_scripts');

// Shortcode ile QR Kod oluşturma formunu ekle
function qr_code_generator_shortcode() {
    ob_start();
    ?>
    <div class="qr-code-generator">
        <h2>QR Kod Oluşturucu</h2>
<p>QR kodunuzu görüntülemek için telefonunuzun kamerasına okutun. Açılış
sayfanızı gerçek bir mobil cihazda önizleme yapmak için telefonunuzla tarayın. </p>

        <input type="text" id="qr-data" placeholder="QR koduna dönüştürülecek veriyi girin" />
        <button id="generate-qr-code">QR Kod Oluştur</button>
        <div id="qr-code-result"></div>
        <!-- Güvenlik Nonce -->
        <?php wp_nonce_field('qr_code_nonce_action', 'qr_code_nonce'); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('qr_code_generator', 'qr_code_generator_shortcode');

// QR Kod oluşturma işlevi - Ajax ile
function generate_qr_code_ajax() {
    // Nonce kontrolü - Güvenlik için
    if (!isset($_POST['qr_code_nonce']) || !wp_verify_nonce($_POST['qr_code_nonce'], 'qr_code_nonce_action')) {
        wp_die('Güvenlik doğrulaması başarısız!');
    }

    // Girdi verisi mevcut mu?
    if (isset($_POST['data'])) {
        $data = sanitize_text_field($_POST['data']);

        // PHP QR Code kütüphanesini dahil et
        require_once(plugin_dir_path(__FILE__) . 'phpqrcode/qrlib.php');

        // QR kodunu geçici klasöre kaydet
        $tempDir = plugin_dir_path(__FILE__) . 'temp/';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755); // Klasörü oluştur ve izinleri ayarla
        }
        $filename = $tempDir . 'qrcode_' . md5($data) . '.png';
        QRcode::png($data, $filename, QR_ECLEVEL_L, 10);

        // QR kodu URL'sini döndür
        echo plugins_url('temp/' . basename($filename), __FILE__);
    }

    wp_die();
}
add_action('wp_ajax_generate_qr_code', 'generate_qr_code_ajax');
add_action('wp_ajax_nopriv_generate_qr_code', 'generate_qr_code_ajax');

// Güvenlik için boş index dosyası ekle
function qr_code_generator_add_index_files() {
    $dirs = array(plugin_dir_path(__FILE__), plugin_dir_path(__FILE__) . 'temp/');
    foreach ($dirs as $dir) {
        if (!file_exists($dir . 'index.php')) {
            file_put_contents($dir . 'index.php', "<?php\n// Güvenlik için boş index dosyası.");
        }
    }
}
register_activation_hook(__FILE__, 'qr_code_generator_add_index_files');


// Saatlik cron job ekleme
function qr_code_generator_activation() {
    if (!wp_next_scheduled('qr_code_cleanup_cron')) {
        wp_schedule_event(time(), 'hourly', 'qr_code_cleanup_cron');
    }
}
register_activation_hook(__FILE__, 'qr_code_generator_activation');

// Cron job'u kaldırma (Eklenti devre dışı bırakıldığında)
function qr_code_generator_deactivation() {
    wp_clear_scheduled_hook('qr_code_cleanup_cron');
}
register_deactivation_hook(__FILE__, 'qr_code_generator_deactivation');

// QR kodu dosyalarını silme işlevi
function qr_code_cleanup() {
    $tempDir = plugin_dir_path(__FILE__) . 'temp/';
    if (file_exists($tempDir)) {
        $files = glob($tempDir . 'qrcode_*.png'); // QR kodu dosyalarını al
        
        $now = time();
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileTime = filemtime($file);
                
                // Dosya 1 saatten eskiyse sil
                if ($now - $fileTime > 3600) {
                    unlink($file);
                }
            }
        }
    }
}
add_action('qr_code_cleanup_cron', 'qr_code_cleanup');

