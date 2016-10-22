<?php

	require_once(COMMON_LIB_DIR . "ecash_admin_resources.php");

    /**
     * Ecash override for the Admin_Resources class
     */
    class Ecash_Admin_Resources extends Admin_Resources
    {
        /**
         * Returns what key to use for this node. 
         * @return string
         */
        protected function get_tree_node_key($node_object)
        {
            return $node_object->name;
        }

	}

?>
