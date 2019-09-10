<?php
	// кодировка по умолчанию
	header("Content-Type: text/html; charset=utf-8");

	// подгрузка и использование файлов классов взаимодействия с дискордом
	require_once "Client.php";
	require_once "Embed.php";

	// данные для соединения с базой даанных
	define ("DBHOST", "localhost");
	define ("DBNAME", "rssmsg");
	define ("DBUSER", "rssmsg");
	define ("DBPASS", "pass");

	// токен от любого вк аккаута
	define ("TOKENVK", "you_token");

	// переменная-массив id пабликов ВК
	define ("IDPUBVK", array ("57846937", "45745333", "132799222", "68674315", "22751485", "97494559"));
	
	// имя дискорд бота (веб хука)
	define ("NAMEBOT", "МЕМЫЧ");

	// колличество последних записей для обработки на каждую RSS ленту 
	define ("RSSMSGCOUNT", "3");
	
	// вебхук дискорда
	define ("WEBHOOKDISCORD", "https://discordapp.com/api/webhooks/");

	// соединяемся с базой данных
	$db = mysqli_connect(DBHOST, DBUSER, DBPASS, DBNAME);
	// выводим отчет о соединении
	if (!$db) 
	{
		die ('Не могу соединиться с БД. Код ошибки: ' . mysqli_connect_errno() . ', ошибка: ' . mysqli_connect_error());
	}
	else 
	{
		echo "<h1><b>Соединение с базой данных удалось , работаем дальше !</b></h1></br>";
	}
	
// RSS формирование, подгрузка, анализ, обработка и запись нужных данных в дискорд по каждому элементу массива ВК пабликов
for($i=0, $a_l=count(IDPUBVK); $i<$a_l; $i++) 
{   
 echo "Проход №" . $i . "</br>";
 // формируем RSS ленту
 $rss[$i] = "https://vkapi.ga/functional/vk2rss/rss.php?access_token=" . TOKENVK . "&id=public" . IDPUBVK[$i] . "&count=" . RSSMSGCOUNT . "&include=&exclude=";
 
 // подгружаем содержимое RSS одной строкой 
 $rss[$i] = file_get_contents($rss[$i]);
 
 // удаляем ненужные теги
 $rss[$i] = preg_replace_callback ("|(CDATA\[)(.+)(\]\])|imU",
 function ($matches){
  $t1 = strip_tags($matches[2], "<img>");
  $t2 = preg_replace ("|(\<img src=')(.+)('\s*?/\>)|imU", "$2", $t1);
  $t3 = $matches[1] . $t2 . $matches[3];
  return $t3;
 }
 , $rss[$i]); 

 // создаём объект из обработанной RSS ленты с доступом к каждому элементу встроенным средсвом
 $rss[$i] = new SimpleXMLElement($rss[$i]);
 $j=1;
 
 // перебираем все элементы "item" содержащий в себе массив данных записей
 
 foreach ($rss[$i]->channel->item as $items) 
 {
  echo "--Подпроход №" . $j . "</br>";
  $j++; 
  // делае запрос в базу данных и ищем соответсвия по ссылкам на посты
  if (!$sql = mysqli_query($db, "SELECT `link` FROM `msgdata` WHERE `link` = '{$items->link}'"))
  { 
   echo "<h1>Немогу отправить запрос в базу данных!</h1>";
  }
 
  // определяем кол-во совпадений предыдущего пункта
  $c = mysqli_num_rows($sql);
  echo "В базе найдено " . $c . " соответсвий!</br>";
 
  // если они есть , значит данный пост нужно скипать
  if ($c > 0)
  {
   echo "<font color='red'>Найдено {$c} cхожих записей в базе с пабликом - {$items->author} и повторно добавлено не будет! </font><li><b>{$items->description}</b> </li></br>";
  }
  else // иначе
  { 
   // подгружаем классы для взаимодействия с вебхуками дискорда
   $webhook = new Client(WEBHOOKDISCORD);
   $embed = new Embed();
	
   // проверка создания классов
   if (!$webhook or !$embed)
   {
    die ("<h1>Не могу создать объекты классов взаимодействия с дискордом!<h1>");
   }
 
   // если в посте есть изображения
   if (preg_match_all("|https:\/\/[\d\w-]+\.userapi\.com\/[\w\d\/-]+\.jpg|im", $items->description, $post_link_img))
   {   
    // устанавливаем в качестве изображение первую ссылку
    $embed->image((string)$post_link_img[0][0]);
   }
 
   // если элемент Title не пуст , берём его за описание поста
   if ((string)$items->title !== "[Без текста]")
   {
    $embed->description ((string)$items->title);
   }
 
   // прочие установки
   $embed->title ((string)$items->author);
   $embed->url((string)$items->link);
   $embed->color ("#696969");
   
   var_dump ($embed); // смотрим установку переменных поста
   
   if ($webhook->username(NAMEBOT)) 
   { 
    echo "Имя бота установлено! </br>";
   }
   
   if ($webhook->embed($embed)) 
   { 
    echo "Параметры поста иницилизированы! </br>";
   }
   
   // отправка массива полученных данных в  вебхук дискорда
   if ($webhook->send()) 
   { 
    echo "Пост удачно отправлен в дискорд! </br>";
   }
   else 
   { 
    die ("Ошибка отправки постака по WebHook в дискорд! </br>");
   }
   
   // записи ссылки и содержания поста в базу данных и вывод результата операций
   if (mysqli_query($db, "INSERT INTO `msgdata` (`link`,`msg`) VALUES ('{$items->link}','{$items->description}')"))
   {
    echo <<<HTML
	<font color='green'>Запись с {$items->author} была успешно добавлена в базу и дискорд!</font>
	<li><b>{$items->description}</b> </li></br>
HTML;
   }
   else
   {
    echo <<<HTML
	<b>Не удалось добавить запись с {$items->author} в базу и дискорд!!!!</b>
	<li> {$items->description} </li></br>
HTML;
   }
   // очистка созданных объектов взаимодействия с дискордом
   $webhook = NULL; 
   $embed = NULL; 
  }
 }
}
 
// закрываем соединение с базой данных
mysqli_close($db);

?>
