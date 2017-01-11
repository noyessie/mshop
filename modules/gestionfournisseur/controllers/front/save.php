<?php

class gestionfournisseursaveModuleFrontController extends ModuleFrontController
{
  public function initContent()
  {
    parent::initContent();
    //$this->setTemplate('display.tpl');
    //return $this->display(__FILE__, 'views/templates/front/display.tpl');
    $this->context->smarty->assign(
	      array(
            'link_save_fournisseur' => $this->context->link->getModuleLink("gestionfournisseur",'save'),
            'toto'=>'yes'
	     
	  ));

    $this->processSave();
    $this->setTemplate('save.tpl');
    //return $this->display(__FILE__ , 'display.tpl');
  }

  public function processSave()
    {
        //on récupère la liste des profile:
    $profiles = Profile::getProfiles($this->context->language->id);
    
    $fournisseur_role = "";
    //on recherche le role fournisseur:
    foreach($profiles as $profile){
      if($profile["name"]==Configuration::get('PROFILE')){
        $fournisseur_role = $profile;
        break;
      }
    }

    if($fournisseur_role == ""){
      
      $this->context->cookie->__set('redirect_errors',
         Tools::displayError("Profile '".Configuration::get('PROFILE')."' inexistant , Contactez l'administrateur"));

      Tools::redirect($this->context->link->getModuleLink("gestionfournisseur",'display'));
    }
    
    $params = array(
        'id_profile' => $fournisseur_role['id_profile'],
        'lastname'=>Tools::getValue('lastname'),
        'firstname'=>Tools::getValue('firstname'),
        'email'=>Tools::getValue("email"),
        'passwrd'=>Tools::getValue("password"),
        'id_lang'=>$this->context->language->id
      );



    $employee = new Employee(3);
    $employee->id_profile = $params['id_profile'];
    $employee->lastname = $params['lastname'];
    $employee->firstname = $params['firstname'];
    $employee->email = $params['email'];
    $employee->passwd = $params['passwrd'];
    $employee->id_lang = $params['id_lang'];

    //verification
    $error = array();
    if(!Validate::isName($employee->firstname) || count($employee->firstname)<1)
      $error[] = "Non invalide";

    if(!Validate::isName($employee->lastname) || count($employee->firstname)<1)
      $error[] = "Prenom Invalide";

    if(!Validate::isEmail($employee->email))
      $error[] = "Email Invalide";

    if(Employee::employeeExists($employee->email))
      $error[] = "l'employee existe déjà";

    if(!Validate::isPasswd($employee->passwd , 5))
      $error[] = "Mot de passe invalid";


    if(count($error)>0){
      $this->context->cookie->__set('redirect_errors',
         Tools::displayError(implode("@" , $error)));
      //dump($error);
      Tools::redirect($this->context->link->getModuleLink("gestionfournisseur",'display'));
    }

    //$employee->passwd = $crypto->hash($password = $employee->passwd, _COOKIE_KEY_);
    $employee->active = 0;
    $employee->default_tab = 21;
    $employee->passwd = md5(_COOKIE_KEY_.$employee->passwd);
    
    
     
 
    $this->sendMail($employee);
    
    //$employee->add();
    //dump("added employee");
    //dump($employee);
    $this->context->smarty->assign(
        array(
          'link_login_provider'=>$this->context->link->getAdminLink("admin","adminaccess"),
        'admin_dir'=>"/admin5gi"
          )
      );
    
    }


    public function sendMail($fournisseur){
      //on récupère la liste des admins
      $employees = Employee::getEmployeesByProfile(1);
      $id_land = Language::getIdByIso('en');  //Set the English mail template
      //$template_name = 'template'; //Specify the template file name
      /*$title = Mail::l('Test Mail'); //Mail subject with translation
      $from = Configuration::get('PS_SHOP_EMAIL');   //Sender's email
      $fromName = Configuration::get('PS_SHOP_NAME'); //Sender's name
      $mailDir = dirname(__FILE__).'/mails/'; //Directory with message templates
      $toName = $employee->firstname.' '.$employee->lastname; //Customer name
     
      //$fileAttachment['content'] = file_get_contents(_PS_BASE_URL_.__PS_BASE_URI__.'download/fb.zip'); //File path
      //$fileAttachment['name'] = 'fileAttachment'; //Attachment filename
      //$fileAttachment['mime'] = 'application/zip'; //mime file type
     */
      //d($fournisseur);
      $templateVars = array();
     $templateVars['{firstname}'] = $fournisseur->firstname;
     $templateVars['{lastname}'] = $fournisseur->lastname;
     $templateVars['{email}'] = $fournisseur->email;
     
   //d($fournisseur->firstname);
   // d($fournisseur['lastname']);
    //$templateVars['{src_img}'] = _PS_BASE_URL_.__PS_BASE_URI__.'img/logo.jpg'; //Image to be displayed in the message
    $sujet = 'Un nouveau compte fournisseur a activer';
    //$destinataire = array();
    foreach($employees as $employee){
      //$destinataire[] = $employee['email'];
      $templateVars['{firstnameAdm}'] = $employee['firstname'];
      $templateVars['{lastnameAdm}'] = $employee['lastname'];
      Mail::Send($id_land, 'montemplate', $sujet , $templateVars, $employee['email'], NULL, NULL, NULL, NULL, NULL, 'mails/');
    
    }

    }

}