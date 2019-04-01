<?php
namespace Filerepo\Controller;

use Cake\Event\Event;

/**
 * Fileobjects Controller
 *
 * @property \Filerepo\Model\Table\FileobjectsTable $Fileobjects
 */
class FileobjectsController extends AppController
{
	public $guestActions = ['view', 'download'];

	public function initialize()
	{
		parent::initialize();

		$this->Crud->mapAction('download', 'Crud.View');
		$this->Crud->mapAction('view', 'Crud.View');
	}

	function view()
	{
		$this->Crud->on('afterFind', function (Event $event) {
			/** @var \Filerepo\Model\Entity\Fileobject */
			$fileobject = $event->getSubject()->entity;

			return $this->outputFileobject($fileobject, false);
		});
		return $this->Crud->execute(null, [
			'id' => $this->getRequest()->getParam('id')
		]);
	}

	private function outputFileobject($fileobject, $download = false)
	{
		/** @var \Filerepo\Model\Entity\Fileobject $fileobject */

		/** @var \Cake\Http\Response $response */
		$response = $this->getResponse();
		if ($download) {
			$response = $response->withModified($fileobject->modified);
		} else {
			$response = $response->withCache($fileobject->modified);
		}
		if ($response->checkNotModified($this->request)) {
			return $response;
		}

		$response = $response
			->withBody($fileobject->getStream())
			->withType($fileobject->type)
			->withHeader('Content-Transfer-Encoding', 'binary')
			->withHeader('Content-Length', (string)$fileobject->size);
		if ($download) {
			$response = $response->withDownload($fileobject->name);
		}
		return $response;
	}

	function download()
	{
		$this->Crud->on('afterFind', function (Event $event) {
			/** @var \Filerepo\Model\Entity\Fileobject */
			$fileobject = $event->getSubject()->entity;

			return $this->outputFileobject($fileobject, true);
		});
		return $this->Crud->execute(null, [
			'id' => $this->getRequest()->getParam('id')
		]);
	}
}
