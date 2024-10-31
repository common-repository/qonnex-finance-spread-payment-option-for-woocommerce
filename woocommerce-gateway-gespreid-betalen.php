<?php
/*
Plugin Name: WooCommerce Gespreid Betalen
Plugin URI: #
Description: WooCommerce Gespreid Betalen Payment Gateway.
Version: 1.0.0
Author: Qonnex
Author URI: #
License: GNU General Public License v3.0
License URI: #
Donate link: #
Contributors: Qonnex
*/
add_action('plugins_loaded', 'woocommerce_gateway_gespreid_betalen_init', 0);
function woocommerce_gateway_gespreid_betalen_init()
{
    if (!class_exists('WC_Payment_Gateway')) return;

    /**
     * Gespreid Betalen Gateway.
     *
     * Provides a Gespreid Betalen Payment Gateway.
     *
     * @class 		WC_Gateway_Gespreid_Betalen
     * @extends		WC_Payment_Gateway
     * @version		1.0.0
     * @package		WooCommerce/Classes/Payment
     * @author 		Qonnex
     */
    class WC_Gateway_Gespreid_Betalen extends WC_Payment_Gateway {

        /**
         * Constructor for the gateway.
         */
        public function __construct() {
            $this->id                 = 'gespreid_betalen';
            $this->method_title       = __('Gespreid betalen', 'woocommerce');
            $this->method_description = __('Gespreid betalen payment.', 'woocommerce');
            $this->has_fields         = false;

            // Load the settings
            $this->init_form_fields();
            $this->init_settings();

            // Get settings
            $this->title              = $this->get_option( 'title' );
            $this->description        = $this->get_option( 'description' );
            $this->instructions       = $this->get_option( 'instructions', $this->description );
            $this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
            $this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes' ? true : false;

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_gespreid_betalen', array( $this, 'thankyou_page' ) );
            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
            add_action('woocommerce_receipt_gespreid_betalen', array(&$this, 'receipt_page'));
        }

        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields() {
            $shipping_methods = array();
            if ( is_admin() )
                foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
                    $shipping_methods[ $method->id ] = $method->get_title();
                }
            $this->form_fields = include( 'gespreid-betalen-settings.php' );
        }

        /**
         * Check If The Gateway Is Available For Use.
         *
         * @return bool
         */
        public function is_available() {
            $order          = null;
            $needs_shipping = false;
            //Test if comply with minimum order
            if(WC()->cart->subtotal < floatval($this->settings['min_order_total'])){
                return false;
            }
            // Test if shipping is needed first
            if ( WC()->cart && WC()->cart->needs_shipping() ) {
                $needs_shipping = true;
            } elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
                $order_id = absint( get_query_var( 'order-pay' ) );
                $order    = wc_get_order( $order_id );
                // Test if order needs shipping.
                if ( 0 < sizeof( $order->get_items() ) ) {
                    foreach ( $order->get_items() as $item ) {
                        $_product = $order->get_product_from_item( $item );
                        if ( $_product && $_product->needs_shipping() ) {
                            $needs_shipping = true;
                            break;
                        }
                    }
                }
            }
            $needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );
            // Virtual order, with virtual disabled
            if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
                return false;
            }
            // Check methods
            if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {
                // Only apply if all packages are being shipped via chosen methods, or order is virtual
                $chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );
                if ( isset( $chosen_shipping_methods_session ) ) {
                    $chosen_shipping_methods = array_unique( $chosen_shipping_methods_session );
                } else {
                    $chosen_shipping_methods = array();
                }
                $check_method = false;
                if ( is_object( $order ) ) {
                    if ( $order->shipping_method ) {
                        $check_method = $order->shipping_method;
                    }

                } elseif ( empty( $chosen_shipping_methods ) || sizeof( $chosen_shipping_methods ) > 1 ) {
                    $check_method = false;
                } elseif ( sizeof( $chosen_shipping_methods ) == 1 ) {
                    $check_method = $chosen_shipping_methods[0];
                }
                if ( ! $check_method ) {
                    return false;
                }
                $found = false;
                foreach ( $this->enable_for_methods as $method_id ) {
                    if ( strpos( $check_method, $method_id ) === 0 ) {
                        $found = true;
                        break;
                    }
                }
                if ( ! $found ) {
                    return false;
                }
            }
            return parent::is_available();
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {
            global $woocommerce;
            $order = new WC_Order($order_id);
            if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' ) ) {
                $checkout_payment_url = $order->get_checkout_payment_url( true );
            } else {
                $checkout_payment_url = get_permalink( get_option ( 'woocommerce_pay_page_id' ) );
            }
            $order->reduce_order_stock();
            WC()->cart->empty_cart();
            return array(
                'result' => 'success',
                'redirect' => add_query_arg(
                    'order',
                    $order->id,
                    add_query_arg(
                        'key',
                        $order->order_key,
                        $checkout_payment_url
                    )
                )
            );
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page()
        {
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
            if ( $this->instructions && ! $sent_to_admin && 'gespreid_betalen' === $order->payment_method ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }

        /**
         * Receipt Page
         **/
        public function receipt_page($order){
            $aPayData =  $this->getPayData($order);
            echo "<script type='text/javascript'>
            var mo_inl_dlr    = '".$aPayData['mo_inl_dlr']."';
            var mo_inl_plc    = '".$aPayData['mo_inl_plc']."';
            var mo_inl_csu    = '".$aPayData['mo_inl_csu']."';
            var mo_inl_width  = '1200';
            var mo_inl_height = '500';
            var mo_inl_amt    = '".$aPayData['mo_inl_amt']."';
            </script>
            <script src='https://app.qonnex.nl/inlinking.js'></script>
            ";
        }

        /**
         * getPayData for form
         **/
        public function getPayData($order_id){
            $order = wc_get_order( $order_id );
            return array(
                'mo_inl_dlr'     => $this->settings['dealer_id'],
                'mo_inl_plc'     => $this->settings['finanformulier_idnummer'],
                'mo_inl_csu'     => $this->get_return_url( $order ),
                'mo_inl_amt'     => number_format ( $order->get_total(), 2, ".", ""),
            );
        }
    }

    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_gateway_gespreid_betale($methods) {
        $methods[] = 'WC_Gateway_Gespreid_Betalen';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_gespreid_betale' );
}