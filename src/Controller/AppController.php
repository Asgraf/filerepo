<?php

namespace Filerepo\Controller;

use App\Controller\AppController as BaseController;

class AppController extends BaseController
{
	public function initialize()
	{
		parent::initialize();

		if (!$this->components()->has('Crud')) {
			$this->loadComponent('Crud');
		}
	}
}
