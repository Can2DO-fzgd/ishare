<?php
namespace Topxia\Service\Product\Dao;

interface ProductAnnouncementDao
{
	public function getAnnouncement($id);

	public function findAnnouncementsByProductId($productId, $start, $limit);

	public function findAnnouncementsByProductIds($ids, $start, $limit);

	public function addAnnouncement($fields);

	public function deleteAnnouncement($id);

	public function updateAnnouncement($id, $fields);
}