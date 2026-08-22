<?php
/**
 * WP-MCP WooCommerce Integration Tools
 *
 * @package WPMCP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers tools for WooCommerce store management (Products, Orders, Stock).
 */
class WPMCP_WooCommerce_Tools {

	/**
	 * Register WooCommerce tools with the registry.
	 *
	 * @param WPMCP_Tool_Registry $registry Registry instance.
	 */
	public static function register( WPMCP_Tool_Registry $registry ): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$registry->register_tool( new WPMCP_Tool_WC_Get_Products() );
		$registry->register_tool( new WPMCP_Tool_WC_Update_Product() );
		$registry->register_tool( new WPMCP_Tool_WC_Get_Orders() );
	}
}

/**
 * Tool: Query WooCommerce Products.
 */
class WPMCP_Tool_WC_Get_Products extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_wc_get_products';
	}

	public function get_description(): string {
		return 'Query WooCommerce products by search keyword, category, status, or stock status.';
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function get_risk_level(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'search'       => array(
					'type'        => 'string',
					'description' => 'Product title or SKU keyword.',
				),
				'stock_status' => array(
					'type'        => 'string',
					'enum'        => array( 'instock', 'outofstock', 'onbackorder', 'any' ),
					'default'     => 'any',
					'description' => 'Filter by stock status.',
				),
				'limit'        => array(
					'type'        => 'integer',
					'default'     => 10,
					'description' => 'Number of products to return (max 50).',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return $this->error( __( 'WooCommerce is not active.', 'wpmcp' ) );
		}

		$args = array(
			'limit' => min( 50, max( 1, (int) ( $params['limit'] ?? 10 ) ) ),
		);

		if ( ! empty( $params['search'] ) ) {
			$args['s'] = sanitize_text_field( $params['search'] );
		}

		if ( ! empty( $params['stock_status'] ) && 'any' !== $params['stock_status'] ) {
			$args['stock_status'] = sanitize_key( $params['stock_status'] );
		}

		$products = wc_get_products( $args );
		$output   = array();

		foreach ( $products as $prod ) {
			$output[] = array(
				'id'           => $prod->get_id(),
				'name'         => $prod->get_name(),
				'sku'          => $prod->get_sku(),
				'price'        => $prod->get_price(),
				'regular_price'=> $prod->get_regular_price(),
				'sale_price'   => $prod->get_sale_price(),
				'stock_status' => $prod->get_stock_status(),
				'stock_qty'    => $prod->get_stock_quantity(),
				'permalink'    => $prod->get_permalink(),
			);
		}

		return $this->success(
			array(
				'count'    => count( $output ),
				'products' => $output,
			),
			sprintf( __( 'Found %d WooCommerce products.', 'wpmcp' ), count( $output ) )
		);
	}
}

/**
 * Tool: Update WooCommerce Product (price, stock, title).
 */
class WPMCP_Tool_WC_Update_Product extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_wc_update_product';
	}

	public function get_description(): string {
		return 'Update a WooCommerce product details (regular price, sale price, stock quantity, stock status, or title).';
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function get_risk_level(): string {
		return 'write';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'product_id' ),
			'properties' => array(
				'product_id'    => array(
					'type'        => 'integer',
					'description' => 'The ID of the WooCommerce product to update.',
				),
				'regular_price' => array(
					'type'        => 'string',
					'description' => 'New regular price.',
				),
				'sale_price'    => array(
					'type'        => 'string',
					'description' => 'New sale price.',
				),
				'stock_quantity'=> array(
					'type'        => 'integer',
					'description' => 'New stock quantity.',
				),
				'stock_status'  => array(
					'type'        => 'string',
					'enum'        => array( 'instock', 'outofstock', 'onbackorder' ),
					'description' => 'Stock status.',
				),
				'name'          => array(
					'type'        => 'string',
					'description' => 'New product title/name.',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return $this->error( __( 'WooCommerce is not active.', 'wpmcp' ) );
		}

		$product_id = (int) ( $params['product_id'] ?? 0 );
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			return $this->error( sprintf( __( 'Product ID %d not found.', 'wpmcp' ), $product_id ) );
		}

		if ( isset( $params['name'] ) ) {
			$product->set_name( sanitize_text_field( $params['name'] ) );
		}
		if ( isset( $params['regular_price'] ) ) {
			$product->set_regular_price( sanitize_text_field( $params['regular_price'] ) );
		}
		if ( isset( $params['sale_price'] ) ) {
			$product->set_sale_price( sanitize_text_field( $params['sale_price'] ) );
		}
		if ( isset( $params['stock_quantity'] ) ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( (int) $params['stock_quantity'] );
		}
		if ( isset( $params['stock_status'] ) ) {
			$product->set_stock_status( sanitize_key( $params['stock_status'] ) );
		}

		$product->save();

		return $this->success(
			array(
				'product_id' => $product_id,
				'name'       => $product->get_name(),
				'price'      => $product->get_price(),
				'stock_qty'  => $product->get_stock_quantity(),
			),
			sprintf( __( 'Updated product "%s" (ID: %d).', 'wpmcp' ), $product->get_name(), $product_id )
		);
	}
}

/**
 * Tool: Query WooCommerce Orders.
 */
class WPMCP_Tool_WC_Get_Orders extends WPMCP_Base_Tool {

	public function get_name(): string {
		return 'wpmcp_wc_get_orders';
	}

	public function get_description(): string {
		return 'Query recent WooCommerce orders with customer info, total, and status.';
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function get_risk_level(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'status' => array(
					'type'        => 'string',
					'enum'        => array( 'any', 'processing', 'completed', 'on-hold', 'pending', 'cancelled', 'refunded' ),
					'default'     => 'any',
					'description' => 'Filter by order status.',
				),
				'limit'  => array(
					'type'        => 'integer',
					'default'     => 10,
					'description' => 'Number of orders to retrieve (max 50).',
				),
			),
		);
	}

	public function execute( array $params, int $user_id ): array {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return $this->error( __( 'WooCommerce is not active.', 'wpmcp' ) );
		}

		$args = array(
			'limit' => min( 50, max( 1, (int) ( $params['limit'] ?? 10 ) ) ),
		);

		if ( ! empty( $params['status'] ) && 'any' !== $params['status'] ) {
			$args['status'] = sanitize_key( $params['status'] );
		}

		$orders = wc_get_orders( $args );
		$output = array();

		foreach ( $orders as $order ) {
			$output[] = array(
				'id'             => $order->get_id(),
				'status'         => $order->get_status(),
				'total'          => $order->get_total(),
				'currency'       => $order->get_currency(),
				'customer_name'  => $order->get_formatted_billing_full_name(),
				'customer_email' => $order->get_billing_email(),
				'date_created'   => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
				'item_count'     => $order->get_item_count(),
			);
		}

		return $this->success(
			array(
				'count'  => count( $output ),
				'orders' => $output,
			),
			sprintf( __( 'Found %d WooCommerce orders.', 'wpmcp' ), count( $output ) )
		);
	}
}
