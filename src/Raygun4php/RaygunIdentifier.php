<?php
namespace Raygun4php
{
	class RaygunIdentifier
	{
		public $Identifier;

		public function __construct($id)
		{
			$this->Identifier = $id;
		}
	}
}