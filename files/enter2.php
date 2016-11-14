<?php
//контролер обрабатывает данные авторизации
  class Application_Controllers_Enter extends Lib_BaseController
  {
     function index()
	 {
	 
		//если пришли данные логин и пароль, создаем модель проверки авторизации и передаем в нее данные.
		if($_REQUEST['login']||$_REQUEST['pass']){		
			$model=new Application_Models_Auth;
			$resultValid=$model->ValidData($_REQUEST['login'],$_REQUEST['pass']);
			//полученный результат проверки записываем в переменные для вывода в публичной части сайта
			$this->unVisibleForm=$resultValid['unVisibleForm'];
			$this->userName=$resultValid['login'];
			$this->msg=$resultValid['msg'];
			$this->login=$resultValid['login'];
			$this->pass=$resultValid['pass'];
			
			if($_REQUEST['location']) header('Location: '.$_REQUEST['location']);
			
		}
		else 
			if($_SESSION["Auth"])$this->unVisibleForm=true;	//Если пользователь уже авторизован, не будем выводить ему форму авторизации
		
		//если пользователь послал запрос о выходе из кабинета, сбрасываем флаги авторизации
		if($_REQUEST['out']=="1"){
			$_SESSION["Auth"]=false;
			$_SESSION["User"]="";
			$_SESSION["role"]="";
			$this->unVisibleForm=false;
		}
	 }
  }
  /*
  Автор: Авдеев Марк.
  e-mail: mark-avdeev@mail.ru
  blog: lifeexample.ru
*/
?>