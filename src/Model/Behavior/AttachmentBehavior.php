<?php
namespace Filerepo\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\Utility\Inflector;
use Filerepo\Model\Entity\Fileobject;
use Filerepo\Model\Table\FileobjectsTable;

/**
 * Attachment behavior
 */
class AttachmentBehavior extends Behavior
{
	protected $_defaultConfig = [
		'fileobjectTableClass' => FileobjectsTable::class
	];

	public function addAttachmentField($fieldname, $multiple = false)
	{
		$alias = Inflector::camelize($fieldname);
		if ($multiple) {
			/** @var \Cake\ORM\Association\HasMany $assoc */
			$assoc = $this
				->getTable()
				->hasMany($alias)
				->setSort([
					'order' => 'asc'
				]);
		} else {
			/** @var \Cake\ORM\Association\HasOne $assoc */
			$assoc = $this->getTable()->hasOne($alias);
		}

		$assoc
			->setForeignKey('fk_id')
			->setClassName($this->getConfig('fileobjectTableClass'))
			->setDependent(true)
			->setConditions([
				$alias.'.fk_model' => $this->getTable()->getAlias(),
				$alias.'.scope' => $fieldname
			]);

		$assoc->getEventManager()->on(
			'Model.afterSave',
			[],
			function (Event $event, Fileobject $fileobject, ArrayObject $options) {
				/** @var \Filerepo\Model\Table\FileobjectsTable $Fileobjects */
				$Fileobjects = $event->getSubject();

				$Fileobjects->deleteAll([
					$Fileobjects->getAlias().'.fk_model' => $Fileobjects->getAlias(),
					$Fileobjects->getAlias() . '.fk_id' => $fileobject->id,
					$Fileobjects->getAlias() . '.scope LIKE' => 'thumb%'
				]);
			}
		);

		$this->getTable()->getEventManager()->on(
			'Model.beforeMarshal',
			[],
			function (Event $event, ArrayObject $data, ArrayObject $options) use ($fieldname, $multiple) {
				if (!empty($data[$fieldname])) {
					$mapper = function (&$file) use ($fieldname) {
						if (isset($file['tmp_name'])) {
							$file['scope'] = $fieldname;
							$file['fk_model'] = $this->getTable()->getAlias();
							if (isset($file['error']) && $file['error'] != UPLOAD_ERR_OK) {
								$file = null;
							}
						}
						return $file;
					};

					if (!$multiple) {
						$mapper($data[$fieldname]);
					} else {
						foreach ($data[$fieldname] as &$row) {
							$mapper($row);
						}
						$data[$fieldname] = array_filter($data[$fieldname]);
						if (empty($data[$fieldname])) {
							$data[$fieldname] = null;
						}
					}
				}
			}
		);

		return $assoc;
	}
}
