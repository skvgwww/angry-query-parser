<?
ini_set('max_execution_time', 0);
ini_set('memory_limit', '128M');

require("RollingCurl.class.php");
require("AngryCurl.class.php");

# Определение функции, вызываемой при завершении потока
function callback_function($response, $info, $request)
{
    if($info['http_code']!==200)
    {
        AngryCurl::add_debug_msg(
            "->\t" .
            $request->options[CURLOPT_PROXY] .
            "\tFAILED\t" .
            $info['http_code'] .
            "\t" .
            $info['total_time'] .
            "\t" .
            $info['url']
        );
        return;
    }
    else
    {
        AngryCurl::add_debug_msg(
            "->\t" .
            $request->options[CURLOPT_PROXY] .
            "\tOK\t" .
            $info['http_code'] .
            "\t" .
            $info['total_time'] .
            "\t" .
            $info['url']
        );
        return;
    }
    // Здесь необходимо не забывать проверять целостность и валидность возвращаемых данных, о чём писалось выше.
}

$AC = new AngryCurl('callback_function');
# Включаем принудительный вывод логов без буферизации в окно браузера
$AC->init_console(); 

# Загружаем список прокси-серверов из /import/proxy_list.txt
# Опционально: задаем количество потоков, regexp и url для проверки работоспособности
# Можно использовать импорт из массива: $AC->load_proxy_list($proxy array);
$AC->load_proxy_list(
    # путь до файла
    'proxy_list.txt',
    # опционально: количество потоков
    200,
    # опционально: тип proxy (http/socks5)
    'http',
    # опционально: URL для проверки proxy
    'http://google.com',
    # опционально: regexp для проверки валидности отдаваемой прокси-сервером информации
    'title>G[o]{2}gle'
);
# Загружаем список useragent из /import/useragent_list.txt
$AC->load_useragent_list( 
    'useragent_list.txt'
);

# Организуем очередь запросов (варианты дополнительных возможностей организации запросов -
# POST, HEADERS, OPTIONS - можно посмотреть в примере extended_requests в папке demo
$AC->request('http://ya.ru');
$AC->get('http://ya.ru');
$AC->post('http://ya.ru');

/**
 * Можно использовать следующие конструкции для добавления запросов:
 *  $AC->request($url, $method = "GET", $post_data = null, $headers = null, $options = null);
 *  $AC->get($url, $headers = null, $options = null);
 *  $AC->post($url, $post_data = null, $headers = null, $options = null);
 * - где:
 *  $options - cURL options, передаваемые в  curl_setopt_array;
 *  $headers - HTTP заголовки, устанавливаемые CURLOPT_HTTPHEADER;
 *  $post_data - массив передаваемых POST-параметров;
 *  $method - GET/POST;
 *  $url - собственно сам путь до страницы, которую необходимо "спарсить".
*/

# Задаем количество потоков и запускаем
$AC->execute(200);

# Вывод лога при выключенном console_mode
//AngryCurl::print_debug(); 

