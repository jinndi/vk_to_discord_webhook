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
	define ("TOKENVK", "5667087db89cb4de7de8e6438bd0ade6a6e910c94989c1fb18e33d7a28a512a7d27dbfa40dddc8436a7de");

	// конфиг разделов постинга
	$section = [
	
		[
		'name' => 'memes', // название категории , не изменять!
		'avatar_link' => '',
		'name_bot' => 'МЕМЫЧ',
		'color' => '',
		'webhook' => 'https://discordapp.com/api/webhooks/622669889638760449/icO8D0vmlzAryE7T2W_IcGAe4jbYYiGk1A7jAvDiRHmNlHLsLUjnvkQ0LaCJjWGrNmSd',
		'pub_vk' => ["57846937","45745333","132799222","68674315","22751485","97494559"],
		'rss_count_run' => 3
		],
		
		[
		"name"=>"porn", // название категории , не изменять!
		'avatar_link' => '',
		"name_bot" => 'ПОРНЫЧ',
		'color' => '',
		"webhook" => 'https://discordapp.com/api/webhooks/622674475875434506/j5v9fiAtt4qLGeIBitp8krE1iZYUQbyzb5HECvt2xD6sVE7wQ68Z41kpOe6tgcya2SKt',
		"pub_vk" => ["130040287","81804447","79049539", "78387512", "109051265", "51498882","65636693", "91921027"],
		"rss_count_run" => 3
		]
		
	];
	
	// соединяемся с базой данных
	$db = mysqli_connect(DBHOST, DBUSER, DBPASS, DBNAME);
	$db->set_charset('utf8mb4');
	
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
 $rss[$j] = preg_replace_callback ("/(CDATA\[)(.+)(\]\])/imU",
 function ($matches){
  $t1 = strip_tags($matches[2], "<img>");
 $t2 = preg_replace ("/(\<img src=')(.+)('\s*?\/\>)/imU", "$2", $t1);
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
  echo "--Грабим паблик - " . $items->author . " запись № " .$x . "</br>";
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
   echo "<font color='red'>Запись в базе существует и повторно добавлена не будет! </font></br> {$items->title}</b></br><hr>";
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
   if (preg_match_all("/https:\/\/[\d\w-]+\.userapi\.com\/[\w\d\/-]+\.jpg/im", $items->description, $post_link_img) and ($cl = count($post_link_img[0])) == 1)
   { 
		// установить его в качестве изображения поста
		$embed->image((string)$post_link_img[0][0]);
   }
   else {
	   echo "Пост содержит " . $cl . " изображений и будет пропущен!</br><hr>";
	   continue;
   }

	// если элемент Title не пуст , берём его за описание поста
	if ((string) $items->title!== '[Без текста]')
	{  
		$description = (string)$items->description;
		$description = preg_replace("/https:\/\/[\d\w-]+\.userapi\.com\/[\w\d\/-]+\.jpg/im", " ", $description);
		$embed->description("***{$description}***");
	}
	else{
		$description = NULL;
	}
	
	// если задано в настройках название бота берем его
	if (!empty($section[$i]['name_bot'])){
		$webhook->username($section[$i]['name_bot']);
	}
	else { // иначе берем название паблика
		$webhook->username((string)$items->author);
	}
   
	// если задана настройка аватара то выбрать её
	if (!empty($section[$i]['avatar_link'])){
		$webhook->avatar($section[$i]['avatar_link']);
	}
	else { // иначе аватар будер соответствующий изображения поста
		$webhook->avatar((string)$post_link_img[0][0]);
	}
		
	// если задана настройка цвета раздела то вырать цвет соответствующий
	if (!empty($section[$i]['color'])){
		$embed->color ($section[$i]['color']);
	} 
	else { // иначе цвет нандомный
		$embed->color ('#' . dechex(rand(0,10000000)));
	}
   
	// прочие установки
	$embed->title ((string)$items->author);
	$embed->url((string)$items->link);
	$embed->timestamp(date("c"));

	// var_dump ($embed); // смотрим установку переменных поста
   
	if ($webhook->embed($embed)) { 
		echo "Параметры поста иницилизированы! </br>";
	}
	else {
		echo "Ошибка иницилизации папаметров поста! </br>";
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
   if (mysqli_query($db, "INSERT INTO `msgdata` (`section`, `link`, `img`, `msg`) VALUES ('{$section[$i]['name']}', '{$items->link}', '{$post_link_img[0][0]}', '{$description}')"))
   {
    echo <<<HTML
	<font color='green'>Запись с {$items->author} была успешно добавлена в базу!</font></br>
	<b>	{$description}</b></br>
	<b><img src='{$post_link_img[0][0]}' width='300'></b></br><hr>
HTML;
   }
   else
   {
    echo <<<HTML
	<b>Не удалось добавить запись с {$items->author} в базу!!!!</b></br>
	<b>	{$description}</b></br>
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
