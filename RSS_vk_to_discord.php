<?php
	// кодировка по умолчанию
	header("Content-Type: text/html; charset=utf-8");

	// подгрузка и использование файлов классов взаимодействия с дискордом
	require_once "Client.php";
	require_once "Embed.php";

	// данные для соединения с базой даанных
	define ("DBHOST", "localhost");
	define ("DBNAME", "jinnd");
	define ("DBUSER", "jinnd");
	define ("DBPASS", "");

	// токен от любого вк аккаута
	define ("TOKENVK", "a319f4f47e29e33efcef7bc81defd33e6ec82c642f4b395ccbd2ecf76905cd6bf9e8b301c96610b5c0b6f");

	// конфиг разделов постинга
	$section = [
	
		[
		"name" => "memes", 
		"name_bot" => "Мемыч",
		"webhook" => "https://discordapp.com/api/webhooks/622669889638760449/icO8D0vmlzAryE7T2W_IcGAe4jbYYiGk1A7jAvDiRHmNlHLsLUjnvkQ0LaCJjWGrNmSd",
		"pub_vk" => ["57846937","45745333","132799222","68674315","22751485","97494559"],
		"rss_count_run" => 3
		],
		
		[
		"name"=>"porn", 
		"name_bot" => "Порныч",
		"webhook" => "https://discordapp.com/api/webhooks/622674475875434506/j5v9fiAtt4qLGeIBitp8krE1iZYUQbyzb5HECvt2xD6sVE7wQ68Z41kpOe6tgcya2SKt",
		"pub_vk" => ["130040287","81804447","79049539"],
		"rss_count_run" => 3
		]
		
	];
	
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

//обработка всех разделов по порядку
for($i=0, $a_l=count($section); $i<$a_l; $i++) 
{   
 echo "<h2>Обработка раздела " . $section[$i]['name'] . "</h2></br>";
 
 // обработка массива публиков раздела
 for($j=0, $a_j=count($section[$i]['pub_vk']); $j<$a_j; $j++) 
{  
 // формируем RSS ленту
 $rss[$j] = "https://vkapi.ga/functional/vk2rss/rss.php?access_token=" . TOKENVK . "&id=public" . $section[$i]['pub_vk'][$j] . "&count=" . $section[$i]['rss_count_run'] . "&include=&exclude=";
 
 // подгружаем содержимое RSS одной строкой 
 $rss[$j] = file_get_contents($rss[$j]);
 
 // удаляем ненужные теги
 $rss[$j] = preg_replace_callback ("|(CDATA\[)(.+)(\]\])|imU",
 function ($matches){
  $t1 = strip_tags($matches[2], "<img>");
  $t2 = preg_replace ("|(\<img src=')(.+)('\s*?/\>)|imU", "$2", $t1);
  $t3 = $matches[1] . $t2 . $matches[3];
  return $t3;
 }
 , $rss[$j]); 

 // создаём объект из обработанной RSS ленты с доступом к каждому элементу встроенным средсвом
 $rss[$j] = new SimpleXMLElement($rss[$j]);
 $x=1;
 
 // перебираем все элементы "item" содержащий в себе массив данных записей
 
 foreach ($rss[$j]->channel->item as $items) 
 {
  echo "--Подпроход №" . $x . "</br>";
  $x++; 
  // делае запрос в базу данных и ищем соответсвия по ссылкам на посты
  if (!$sql = mysqli_query($db, "SELECT `link` FROM `msgdata` WHERE `link` = '{$items->link}'"))
  { 
   echo "<h1>Немогу отправить запрос в базу данных!</h1>";
  }
 
  // определяем кол-во совпадений предыдущего пункта
  $c = mysqli_num_rows($sql);
 
  // если они есть , значит данный пост нужно скипать
  if ($c > 0)
  {
   echo "<hr><font color='red'>Найдено {$c} cхожих записей в базе с пабликом - {$items->author} и повторно добавлено не будет! </font></br> {$items->title}</b></br><hr>";
  }
  else // иначе
  { 
   // подгружаем классы для взаимодействия с вебхуками дискорда
   $webhook = new Client($section[$i]['webhook']);
   $embed = new Embed();
	
   // проверка создания классов
   if (!$webhook or !$embed)
   {
    die ("<h1>Не могу создать объекты классов взаимодействия с дискордом!<h1>");
   }
 
   // если пост содержит одно изображение
   if (preg_match_all("|https:\/\/[\d\w-]+\.userapi\.com\/[\w\d\/-]+\.jpg|im", $items->description, $post_link_img) and ($cl = count($post_link_img[0])) == 1)
   { 
		// установить его в качестве изображения поста
		$embed->image((string)$post_link_img[0][0]);
   }
   else {
	   echo "Пост содержит " . $cl . " изображений и будет пропущен!</br><hr>";
	   continue;
   }

	// если элемент Title не пуст , берём его за описание поста
	if (($title = (string) $items->title) and ($title !== "[Без текста]"))
	{  
		$title = preg_replace("/[^ a-zа-яё\?\!\(\)-\d]/ui", "", $title);
		$embed->description ($title);
	}
	else{
		$title = NULL;
	}

   // прочие установки
   $embed->title ((string)$items->author);
   $embed->url((string)$items->link);
   $embed->color ("#696969");
   
   var_dump ($embed); // смотрим установку переменных поста
   
   if ($webhook->username($section[$i]['name_bot']))
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
   if (mysqli_query($db, "INSERT INTO `msgdata` (`section`, `link`, `img`, `msg`) VALUES ('{$section[$i]['name']}', '{$items->link}', '{$post_link_img[0][0]}', '{$title}')"))
   {
    echo <<<HTML
	<font color='green'>Запись с {$items->author} была успешно добавлена в базу!</font></br>
	<b>	{$title}</b></br>
	<b><img src='{$post_link_img[0][0]}' width='300'></b></br><hr>
HTML;
   }
   else
   {
    echo <<<HTML
	<b>Не удалось добавить запись с {$items->author} в базу!!!!</b></br>
	<b>	{$title}</b></br>
	<img src='{$post_link_img[0][0]}' width='300'></br><hr>
HTML;
   }
   // очистка созданных объектов взаимодействия с дискордом
   $webhook = NULL; 
   $embed = NULL; 
  }
 }
}
}
 
// закрываем соединение с базой данных
mysqli_close($db);

?>
