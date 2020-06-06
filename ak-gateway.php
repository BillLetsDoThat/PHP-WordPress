<?php
/*
Plugin Name: Alikassa Gateway
Description: Платежный шлюз "Аликасса" для сайтов на WordPress
Version: 1.10
Last Update: 04.12.2019
Author: billletsdothat
Author URI: https://kwork.ru/user/billletsdothat
*/
if (!defined('ABSPATH')) exit;

add_action('plugins_loaded', 'ak_init', 0);

function ak_init()
{
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_alikassa extends WC_Payment_Gateway
    {
        const akUrlSCI = 'https://sci.alikassa.com/';

        public function __construct()
        {
            global $woocommerce;

            $plugin_dir = basename(dirname(__FILE__));
            load_plugin_textdomain('alikassa', false, $plugin_dir);

            $this->id = 'alikassa';
            $this->has_fields = false;
            $this->method_title = __('Аликасса', 'alikassa');
            $this->method_description = __('Аликасса', 'alikassa');
            $this->init_form_fields();
            $this->init_settings();

            $this->icon = apply_filters('woocommerce_alikassa_icon', plugin_dir_url(__FILE__) . 'images/logo_alikassa.svg');

            $this->title = $this->get_option('title');
			$this->hashAlgo = $this->get_option('hashAlgo');
            $this->test_mode = ($this->get_option('test_mode') == 'yes') ? 1 : 0;
            $this->description = $this->get_option('description');
            $this->merchant_uuid = $this->get_option('merchant_uuid');
            $this->secret = $this->get_option('secret');
            $this->language = $this->get_option('language');
			$this->useSign = $this->get_option('useSign');
            $this->paymenttime = $this->get_option('paymenttime');
            $this->payment_method = $this->get_option('payment_method');

			include 'AliKassa.class.php';
			$aliAPI = new \AliKassa($this->merchant_uuid, $this->secret, $method->hashAlgo);

            // Actions
            add_action('woocommerce_receipt_alikassa', array($this, 'receipt_page'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // Payment listener/API hook
            add_action('woocommerce_api_wc_gateway_alikassa', array($this, 'check_ipn_response'));

            // Payment listener/API hook
            add_action('woocommerce_api_wc_ak_sign', array($this, 'ajaxSign_generate'));

            // Answer from SCI/API hook
            add_action('woocommerce_api_wc_ak_api', array($this, 'getAnswerFromAPI'));

            if (!$this->is_valid_for_use()) {
                $this->enabled = false;
            }
        }

        public function admin_options()
        {
            global $woocommerce;

            ?>
            <h3><?php _e('Аликасса 2.0', 'alikassa'); ?></h3>

            <?php if ($this->is_valid_for_use()) { ?>

            <table class="form-table">
                <?php

                $this->generate_settings_html();
                ?>
            </table>

        <?php } else { ?>
            <div class="inline error"><p>
                    <strong><?php _e('Шлюз отключен', 'alikassa'); ?></strong>: <?php _e('Alikassa не поддерживает валюты Вашего магазина.', 'woocommerce'); ?>
                </p></div>
            <?php
        }
        }

        public function init_form_fields()
        {
            global $woocommerce;

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Вкл. / Выкл.', 'alikassa'),
                    'type' => 'checkbox',
                    'label' => __('Включить', 'alikassa'),
                    'default' => 'yes'
                ),
                'test_mode' => array(
                    'title' => __('Тестовый режим', 'alikassa'),
                    'type' => 'checkbox',
                    'label' => __('Включить тестовый режим', 'alikassa'),
                    'default' => 'yes'
                ),
                'useSign' => array(
                    'title' => __('Использовать подпись платежа', 'alikassa'),
                    'type' => 'checkbox',
                    'label' => __('Да', 'alikassa'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Заголовок', 'alikassa'),
                    'type' => 'text',
                    'description' => __('Заголовок, который отображается на странице оформления заказа', 'alikassa'),
                    'default' => __('Аликасса', 'alikassa'),
                    'desc_tip' => true,
                ),
				
				'hashAlgo' => array(
					'title' => __('Алгоритм шифрования', 'alikassa'),
					'type' => 'select',
					'description' => __('Выберите алгоритм формирования подписи', 'alikassa'),
					'default' => __('Аликасса', 'alikassa'),
					'required'    => true,
					'options'     => array(
						'md5' => __('MD5'),
						'sha25' => __('NO')
					),
					'default' => 'md5'),
				),
				
                'description' => array(
                    'title' => __('Описание', 'alikassa'),
                    'type' => 'textarea',
                    'description' => __('Описание, которое отображается в процессе выбора формы оплаты', 'alikassa'),
                    'default' => __('Оплатить через электронную платежную систему Аликасса', 'alikassa'),
                ),
                'merchant_uuid' => array(
                    'title' => __('Идентификатор кассы', 'alikassa'),
                    'type' => 'text',
                    'description' => __('Уникальный идентификатор кассы в системе Аликасса.', 'alikassa'),
                ),
                'secret' => array(
                    'title' => __('Секретный ключ', 'alikassa'),
                    'type' => 'text',
                    'description' => __('Секретный ключ', 'alikassa'),
                ),

            );
        }

        function is_valid_for_use()
        {
            if (!in_array(get_option('woocommerce_currency'), array('RUB', 'UAH', 'USD', 'EUR'))) {
                return false;
            }

            return true;
        }

        function process_payment($order_id)
        {
            $order = new WC_Order($order_id);
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(1)
            );

        }

        public function receipt_page($order)
        {
            echo '<p>' . __('Спасибо за Ваш заказ, пожалуйста, нажмите кнопку ниже, чтобы заплатить.', 'alikassa') . '</p>';
            echo $this->generate_form($order);
        }

        public function generate_form($order_id)
        {
            global $woocommerce;

            $order = new WC_Order($order_id);

            $FormData = [
                'amount' => $order->order_total,
                'currency' => get_woocommerce_currency(),
                'merchantUuid' => $this->merchant_uuid,
                'orderId' => $order_id,
                'desc' => "Order # $order_id",
                'urlSuccess' => str_replace('amp;', '', $this->get_return_url($order)),
                'urlFail' => str_replace('amp;', '', $order->get_cancel_order_url()),
            ];


			include 'AliKassa.class.php';
			$aliAPI = new \AliKassa($this->merchant_uuid, $this->secret, $method->hashAlgo);

            $FormData['sign'] = $aliAPI->sign($FormData, $this->secret, $this->hashAlgo);
            $hidden_fields = '';
            foreach ($FormData as $key => $value) {
                $hidden_fields .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . htmlspecialchars($value) . '" />';
            }

            $cancel_url = '<a class="button cancel" href="'
                . $order->get_cancel_order_url() .
                '">' . __('Отказаться от оплаты', 'alikassa') . '</a>';

            $ajax_url = add_query_arg('wc-api', 'wc_ak_sign', home_url('/'));
            $plugin_path = plugin_dir_url('ak-gateway') . 'ak-gateway/';
            $image_path = plugin_dir_url('ak-gateway') . 'ak-gateway/images/';

            include 'tpl.php';
        }

        public function check_ipn_response()
        {
            global $woocommerce;

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                $ak_response = $_POST;
                $order_id = (int)$ak_response['orderId'];
                $order = new WC_Order($order_id);
                if (!$order) {
                    return false;
                }


                $key = $this->secret;

				include 'AliKassa.class.php';
				$aliAPI = new \AliKassa($this->merchant_uuid, $this->secret, $method->hashAlgo);

                $ak_sign = $aliAPI->sign($ak_response, $key, $this->hashAlgo);

                if ($ak_response['sign'] == $ak_sign && ($ak_response['merchantUuid'] == $this->merchant_uuid)) {

                    if ($ak_response['payStatus'] == 'success') {
                        $order->payment_complete();
                        $order->add_order_note(__('Платеж успешно оплачен через Аликассу', 'alikassa'));
                    } elseif ($ak_response['payStatus'] == 'fail') {
                        $order->update_status('failed', __('Платеж не оплачен', 'alikassa'));
                        $order->add_order_note(__('Платеж не оплачен', 'alikassa'));
                    }
                    echo 'OK';
                    header("HTTP/1.1 200 OK");
                    exit;
                } else {
                    $order = new WC_Order($ak_response['orderId']);
                    wp_redirect($order->get_cancel_order_url());
                    exit;
                }
            }
        }

        public function ajaxSign_generate()
        {
            header("Pragma: no-cache");
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
            header("Content-type: text/plain");
            $request = $_POST;

			include 'AliKassa.class.php';
			$aliAPI = new \AliKassa($this->merchant_uuid, $this->secret, $method->hashAlgo);


            $data = $aliAPI->sign($request, $this->secret, $this->hashAlgo);

			header("Content-type: plain/text");
            echo $data;
            exit;
        }

    }

    function woocommerce_add_alikassa_gateway($methods)
    {
        $methods[] = 'WC_Gateway_alikassa';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_alikassa_gateway');
}
