<?php

// кодировка , запрет кеширования
header("Content-Type: text/html; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Expires: " . date("r"));

require_once '../../../../vendor/autoload.php';

 // подгрузка и использование файлов классов взаимодействия с дискордом
require_once "Client.php";
require_once "Embed.php";

// данные для соединения с базой даанных
define ("DBHOST", "localhost");
define ("DBNAME", "namedb");
define ("DBUSER", "dbuser");
define ("DBPASS", "dbpass");

function VK_auth ()
{
	$oauth = new VK\OAuth\VKOAuth();

	$client_id = 4444587;
	$state = 'Civyrf14Lb21sdfsdzFv';
	$redirect_uri = 'http://sloto.pro/discord/dfgdfgtgghgjggdgdsfgd44534.php';

	if (!isset ($_GET['code']))
	{
		$display = VK\OAuth\VKOAuthDisplay::PAGE;
		$scope = [VK\OAuth\Scopes\VKOAuthUserScope::WALL, VK\OAuth\Scopes\VKOAuthUserScope::GROUPS];
		$browser_url = $oauth->getAuthorizeUrl(VK\OAuth\VKOAuthResponseType::CODE, $client_id, $redirect_uri, $display, $scope, $state);	
		header("Location: ".$browser_url);
	}
	else
	{
		$code = $_GET['code'];
		$response = $oauth->getAccessToken($client_id, $state, $redirect_uri, $code);
		$token = $response['access_token'];

	}
}

$vk = new VK\Client\VKApiClient(5.101);
$token = '2512fsfs5g4df5h14gf5jg4gj4fgh21fghf2g1h1fb9401e41f7138df99db3';


// конфиг разделов постинга

