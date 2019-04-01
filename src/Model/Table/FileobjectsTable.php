<?php
namespace Filerepo\Model\Table;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Filerepo\Model\Entity\Fileobject;

/**
 * Fileobjects Model
 *
 * @method \Filerepo\Model\Entity\Fileobject get($primaryKey, $options = [])
 * @method \Filerepo\Model\Entity\Fileobject newEntity($data = null, array $options = [])
 * @method \Filerepo\Model\Entity\Fileobject[] newEntities(array $data, array $options = [])
 * @method \Filerepo\Model\Entity\Fileobject|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Filerepo\Model\Entity\Fileobject|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Filerepo\Model\Entity\Fileobject patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Filerepo\Model\Entity\Fileobject[] patchEntities($entities, array $data, array $options = [])
 * @method \Filerepo\Model\Entity\Fileobject findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class FileobjectsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

		$this->setTable('filerepo_fileobjects');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', 'create');

        $validator
            ->scalar('name')
            ->maxLength('name', 150)
            ->requirePresence('name', 'create')
            ->allowEmptyString('name', false);

        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->allowEmptyString('title');

        $validator
            ->scalar('type')
            ->maxLength('type', 100)
            ->requirePresence('type', 'create')
            ->allowEmptyString('type', false);

        $validator
            ->requirePresence('size', 'create')
            ->allowEmptyString('size', false);

        $validator
            ->scalar('scope')
            ->maxLength('scope', 32)
            ->requirePresence('scope', 'create')
            ->allowEmptyString('scope', false);

        $validator
            ->scalar('fk_model')
            ->maxLength('fk_model', 250)
            ->requirePresence('fk_model', 'create')
            ->allowEmptyString('fk_model', false);

        $validator
            ->numeric('order')
            ->allowEmptyString('order');

        $validator
            ->scalar('metadata')
            ->maxLength('metadata', 4294967295)
            ->allowEmptyString('metadata');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        return $rules;
    }

	protected function findWithoutFileContent(Query $query, array $options)
	{
		$fields = array_diff($this->getSchema()->columns(), ['filedata']);
		$fields = $query->aliasFields($fields, $this->getAlias());
		return $query->select($fields);
	}

	public function beforeSave(Event $event, Fileobject $fileobject, ArrayObject $options)
	{
		if ($fileobject->isDirty('filedata')) {
			$this->getEventManager()->on('afterSave', function (Event $event, Fileobject $fileobject, ArrayObject $options) {
				$this->clearThumbs($fileobject->id);
			});
		}
	}

	public function beforeDelete(Event $event, Fileobject $fileobject, ArrayObject $options)
	{
		if ($fileobject->isDirty('filedata')) {
			$this->getEventManager()->on('afterDelete', function (Event $event, Fileobject $fileobject, ArrayObject $options) {
				$this->clearThumbs($fileobject->id);
			});
		}
	}

	/**
	 * Clears thumbnails of given image
	 * @param string|null $fileobject_id
	 * @return int
	 */
	public function clearThumbs($fileobject_id)
	{
		return $this->deleteAll([
			'fk_model' => $this->getAlias(),
			'fk_id' => $fileobject_id
		]);
	}

	/**
	 * Clears all thumbnails
	 * @return int
	 */
	public function clearAllThumbs()
	{
		return $this->deleteAll([
			'fk_model' => $this->getAlias()
		]);
	}

	public function cleanupUnusedFiles($modelname = null)
	{
		if ($modelname === null) {
			$this->getAlias();
		}
		/** @var Table $table */
		$table = TableRegistry::getTableLocator()->get($modelname);

		return $this->deleteAll([
			'fk_model' => $table->getAlias(),
			'fk_id NOT IN' => $table->find()->select($table->getPrimaryKey())
		]);
	}

	public function getThumbnail($sourceFileobject, $width, $height = null, $crop = false)
	{
		if(!($sourceFileobject instanceof Fileobject)) {
			$sourceFileobject = $this->get($sourceFileobject);
		}

		if ($height === null) {
			$height = $width;
		}

		if (!is_integer($width)) {
			throw new InternalErrorException('Thumbnail width should be integer');
		}
		if (!is_integer($height)) {
			throw new InternalErrorException('Thumbnail height should be integer');
		}
		if ($sourceFileobject->isNew()) {
			throw new InternalErrorException('You cannot generate thumbnail for not persited fileobject');
		}
		if (substr($sourceFileobject->type, 0, 6) != 'image/') {
			throw new InternalErrorException('Cannot create thumb from ' . $sourceFileobject->type);
		}

		$scope = "thumb{$width}x{$height}";

		$watermark = Configure::read('watermark', false);
		if ($watermark) {
			$scope .= '_wtr';
		}

		$transparent = ($sourceFileobject->type != 'image/jpeg');

		$data = [
			'scope' => $scope,
			'fk_model' => $this->getAlias(),
			'fk_id' => $sourceFileobject->id,
		];
		/** @var \Filerepo\Model\Entity\Fileobject|null $thumbFileobject */
		$thumbFileobject = $this->find()->where($data)->first();
		if (!$thumbFileobject) {
			$changed = false;
			if (!$sourceFileobject->has('filedata')) {
				$sourceFileobject = $this->get($sourceFileobject->id);
			}
			$image = $sourceFileobject->getImageResource();
			if ($watermark) {
				$image = \Filerepo\Lib\ImageEdit::addWatermark($image);
				$changed = true;
			}
			$data['type'] = $sourceFileobject->type;
			if (
				imagesx($image) > $width || imagesy($image) > $height ||
				$crop && (imagesx($image) != $width || imagesy($image) != $height)
			) {
				$data['type'] = 'image/jpeg';
				$image = \Filerepo\Lib\ImageEdit::image_resize($image, $width, $height, $crop, $transparent);
				$changed = true;
			}
			$ext = null;
			if ($changed) {
				switch ($data['type']) {
					case 'image/jpeg':
						$data['filedata'] = \Filerepo\Lib\ImageEdit::image2jpegstring($image);
						$ext = 'jpeg';
						break;
					case 'image/png':
						$data['filedata'] = \Filerepo\Lib\ImageEdit::image2pngstring($image);
						$ext = 'png';
						break;
					case 'image/webp':
						$data['filedata'] = \Filerepo\Lib\ImageEdit::image2imagewebpstring($image);
						$ext = 'webp';
						break;
					default:
						throw new InternalErrorException('Unsuported content type ' . $data['type']);
				}
				$data['size'] = strlen($data['filedata']);
				$data['name'] = Text::slug(basename($sourceFileobject->name)) . '-' . $scope . '.' . $ext;

				$thumbFileobject = $this->newEntity($data);
				$this->saveOrFail($thumbFileobject);
			} else {
				$thumbFileobject = $sourceFileobject;
			}
			imagedestroy($image);
		}
		return $thumbFileobject;
	}
}
