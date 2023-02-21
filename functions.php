<?php

// функция возвращает PDO или строковую ошибку PDOException $e
	function f_pdoConnect() {         // Но лучше возвращать тип PDO
		static $db;                   // объявление $db
		if($db===null) {              // если небыло коннекта    
			try {                     // попытка подключения
				$db = new PDO('mysql:host=;dbname=','root','',[
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
				]); 
				$db->exec('SET NAMES UTF8');	 
				//echo "<br /> Успешное подключение <br />";  
			} catch (PDOException $e) {
				echo '<br /> Подключение не удалось: ' . $e->getMessage();
				return  $e->getMessage(); //->getMessage();
			}	 
		} 
		return $db;   
	}
    
// функция возвращает массив аккордов по id  f_strArrGetAkkords($strIdUser);
	function f_strArrGetAkkords($id):array {
		// добавить проверку выполнения
		$db = f_pdoConnect();  
		$arrAkkords = [];
		$sql        = "SELECT * FROM Akkords WHERE id_user = $id";
		$strResult  = $db->query($sql);
		$arrResult  = $strResult->fetchAll();	
		for($i=0;$i<count($arrResult);$i++)
		{
			$arrAkkords[$i][0] = $arrResult[$i]["akkord_name"];
			$arrAkkords[$i][1] = $arrResult[$i]["view"];
			$arrAkkords[$i][2] = $arrResult[$i]["note"];   
		}
		return($arrAkkords);  
	}

// Функция удаляет аккорды из базы для id пользователя f_blDelUserAkkords($strIdUser)
	function f_blDelUserAkkords($strId): bool {
		$db  = f_pdoConnect();
		$sql = "DELETE FROM Akkords WHERE id_user = $strId";
		try { // пытаемся выполнить запрос
			$db -> exec($sql);     // POD запрос
			echo "<br />  удалены предыдущие аккорды";
			return true;           // выводим true;
		} catch (Exception $e) { // Отловить ошибку PDO
			echo "<br /> Ошибка удаления <br />";
			return false;
		}
    
	}

// функция добавляет массив пользовательских аккордов в базу
	function f_blInsertUsersAkkords($strId,$strUserAkkords):bool {
    //------------------------------ id   ,'["Dm","1,2,3","55,56,77,"]' 
		$db  = f_pdoConnect();
		$sql = "INSERT INTO Akkords (id_user,akkord_name,view,note) VALUES "; 
		$arrAkkords = json_decode($strUserAkkords);
		foreach($arrAkkords as $arrAkkord) {
			$sql = $sql . "('" . $strId . "','" . $arrAkkord[0] . "','" . $arrAkkord[1] . "','" . $arrAkkord[2] . "'),";
		}
		$sql = substr($sql,0,-1); // Удаляем последнюю запятую

		try { // пытаемся выполнить запрос
			$db -> exec($sql);     // POD запрос
			// echo "<br /> Успешно добавлено <br />";
			return true;           // выводим true;
		} catch (Exception $e) { // Отловить ошибку PDO
			// echo "<br /> Ошибка добавления <br /> $e";
			return false;
		}

	}

// функция проверяет есть ли id пользователя f_blCheckIdUser($strIdUser)
	function f_blCheckIdUser($strId): bool {
		$db  = f_pdoConnect(); 
		$sql = "SELECT id_user FROM Akkords WHERE id_user = $strId";
		$strResult = $db->query($sql);
		$arrResult = $strResult->fetchAll(PDO::FETCH_COLUMN, 0);
		if($arrResult == []) return false;
		else return true;
	}

//--------------------------login form-----------------------------------------------
//-----------------------------------------------------------------------------------
// функция проверяет полученные POST   / f_blCheckParamPost(["Name","Pass","Mail"]
	function f_blCheckParamPost($arrStrings) : bool { 
		foreach($arrStrings as $string) {
			if(!isset($_POST[$string])) return false;   // нету в POST
			if($_POST[$string] == "")   return false;   //  пустая
		}
		return true;
	}


// Проверяет есть ли уже такой ник нейм /  f_blCheckNickName($_POST["Name"])
    function f_blCheckNickName($strNickName) : bool {                                             
	    $db  = f_pdoConnect();                                    // вернем PDO
		$strRequest = $db->query('SELECT nick_name FROM Users');  // выполняем запрос
		$arrResult = $strRequest->fetchAll(PDO::FETCH_COLUMN, 0); // перевели строку в массив
		foreach($arrResult as $strRow){                           // для каждого элемента
		  if($strRow == $strNickName) return true;              // если уже есть false
		}
	 return false;
   }

