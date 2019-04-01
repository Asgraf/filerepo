<?php
namespace Filerepo\Model\Entity;

use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Zend\Diactoros\Stream;

/**
 * Fileobject Entity
 *
 * @property string $id
 * @property string $name
 * @property string|null $title
 * @property string $type
 * @property int $size
 * @property string|null $scope
 * @property string $fk_model
 * @property string $fk_id
 * @property float|null $order
 * @property string|resource $filedata
 * @property string|null $metadata
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 */
class Fileobject extends Entity
{
	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * Note that when '*' is set to true, this allows all unspecified fields to
	 * be mass assigned. For security purposes, it is advised to set '*' to false
	 * (or remove it), and explicitly make individual fields accessible as needed.
	 *
	 * @var array
	 */
	protected $_accessible = [
		'name' => true,
		'title' => true,
		'type' => true,
		'size' => true,
		'scope' => true,
		'fk_model' => true,
		'fk_id' => true,
		'order' => true,
		'filedata' => true,
		'metadata' => true,
		'created' => true,
		'modified' => true,
		'upload' => true,
		'tmp_name' => true,
		'error' => true,
	];

	protected $_virtual = [
		'upload',
		'tmp_name',
		'error'
	];

	public function getStream()
	{
		return new Stream($this->filedata);
	}

	protected function _setUpload($upload)
	{
		if (is_array($upload)) {
			foreach (['tmp_name', 'error', 'name', 'type', 'size'] as $uploadField) {
				if (array_key_exists($uploadField, $upload)) {
					$this->set($uploadField, $upload[$uploadField]);
				}
			}
		}
		return $upload;
	}

	protected function _setTmpName($tmp_name)
	{
		if ($tmp_name) {
			if (is_uploaded_file($tmp_name)) {
				$this->filedata = fopen($tmp_name, 'rb');
			} else {
				throw new ForbiddenException('Invalid uploaded file');
			}
		}
		return $tmp_name;
	}

	protected function _setError($error_code)
	{
		if ($error_code != UPLOAD_ERR_OK) {
			$this->setError('upload', $this->errorcodeToMessage($error_code));
		}
	}

	public function errorcodeToMessage($code)
	{
		switch ($code) {
			case UPLOAD_ERR_OK:
				$message = __d('filerepo', 'File uploaded successfully');
				break;
			case UPLOAD_ERR_INI_SIZE:
				$message = __d('filerepo', 'The uploaded file exceeds the upload_max_filesize directive in php.ini');
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$message = __d('filerepo', 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
				break;
			case UPLOAD_ERR_PARTIAL:
				$message = __d('filerepo', 'The uploaded file was only partially uploaded');
				break;
			case UPLOAD_ERR_NO_FILE:
				$message = __d('filerepo', 'No file was uploaded');
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$message = __d('filerepo', 'Missing a temporary folder');
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$message = __d('filerepo', 'Failed to write file to disk');
				break;
			case UPLOAD_ERR_EXTENSION:
				$message = __d('filerepo', 'File upload stopped by PHP extension');
				break;
			default:
				$message = __d('filerepo', 'Unknown upload error');
				break;
		}
		return $message;
	}

	public function getUrl($download = false)
	{
		if ($this->id) {
			return [
				'plugin' => 'Filerepo',
				'controller' => 'Fileobjects',
				'action' => $download ? 'download' : 'view',
				'_entity' => $this
			];
		}
		return false;
	}

	public function getImageResource()
	{
		if (!$this->has('filedata')) {
			throw new InternalErrorException('Filedata empty ' . $this->id);
		}
		$image = imagecreatefromstring(stream_get_contents($this->filedata));
		if (!$image) {
			throw new InternalErrorException('Cannot create thumb from ' . $this->name);
		}
		return $image;
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @param bool $crop
	 * @return Fileobject
	 */
	public function getThumbnail($width, $height = null, $crop = false)
	{
		/** @var \Filerepo\Model\Table\FileobjectsTable $Fileobjects */
		$Fileobjects = TableRegistry::getTableLocator()->get($this->getSource());

		return $Fileobjects->getThumbnail($this, $width, $height, $crop);
	}


}
