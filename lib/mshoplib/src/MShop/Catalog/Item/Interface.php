<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2011
 * @license LGPLv3, http://www.arcavias.com/en/license
 * @package MShop
 * @subpackage Catalog
 * @version $Id: Interface.php 14246 2011-12-09 12:25:12Z nsendetzky $
 */


/**
 * Generic interface for catalog items.
 *
 * @package MShop
 * @subpackage Catalog
 */
interface MShop_Catalog_Item_Interface extends MShop_Common_Item_Interface
{
	/**
	 * Returns the name of the item.
	 *
	 * @return string Name of the item
	 */
	public function getLabel();

	/**
	 * Sets the new name of the item.
	 *
	 * @param string $name New name of the item
	 */
	public function setLabel( $name );

	/**
	 * Returns the code of the item.
	 *
	 * @return string Code of the item
	 */
	public function getCode();

	/**
	 * Sets the code of the item.
	 *
	 * @param string $name New code of the item
	 */
	public function setCode( $name );

	/**
	 * Returns the status of the item.
	 *
	 * @return boolean True if enabled, false if not
	 */
	public function getStatus();

	/**
	 * Sets the new status of the item.
	 *
	 * @param boolean $status True if enabled, false if not
	 */
	public function setStatus( $status );

	/**
	 * Returns a child of this node identified by its index.
	 *
	 * @param integer $index Index of child node
	 * @return MShop_Catalog_Item_Interface Selected node
	 */
	public function getChild( $index );

	/**
	 * Returns all children of this node.
	 *
	 * @return array Numerically indexed list of nodes
	 */
	public function getChildren();

	/**
	 * Tests if a node has children.
	 *
	 * @return boolean True if node has children, false if not
	 */
	public function hasChildren();

	/**
	 * Adds a child node to this node.
	 *
	 * @param MShop_Catalog_Item_Interface $item Child node to add
	 */
	public function addChild( MShop_Catalog_Item_Interface $item );
}
