<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_basedb extends CI_Migration {

        public function up()
        {
			$this->load->helper('file');
            $sqlfile =  read_file('./system/fhcomplete3.0.sql');

			if (!$this->db->simple_query($sqlfile))
			{
					echo "Error creating Basis DB-Schema!";
			}
        }

        public function down()
        {
                $this->db->simple_query('DROP SCHEMA addon;');
				$this->db->simple_query('DROP SCHEMA bis;');
				$this->db->simple_query('DROP SCHEMA campus;');
				$this->db->simple_query('DROP SCHEMA fue;');
				$this->db->simple_query('DROP SCHEMA kommune;');
				$this->db->simple_query('DROP SCHEMA lehre;');
				$this->db->simple_query('DROP SCHEMA reports;');
				$this->db->simple_query('DROP SCHEMA sync;');
				$this->db->simple_query('DROP SCHEMA system;');
				$this->db->simple_query('DROP SCHEMA testtool;');
				$this->db->simple_query('DROP SCHEMA wawi;');
        }
}

