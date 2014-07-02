<?php
namespace Raygun4php
{
	class RaygunIdentifier
	{
		public $Identifier;

    public $FirstName;

    public $FullName;

    public $Email;

    public $IsAnonymous;

    public $Uuid;

		public function __construct($id, $firstName = null, $fullName = null, $email = null, $isAnonymous = null, $uuid = null)
		{
			$this->Identifier = $id;
      $this->FirstName = $firstName;
      $this->FullName = $fullName;
      $this->Email = $email;
      $this->IsAnonymous = $isAnonymous;
      $this->Uuid = $uuid;
		}
	}
}