<?php
namespace Filerepo\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Filerepo\Model\Entity\Fileobject;

/**
 * Fileobjects Model
 *
 * @property |\Cake\ORM\Association\BelongsTo $Fds
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
			->scalar('fk_model')
			->maxLength('fk_model', 250)
			->requirePresence('fk_model', 'create')
			->allowEmptyString('fk_model', false);

		$validator
			->scalar('scope')
			->maxLength('scope', 32)
			->requirePresence('scope', 'create')
			->allowEmptyString('scope', false);


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
}
