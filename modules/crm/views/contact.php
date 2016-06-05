<?php
global $current_screen;

$advance_search_id =  isset( $_GET['erp_save_search' ] ) ? $_GET['erp_save_search' ] : 0;
$if_advance_search = ( isset( $_GET['erp_save_search' ] ) && $_GET['erp_save_search' ] == 0 ) ? true : false;

if ( isset( $_GET['filter_assign_contact' ] ) && !empty( $_GET['filter_assign_contact' ] ) ) {
    $id = intval( $_GET['filter_assign_contact'] );

    $custom_data = [
        'filter_assign_contact' => [
            'id'           => $id,
            'display_name' => get_the_author_meta( 'display_name', $id )
        ],
        'searchFields' => array_keys( erp_crm_get_serach_key( $current_screen->base ) )
    ];
} else {
    $custom_data = [
        'searchFields' => array_keys( erp_crm_get_serach_key( $current_screen->base ) )
    ];
}
?>

<div class="wrap erp-crm-customer erp-crm-customer-listing" id="wp-erp" v-cloak>

    <h2>
        <?php _e( 'Contact', 'erp' ); ?>
        <?php if ( current_user_can( 'erp_crm_add_contact' ) ): ?>
            <a href="#" @click.prevent="addContact( 'contact', '<?php _e( 'Add New Contact', 'erp' ); ?>' )" id="erp-customer-new" class="erp-contact-new add-new-h2"><?php _e( 'Add New Contact', 'erp' ); ?></a>
        <?php endif ?>
    </h2>

    <!-- Advance search filter vue component -->
    <advance-search></advance-search>

    <!-- vue table for displaying contact list -->
    <vtable v-ref:vtable
        wrapper-class="erp-crm-list-table-wrap"
        table-class="customers"
        row-checkbox-id="erp-crm-customer-id-checkbox"
        row-checkbox-name="customer_id"
        action="erp-crm-get-contacts"
        :wpnonce="wpnonce"
        page = "<?php echo add_query_arg( [ 'page' => 'erp-sales-customers' ], admin_url( 'admin.php' ) ); ?>"
        per-page="4"
        :fields=fields
        :item-row-actions=itemRowActions
        :search="search"
        :top-nav-filter="topNavFilter"
        :bulkactions="bulkactions"
        :extra-bulk-action="extraBulkAction"
        :additional-params="additionalParams"
        :remove-url-params="removeUrlParams"
        :custom-data = '<?php echo json_encode( $custom_data, JSON_UNESCAPED_UNICODE ); ?>'
    ></vtable>

</div>
