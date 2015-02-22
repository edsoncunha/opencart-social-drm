<?php
class ModelCatalogDownload extends Model {

//tri mod start
function syncDownloadDb($remaining=999) {
  $files = glob(DIR_DOWNLOAD . "*");

  $db_files = array();
  $rm_ids = array();

  $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "download");
  foreach ($query->rows as $r) {
    $db_files[] = $r["filename"];
    if (!in_array(DIR_DOWNLOAD . $r["filename"], $files)) $rm_ids[] = $r["download_id"];
  }

  $added = 0;

  foreach ($files as $f) {
    $filename = basename($f);

    if (!in_array($filename, $db_files) && $filename != "index.html") {
      $added++;
      $this->db->query("INSERT INTO " . DB_PREFIX . "download SET filename='" . $this->db->escape($filename) . "', mask='" . $this->db->escape($filename) . "', remaining='" . (int)$remaining . "', date_added=NOW()");

      $download_id = $this->db->getLastId();
      $this->db->query("INSERT INTO " . DB_PREFIX . "download_description SET download_id='" . (int)$download_id . "', name='" . $this->db->escape($filename) . "', language_id='" . (int)$this->config->get("config_language_id") . "'");
    }
  }

  if ($rm_ids) {
    $this->db->query("DELETE FROM " . DB_PREFIX . "download WHERE download_id IN (" . implode(",", $rm_ids) . ")");
    $this->db->query("DELETE FROM " . DB_PREFIX . "download_description WHERE download_id IN (" . implode(",", $rm_ids) . ")");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE download_id IN (" . implode(",", $rm_ids) . ")");
  }

  if (isset($this->request->post["apply_existing"])) $this->db->query("UPDATE " . DB_PREFIX . "download SET remaining='" . (int)$remaining . "'");

  return "$added files added to database.  " . count($rm_ids) . " entries removed from database.";
}
//tri mod end
      
	public function addDownload($data) {
		$this->event->trigger('pre.admin.download.add', $data);

		$this->db->query("INSERT INTO " . DB_PREFIX . "download SET filename = '" . $this->db->escape($data['filename']) . "', mask = '" . $this->db->escape($data['mask']) . "', date_added = NOW()");

		$download_id = $this->db->getLastId();

		foreach ($data['download_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "download_description SET download_id = '" . (int)$download_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
		}

		$this->event->trigger('post.admin.download.add', $download_id);

		return $download_id;
	}

	public function editDownload($download_id, $data) {
		$this->event->trigger('pre.admin.download.edit', $data);

		$this->db->query("UPDATE " . DB_PREFIX . "download SET filename = '" . $this->db->escape($data['filename']) . "', mask = '" . $this->db->escape($data['mask']) . "' WHERE download_id = '" . (int)$download_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "download_description WHERE download_id = '" . (int)$download_id . "'");

		foreach ($data['download_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "download_description SET download_id = '" . (int)$download_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
		}

		$this->event->trigger('post.admin.download.edit', $download_id);
	}

	public function deleteDownload($download_id) {

//tri mod start
$filepath = DIR_DOWNLOAD . $this->db->query("SELECT * FROM " . DB_PREFIX . "download WHERE download_id='" . (int)$download_id . "'")->row["filename"];
if (file_exists($filepath)) unlink($filepath);
//tri mod end
      
		$this->event->trigger('pre.admin.download.delete', $download_id);

		$this->db->query("DELETE FROM " . DB_PREFIX . "download WHERE download_id = '" . (int)$download_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "download_description WHERE download_id = '" . (int)$download_id . "'");

		$this->event->trigger('post.admin.download.delete', $download_id);
	}

	public function getDownload($download_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "download d LEFT JOIN " . DB_PREFIX . "download_description dd ON (d.download_id = dd.download_id) WHERE d.download_id = '" . (int)$download_id . "' AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getDownloads($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "download d LEFT JOIN " . DB_PREFIX . "download_description dd ON (d.download_id = dd.download_id) WHERE dd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND dd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sort_data = array(
			'dd.name',
			'd.date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY dd.name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getDownloadDescriptions($download_id) {
		$download_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "download_description WHERE download_id = '" . (int)$download_id . "'");

		foreach ($query->rows as $result) {
			$download_description_data[$result['language_id']] = array('name' => $result['name']);
		}

		return $download_description_data;
	}

	public function getTotalDownloads() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "download");

		return $query->row['total'];
	}
}