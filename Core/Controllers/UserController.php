<?php 

namespace Core\Controllers;
use Core\Models\UserModel as MUser;

/**
*  Контроллер пользователя
*/
class UserController{	

	static private $app;
	
	function __construct() 	{
		self::$app = \Slim\Slim::getInstance();
	}


	/**
	 *	Отображение данных пользователя
	 *	@param int id
	 *	@return  bool false если ошибка
	 */

	static public function index($id){		
		if(!is_numeric($id) || intval($id)<=0) return false;
		$aUser = MUser::getUserByID($id);
		$aUser = ($aUser)? $aUser : array();		
		self::$app->render('user.tpl', array('user'=>$aUser));			
	}


	/**
	 *	Создание пользователя 
	 *	@param int id
	 */

	static public function add(){	
		$aPost = self::$app->request()->post();

		// Валидация Card Number
		$isCartNumber =  MUser::validCreditcard($aPost['CardNumber']);	

		if (!preg_match("/^[а-яА-ЯёЁa-zA-Z0-9]+$/u", $aPost['FirstName'])) {
			$aError[]='Заполните правильно поле "First Name"';
		} 

		if (!preg_match("/^[а-яА-ЯёЁa-zA-Z0-9]+$/u", $aPost['LastName'])) {
			$aError[]='Заполните правильно поле "Last Name"';
		}

		if(!$isCartNumber) $aError[]='Заполните правильно поле "Cart Number"';

		if (!preg_match("/^[0-9]{3,4}$/", $aPost['CV2'])) {
			$aError[]='Заполните правильно поле "CV2"';
		}

		if (!preg_match("/^(0[1-9]|1[0-2])\/?([0-9]{4}|[0-9]{2})$/", $aPost['ExpirationDate'])) {
			$aError[]='Заполните правильно поле "Expiration Date", формат MM/YY';
		}		

		if (!preg_match("/^[a-z A-Z]+$/", $aPost['CardHolderName'])) {
			$aError[]='Заполните правильно поле "Card Holder Name"';
		}		

		if (!filter_var($aPost['Email'], FILTER_VALIDATE_EMAIL)) {
			$aError[]='Заполните правильно поле "Email"';
		}

		if (!preg_match("/^[0-9]+$/", $aPost['Amount'])) {
			$aError[]='Заполните правильно поле "Amount"';
		}

		if(!empty($aError)){
			self::$app->flash('error', implode("<br>", $aError));
		}else{
			$aValues = array(
				'FirstName'			=>$aPost['FirstName'],
				'LastName'			=>$aPost['LastName'],
				'CardNumber'		=>$aPost['CardNumber'],
				'CV2'				=>$aPost['CV2'],
				'ExpirationDate'	=>$aPost['ExpirationDate'],
				'CardHolderName'	=>$aPost['CardHolderName'],
				'Email'				=>$aPost['Email'],
				'Amount'			=>$aPost['Amount'],
				);

			if(MUser::addUser($aValues)){
				self::$app->flash('success', 'Пользователь добавлен');
			}else{
				self::$app->flash('error', 'Ошибка, пользователь не добавлен');
			}			
		}
		self::$app->redirect("/user/add/");
		return true;

	}

	/**
	 * Создание транзакции
	 */
	static public function addTransaction(){
		$aPost = self::$app->request()->post();

		// Валидация Card Number
		$isCartNumberFrom =  MUser::validCreditcard($aPost['CardNumberFrom']);	
		$isCartNumberTo =  MUser::validCreditcard($aPost['CardNumberTo']);	


		if(!$isCartNumberFrom) $aError[]='Неверный "Cart Number" отправителя';
		if(!$isCartNumberTo) $aError[]='Неверный "Cart Number" получателя';


		// Убедимся что карточки разные
		
		if ($aPost['CardNumberFrom'] == $aPost['CardNumberTo']) {
			$aError[]='Карта отправителя и получателя совпадают';
		}

		// Проверим сумму перевода
		$aPost['Amount'] = intval($aPost['Amount']);

		if ($aPost['Amount']<=0) {
			$aError[]='Недопустимая сумма перевода';
		}

		// Выберем карточки отправителя и получателя
		$aUserFrom = MUser::getUserByCardNumber($aPost['CardNumberFrom']);
		if(!$aUserFrom) $aError[]='В базе не найден отправитель';

		$aUserTo = MUser::getUserByCardNumber($aPost['CardNumberTo']);
		if(!$aUserTo) $aError[]='В базе не найден получатель';

		// Проверим на доступность средств у отправителя
		if($aUserFrom['Amount'] < $aPost['Amount']){
			$aError[]='Недостаточно средств';
		}
				

		if(!empty($aError)){
			self::$app->flash('error', implode("<br>", $aError));
		}else{
			if(MUser::addTransaction($aPost['CardNumberFrom'], $aPost['CardNumberTo'], $aPost['Amount'])){
				self::$app->flash('success', 'Транзакция успешна');
			}else{
				self::$app->flash('error', 'Ошибка. Транзакция отменена');
			}			
		}
		self::$app->redirect("/transactions/add/");
		return true;

	}


	/**
	 *	Отображение формы добавленния пользователя
	 *	@param int id
	 */

	static public function form(){	
		self::$app->render('user.form.tpl', array());
		return true;

	}	

	/**
	 *	Отображение формы создания транзакции
	 *	@param int id
	 */

	static public function formTransactions(){	
		// Выберем список пользователей
		$aUserList = MUser::getUserList();	
		self::$app->render('transactions.form.tpl', array('aUserList'=>$aUserList));
		return true;
	}
}

$oUserController = new UserController();