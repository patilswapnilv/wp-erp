<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Get all chart of accounts
 *
 * @return array
 */
function erp_acct_get_all_charts() {
    global $wpdb;

    $charts = $wpdb->get_results( "SELECT id, name AS label FROM {$wpdb->prefix}erp_acct_chart_of_accounts", ARRAY_A );

    return $charts;
}

/**
 * Get Ledger name by id
 *
 * @param $ledger_id
 * @return mixed
 */
function erp_acct_get_ledger_name_by_id( $ledger_id ) {
    global $wpdb;

    $sql = "SELECT id, name  FROM {$wpdb->prefix}erp_acct_ledgers WHERE id = {$ledger_id}";

    $row = $wpdb->get_row( $sql );

    return $row->name;
}

/**
 * Get ledger categories
 */
function erp_acct_get_ledger_categories( $chart_id ) {
    global $wpdb;

    $sql = "SELECT id, name AS label, chart_id, parent_id, system FROM {$wpdb->prefix}erp_acct_ledger_categories WHERE chart_id = {$chart_id}";

    return $wpdb->get_results( $sql, ARRAY_A );
}

/**
 * Create ledger category
 */
function erp_acct_create_ledger_category( $args ) {
    global $wpdb;

    $exist = $wpdb->get_var( "SELECT name FROM {$wpdb->prefix}erp_acct_ledger_categories WHERE name = '{$args['name']}'" );

    if ( ! $exist ) {
        $wpdb->insert( "{$wpdb->prefix}erp_acct_ledger_categories", [
            'name'      => $args['name'],
            'parent_id' => ! empty( $args['parent'] ) ? $args['parent'] : null
        ] );

        return $wpdb->insert_id;
    }

    return false;
}

/**
 * Update ledger category
 */
function erp_acct_update_ledger_category( $args ) {
    global $wpdb;

    $exist = $wpdb->get_var( "SELECT name FROM {$wpdb->prefix}erp_acct_ledger_categories WHERE name = '{$args['name']}' AND id <> {$args['id']}" );

    if ( ! $exist ) {
        return $wpdb->update(
            "{$wpdb->prefix}erp_acct_ledger_categories",
            [
                'name'      => $args['name'],
                'parent_id' => ! empty( $args['parent'] ) ? $args['parent'] : null
            ],
            [ 'id' => $args['id'] ],
            [ '%s', '%d' ],
            [ '%d' ]
        );
    }

    return false;
}

/**
 * Remove ledger category
 */
function erp_acct_delete_ledger_category( $id ) {
    global $wpdb;

    $table = "{$wpdb->prefix}erp_acct_ledger_categories";

    $parent_id = $wpdb->get_var( "SELECT parent_id FROM {$table} WHERE id = {$id}" );

    $wpdb->update(
        $table,
        [ 'parent_id' => $parent_id ],
        [ 'parent_id' => $id ],
        [ '%s' ],
        [ '%d' ]
    );

    return $wpdb->delete( $table, [ 'id' => $id ] );

}

/**
 * @param $chart_id
 * @return array|object|null
 */
function erp_acct_get_ledgers_by_chart_id( $chart_id ) {
    global $wpdb;

    $ledgers = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}erp_acct_ledgers WHERE chart_id = {$chart_id} AND unused IS NULL", ARRAY_A );

    for ( $i = 0; $i < count( $ledgers ); $i++ ) {
        $ledgers[$i]['balance'] = erp_acct_get_ledger_balance( $ledgers[$i]['id'] );
    }

    return $ledgers;
}

/**
 * Get ledger transaction count
 *
 * @param $ledger_id
 * @return mixed
 */
function erp_acct_get_ledger_trn_count( $ledger_id ) {
    global $wpdb;

    $sql = "SELECT
        COUNT(*) as count
        FROM {$wpdb->prefix}erp_acct_ledger_details
        WHERE ledger_id = {$ledger_id}";

    $ledger = $wpdb->get_row( $sql, ARRAY_A );

    return $ledger['count'];
}

/**
 * Get ledger balance
 *
 * @param $ledger_id
 * @return mixed
 */
function erp_acct_get_ledger_balance( $ledger_id ) {
    global $wpdb;

    $sql = "SELECT
        ledger.id,
        ledger.name,
        SUM(ld.debit - ld.credit) as balance

        FROM {$wpdb->prefix}erp_acct_ledgers AS ledger
        LEFT JOIN {$wpdb->prefix}erp_acct_ledger_details as ld ON ledger.id = ld.ledger_id
        WHERE ledger.id = {$ledger_id}";

    $ledger = $wpdb->get_row( $sql, ARRAY_A );

    return $ledger['balance'];
}


/**============
 * Ledger CRUD
 * ===============*/

/**
 * Get a ledger by id
 *
 * @param $id
 * @return array|object|void|null
 */
function erp_acct_get_ledger( $id ) {
    global $wpdb;

    $sql = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}erp_acct_ledgers WHERE id = %d", $id );

    return $wpdb->get_row( $sql );
}

/**
 * Insert a ledger
 *
 * @param $item
 * @return array|object|void|null
 */
