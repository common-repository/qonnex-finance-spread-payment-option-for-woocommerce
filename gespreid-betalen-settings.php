<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for Gespreid Betalen Payment Gateway.
 */
return
    array(
        'enabled' => array(
            'title'       => __( 'Enable/Disable:', 'woocommerce' ),
            'label'       => __( 'Enable Gespreid Betalen', 'woocommerce' ),
            'type'        => 'checkbox',
            'description' => 'Show in the Payment List as a payment option',
            'default'     => 'no'
        ),
        'title' => array(
            'title'       => __( 'Title', 'woocommerce' ),
            'type'        => 'text',
            'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
            'default'     => __( 'Gespreid Betalen', 'woocommerce' ),
            'desc_tip'    => true,
        ),
        'dealer_id' => array(
            'title'       => __( 'Dealer ID', 'woocommerce' ),
            'type'        => 'text',
            'description' => 'dealer ID gekregen van Qonnex',
            'default'     => __( '', 'woocommerce' ),
        ),
        'finanformulier_idnummer' => array(
            'title'       => __( 'Finan formulier code', 'woocommerce' ),
            'type'        => 'text',
            'description' => 'Finan formuliercode gekregen van Qonnex',
            'default'     => __( '', 'woocommerce' ),
        ),
        'instructions' => array(
            'title'       => __( 'Tekst na invullen aanvraag', 'woocommerce' ),
            'type'        => 'textarea',
            'description' => __('Deze tekst wordt op de success pagina getoond na het sucesevol invullen van het aanvraagformulier. Deze tekst is alleen zichtbaar op de sucesspagina na een bestelling met betaalmethode.', 'woocommerce' ),
            'default'     => __('Geachte heer/mevrouw, u heeft er voor gekozen gespreid te betalen. Wanneer uw aanvraag afgrond is krijgen wij hier automatisch bericht van. Wanneer dit is gebeurd zullen wij contact met u opnemen omtrent de leverdatum. Bedankt voor uw vertrouwen!.', 'woocommerce' ),
            //'desc_tip'    => true,
        ),
        'min_order_total' => array(
            'title'       => __( 'Minimum Order Total', 'woocommerce' ),
            'type'        => 'text',
            'description' => 'minumum bestelbedrag waarbij deze betaalmethode beschikbaar is. Standaard: 500',
            'default'     => __( '500', 'woocommerce' ),
        ),
        'description' => array(
            'title'       => __( 'Description', 'woocommerce' ),
            'type'        => 'textarea',
            'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce' ),
            'default'     => __( 'Betaal via Gespreid Betalen.', 'woocommerce' ),
            'desc_tip'    => true,
        ),
        'enable_for_methods' => array(
            'title'             => __( 'Enable for shipping methods', 'woocommerce' ),
            'type'              => 'multiselect',
            'class'             => 'wc-enhanced-select',
            'css'               => 'width: 450px;',
            'default'           => '',
            'description'       => __( 'If Gespreid Betalen is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce' ),
            'options'           => $shipping_methods,
            'desc_tip'          => true,
            'custom_attributes' => array(
                'data-placeholder' => __( 'Select shipping methods', 'woocommerce' )
            )
        ),
        'enable_for_virtual' => array(
            'title'             => __( 'Accept for virtual orders', 'woocommerce' ),
            'label'             => __( 'Accept Gespreid Betalen if the order is virtual', 'woocommerce' ),
            'type'              => 'checkbox',
            'default'           => 'no'
        )

);