$section = [

[
	'name' => 'memes', 		// название категории , не изменять!
	'avatar_link' => '', 	// ссылка на аватар бота
	'name_bot' => '',// название бота
	'color' => '',        // цвет рамки постов
	'webhook' => 'https://discordapp.com/api/webhooks/556448436992475137/Gz4D7NPjm9_UqOe5FLDSDwBXm5445df4g5df4g8d4gd844F34-S9gFqQXCWF_0sxHf',
	'pub_vk' => [
	57846937,	//0 MDK				https://vk.com/club57846937	
	45745333,	//1 4ch inc.	https://vk.com/club45745333	
	132799222,//2 Memes			https://vk.com/club132799222
	68674315,	//3	Мемология	https://vk.com/club68674315	
	22751485,	//4	Двач			https://vk.com/club22751485	
	97494559	//5	MEMCHAN		https://vk.com/club97494559
	],
	'count' => 2, 			// колличество получаемых постов для обработки
	'filter' => 'all',// получать посты от имени
	'extended' => 1     // получать доп. поля
	],
	
	[
	"name"=>"porn", // название категории , не изменять!
	'avatar_link' => '',
	"name_bot" => '',
	'color' => '',
	"webhook" => 'https://discordapp.com/api/webhooks/622880483226812436/mYt0b0hDyB1KvL5454512158815Ji0mTdLa47HbWSHNJA-oLWKihNk',
	"pub_vk" => [
	130040287,	//0
	62151912,		//2	
	81804447,		//3
	79049539,		//4
	78387512,		//5
	109051265,	//6	
	51498882,		//7	
	91921027,		//8	
	151243691,	//9	
	137741580 
	],
	"count" => 2,
	'filter' => 'all',
	'extended' => 1
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

//**подгрузка, анализ, обработка и запись нужных данных в дискорд по каждому элементу массива ВК пабликов**

//обработка всех разделов по порядку ай
for($i=0,$a_l=count($section);$i<$a_l;$i++)
{
	echo "<h2>Обработка раздела " . $section[$i]['name'] . "</h2></br>\n";

	// обработка массива пабликов раздела по порядку джи
	for($j=0,$a_j=count($section[$i]['pub_vk']);$j<$a_j;$j++) 
	{
		//https://vk.com/dev/wall.post запрос записей
		$wl=$vk->wall()->get($token,
		[
		'owner_id'=>-1*(int)$section[$i]['pub_vk'][$j],		//ID группы
		'count'=>$section[$i]['count'],										//колличество вытаскиваемых записей  
		'filter'=>$section[$i]['filter'],									//фильтр запроса
		'extended'=>$section[$i]['extended'],							//вытаскивать ли доп. поля
		'v'=>5.101																				//версия API для запроса
		]);
		sleep (1); 	// немного спим во избежание ошибки лимита запросов
		// проверяем запрос
//print_r ($wl);

		//обрабатываем все полученные записи согласно настройкам запроса

	
		foreach ($wl['items'] as $items)
		{
//print_r ($items);
///**	
			$k=0;//счетчик подсчета №записи
			//формируем ссылку на грууппу вк
			$link_groups = 'https://vk.com/club' . $wl['groups'][$k]['id'];
			//формируем ссылку на пост вк
			$link_post = 'https://vk.com/wall-' . $wl['groups'][$k]['id'] . '_' . $items['id'] ;

			echo "<b>-Грабим паблик №{$j} - <a href='{$link_groups}'>{$wl['groups'][$k]['name']}</a></b></br>\n";
			echo "--Запись - <a href='{$link_post}'>ID-{$items['id']}</a> сграблена!</br>\n";

 //print_r ($items);

			echo $link_post ."</br>\n";

			// делае запрос в базу данных и ищем соответсвия по ссылкам на посты
			if (!$sql = mysqli_query($db, "SELECT `link` FROM `msgdata` WHERE `link` = '{$link_post}'"))
			{ 
				echo "<h1>Немогу отправить запрос в базу данных!</h1>";
			}
			//определяем кол-во совпадений предыдущего пункта
			$c=mysqli_num_rows($sql);
			//если они есть , значит данный пост нужно скипать
			if($c>0)
			{
				echo "<font color='red'>Запись в базе существует и повторно добавлена не будет! </font></br> {$items['text']}</b></br><hr>";
			}
			else // иначе обрабатываем данный паблик
			{ 
				// если есть вложения в виде фото и пост является не закрепленным
				if ( isset($items['attachments']) && !isset($items['attachments']['is_pinned']))
				{
					// обрабатываем все  вложения поста
					foreach($items['attachments'] as $attachments)
					{
						// если есть тип вложения фото
						if (isset($attachments['photo']) && $attachments['type'] =='photo')
						{
							// то делаем обработку всех изображений поста	
							foreach ($attachments['photo'] as $key => $param)
							{
								// подсчет текущего колличства массива с размерами изображений аттачмента
								if ($key == 'sizes') {
									$img = $param;
									$ct = count($img)-1;
								}
								else {
									continue;
								}
								// echo "</br>\n". $ct . "</br>\n";
								// echo "<img src='".$img[$ct]['url']. "'></br>\n";
								 
								//создаём классы для взаимодействия с дискордом
								$webhook=new Client($section[$i]['webhook']);
								$embed=new Embed();
				
								//проверка создания классов
								if(!$webhook or !$embed)
								{
									die ("<h1>Не могу создать объекты классов взаимодействия с дискордом!<h1>");
								}
///**								
								// установить большое разрешение в качестве изображения поста
								$embed->image((string)$img[$ct]['url']);

								// если Title не пуст , берём его за описание поста
								if (!empty($items['text']))
								{  
									$embed->description("***{$items['text']}***");
								}
	
								// если задано в настройках название бота берем его
								if (!empty($section[$i]['name_bot']))
								{
									$webhook->username($section[$i]['name_bot']);
								}
								else // иначе берем название паблика
								{ 
									$webhook->username((string)$wl['groups'][0]['name']);
								}
  
								// если задана настройка ссылки аватара то выбрать её
								if (!empty($section[$i]['avatar_link']))
								{
									$webhook->avatar($section[$i]['avatar_link']);
								}
								else // иначе аватар будер соответствующий аватару паблика вк
								{ 
									$webhook->avatar((string)$wl['groups'][$k]['photo_100']);
								}
		
								// если задана настройка цвета раздела то выбрать цвет соответствующий
								if (!empty($section[$i]['color']))
								{
									$embed->color ($section[$i]['color']);
								} 
								else // иначе цвет рандомный
								{ 
									$embed->color ('#' . dechex(rand(0,10000000)));
								}
  
								// прочие установки
								$embed->title ((string)$wl['groups'][$k]['name']);
								$embed->url($link_post);
								$embed->timestamp(date("c"));

// var_dump ($embed); // смотрим установку переменных поста
								// проверка установок
								if ($webhook->embed($embed)) 
								{ 
									echo "Параметры поста иницилизированы! </br>";
								}
								else 
								{
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
   
								// **записи ссылки и содержания поста в базу данных и вывод результата операций**
								//формирование sql запроса
								$sql_into ="INSERT INTO `msgdata` (`section`, `link`, `img`, `msg`) 
								VALUES ('{$section[$i]['name']}', '{$link_post}', '{$img[$ct]['url']}', '{$items['text']}')";
								// запрос записи в базу данных в выводом результата	
								if (mysqli_query($db, $sql_into))
								{
									echo <<<HTML
									<font color='green'>Запись с {$wl['groups'][$k]['name']} была успешно добавлена в базу!</font></br>
									<b>	{$items['text']}</b></br>
									<b><img src='{$img[$ct]['url']}' width='300'></b></br><hr>
HTML;
								}
								else
								{
									echo <<<HTML
									<b>Не удалось добавить запись с {$wl['groups'][$k]['name']} в базу!!!!</b></br>
									<b>	{$items['text']}</b></br>
									<img src='{$img[$ct]['url']}' width='300'></br><hr>
HTML;
								}
								// очистка созданных объектов взаимодействия с дискордом
								$webhook = NULL; 
								$embed = NULL; 	
//**/								
							}
						}	
						else 
						{
							echo "<font color='red'>Пост не содержит изображений! </font></br><hr>\n";
						}		
					} 
				}
				else 
				{
					echo "<font color='red'>В посте нет вложений, либо пост является закрепленным! </font></br><hr>\n";
				}
			} 						
		}
		$k++; // +1 к счетчику обработываемого поста
	}
}		

// закрываем соединение с базой данных
mysqli_close($db);

?>
