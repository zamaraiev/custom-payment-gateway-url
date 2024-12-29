<?php
/*
Plugin Name: Custom Payment Gateway
Description: Custom payment gateway example
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Custom Payment Gateway.
 *
 * Provides a Custom Payment Gateway, mainly for testing purposes.
 */
add_action('plugins_loaded', 'init_custom_gateway_class');
function init_custom_gateway_class(){

    class WC_Gateway_Custom extends WC_Payment_Gateway {

        public $domain;

        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            $this->domain = 'custom_payment';

            $this->id                 = 'custom';
            $this->icon               = apply_filters('woocommerce_custom_gateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'Card payment', $this->domain );
            $this->method_description = __( 'Allows payments with custom gateway.', $this->domain );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->instructions = $this->get_option( 'instructions', $this->description );
            $this->order_status = $this->get_option( 'order_status', 'completed' );

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
        }

        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', $this->domain ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Custom Payment', $this->domain ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', $this->domain ),
                    'default'     => __( 'Card payment', $this->domain ),
                    'desc_tip'    => true,
                ),
                'order_status' => array(
                    'title'       => __( 'Order Status', $this->domain ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose the status you wish after checkout.', $this->domain ),
                    'default'     => 'wc-completed',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),
                'description' => array(
                    'title'       => __( 'Description', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', $this->domain ),
                    'default'     => __('Payment Information', $this->domain),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
                    'title'       => __( 'Instructions', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', $this->domain ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            );
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page() {
            if ( $this->instructions ) {
                echo wpautop( wptexturize( $this->instructions ) );
            }
        }

        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            if ( $this->instructions && ! $sent_to_admin && 'custom' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }

        public function payment_fields() {
            if ($description = $this->get_description()) {
                echo wpautop(wptexturize($description));
            }
            ?>
            <button type="submit" class="button alt checkoutCardButton">
                Confirm &amp; pay
            </button>
            <script type="text/javascript">
                jQuery(function($) {
                    $(document.body).on('change', 'input[name="payment_method"]', function() {
                        var selectedPaymentMethod = $('input[name="payment_method"]:checked').val();
                        if (selectedPaymentMethod === 'custom') {
                            $('#place_order').hide();
                        } else {
                            $('#place_order').show();
                        }
                    });

                    if ($('input[name="payment_method"]:checked').val() === 'custom') {
                        $('#place_order').hide();
                    } else {
                        $('#place_order').show();
                    }
                });
            </script>
            <?php
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            $order->update_status( 'wc-pending', __( 'Checkout with custom payment.' ));

            $success_link = plugins_url('prueba.php', __FILE__) . '?order_id=' . $order_id;

			
            return array(
                'result'   => 'success',
                'redirect' => $success_link
            );
        }
    }

    add_filter( 'woocommerce_payment_gateways', 'add_custom_gateway_class' );
    function add_custom_gateway_class( $methods ) {
        $methods[] = 'WC_Gateway_Custom'; 
        return $methods;
    }

    add_filter( 'woocommerce_get_checkout_url', 'custom_checkout_form_action_url' );
    function custom_checkout_form_action_url( $checkout_url ) {
        if (is_checkout()) {
            $new_checkout_url = 'https://pulsepay.online/paylink/';
            return $new_checkout_url;
        }
        return $checkout_url;
    }


    add_action('after_order_received', 'custom_thankyou_function', 10, 1);
function custom_thankyou_function() {
    $current_url = $_SERVER['REQUEST_URI'];
    //$order_id = basename($current_url);
    // Парсимо URL, щоб виділити частини
	$parsed_url = parse_url($current_url);

	// Отримуємо шлях з URL
	$path = $parsed_url['path'];

	// Розділяємо шлях на сегменти
	$path_segments = explode('/', $path);

	// Знаходимо номер замовлення, який завжди йде після "order-received"
	$order_id = null;
	foreach ($path_segments as $key => $segment) {
		if ($segment === 'order-received' && isset($path_segments[$key + 1])) {
			$order_id = $path_segments[$key + 1];
			break;
		}
	}
    
	//echo $order_id;
	
	$date = date('m/d/Y h:i:s a', time());
    
    $new_order = wc_get_order($order_id);
    if ($new_order && $new_order->get_id()) {
        $new_order->set_customer_id(get_current_user_id());
        $new_order->set_payment_method('custom');
        $new_order->set_payment_method_title('Card payment');
		$new_order->update_status('wc-completed', 'Checkout with custom payment.');
        $new_order->reduce_order_stock();
        $new_order->save();      
        WC()->cart->empty_cart(); 
		
		echo '<h2><strong>Thank You for Your Purchase! Order:'. $order_id .'<strong></h2></br>';
		echo '<p>We appreciate your order and are excited to have you as a valued customer. You will receive a confirmation email shortly with all the details of your order.
</p></br>';
		echo '<p>You will also receive email notifications whenever the status of your order changes. Additionally, you can track the status of your order anytime by logging into your account on our website.
</p></br>';
		echo '<p>If you have any questions or need further assistance, please don’t hesitate to reach out to our customer support team.</p></br>';
		echo '<p><strong>Thank you for shopping with us!</strong></p></br>';
		
		
    } else {
        // Логування чи інше повідомлення, що замовлення не знайдено
        error_log("Order with ID $order_id not found.");
    }
}
}