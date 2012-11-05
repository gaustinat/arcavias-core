<?php
/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2012
 * @license LGPLv3, http://www.arcavias.com/en/license
 * @package MShop
 * @subpackage Catalog
 * @version $Id: Default.php 1334 2012-10-24 16:17:46Z doleiynyk $
 */

/**
 * Submanager for catalog.
 *
 * @package MShop
 * @subpackage Catalog
 */
class MShop_Catalog_Manager_Index_Price_Default
	extends MShop_Common_Manager_Abstract
	implements MShop_Catalog_Manager_Index_Price_Interface
{

	private $_searchConfig = array(
		'catalog.index.price.id' => array(
			'code'=>'catalog.index.price.id',
			'internalcode'=>':site AND mcatinpr."priceid"',
			'internaldeps'=>array( 'LEFT JOIN "mshop_catalog_index_price" AS mcatinpr ON mcatinpr."prodid" = mpro."id"' ),
			'label'=>'Product index price ID',
			'type'=> 'integer',
			'internaltype' => MW_DB_Statement_Abstract::PARAM_INT,
			'public' => false,
		),
		'catalog.index.price.value' => array(
			'code'=>'catalog.index.price.value()',
			'internalcode'=>':site AND mcatinpr."listtype" = $1 AND mcatinpr."currencyid" = $2 AND mcatinpr."type" = $3 AND mcatinpr."value"',
			'label'=>'Product price, parameter(<list type code>,<currency ID>,<price type code>)',
			'type'=> 'decimal',
			'internaltype' => MW_DB_Statement_Abstract::PARAM_STR,
			'public' => false,
		),
		'sort:catalog.index.price.value' => array(
			'code'=>'sort:catalog.index.price.value()',
			'internalcode'=>'mcatinpr."value"',
			'label'=>'Sort product price, parameter(<list type code>,<currency ID>,<price type code>)',
			'type'=> 'string',
			'internaltype' => MW_DB_Statement_Abstract::PARAM_STR,
			'public' => false,
		)
	);


	/**
	 * Initializes the manager instance.
	 *
	 * @param MShop_Context_Item_Interface $context Context object
	 */
	public function __construct( MShop_Context_Item_Interface $context )
	{
		parent::__construct( $context );

		$site = $context->getLocale()->getSitePath();
		$types = array( 'siteid' => MW_DB_Statement_Abstract::PARAM_INT );

		$search = $this->createSearch();
		$expr = array(
			$search->compare( '==', 'siteid', null ),
			$search->compare( '==', 'siteid', $site ),
		);
		$search->setConditions( $search->combine( '||', $expr ) );

		$string = $search->getConditionString( $types, array( 'siteid' => 'mcatinpr."siteid"' ) );
		$this->_searchConfig['catalog.index.price.id']['internalcode'] =
		str_replace( ':site', $string, $this->_searchConfig['catalog.index.price.id']['internalcode'] );

		$this->_replaceSiteMarker( $this->_searchConfig['catalog.index.price.value'], 'mcatinpr."siteid"', $site );
	}


	/**
	 * Creates new price item object.
	 *
	 * @return MShop_Price_Item_Interface New product item
	 */
	public function createItem()
	{
		return MShop_Product_Manager_Factory::createManager( $this->_getContext() )->createItem();
	}


	/**
	 * Creates a search object and optionally sets base criteria.
	 *
	 * @param boolean $default Add default criteria
	 * @return MW_Common_Criteria_Interface Criteria object
	 */
	public function createSearch( $default = false )
	{
		return MShop_Product_Manager_Factory::createManager( $this->_getContext() )->createSearch( $default );
	}


	/**
	 * Optimizes the index if necessary.
	 * Execution of this operation can take a very long time and shouldn't be
	 * called through a web server enviroment.
	 */
	public function optimize()
	{
		$context = $this->_getContext();
		$config = $context->getConfig();
		$path = 'mshop/catalog/manager/index/price/default/optimize';

		if( ( $sql = $config->get( $path, null ) ) === null ) {
			return;
		}

		$dbm = $context->getDatabaseManager();
		$conn = $dbm->acquire();

		try
		{
			$stmt = $conn->create( $sql )->execute()->finish();
			$dbm->release( $conn );
		}
		catch( Exception $e )
		{
			$dbm->release( $conn );
			throw $e;
		}
	}


	/**
	 * Removes an item from the index.
	 *
	 * @param integer $id Product ID
	 */
	public function deleteItem( $id )
	{
		$context = $this->_getContext();
		$siteid = $context->getLocale()->getSiteId();

		$dbm = $context->getDatabaseManager();
		$conn = $dbm->acquire();

		try
		{
			$stmt = $this->_getCachedStatement( $conn, 'mshop/catalog/manager/index/price/default/item/delete' );
			$stmt->bind( 1, $id, MW_DB_Statement_Abstract::PARAM_INT );
			$stmt->bind( 2, $siteid, MW_DB_Statement_Abstract::PARAM_INT );
			$stmt->execute()->finish();

			$dbm->release( $conn );
		}
		catch( Exception $e )
		{
			$dbm->release( $conn );
			throw $e;
		}
	}


	/**
	 * Returns a list of objects describing the available criterias for searching.
	 *
	 * @param boolean $withsub Return also attributes of sub-managers if true
	 * @return array List of items implementing MW_Common_Criteria_Attribute_Interface
	 */
	public function getSearchAttributes($withsub = true)
	{
		foreach( $this->_searchConfig as $key => $fields ) {
			$list[ $key ] = new MW_Common_Criteria_Attribute_Default( $fields );
		}

		if( $withsub === true )
		{
			$path = 'mshop/catalog/manager/index/price/default/submanagers';
			foreach( $this->_getContext()->getConfig()->get( $path, array() ) as $domain ) {
				$list = array_merge( $list, $this->getSubManager( $domain )->getSearchAttributes() );
			}
		}

		return $list;
	}


	/**
	 * Stores a new item in the index.
	 *
	 * @param MShop_Common_Item_Interface $item Product item
	 * @param boolean $fetch True if the new ID should be returned in the item
	 */
	public function saveItem( MShop_Common_Item_Interface $item, $fetch = true )
	{
		$this->rebuildIndex( array( $item ) );
	}


	/**
	 * Returns the price item for the given ID
	 *
	 * @param integer $id Id of item
	 * @return MShop_Price_Item_Interface Item object
	 */
	public function getItem( $id, array $ref = array() )
	{
		return MShop_Product_Manager_Factory::createManager( $this->_getContext() )->getItem( $id, $ref );
	}


	/**
	 * Returns a new manager for product extensions.
	 *
	 * @param string $manager Name of the sub manager type in lower case
	 * @param string|null $name Name of the implementation, will be from configuration (or Default) if null
	 * @return mixed Manager for different extensions, e.g stock, tags, locations, etc.
	 */
	public function getSubManager( $manager, $name = null )
	{
		return $this->_getSubManager( 'catalog', 'index/price/' . $manager, $name );
	}


	/**
	 * Searches for items matching the given criteria.
	 *
	 * @param MW_Common_Criteria_Interface $search Search criteria
	 * @param integer &$total Total number of items matched by the given criteria
	 * @return array List of items implementing MShop_Product_Item_Interface with ids as keys
	 */
	public function searchItems( MW_Common_Criteria_Interface $search, array $ref = array(), &$total = null )
	{
		$items = $ids = array();
		$dbm = $this->_getContext()->getDatabaseManager();
		$conn = $dbm->acquire();

		try
		{
			$cfgPathSearch = 'mshop/catalog/manager/index/price/default/item/search';
			$cfgPathCount =  'mshop/catalog/manager/index/price/default/item/count';
			$required = array( 'product' );

			$results = $this->_searchItems( $conn, $search, $cfgPathSearch, $cfgPathCount, $required, $total );

			while( ( $row = $results->fetch() ) !== false )	{
				$ids[] = $row['id'];
			}

			$dbm->release( $conn );
		}
		catch( Exception $e )
		{
			$dbm->release( $conn );
			throw $e;
		}

		$productManager = MShop_Product_Manager_Factory::createManager( $this->_getContext() );

		$search = $productManager->createSearch();
		$search->setConditions( $search->compare( '==', 'product.id', $ids ) );
		$products = $productManager->searchItems( $search, $ref, $total );

		foreach( $ids as $id )
		{
			if( isset( $products[$id] ) ) {
				$items[ $id ] = $products[ $id ];
			}
		}

		return $items;
	}


	/**
	 * Rebuilds the catalog index price for searching products or specified list of products.
	 * This can be a long lasting operation.
	 *
	 * @param array $items List of product items implementing MShop_Product_Item_Interface
	 */
	public function rebuildIndex( array $items = array() )
	{
		if( empty( $items ) ) { return; }

		MW_Common_Abstract::checkClassList( 'MShop_Product_Item_Interface', $items );

		$path = 'mshop/catalog/manager/index/price/default/submanagers';
		foreach( $this->_getContext()->getConfig()->get( $path, array() ) as $domain ) {
			$this->getSubManager( $domain )->rebuildIndex( $items );
		}

		$this->_begin();

		$context = $this->_getContext();
		$siteid = $context->getLocale()->getSiteId();
		$editor = $context->getEditor();
		$date = date( 'Y-m-d H:i:s' );

		$dbm = $context->getDatabaseManager();
		$conn = $dbm->acquire();

		try
		{
			foreach ( $items as $item )
			{
				$listTypes = array();
				foreach( $item->getListItems( 'price' ) as $listItem ) {
					$listTypes[ $listItem->getRefId() ][] = $listItem->getType();
				}

				$stmt = $this->_getCachedStatement( $conn, 'mshop/catalog/manager/index/price/default/item/insert' );

				foreach( $item->getRefItems( 'price' ) as $priceItem )
				{
					if( !isset( $listTypes[ $priceItem->getId() ] ) )
					{
						$msg = sprintf( 'No list type for price item with ID "%1$s"', $priceItem->getId() );
						throw new MShop_Catalog_Exception( $msg );
					}

					foreach( $listTypes[ $priceItem->getId() ] as $listType )
					{
						$stmt->bind( 1, $item->getId(), MW_DB_Statement_Abstract::PARAM_INT );
						$stmt->bind( 2, $siteid, MW_DB_Statement_Abstract::PARAM_INT );
						$stmt->bind( 3, $priceItem->getId(), MW_DB_Statement_Abstract::PARAM_INT );
						$stmt->bind( 4, $priceItem->getCurrencyId() );
						$stmt->bind( 5, $listType );
						$stmt->bind( 6, $priceItem->getType() );
						$stmt->bind( 7, $priceItem->getValue() );
						$stmt->bind( 8, $priceItem->getShipping() );
						$stmt->bind( 9, $priceItem->getRebate() );
						$stmt->bind( 10, $priceItem->getTaxRate() );
						$stmt->bind( 11, $priceItem->getQuantity(), MW_DB_Statement_Abstract::PARAM_INT );
						$stmt->bind( 12, $date );//mtime
						$stmt->bind( 13, $editor );
						$stmt->bind( 14, $date );//ctime
						$result = $stmt->execute()->finish();
					}
				}
			}

			$dbm->release( $conn );
		}
		catch( Exception $e )
		{
			$dbm->release( $conn );
			throw $e;
		}

		$this->_commit();
	}
}