function erp_acct_insert_ledger( $item ) {
    global $wpdb;

    $wpdb->insert( "{$wpdb->prefix}erp_acct_ledgers", [
        'chart_id'    => $item['chart_id'],
        'category_id' => $item['category_id'],
        'name'        => $item['name'],
        'slug'        => slugify( $item['name'] ),
        'code'        => $item['code']
    ] );

    return erp_acct_get_ledger( $wpdb->insert_id );
}

/**
 * Update a ledger
 *
 * @param $item
 * @param $id
 * @return array|object|void|null
 */
function erp_acct_update_ledger( $item, $id ) {
    global $wpdb;

    $wpdb->update( "{$wpdb->prefix}erp_acct_ledgers", [
        'chart_id'    => $item['chart_id'],
        'category_id' => $item['category_id'],
        'name'        => $item['name'],
        'slug'        => slugify( $item['name'] ),
        'code'        => $item['code']
    ], [ 'id' => $id ]
    );

    return erp_acct_get_ledger( $id );
}


/**
 * =====================
 * =========================
 * =============================
 */
/**
 * Get ledger with balances
 *
 * @return array
 */
function erp_acct_get_ledgers_with_balances() {
    global $wpdb;

    $today = date( 'Y-m-d' );

    $sql = "SELECT ledger.id, ledger.chart_id, ledger.category_id, ledger.name,
        ledger.slug, ledger.code, ledger.system, chart_of_account.name as account_name
        FROM {$wpdb->prefix}erp_acct_ledgers AS ledger
        LEFT JOIN {$wpdb->prefix}erp_acct_chart_of_accounts AS chart_of_account ON ledger.chart_id = chart_of_account.id WHERE ledger.unused IS NULL";

    $ledgers = $wpdb->get_results( $sql, ARRAY_A );

    // get closest financial year id and start date
    $closest_fy_date = erp_acct_get_closest_fn_year_date( $today );

    // get opening balance data within that(^) financial year
    $opening_balance = erp_acct_opening_balance_by_fn_year_id( $closest_fy_date['id'] );

    $sql2 = "SELECT ledger.id, ledger.name, SUM(ld.debit - ld.credit) as balance
        FROM {$wpdb->prefix}erp_acct_ledgers AS ledger
        LEFT JOIN {$wpdb->prefix}erp_acct_ledger_details as ld ON ledger.id = ld.ledger_id
        AND ld.trn_date BETWEEN '%s' AND '%s' GROUP BY ld.ledger_id";

    $data = $wpdb->get_results( $wpdb->prepare( $sql2, $closest_fy_date['start_date'], $today ), ARRAY_A );

    return erp_acct_ledger_balance_with_opening_balance( $ledgers, $data, $opening_balance );
}

/**
 * Ledgers opening balance
 *
 * @param int $id
 *
 * @return void
 */
function erp_acct_ledgers_opening_balance_by_fn_year_id( $id ) {
    global $wpdb;

    $sql = "SELECT ledger.id, ledger.name, SUM(opb.debit - opb.credit) AS balance
        FROM {$wpdb->prefix}erp_acct_ledgers AS ledger
        LEFT JOIN {$wpdb->prefix}erp_acct_opening_balances AS opb ON ledger.id = opb.ledger_id
        WHERE opb.financial_year_id = %d opb.type = 'ledger' GROUP BY opb.ledger_id";

    return $wpdb->get_results( $wpdb->prepare( $sql, $id ), ARRAY_A );
}

/**
 * Get ledger balance with opening balance for chart of accounts
 *
 * @param array $ledgers
 * @param array $data
 * @param array $opening_balance
 *
 * @return array
 */
function erp_acct_ledger_balance_with_opening_balance( $ledgers, $data, $opening_balance ) {
    $temp_data = [];

    /**
     * Start writing a very `inefficient :(` foreach loop
     */
    foreach ( $ledgers as $ledger ) {
        $balance = 0;

        foreach ( $data as $row ) {
            if ( $row['id'] == $ledger['id'] ) {
                $balance += (float) $row['balance'];
            }
        }

        foreach ( $opening_balance as $op_balance ) {
            if ( $op_balance['id'] == $ledger['id'] ) {
                $balance += (float) $op_balance['balance'];
            }
        }

        $temp_data[] = [
            'id'          => $ledger['id'],
            'chart_id'    => $ledger['chart_id'],
            'category_id' => $ledger['category_id'],
            'name'        => $ledger['name'],
            'slug'        => $ledger['slug'],
            'code'        => $ledger['code'],
            'system'      => $ledger['system'],
            'balance'     => $balance
        ];
    }

    return $temp_data;
}

/**
 * Get chart of account id by slug
 *
 * @param string $key
 *
 * @return int
 */
function erp_acct_get_chart_id_by_slug( $key ) {
    switch ( $key ) {
        case 'asset':
            $id = 1;
            break;
        case 'liability':
            $id = 2;
            break;
        case 'equity':
            $id = 3;
            break;
        case 'income':
            $id = 4;
            break;
        case 'expense':
            $id = 5;
            break;
        case 'asset_liability':
            $id = 6;
            break;
        case 'bank':
            $id = 7;
            break;
        default:
            $id = null;
    }

    return $id;
}

/**
 * Get ledgers
 *
 * @param $chart_id
 * @return array|object|null
 */
function erp_acct_get_ledgers() {
    global $wpdb;

    $ledgers = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}erp_acct_ledgers WHERE unused IS NULL", ARRAY_A );

    return $ledgers;
}
