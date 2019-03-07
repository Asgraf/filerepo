<?php
namespace Filerepo\Model\Entity;

use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Filerepo\Lib\ImageEdit;
use Zend\Diactoros\Stream;

/**
 * Fileobject Entity
 *
 * @property string|resource $id
 * @property string $name
 * @property string|null $title
 * @property string $type
 * @property int $size
 * @property \Cake\I18n\FrozenTime $created
 * @property string $fk_model
 * @property int $fk_id
 * @property string|null $scope
 * @property float|null $order
 * @property string|resource $filedata
 * @property string|null $metadata
 */
class Fileobject extends Entity
{
	protected $_accessible = [
		'name' => true,
		'title' => true,
		'type' => true,
		'size' => true,
		'created' => true,
		'fk_model' => true,
		'fk_id' => true,
		'scope' => true,
		'order' => true,
		'filedata' => true,
		'metadata' => true,
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
		if ($height === null) {
			$height = $width;
		}

		if (!is_integer($width)) {
			throw new InternalErrorException('Thumbnail width should be integer');
		}
		if (!is_integer($height)) {
			throw new InternalErrorException('Thumbnail height should be integer');
		}
		if ($this->isNew()) {
			throw new InternalErrorException('You cannot generate thumbnail for not persited fileobject');
		}
		if (substr($this->type, 0, 6) != 'image/') {
			throw new InternalErrorException('Cannot create thumb from ' . $this->type);
		}

		/** @var \Filerepo\Model\Table\FileobjectsTable $Fileobjects */
		$Fileobjects = TableRegistry::getTableLocator()->get('Filerepo.Fileobjects');

		$scope = "thumb{$width}x{$height}";

		$watermark = Configure::read('watermark', false);
		if ($watermark) {
			$scope .= '_wtr';
		}

		$transparent = ($this->type != 'image/jpeg');

		$data = [
			'scope' => $scope,
			'fk_model' => $Fileobjects->getAlias(),
			'fk_id' => $this->id,
		];
		/** @var \Filerepo\Model\Entity\Fileobject|null $thumbFileobject */
		$thumbFileobject = $Fileobjects->find()->where($data)->first();
		if (!$thumbFileobject) {
			$changed = false;
			$image = $this->getImageResource();
			if ($watermark) {
				$image = ImageEdit::addWatermark($image);
				$changed = true;
			}
			$data['type'] = $this->type;
			if (
				imagesx($image) > $width || imagesy($image) > $height ||
				$crop && (imagesx($image) != $width || imagesy($image) != $height)
			) {
				$data['type'] = 'image/jpeg';
				$image = ImageEdit::image_resize($image, $width, $height, $crop, $transparent);
				$changed = true;
			}
			$ext = null;
			if ($changed) {
				switch ($data['type']) {
					case 'image/jpeg':
						$data['filedata'] = ImageEdit::image2jpegstring($image);
						$ext = 'jpeg';
						break;
					case 'image/png':
						$data['filedata'] = ImageEdit::image2pngstring($image);
						$ext = 'png';
						break;
					case 'image/webp':
						$data['filedata'] = ImageEdit::image2imagewebpstring($image);
						$ext = 'webp';
						break;
					default:
						throw new InternalErrorException('Unsuported content type ' . $data['type']);
				}
				$data['size'] = strlen($data['filedata']);
				$data['name'] = Text::slug(basename($this->name)) . '-' . $scope . '.' . $ext;

				$thumbFileobject = $Fileobjects->newEntity($data);
				$Fileobjects->saveOrFail($thumbFileobject);
			} else {
				$thumbFileobject = $this;
			}
			imagedestroy($image);
		}
		return $thumbFileobject;
	}


}
