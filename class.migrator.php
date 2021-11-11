<?php
/**
 * Created by PhpStorm.
 * User: Jake
 * Date: 10/25/2016
 * Time: 9:50 PM
 */

class Migrator {
    public $db_vers;
    public $wpdb;

    function __construct($db_vers, $wpdb) {
        $this->db_vers = $db_vers;
        $this->wpdb = $wpdb;
    }

    /**
     * Installs the database if migrations have not yet been performed
     */
    private function install() {
        //Adds track columns to existing database
        $complex_id_col = $this->wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$this->wpdb->prefix."posts' AND column_name = 'complex_id'"  );
        if (empty($complex_id_col)) {
            $this->wpdb->query("ALTER TABLE " . $this->wpdb->prefix . "posts ADD COLUMN complex_id BIGINT(20)");
        }

        $group_id_col = $this->wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$this->wpdb->prefix."posts' AND column_name = 'group_id'"  );
        if (empty($group_id_col)) {
            $this->wpdb->query("ALTER TABLE " . $this->wpdb->prefix . "posts ADD COLUMN group_id VARCHAR(50)");
        }

        $parent_listing_col = $this->wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$this->wpdb->prefix."posts' AND column_name = 'parent_listing'"  );
        if (empty($parent_listing_col)) {
            $this->wpdb->query("ALTER TABLE " . $this->wpdb->prefix . "posts ADD COLUMN parent_listing BIGINT(20)");
        }

        $unit_id_col = $this->wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$this->wpdb->prefix."posts' AND column_name = 'unit_id'"  );
        if (empty($unit_id_col)) {
            $this->wpdb->query("ALTER TABLE " . $this->wpdb->prefix . "posts ADD COLUMN unit_id BIGINT(20)");
        }

        $node_id_col = $this->wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$this->wpdb->prefix."terms' AND column_name = 'node_id'"  );
        if (empty($node_id_col)) {
            $this->wpdb->query("ALTER TABLE " . $this->wpdb->prefix . "terms ADD COLUMN node_id BIGINT(20)");
        }
        $node_type_id_col = $this->wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$this->wpdb->prefix."terms' AND column_name = 'node_type_id'"  );
        if (empty($node_type_id_col)) {
            $this->wpdb->query("ALTER TABLE " . $this->wpdb->prefix . "terms ADD COLUMN node_type_id BIGINT(20)");
        }

        $amenity_id_col = $this->wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$this->wpdb->prefix."terms' AND column_name = 'amenity_id'"  );
        if (empty($amenity_id_col)) {
            $this->wpdb->query("ALTER TABLE " . $this->wpdb->prefix . "terms ADD COLUMN amenity_id BIGINT(20)");
        }

        $track_node_types_table = $this->wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$this->wpdb->prefix."track_node_types' AND column_name = 'name'"  );
        if (empty($track_node_types_table)) {
            $this->wpdb->query("CREATE TABLE " . $this->wpdb->prefix . "track_node_types (
              id INT(12) UNSIGNED NOT NULL AUTO_INCREMENT,
              name VARCHAR(250) DEFAULT NULL,
              type_id INT(11) NOT NULL,
              active TINYINT(1) NOT NULL DEFAULT '0',
              PRIMARY KEY (id)
            ) ENGINE=InnoDB");
        }

        $track_amenities_table = $this->wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$this->wpdb->prefix."track_amenities' AND column_name = 'name'"  );
        if(empty($track_amenities_table)){
            $this->wpdb->query("CREATE TABLE `".$this->wpdb->prefix."track_amenities` (
              `id` int(15) unsigned NOT NULL AUTO_INCREMENT,
              `amenity_id` int(15) DEFAULT NULL,
              `name` varchar(100) DEFAULT NULL,
              `group_id` int(15) DEFAULT NULL,
              `group_name` varchar(100) DEFAULT NULL,
              `active` tinyint(1) NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB");
        }
        $this->wpdb->query("UPDATE wp_options SET option_value = '2' WHERE option_name = 'track_db_version';");
        $this->db_vers = 2;

        $this->run();
    }

    public function run() {
        if ($this->db_vers == '1') {
            $this->install();
        }

        return $this->db_vers;
    }
}