// функция добавляет значения   f_blInsertParamsPost(["nick_name","pass","email"],  
//	                                    [$_POST['Name'],$_POST['Pass'],$_POST['Mail']]); в базу
	function f_blInsertParamsPost($arrStringsInputValues, $arrStringNameSqlValues) : bool { 
  //-------------------------------массив входных значений, массив входных полей	 
		$db  = f_pdoConnect();                                    // вернем PDO
		for($i=0;$i<count($arrStringNameSqlValues);$i++) {        // Добавляем кавычки
		 $arrStringNameSqlValues[$i] = "'" . $arrStringNameSqlValues[$i] . "'";
		}
		// объединяем элементы массива implode / разделяем exlode 
		$arrInput  = implode(",",$arrStringsInputValues);             // объединяем входной массив 
		$arrSqlVal = implode(",",$arrStringNameSqlValues);            // объединяем массив значений
		$sql   = "INSERT INTO Users ($arrInput) VALUES ($arrSqlVal)"; // добавляем в строку запроса
		// echo $sql;
		// примитивно отловить ошибку
		try { // пытаемся выполнить запрос
			$db -> exec($sql);     // POD запрос
			return true;           // выводим true;
		} catch (Exception $e) {
			return false;
			header("Location:2.php?none = true;");
		}
	}
	

// Функция добавляет значения базовых аккордов в таблицу akkords  для определенного $strNickName
//  f_blInsertUserStartArray($_POST["Name"])
	function f_blInsertUserStartArray($strNickName): bool {
		$db  = f_pdoConnect(); 
		  $strNickName = "'" . $strNickName . "'";
		  $sql = "SELECT id_user FROM Users WHERE nick_name = $strNickName";
		  $strResult = $db->query($sql);
		  $arrResult = $strResult->fetchAll(PDO::FETCH_COLUMN, 0); // можно заменить на fetchColumn;
		  //echo "<br />" . $arrResult[0];
		  $sqlNickName = $arrResult[0];
		  $sql = "INSERT INTO akkords (id_user,akkord_name,view,note) 
		   VALUES ($sqlNickName,'Am','29,42,54','14,29,42,29,54,29,42,29'),
				  ($sqlNickName,'Dm','42,56,67','27,42,56,42,67,42,56,42'),
				  ($sqlNickName,'Em','16,29','16,29,40,29,53,29,40,29'),
				  ($sqlNickName,'F' ,'2,17,30,42,54,67','17,30,42,30,54,30,42,30'),
				  ($sqlNickName,'G','4,16,69','4,40,53,40,69,40,53,40'),
				  ($sqlNickName,'C','17,29,54','17,40,54,40,66,40,54,40'),
				  ($sqlNickName,'0','','1,40,53,66,53,40')";
		  
		  try { // пытаемся выполнить запрос
		   $db -> exec($sql);     // POD запрос
		   //echo "<br /> Успешно добавлено";
		   return true;           // выводим true;
		  } catch (Exception $e) { // Отловить ошибку PDO
		   echo "<br /> Ошибка добавления";
		   return false;
		   //header("Location:2.php?none = true;");
		  }
		  
		  return true;
	  }

// функция возвращяет Id пользователя f_strReturnUserId($_POST["Name"]);
	   function f_strReturnUserId($strNickName):string {
		  $db  = f_pdoConnect();
		  $strNickName = "'" . $strNickName . "'";
		  $sql = "SELECT id_user FROM Users WHERE nick_name = $strNickName";
		  $strResult = $db->query($sql);
		  $arrResult = $strResult->fetchAll(PDO::FETCH_COLUMN, 0);
		  return $arrResult[0]; 
	   }


// функия возвращает если никнейма нет 0 
 //                   если пароль не совпадает 1
  //                   если все совпадает id_user 
		function f_blCheckNameAndPass($strNickName, $strPass) : string  {
			$db  = f_pdoConnect(); 	                                  // подключаем PD
			$blIs = false;                                            // есть ник нейм
			$strRequest = $db->query('SELECT nick_name FROM Users');  // выполняем запрос
			$arrResult = $strRequest->fetchAll(PDO::FETCH_COLUMN, 0); // перевели строку в массив
			foreach($arrResult as $strRow) {                          // для каждого элемента                  
				if($strRow == $strNickName) $blIs = true;             // если есть такой nick_name
			}
			$strNickName = "'" . $strNickName . "'";
			if($blIs) { // если ник нейм есть проверяем пароль
				$sql = "SELECT pass FROM Users WHERE nick_name = $strNickName";
				$strResult = $db->query($sql);
				$arrResult = $strResult->fetchAll(PDO::FETCH_COLUMN, 0);
				if($strPass == $arrResult[0]) {  // если пароль совпадает
					$sql = "SELECT id_user FROM Users WHERE nick_name = $strNickName";
					$strResult = $db->query($sql);
					$arrResult = $strResult->fetchAll(PDO::FETCH_COLUMN, 0);
					return $arrResult[0];  
				} else {
					return '1';
				}
			} else {      // если никнейм не совпадает
				return '0';
			}
		}



?>
